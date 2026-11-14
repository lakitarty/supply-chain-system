<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/price-confirmation.php';
require_once __DIR__ . '/../products/pricing-rules.php';
require_once __DIR__ . '/supply-arrangement-rules.php';

const APPOINTMENT_NOTICE_DAYS = 14;

require_post();

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Manufacturer organisation administrator access is required.');
    }

    $body = read_json_body();
    $appointmentId = filter_var($body['appointmentId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $withdraw = ($body['action'] ?? 'end') === 'withdraw';

    if ($appointmentId === false || $appointmentId === null) {
        respond_field_error(400, field_error('appointmentId', 'Select a valid appointment.'));
    }

    require_price_confirmation_password(
        $pdo,
        $actor,
        (string)($body['adminPassword'] ?? ''),
        $withdraw ? 'notice withdrawal' : 'appointment change'
    );

    $organizationId = (int)$actor['organization_id'];
    $today = nairobi_today();
    $noticeEnd = (new DateTimeImmutable($today))->modify('+' . APPOINTMENT_NOTICE_DAYS . ' days')->format('Y-m-d');

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        "SELECT ps.product_supplier_id, ps.product_id, ps.supplier_id, ps.area_id, ps.status,
                ps.start_date, ps.end_date, p.product_name,
                supplier.organization_name AS supplier_name, area.area_name
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN organizations supplier ON supplier.organization_id = ps.supplier_id
         LEFT JOIN distribution_areas area ON area.area_id = ps.area_id
         WHERE ps.product_supplier_id = ?
           AND p.manufacturer_id = ?
         LIMIT 1 FOR UPDATE"
    );
    $statement->execute([(int)$appointmentId, $organizationId]);
    $appointment = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $pdo->rollBack();
        respond_error(404, 'The appointment was not found among your organisation products.');
    }

    $endDate = $appointment['end_date'];

    if (!in_array($appointment['status'], ['ACTIVE', 'PENDING'], true) || ($endDate !== null && $endDate < $today)) {
        $pdo->rollBack();
        respond_error(409, 'This appointment has already ended or been cancelled.');
    }

    $productName = (string)$appointment['product_name'];
    $supplierName = (string)$appointment['supplier_name'];
    $supplierId = (int)$appointment['supplier_id'];
    $productId = (int)$appointment['product_id'];
    $areaId = $appointment['area_id'] === null ? null : (int)$appointment['area_id'];
    $county = $appointment['area_name'] ?: 'the unassigned county';
    $retailerIds = array_column(supply_arrangements($pdo, $today, null, (int)$appointmentId), 'retailer_id');
    $notifyRetailers = true;

    if ($appointment['status'] === 'PENDING') {
        $statement = $pdo->prepare("UPDATE product_suppliers SET status = 'INACTIVE' WHERE product_supplier_id = ?");
        $statement->execute([(int)$appointmentId]);

        $notifyRetailers = false;
        $auditAction = 'PRODUCT_DISTRIBUTION_CANCELLED';
        $title = 'Pending appointment cancelled';
        $partyMessage = "The pending appointment of {$supplierName} for {$productName} in {$county} was cancelled before the supplier responded.";
        $retailerMessage = $partyMessage;
        $result = 'Pending appointment cancelled.';
        $endsOn = null;
    } elseif ($withdraw) {
        if ($endDate === null) {
            $pdo->rollBack();
            respond_error(409, 'There is no ending notice to withdraw.');
        }

        $statement = $pdo->prepare('UPDATE product_suppliers SET end_date = NULL WHERE product_supplier_id = ?');
        $statement->execute([(int)$appointmentId]);

        $statement = $pdo->prepare(
            "UPDATE supplier_product_offers
             SET effective_to = NULL
             WHERE supplier_id = ? AND product_id = ? AND area_id <=> ?
               AND status = 'ACTIVE' AND effective_to = ?"
        );
        $statement->execute([$supplierId, $productId, $areaId, $endDate]);

        $auditAction = 'PRODUCT_DISTRIBUTION_NOTICE_WITHDRAWN';
        $title = 'Appointment ending withdrawn';
        $partyMessage = "The ending of {$supplierName}'s appointment for {$productName} in {$county} has been withdrawn. Supply continues.";
        $retailerMessage = $partyMessage;
        $result = 'Ending notice withdrawn. The appointment continues.';
        $endsOn = null;
    } elseif ($appointment['start_date'] > $today) {
        $statement = $pdo->prepare("UPDATE product_suppliers SET status = 'INACTIVE' WHERE product_supplier_id = ?");
        $statement->execute([(int)$appointmentId]);

        $statement = $pdo->prepare(
            "UPDATE supplier_product_offers
             SET status = 'INACTIVE'
             WHERE supplier_id = ? AND product_id = ? AND area_id <=> ?
               AND status = 'ACTIVE' AND effective_from >= ?"
        );
        $statement->execute([$supplierId, $productId, $areaId, $appointment['start_date']]);

        $notifyRetailers = false;
        $auditAction = 'PRODUCT_DISTRIBUTION_CANCELLED';
        $title = 'Scheduled appointment cancelled';
        $partyMessage = "The scheduled appointment of {$supplierName} for {$productName} in {$county} was cancelled before it started.";
        $retailerMessage = $partyMessage;
        $result = 'Scheduled appointment cancelled.';
        $endsOn = null;
    } else {
        if ($endDate !== null && $endDate > $today) {
            $pdo->rollBack();
            respond_error(409, "This appointment already ends on {$endDate}. Withdraw that notice first if you want to change it.");
        }

        $statement = $pdo->prepare(
            "SELECT replacement.organization_name
             FROM product_suppliers ps
             INNER JOIN organizations replacement ON replacement.organization_id = ps.supplier_id AND replacement.status = 'ACTIVE'
             WHERE ps.product_id = ?
               AND ps.area_id <=> ?
               AND ps.supplier_id <> ?
               AND ps.status = 'ACTIVE'
               AND ps.start_date <= ?
               AND (ps.end_date IS NULL OR ps.end_date >= ?)
             LIMIT 1"
        );
        $statement->execute([$productId, $areaId, $supplierId, $noticeEnd, $noticeEnd]);
        $replacementName = $statement->fetchColumn();

        if ($replacementName === false) {
            $pdo->rollBack();
            $message = "Appoint another supplier for {$productName} in {$county} before ending this appointment.";
            respond(409, [
                'success' => false,
                'code' => 'REPLACEMENT_REQUIRED',
                'message' => $message,
                'error' => $message
            ]);
        }

        $statement = $pdo->prepare('UPDATE product_suppliers SET end_date = ? WHERE product_supplier_id = ?');
        $statement->execute([$noticeEnd, (int)$appointmentId]);

        $statement = $pdo->prepare(
            "UPDATE supplier_product_offers
             SET effective_to = ?
             WHERE supplier_id = ? AND product_id = ? AND area_id <=> ?
               AND status = 'ACTIVE' AND effective_from <= ?
               AND (effective_to IS NULL OR effective_to > ?)"
        );
        $statement->execute([$noticeEnd, $supplierId, $productId, $areaId, $noticeEnd, $noticeEnd]);

        $auditAction = 'PRODUCT_DISTRIBUTION_NOTICE_GIVEN';
        $title = 'Regional product appointment ending';
        $partyMessage = "The appointment of {$supplierName} for {$productName} in {$county} will end on {$noticeEnd}. {$replacementName} is appointed to continue supplying it.";
        $retailerMessage = "{$supplierName} will stop supplying {$productName} in {$county} on {$noticeEnd}. {$replacementName} is appointed to supply it in your area. Connect with {$replacementName} to keep receiving the product.";
        $result = "Notice given. The appointment ends on {$noticeEnd}.";
        $endsOn = $noticeEnd;
    }

    record_audit($pdo, $actor, $auditAction, 'PRODUCT_SUPPLIER', (int)$appointmentId, $partyMessage);

    notify_organizations($pdo, [$organizationId, $supplierId], (int)$appointmentId, $title, $partyMessage);

    if ($notifyRetailers) {
        notify_organizations($pdo, $retailerIds, (int)$appointmentId, $title, $retailerMessage);
    }

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => $result,
        'ends_on' => $endsOn
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('regional distribution end', $e);
    respond_error(500, 'Unable to update the regional appointment.');
}
