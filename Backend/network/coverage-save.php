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
    $areaId = filter_var($body['areaId'] ?? null, FILTER_VALIDATE_INT);
    $startDate = trim((string)($body['startDate'] ?? ''));
    $endDate = trim((string)($body['endDate'] ?? ''));

    if ($areaId === false || $areaId === null || $areaId < 1) {
        respond_field_error(400, field_error('areaId', 'Choose a county.'));
    }

    if (!valid_date($startDate)) {
        respond_field_error(400, field_error('startDate', 'Enter a valid start date.'));
    }

    if ($endDate !== '' && !valid_date($endDate)) {
        respond_field_error(400, field_error('endDate', 'Enter a valid end date.'));
    }

    $today = (string)$pdo->query('SELECT CURDATE()')->fetchColumn();

    if ($startDate > $today) {
        respond_field_error(400, field_error('startDate', 'Supplier coverage cannot be scheduled for a future date yet.'));
    }

    if ($endDate !== '' && $endDate < $today) {
        respond_field_error(400, field_error('endDate', 'End date must be today or later.'));
    }

    if ($endDate !== '' && $endDate < $startDate) {
        respond_field_error(400, field_error('endDate', 'End date cannot be earlier than the start date.'));
    }

    $areaStatement = $pdo->prepare(
        "SELECT area_name
         FROM distribution_areas
         WHERE area_id = ? AND status = 'ACTIVE'
         LIMIT 1"
    );
    $areaStatement->execute([(int)$areaId]);
    $areaName = $areaStatement->fetchColumn();

    if ($areaName === false) {
        respond_field_error(400, field_error('areaId', 'Choose an active county.'));
    }

    $endBoundary = $endDate !== '' ? $endDate : '9999-12-31';
    $overlap = $pdo->prepare(
        "SELECT supplier_area_id
         FROM supplier_distribution_areas
         WHERE supplier_id = ?
           AND area_id = ?
           AND status = 'ACTIVE'
           AND start_date <= ?
           AND (end_date IS NULL OR end_date >= ?)
         LIMIT 1"
    );
    $overlap->execute([
        (int)$actor['organization_id'],
        (int)$areaId,
        $endBoundary,
        $startDate
    ]);

    if ($overlap->fetchColumn() !== false) {
        respond_field_error(409, field_error('areaId', 'An active coverage period already overlaps these dates for this county. End or complete the existing period first.'));
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare(
        "INSERT INTO supplier_distribution_areas
            (supplier_id, area_id, start_date, end_date, status)
         VALUES (?, ?, ?, ?, 'ACTIVE')"
    );
    $insert->execute([
        (int)$actor['organization_id'],
        (int)$areaId,
        $startDate,
        $endDate !== '' ? $endDate : null
    ]);

    $coverageId = (int)$pdo->lastInsertId();
    record_audit(
        $pdo,
        $actor,
        'SUPPLIER_COVERAGE_CREATED',
        'SUPPLIER_COVERAGE',
        $coverageId,
        "Declared supplier coverage for {$areaName} from {$startDate}" . ($endDate !== '' ? " through {$endDate}." : ' with no planned end date.')
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Supplier coverage saved.',
        'coverage_id' => $coverageId
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === '23000') {
        respond_error(409, 'A coverage record already exists for this county and start date. Choose a different start date.');
    }

    log_error('supplier coverage save', $e);
    respond_error(500, 'Unable to save supplier coverage.');
}