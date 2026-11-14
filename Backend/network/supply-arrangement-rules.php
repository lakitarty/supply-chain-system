<?php

declare(strict_types=1);
require_once __DIR__ . '/../products/pricing-rules.php';

function supply_arrangements(PDO $pdo, string $date, ?int $retailerId = null, ?int $appointmentId = null): array
{
    $sql = "SELECT DISTINCT
                ps.product_supplier_id,
                ps.product_id,
                p.product_name,
                ps.supplier_id,
                supplier.organization_name AS supplier_name,
                ps.area_id,
                area.area_name,
                ps.start_date,
                ps.end_date,
                r.target_organization_id AS retailer_id,
                retailer.organization_name AS retailer_name,
                COALESCE(a.status, 'PENDING') AS decision,
                a.decided_at
            FROM product_suppliers ps
            INNER JOIN products p
                ON p.product_id = ps.product_id AND p.status = 'ACTIVE'
            INNER JOIN organizations supplier
                ON supplier.organization_id = ps.supplier_id AND supplier.status = 'ACTIVE'
            INNER JOIN distribution_areas area
                ON area.area_id = ps.area_id
            INNER JOIN organization_relationships r
                ON r.source_organization_id = ps.supplier_id
               AND r.relationship_type = 'SUPPLIES'
               AND r.status = 'ACTIVE'
               AND r.start_date <= ?
               AND (r.end_date IS NULL OR r.end_date >= ?)
            INNER JOIN organizations retailer
                ON retailer.organization_id = r.target_organization_id
               AND retailer.organization_type = 'RETAILER'
               AND retailer.status = 'ACTIVE'
            LEFT JOIN product_supply_acceptances a
                ON a.product_supplier_id = ps.product_supplier_id
               AND a.retailer_id = r.target_organization_id
            WHERE ps.status = 'ACTIVE'
              AND (ps.end_date IS NULL OR ps.end_date >= ?)
              AND EXISTS (
                  SELECT 1
                  FROM branches b
                  WHERE b.organization_id = r.target_organization_id
                    AND b.area_id = ps.area_id
                    AND b.status = 'ACTIVE'
              )";

    $params = [$date, $date, $date];

    if ($retailerId !== null) {
        $sql .= ' AND r.target_organization_id = ?';
        $params[] = $retailerId;
    }

    if ($appointmentId !== null) {
        $sql .= ' AND ps.product_supplier_id = ?';
        $params[] = $appointmentId;
    }

    $sql .= ' ORDER BY product_name, supplier_name, product_supplier_id';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function notify_organizations(PDO $pdo, array $organizationIds, int $referenceId, string $title, string $message): void
{
    foreach (array_unique(array_map('intval', $organizationIds)) as $organizationId) {
        create_organization_notification($pdo, $organizationId, 'SUPPLY_CHANGE', $referenceId, $title, $message);
    }
}

function tax_change_recipients(PDO $pdo, int $manufacturerId, int $productId, string $date): array
{
    $statement = $pdo->prepare(
        "SELECT ps.product_supplier_id, ps.supplier_id
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         WHERE ps.product_id = ?
           AND p.manufacturer_id = ?
           AND ps.status = 'ACTIVE'
           AND (ps.end_date IS NULL OR ps.end_date >= ?)"
    );
    $statement->execute([$productId, $manufacturerId, $date]);
    $recipients = [];

    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $appointment) {
        $recipients[] = (int)$appointment['supplier_id'];

        foreach (supply_arrangements($pdo, $date, null, (int)$appointment['product_supplier_id']) as $arrangement) {
            $recipients[] = (int)$arrangement['retailer_id'];
        }
    }

    return array_values(array_unique($recipients));
}

function create_appointment_offer(PDO $pdo, int $appointmentId, int $userId): void
{
    $source = price_rule_row(
        $pdo,
        "SELECT
            ps.supplier_id,
            ps.area_id,
            p.product_id,
            pv.product_version_id,
            GREATEST(CURDATE(), ps.start_date, pv.effective_from) AS offer_start_date,
            CASE
                WHEN ps.end_date IS NULL THEN pv.effective_to
                WHEN pv.effective_to IS NULL THEN ps.end_date
                ELSE LEAST(ps.end_date, pv.effective_to)
            END AS offer_end_date,
            (
                SELECT rec.amount
                FROM product_price_recommendations rec
                WHERE rec.manufacturer_id = p.manufacturer_id
                  AND rec.product_id = p.product_id
                  AND rec.product_version_id = pv.product_version_id
                  AND rec.recommendation_type = 'RSP'
                  AND rec.currency = 'KES'
                  AND rec.effective_from <= GREATEST(CURDATE(), ps.start_date, pv.effective_from)
                ORDER BY rec.effective_from DESC, rec.recommendation_id DESC
                LIMIT 1
            ) AS rsp_amount,
            (
                SELECT sp.price_includes_vat
                FROM product_prices sp
                WHERE sp.organization_id = p.manufacturer_id
                  AND sp.product_id = p.product_id
                  AND sp.product_version_id = pv.product_version_id
                  AND sp.price_type = 'SELLING'
                  AND sp.effective_from <= GREATEST(CURDATE(), ps.start_date, pv.effective_from)
                  AND (sp.effective_to IS NULL OR sp.effective_to >= GREATEST(CURDATE(), ps.start_date, pv.effective_from))
                ORDER BY sp.effective_from DESC, sp.price_id DESC
                LIMIT 1
            ) AS selling_includes_vat
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN product_versions pv
            ON pv.product_id = p.product_id
           AND pv.is_current = 1
         WHERE ps.product_supplier_id = ?
           AND ps.status = 'ACTIVE'
           AND p.status = 'ACTIVE'
           AND p.availability_status = 'AVAILABLE'
           AND (pv.effective_to IS NULL OR pv.effective_to >= GREATEST(CURDATE(), ps.start_date, pv.effective_from))
           AND (ps.end_date IS NULL OR ps.end_date >= GREATEST(CURDATE(), ps.start_date, pv.effective_from))
         LIMIT 1",
        [$appointmentId]
    );

    if ($source === null || $source['rsp_amount'] === null || $source['selling_includes_vat'] === null) {
        return;
    }

    $start = (string)$source['offer_start_date'];
    $end = $source['offer_end_date'] !== null ? (string)$source['offer_end_date'] : null;

    $existing = price_rule_row(
        $pdo,
        "SELECT supplier_product_offer_id
         FROM supplier_product_offers
         WHERE supplier_id = ?
           AND product_id = ?
           AND product_version_id = ?
           AND area_id = ?
           AND status = 'ACTIVE'
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         LIMIT 1",
        [
            (int)$source['supplier_id'],
            (int)$source['product_id'],
            (int)$source['product_version_id'],
            (int)$source['area_id'],
            $end ?? '9999-12-31',
            $start
        ]
    );

    if ($existing !== null) {
        return;
    }

    $pdo->prepare(
        "INSERT INTO supplier_product_offers
            (supplier_id, product_id, product_version_id, area_id,
             unit_price, price_basis, currency_code,
             effective_from, effective_to, status, offer_note, created_by)
         VALUES (?, ?, ?, ?, ?, ?, 'KES', ?, ?, 'ACTIVE', ?, ?)"
    )->execute([
        (int)$source['supplier_id'],
        (int)$source['product_id'],
        (int)$source['product_version_id'],
        (int)$source['area_id'],
        $source['rsp_amount'],
        (int)$source['selling_includes_vat'] === 1 ? 'VAT_INCLUSIVE' : 'VAT_EXCLUSIVE',
        $start,
        $end,
        'Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',
        $userId
    ]);
}

function seed_supplier_prices(PDO $pdo, int $appointmentId): void
{
    $appointment = price_rule_row(
        $pdo,
        "SELECT ps.supplier_id, ps.start_date, p.product_id, p.manufacturer_id, v.product_version_id
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN product_versions v ON v.product_id = p.product_id AND v.is_current = 1
         WHERE ps.product_supplier_id = ?",
        [$appointmentId]
    );

    if ($appointment === null) {
        return;
    }

    $today = nairobi_today();
    $versionId = (int)$appointment['product_version_id'];
    $selling = manufacturer_price_in_effect($pdo, (int)$appointment['manufacturer_id'], $versionId, 'SELLING', $today);
    $rsp = recommendation_in_effect($pdo, $versionId, 'RSP', $today);
    $percent = version_vat_percent($pdo, $versionId, $today);
    $tax = price_rule_row(
        $pdo,
        'SELECT tax_category
         FROM product_tax_classifications
         WHERE product_version_id = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 1',
        [$versionId, $today, $today]
    );

    if ($selling === null || $percent === null || $tax === null) {
        return;
    }

    $gross = fn(float $amount): float => (int)$selling['price_includes_vat'] === 1
        ? round($amount, 2)
        : round($amount * (1 + $percent / 100), 2);

    $rows = ['COST' => $gross((float)$selling['price_amount'])];

    if ($rsp !== null) {
        $rows['SELLING'] = $gross((float)$rsp['amount']);
    }

    $insert = $pdo->prepare(
        'INSERT INTO product_prices
            (organization_id, product_id, product_version_id, price_type, price_amount,
             price_includes_vat, tax_category, effective_from)
         VALUES (?, ?, ?, ?, ?, 1, ?, ?)'
    );

    foreach ($rows as $type => $amount) {
        $existing = price_rule_row(
            $pdo,
            'SELECT price_id
             FROM product_prices
             WHERE organization_id = ?
               AND product_version_id = ?
               AND price_type = ?
               AND (effective_to IS NULL OR effective_to >= ?)
             LIMIT 1',
            [(int)$appointment['supplier_id'], $versionId, $type, $today]
        );

        if ($existing === null) {
            $insert->execute([
                (int)$appointment['supplier_id'],
                (int)$appointment['product_id'],
                $versionId,
                $type,
                $amount,
                $tax['tax_category'],
                max($today, (string)$appointment['start_date'])
            ]);
        }
    }
}
