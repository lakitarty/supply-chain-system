<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';

require_post();

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Manufacturer organisation administrator access is required.');
    }

    $body = read_json_body();
    $appointmentId = filter_var($body['appointmentId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($appointmentId === false || $appointmentId === null) {
        respond_field_error(400, field_error('appointmentId', 'Select a valid appointment.'));
    }

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        "SELECT ps.product_supplier_id,
                ps.status,
                ps.start_date,
                ps.end_date,
                p.product_name,
                supplier.organization_name AS supplier_name,
                area.area_name
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN organizations supplier ON supplier.organization_id = ps.supplier_id
         LEFT JOIN distribution_areas area ON area.area_id = ps.area_id
         WHERE ps.product_supplier_id = ?
           AND p.manufacturer_id = ?
         LIMIT 1 FOR UPDATE"
    );
    $statement->execute([(int)$appointmentId, (int)$actor['organization_id']]);
    $appointment = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $pdo->rollBack();
        respond_error(404, 'The appointment was not found among your organisationâ€™s products.');
    }

    if ($appointment['status'] !== 'ACTIVE') {
        $pdo->rollBack();
        respond_error(409, 'This appointment has already ended or been cancelled.');
    }

    $statement = $pdo->prepare(
        "UPDATE product_suppliers
         SET status = 'INACTIVE',
             end_date = CASE WHEN start_date <= CURDATE() THEN CURDATE() ELSE end_date END
         WHERE product_supplier_id = ?"
    );
    $statement->execute([(int)$appointmentId]);

    $county = $appointment['area_name'] ?: 'county not assigned (legacy appointment)';
    record_audit(
        $pdo,
        $actor,
        'PRODUCT_DISTRIBUTION_ENDED',
        'PRODUCT_SUPPLIER',
        (int)$appointmentId,
        "Ended/cancelled appointment of {$appointment['supplier_name']} for {$appointment['product_name']} in {$county}."
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Regional appointment ended. Its history has been retained.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('regional distribution end', $e);
    respond_error(500, 'Unable to end the regional appointment.');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('regional distribution end', $e);
    respond_error(500, 'Unable to end the regional appointment.');
}