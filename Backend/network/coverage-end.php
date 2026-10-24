<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';

require_post();

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Supplier organisation administrator access is required.');
    }

    $body = read_json_body();
    $confirmAppointmentsAffected = in_array($body['confirmAppointmentsAffected'] ?? false, [true, 1, '1', 'true'], true);
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
                sda.status, da.area_name, supplier.organization_name AS supplier_name
         FROM supplier_distribution_areas sda
         INNER JOIN distribution_areas da ON da.area_id = sda.area_id
         INNER JOIN organizations supplier ON supplier.organization_id = sda.supplier_id
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

    $appointmentsStatement = $pdo->prepare(
        "SELECT ps.product_supplier_id, p.product_id, p.product_name,
                p.manufacturer_id, manufacturer.organization_name AS manufacturer_name
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
         WHERE ps.supplier_id = ?
           AND ps.area_id = ?
           AND ps.status = 'ACTIVE'
           AND (ps.end_date IS NULL OR ps.end_date >= CURDATE())
         ORDER BY p.product_name, ps.start_date, ps.product_supplier_id"
    );
    $appointmentsStatement->execute([
        (int)$actor['organization_id'],
        (int)$coverage['area_id']
    ]);
    $affectedAppointmentRows = $appointmentsStatement->fetchAll(PDO::FETCH_ASSOC);
    $affectedAppointmentCount = count($affectedAppointmentRows);
    $affectedProducts = array_values(array_unique(array_column($affectedAppointmentRows, 'product_name')));

    if ($affectedAppointmentCount > 0 && !$confirmAppointmentsAffected) {
        respond(409, [
            'success' => false,
            'code' => 'ACTIVE_PRODUCT_APPOINTMENTS_WARNING',
            'warning' => 'Ending this supplier coverage will not end the product appointments listed below. They will remain active, so their delivery coverage may need review.',
            'area_name' => $coverage['area_name'],
            'affected_appointments' => $affectedAppointmentCount,
            'affected_products' => $affectedProducts
        ]);
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
        "Ended supplier coverage for {$coverage['area_name']}" . ($affectedAppointmentCount > 0 ? ". {$affectedAppointmentCount} active or scheduled product appointment(s) were left unchanged." : '.')
    );

    $supplierNotification = "Your organisation ended declared coverage for {$coverage['area_name']} effective {$today}.";
    if ($affectedAppointmentCount > 0) {
        $supplierNotification .= " {$affectedAppointmentCount} active or scheduled product appointment(s) remain unchanged and may need review.";
    }

    create_organization_notification(
        $pdo,
        (int)$actor['organization_id'],
        'SUPPLY_CHANGE',
        (int)$coverageId,
        'Supplier coverage ended',
        $supplierNotification
    );

    $manufacturerNotifications = [];
    foreach ($affectedAppointmentRows as $affectedRow) {
        $manufacturerId = (int)$affectedRow['manufacturer_id'];
        if (!isset($manufacturerNotifications[$manufacturerId])) {
            $manufacturerNotifications[$manufacturerId] = [
                'name' => (string)$affectedRow['manufacturer_name'],
                'products' => []
            ];
        }
        $manufacturerNotifications[$manufacturerId]['products'][(string)$affectedRow['product_name']] = true;
    }

    foreach ($manufacturerNotifications as $manufacturerId => $manufacturerData) {
        $productNames = array_keys($manufacturerData['products']);
        sort($productNames, SORT_NATURAL | SORT_FLAG_CASE);
        $manufacturerMessage = "{$coverage['supplier_name']} ended declared coverage for {$coverage['area_name']} effective {$today}. These product appointments remain active or scheduled and were not automatically ended: " . implode(', ', $productNames) . '. Review regional delivery arrangements.';

        create_organization_notification(
            $pdo,
            (int)$manufacturerId,
            'SUPPLY_CHANGE',
            (int)$coverageId,
            'Supplier coverage ended in ' . $coverage['area_name'],
            $manufacturerMessage
        );
    }

    $pdo->commit();

    $coverageEndMessage = 'Supplier coverage ended. Its history has been retained.';
    if ($affectedAppointmentCount > 0) {
        $coverageEndMessage .= " {$affectedAppointmentCount} active or scheduled product appointment(s) remain active and were not changed.";
    }

    respond(200, [
        'success' => true,
        'message' => $coverageEndMessage,
        'affected_appointments' => $affectedAppointmentCount
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('supplier coverage end', $e);
    respond_error(500, 'Unable to end supplier coverage.');
}