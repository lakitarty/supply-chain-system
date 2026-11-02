<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../core/price-confirmation.php';
require_once __DIR__ . '/../products/pricing-rules.php';
require_once __DIR__ . '/supply-arrangement-rules.php';

require_post();

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Manufacturer organisation administrator access is required.');
    }

    $body = read_json_body();
    $productId = filter_var($body['productId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $supplierId = filter_var($body['supplierId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $areaId = filter_var($body['areaId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $startDate = trim((string)($body['startDate'] ?? ''));
    $endDateText = trim((string)($body['endDate'] ?? ''));
    $endDate = $endDateText === '' ? null : $endDateText;
    $isExclusive = in_array($body['isExclusive'] ?? false, [true, 1, '1', 'true'], true) ? 1 : 0;


    if ($productId === false || $productId === null) {
        respond_field_error(400, field_error('productId', 'Select one of your products.'));
    }
    if ($supplierId === false || $supplierId === null) {
        respond_field_error(400, field_error('supplierId', 'Select an approved supplier relationship.'));
    }
    if ($areaId === false || $areaId === null) {
        respond_field_error(400, field_error('areaId', 'Select a county.'));
    }
    if (!valid_date($startDate)) {
        respond_field_error(400, field_error('startDate', 'Enter a valid appointment start date.'));
    }
    if ($endDate !== null && !valid_date($endDate)) {
        respond_field_error(400, field_error('endDate', 'Enter a valid appointment end date.'));
    }
    if ($endDate !== null && $endDate < $startDate) {
        respond_field_error(400, field_error('endDate', 'The end date cannot be earlier than the start date.'));
    }

    $organizationId = (int)$actor['organization_id'];
    require_price_confirmation_password(
        $pdo,
        $actor,
        (string)($body['adminPassword'] ?? ''),
        'appointment'
    );

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        "SELECT product_id, product_name
         FROM products
         WHERE product_id = ? AND manufacturer_id = ? AND status = 'ACTIVE'
         LIMIT 1 FOR UPDATE"
    );
    $statement->execute([(int)$productId, $organizationId]);
    $product = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        $pdo->rollBack();
        respond_field_error(400, field_error('productId', 'The selected product does not belong to your organisation or is inactive.'));
    }

    $priceProblems = product_price_problems($pdo, (int)$productId, nairobi_today());

    if ($priceProblems !== []) {
        $pdo->rollBack();
        $priceMessage = "{$product['product_name']} cannot be assigned to a supplier yet. " . implode(' ', $priceProblems);
        respond(409, [
            'success' => false,
            'code' => 'PRODUCT_PRICES_INCOMPLETE',
            'message' => $priceMessage,
            'error' => $priceMessage,
            'product_name' => $product['product_name'],
            'problems' => $priceProblems
        ]);
    }

    $statement = $pdo->prepare(
        "SELECT supplier.organization_id, supplier.organization_name
         FROM organizations supplier
         INNER JOIN organization_relationships r
            ON r.target_organization_id = supplier.organization_id
           AND r.source_organization_id = ?
           AND r.relationship_type = 'SUPPLIES'
           AND r.status = 'ACTIVE'
           AND r.start_date <= ?
           AND (r.end_date IS NULL OR r.end_date >= ?)
         WHERE supplier.organization_id = ?
           AND supplier.status = 'ACTIVE'
           AND supplier.organization_type IN ('SUPPLIER', 'MANUFACTURER_SUPPLIER')
         LIMIT 1"
    );
    $statement->execute([$organizationId, $startDate, $endDate ?? '9999-12-31', (int)$supplierId]);
    $supplier = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$supplier) {
        $pdo->rollBack();
        respond_field_error(400, field_error('supplierId', 'The supplier must have an active, approved supply relationship with your organisation that covers the whole appointment period.'));
    }

    $statement = $pdo->prepare(
        "SELECT area_id, area_name
         FROM distribution_areas
         WHERE area_id = ? AND status = 'ACTIVE'
         LIMIT 1"
    );
    $statement->execute([(int)$areaId]);
    $area = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$area) {
        $pdo->rollBack();
        respond_field_error(400, field_error('areaId', 'Select an active county.'));
    }
    $areaName = (string)$area['area_name'];

    if ($endDate === null) {
        $coverageStatement = $pdo->prepare(
            "SELECT supplier_area_id
             FROM supplier_distribution_areas
             WHERE supplier_id = ?
               AND area_id = ?
               AND status = 'ACTIVE'
               AND start_date <= ?
               AND end_date IS NULL
             LIMIT 1"
        );
        $coverageStatement->execute([(int)$supplierId, (int)$areaId, $startDate]);
    } else {
        $coverageStatement = $pdo->prepare(
            "SELECT supplier_area_id
             FROM supplier_distribution_areas
             WHERE supplier_id = ?
               AND area_id = ?
               AND status = 'ACTIVE'
               AND start_date <= ?
               AND (end_date IS NULL OR end_date >= ?)
             LIMIT 1"
        );
        $coverageStatement->execute([(int)$supplierId, (int)$areaId, $startDate, $endDate]);
    }

    if ($coverageStatement->fetchColumn() === false) {
        $pdo->rollBack();
        respond(409, [
            'success' => false,
            'code' => 'SUPPLIER_COVERAGE_REQUIRED',
            'message' => "{$supplier['organization_name']} has not declared active coverage in {$areaName} for the whole appointment period. Ask the supplier to declare coverage for this county and period, then try again.",
            'error' => "{$supplier['organization_name']} has not declared active coverage in {$areaName} for the whole appointment period. Ask the supplier to declare coverage for this county and period, then try again.",
            'product_name' => $product['product_name'],
            'supplier_name' => $supplier['organization_name'],
            'area_name' => $areaName,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
    $overlapEnd = $endDate ?? '9999-12-31';

    $statement = $pdo->prepare(
        "SELECT product_supplier_id
         FROM product_suppliers
         WHERE product_id = ?
           AND supplier_id = ?
           AND area_id = ?
           AND status = 'ACTIVE'
           AND start_date <= ?
           AND (end_date IS NULL OR end_date >= ?)
         LIMIT 1"
    );
    $statement->execute([(int)$productId, (int)$supplierId, (int)$areaId, $overlapEnd, $startDate]);
    if ($statement->fetchColumn()) {
        $pdo->rollBack();
        respond_error(409, 'This supplier already has an appointment for the selected product and county during overlapping dates.');
    }

    $statement = $pdo->prepare(
        "SELECT ps.product_supplier_id, supplier.organization_name, ps.is_exclusive
         FROM product_suppliers ps
         INNER JOIN organizations supplier ON supplier.organization_id = ps.supplier_id
         WHERE ps.product_id = ?
           AND ps.area_id = ?
           AND ps.status = 'ACTIVE'
           AND ps.supplier_id <> ?
           AND ps.start_date <= ?
           AND (ps.end_date IS NULL OR ps.end_date >= ?)
           AND (ps.is_exclusive = 1 OR ? = 1)
         LIMIT 1"
    );
    $statement->execute([(int)$productId, (int)$areaId, (int)$supplierId, $overlapEnd, $startDate, $isExclusive]);
    $conflict = $statement->fetch(PDO::FETCH_ASSOC);
    if ($conflict) {
        $pdo->rollBack();
        respond_error(409, 'The dates overlap an exclusive appointment for this product and county with ' . $conflict['organization_name'] . '. Change the dates or resolve the existing appointment first.');
    }

    $statement = $pdo->prepare(
        "INSERT INTO product_suppliers
            (product_id, supplier_id, area_id, is_exclusive, start_date, end_date, status)
         VALUES (?, ?, ?, ?, ?, ?, 'ACTIVE')"
    );
    $statement->execute([(int)$productId, (int)$supplierId, (int)$areaId, $isExclusive, $startDate, $endDate]);
    $appointmentId = (int)$pdo->lastInsertId();

    $offerSourceStatement = $pdo->prepare(
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
         LIMIT 1"
    );
    $offerSourceStatement->execute([$appointmentId]);
    $offerSource = $offerSourceStatement->fetch(PDO::FETCH_ASSOC);

    if (
        $offerSource
        && $offerSource['rsp_amount'] !== null
        && $offerSource['selling_includes_vat'] !== null
    ) {
        $offerStartDate = (string)$offerSource['offer_start_date'];
        $offerEndDate = $offerSource['offer_end_date'] !== null
            ? (string)$offerSource['offer_end_date']
            : null;

        $existingOfferStatement = $pdo->prepare(
            "SELECT supplier_product_offer_id
             FROM supplier_product_offers
             WHERE supplier_id = ?
               AND product_id = ?
               AND product_version_id = ?
               AND area_id = ?
               AND status = 'ACTIVE'
               AND effective_from <= ?
               AND (effective_to IS NULL OR effective_to >= ?)
             LIMIT 1"
        );
        $existingOfferStatement->execute([
            (int)$offerSource['supplier_id'],
            (int)$offerSource['product_id'],
            (int)$offerSource['product_version_id'],
            (int)$offerSource['area_id'],
            $offerEndDate ?? '9999-12-31',
            $offerStartDate
        ]);

        if ($existingOfferStatement->fetchColumn() === false) {
            $priceBasis = (int)$offerSource['selling_includes_vat'] === 1
                ? 'VAT_INCLUSIVE'
                : 'VAT_EXCLUSIVE';

            $insertOfferStatement = $pdo->prepare(
                "INSERT INTO supplier_product_offers
                    (supplier_id, product_id, product_version_id, area_id,
                     unit_price, price_basis, currency_code,
                     effective_from, effective_to, status, offer_note, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, 'KES', ?, ?, 'ACTIVE', ?, ?)"
            );
            $insertOfferStatement->execute([
                (int)$offerSource['supplier_id'],
                (int)$offerSource['product_id'],
                (int)$offerSource['product_version_id'],
                (int)$offerSource['area_id'],
                $offerSource['rsp_amount'],
                $priceBasis,
                $offerStartDate,
                $offerEndDate,
                'Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',
                (int)$actor['user_id']
            ]);
        }
    }

    record_audit(
        $pdo,
        $actor,
        'PRODUCT_DISTRIBUTION_APPOINTED',
        'PRODUCT_SUPPLIER',
        $appointmentId,
        "Appointed {$supplier['organization_name']} for {$product['product_name']} in {$areaName} from {$startDate}" . ($endDate !== null ? " to {$endDate}" : ' with no planned end date') . ($isExclusive ? ' (exclusive).' : ' (non-exclusive).')
    );

    $appointmentPeriod = $endDate !== null ? "through {$endDate}" : 'with no planned end date';
    $notificationMessage = "Regional appointment for {$product['product_name']} in {$areaName}: {$supplier['organization_name']} was appointed from {$startDate} {$appointmentPeriod}.";


    create_organization_notification(
        $pdo,
        $organizationId,
        'SUPPLY_CHANGE',
        $appointmentId,
        'Regional product appointment created',
        $notificationMessage
    );
    create_organization_notification(
        $pdo,
        (int)$supplier['organization_id'],
        'SUPPLY_CHANGE',
        $appointmentId,
        'New regional product appointment',
        $notificationMessage
    );

    foreach (supply_arrangements($pdo, nairobi_today(), null, $appointmentId) as $arrangement) {
        create_organization_notification(
            $pdo,
            (int)$arrangement['retailer_id'],
            'SUPPLY_CHANGE',
            $appointmentId,
            'Supply arrangement awaiting your decision',
            "{$arrangement['supplier_name']} has been appointed to supply {$arrangement['product_name']} in {$arrangement['area_name']} from {$startDate}. Open Supply arrangements to accept or decline."
        );
    }
    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Regional distribution appointment created.',
        'appointment_id' => $appointmentId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('regional distribution save', $e);
    respond_error(500, 'Unable to save the regional distribution appointment.');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('regional distribution save', $e);
    respond_error(500, 'Unable to save the regional distribution appointment.');
}