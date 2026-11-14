<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/price-confirmation.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../products/pricing-rules.php';
require_once __DIR__ . '/supply-arrangement-rules.php';

require_post();

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Supplier organisation administrator access is required.');
    }

    $body = read_json_body();
    $appointmentId = filter_var($body['appointmentId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $decision = (string)($body['decision'] ?? '');

    if ($appointmentId === false || $appointmentId === null) {
        respond_field_error(400, field_error('appointmentId', 'Select an appointment.'));
    }

    if (!in_array($decision, ['ACCEPTED', 'DECLINED'], true)) {
        respond_field_error(400, field_error('decision', 'Choose accept or decline.'));
    }

    require_price_confirmation_password($pdo, $actor, (string)($body['adminPassword'] ?? ''), 'appointment decision');

    $supplierId = (int)$actor['organization_id'];
    $today = nairobi_today();
    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        "SELECT ps.product_supplier_id, ps.product_id, ps.start_date, p.product_name, p.manufacturer_id,
                manufacturer.organization_name AS manufacturer_name,
                supplier.organization_name AS supplier_name,
                area.area_name
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
         INNER JOIN organizations supplier ON supplier.organization_id = ps.supplier_id
         INNER JOIN distribution_areas area ON area.area_id = ps.area_id
         WHERE ps.product_supplier_id = ?
           AND ps.supplier_id = ?
           AND ps.status = 'PENDING'
         LIMIT 1 FOR UPDATE"
    );
    $statement->execute([(int)$appointmentId, $supplierId]);
    $appointment = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $pdo->rollBack();
        respond_error(404, 'This appointment is not awaiting your decision.');
    }

    $manufacturerId = (int)$appointment['manufacturer_id'];
    $label = "{$appointment['product_name']} in {$appointment['area_name']}";

    if ($decision === 'ACCEPTED') {
        $problems = product_price_problems($pdo, (int)$appointment['product_id'], $today);

        if ($problems !== []) {
            $pdo->rollBack();
            respond_error(409, "The manufacturer must complete pricing for {$appointment['product_name']} before you can accept. " . implode(' ', $problems));
        }

        $relationship = price_rule_row(
            $pdo,
            "SELECT relationship_id
             FROM organization_relationships
             WHERE source_organization_id = ?
               AND target_organization_id = ?
               AND relationship_type = 'SUPPLIES'
               AND status = 'ACTIVE'
               AND start_date <= ?
               AND (end_date IS NULL OR end_date >= ?)
             LIMIT 1",
            [$manufacturerId, $supplierId, $today, $today]
        );

        if ($relationship === null) {
            $pdo->rollBack();
            respond_error(409, 'Your supply relationship with the manufacturer is no longer active.');
        }

        $pdo->prepare("UPDATE product_suppliers SET status = 'ACTIVE' WHERE product_supplier_id = ?")->execute([(int)$appointmentId]);
        create_appointment_offer($pdo, (int)$appointmentId, (int)$actor['user_id']);
        seed_supplier_prices($pdo, (int)$appointmentId);

        foreach (supply_arrangements($pdo, $today, null, (int)$appointmentId) as $arrangement) {
            notify_organizations(
                $pdo,
                [(int)$arrangement['retailer_id']],
                (int)$appointmentId,
                'Supply arrangement awaiting your decision',
                "{$arrangement['supplier_name']} has been appointed to supply {$arrangement['product_name']} in {$arrangement['area_name']} from {$appointment['start_date']}. Open Supply arrangements to accept or decline."
            );
        }

        $verb = 'accepted';
        $auditAction = 'PRODUCT_DISTRIBUTION_ACCEPTED';
    } else {
        $pdo->prepare("UPDATE product_suppliers SET status = 'DECLINED' WHERE product_supplier_id = ?")->execute([(int)$appointmentId]);
        $verb = 'declined';
        $auditAction = 'PRODUCT_DISTRIBUTION_DECLINED';
    }

    $message = "{$appointment['supplier_name']} {$verb} the appointment to supply {$label}.";
    record_audit($pdo, $actor, $auditAction, 'PRODUCT_SUPPLIER', (int)$appointmentId, $message);
    notify_organizations($pdo, [$manufacturerId], (int)$appointmentId, "Appointment {$verb}", $message);

    $pdo->commit();

    respond(200, ['success' => true, 'message' => "Appointment {$verb}.", 'decision' => $decision]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('supplier appointment decide', $e);
    respond_error(500, 'Unable to save your decision.');
}
