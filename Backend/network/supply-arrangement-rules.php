<?php

declare(strict_types=1);

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
