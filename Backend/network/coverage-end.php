<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';

require_post();

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Supplier organisation administrator access is required.');
    }

    $body = read_json_body();
    $coverageId = filter_var(
        $body['coverageId'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($coverageId === false || $coverageId === null) {
        respond_field_error(400, field_error('coverageId', 'Select a valid coverage record.'));
    }

    $statement = $pdo->prepare(
        'SELECT sda.supplier_area_id, sda.area_id, sda.start_date, sda.end_date,
                sda.status, da.area_name
         FROM supplier_distribution_areas sda
         INNER JOIN distribution_areas da ON da.area_id = sda.area_id
         WHERE sda.supplier_area_id = ? AND sda.supplier_id = ?
         LIMIT 1'
    );
    $statement->execute([(int)$coverageId, (int)$actor['organization_id']]);
    $coverage = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$coverage) {
        respond_error(404, 'Supplier coverage record not found.');
    }

    if ($coverage['status'] !== 'ACTIVE') {
        respond_error(409, 'Only active supplier coverage can be ended.');
    }

    $today = (string)$pdo->query('SELECT CURDATE()')->fetchColumn();

    if ($coverage['start_date'] > $today) {
        respond_error(409, 'This coverage period has not started yet.');
    }

    $pdo->beginTransaction();

    $update = $pdo->prepare(
        "UPDATE supplier_distribution_areas
         SET status = 'INACTIVE',
             end_date = CASE
                 WHEN end_date IS NULL OR end_date > CURDATE() THEN CURDATE()
                 ELSE end_date
             END
         WHERE supplier_area_id = ?
           AND supplier_id = ?
           AND status = 'ACTIVE'"
    );
    $update->execute([(int)$coverageId, (int)$actor['organization_id']]);

    if ($update->rowCount() !== 1) {
        $pdo->rollBack();
        respond_error(409, 'This supplier coverage has already changed. Refresh the list and try again.');
    }

    record_audit(
        $pdo,
        $actor,
        'SUPPLIER_COVERAGE_ENDED',
        'SUPPLIER_COVERAGE',
        (int)$coverageId,
        "Ended supplier coverage for {$coverage['area_name']}."
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Supplier coverage ended. Its history has been retained.'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('supplier coverage end', $e);
    respond_error(500, 'Unable to end supplier coverage.');
}