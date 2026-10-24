<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';

require_post();

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Supplier organisation administrator access is required.');
    }

    $body = read_json_body();
    $productId = filter_var($body['productId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $versionId = filter_var($body['productVersionId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $areaId = filter_var($body['areaId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $priceText = trim((string)($body['unitPrice'] ?? ''));
    $priceBasis = trim((string)($body['priceBasis'] ?? ''));
    $effectiveFrom = trim((string)($body['effectiveFrom'] ?? ''));
    $effectiveToText = trim((string)($body['effectiveTo'] ?? ''));
    $effectiveTo = $effectiveToText === '' ? null : $effectiveToText;
    $note = trim((string)($body['offerNote'] ?? ''));

    if ($productId === false || $productId === null) {
        respond_field_error(400, field_error('productId', 'Select a product.'));
    }
    if ($versionId === false || $versionId === null) {
        respond_field_error(400, field_error('productVersionId', 'Select a package version.'));
    }
    if ($areaId === false || $areaId === null) {
        respond_field_error(400, field_error('areaId', 'Select a county authorised for this product.'));
    }
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $priceText)) {
        respond_field_error(400, field_error('unitPrice', 'Enter a non-negative KSh price with up to two decimal places.'));
    }
    if (!in_array($priceBasis, ['VAT_INCLUSIVE', 'VAT_EXCLUSIVE'], true)) {
        respond_field_error(400, field_error('priceBasis', 'Choose whether the offer price includes VAT.'));
    }
    if (!valid_date($effectiveFrom)) {
        respond_field_error(400, field_error('effectiveFrom', 'Enter a valid offer start date.'));
    }
    if ($effectiveTo !== null && !valid_date($effectiveTo)) {
        respond_field_error(400, field_error('effectiveTo', 'Enter a valid end date or leave it blank.'));
    }
    if ($effectiveTo !== null && $effectiveTo < $effectiveFrom) {
        respond_field_error(400, field_error('effectiveTo', 'The offer end date cannot be earlier than its start date.'));
    }
    if (mb_strlen($note) > 500) {
        respond_field_error(400, field_error('offerNote', 'The offer note must be 500 characters or fewer.'));
    }

    $unitPrice = number_format((float)$priceText, 2, '.', '');
    $supplierId = (int)$actor['organization_id'];
    $candidateEnd = $effectiveTo ?? '9999-12-31';

    $pdo->beginTransaction();

    $appointmentStatement = $pdo->prepare(
        "SELECT ps.product_supplier_id, p.product_name,
                manufacturer.organization_name AS manufacturer_name,
                area.area_name, pv.version_number
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN product_versions pv
            ON pv.product_version_id = ? AND pv.product_id = p.product_id
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
         INNER JOIN organizations supplier ON supplier.organization_id = ps.supplier_id
         INNER JOIN organization_relationships r
            ON r.source_organization_id = p.manufacturer_id
           AND r.target_organization_id = ps.supplier_id
           AND r.status = 'ACTIVE'
         INNER JOIN distribution_areas area ON area.area_id = ps.area_id
         WHERE p.product_id = ?
           AND ps.supplier_id = ?
           AND ps.area_id = ?
           AND ps.status = 'ACTIVE'
           AND ps.start_date <= ?
           AND (ps.end_date IS NULL OR ps.end_date >= ?)
           AND p.status = 'ACTIVE'
           AND manufacturer.status = 'ACTIVE'
           AND supplier.status = 'ACTIVE'
           AND area.status = 'ACTIVE'
         LIMIT 1 FOR UPDATE"
    );
    $appointmentStatement->execute([
        (int)$versionId,
        (int)$productId,
        $supplierId,
        (int)$areaId,
        $effectiveFrom,
        $candidateEnd
    ]);
    $eligibility = $appointmentStatement->fetch(PDO::FETCH_ASSOC);

    if (!$eligibility) {
        $pdo->rollBack();
        respond_error(409, 'No active, approved regional product appointment covers the whole offer period. Check the product, county, and appointment dates. A blank offer end date requires an open-ended appointment.');
    }

    $overlapStatement = $pdo->prepare(
        "SELECT supplier_product_offer_id
         FROM supplier_product_offers
         WHERE supplier_id = ? AND product_id = ? AND product_version_id = ? AND area_id = ?
           AND status = 'ACTIVE'
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         LIMIT 1 FOR UPDATE"
    );
    $overlapStatement->execute([
        $supplierId,
        (int)$productId,
        (int)$versionId,
        (int)$areaId,
        $candidateEnd,
        $effectiveFrom
    ]);

    if ($overlapStatement->fetchColumn() !== false) {
        $pdo->rollBack();
        respond_error(409, 'An active offer already overlaps these dates for this product version and county. End the earlier offer before adding another price period.');
    }

    $insert = $pdo->prepare(
        "INSERT INTO supplier_product_offers
            (supplier_id, product_id, product_version_id, area_id,
             unit_price, price_basis, currency_code, effective_from, effective_to,
             status, offer_note, created_by)
         VALUES (?, ?, ?, ?, ?, ?, 'KES', ?, ?, 'ACTIVE', ?, ?)"
    );
    $insert->execute([
        $supplierId,
        (int)$productId,
        (int)$versionId,
        (int)$areaId,
        $unitPrice,
        $priceBasis,
        $effectiveFrom,
        $effectiveTo,
        $note !== '' ? $note : null,
        (int)$actor['user_id']
    ]);

    $offerId = (int)$pdo->lastInsertId();
    record_audit(
        $pdo,
        $actor,
        'SUPPLIER_PRODUCT_OFFER_CREATED',
        'SUPPLIER_PRODUCT_OFFER',
        $offerId,
        "Created KSh {$unitPrice} {$priceBasis} offer for {$eligibility['product_name']} version {$eligibility['version_number']} in {$eligibility['area_name']} from {$effectiveFrom}" . ($effectiveTo !== null ? " through {$effectiveTo}." : ' with no planned end date.')
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Supplier offer saved.',
        'offer_id' => $offerId
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'This offer conflicts with an existing record. Refresh the page and review the existing offer periods.');
    }
    log_error('supplier product offer save', $e);
    respond_error(500, 'Unable to save the supplier offer.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('supplier product offer save unexpected', $e);
    respond_error(500, 'Unable to save the supplier offer.');
}
