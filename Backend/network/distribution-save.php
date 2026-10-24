<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/validation.php';

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
    $confirmMissingCoverage = in_array($body['confirmMissingCoverage'] ?? false, [true, 1, '1', 'true'], true);

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

    $statement = $pdo->prepare(
        "SELECT supplier.organization_id, supplier.organization_name
         FROM organizations supplier
         INNER JOIN organization_relationships r
            ON r.target_organization_id = supplier.organization_id
           AND r.source_organization_id = ?
           AND r.status = 'ACTIVE'
         WHERE supplier.organization_id = ?
           AND supplier.status = 'ACTIVE'
           AND supplier.organization_type IN ('SUPPLIER', 'MANUFACTURER_SUPPLIER')
         LIMIT 1"
    );
    $statement->execute([$organizationId, (int)$supplierId]);
    $supplier = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$supplier) {
        $pdo->rollBack();
        respond_field_error(400, field_error('supplierId', 'The supplier must have an active, approved supply relationship with your organisation.'));
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

    $hasDeclaredCoverage = $coverageStatement->fetchColumn() !== false;
    $coverageWarningAccepted = !$hasDeclaredCoverage && $confirmMissingCoverage;

    if (!$hasDeclaredCoverage && !$confirmMissingCoverage) {
        $pdo->rollBack();
        respond(409, [
            'success' => false,
            'code' => 'SUPPLIER_COVERAGE_WARNING',
            'warning' => 'The supplier has no active declared coverage in this county for the entire selected appointment period.',
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

    record_audit(
        $pdo,
        $actor,
        'PRODUCT_DISTRIBUTION_APPOINTED',
        'PRODUCT_SUPPLIER',
        $appointmentId,
        "Appointed {$supplier['organization_name']} for {$product['product_name']} in {$areaName} from {$startDate}" . ($endDate !== null ? " to {$endDate}" : ' with no planned end date') . ($isExclusive ? ' (exclusive).' : ' (non-exclusive).') . ($coverageWarningAccepted ? ' Supplier coverage warning acknowledged by manufacturer.' : '')
    );

    $appointmentPeriod = $endDate !== null ? "through {$endDate}" : 'with no planned end date';
    $notificationMessage = "Regional appointment for {$product['product_name']} in {$areaName}: {$supplier['organization_name']} was appointed from {$startDate} {$appointmentPeriod}.";
    if ($coverageWarningAccepted) {
        $notificationMessage .= ' The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.';
    }

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

    $pdo->commit();

    $appointmentMessage = 'Regional distribution appointment created.';
    if ($coverageWarningAccepted) {
        $appointmentMessage .= ' Supplier coverage warning acknowledged; the supplier has no declared coverage for the full appointment period.';
    }

    respond(201, [
        'success' => true,
        'message' => $appointmentMessage,
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