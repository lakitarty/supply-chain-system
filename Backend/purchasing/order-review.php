<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';

require_post();

try {
    $actor = require_login($pdo);
    if ($actor['role'] !== 'ADMIN') respond_error(403, 'Organisation administrator access is required to verify an LPO.');
    $type = (string)$actor['organization_type'];
    if (!in_array($type, ['SUPPLIER', 'MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'This organisation cannot verify incoming LPOs.');
    }
    $body = read_json_body();
    $orderId = filter_var($body['orderId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $decision = strtoupper(trim((string)($body['decision'] ?? '')));
    $reason = trim((string)($body['reason'] ?? ''));
    if ($orderId === false || $orderId === null) respond_error(400, 'A valid LPO number is required.');
    if (!in_array($decision, ['CONFIRMED', 'REJECTED'], true)) respond_error(400, 'Choose Confirm or Reject.');
    if (strlen($reason) > 500) respond_error(400, 'The rejection note must be 500 characters or fewer.');

    $organizationId = (int)$actor['organization_id'];
    $pdo->beginTransaction();
    $orderStatement = $pdo->prepare(
        "SELECT supply_order_id, order_direction, supplier_id, retailer_id, status, lpo_valid_until, lpo_valid_until
         FROM supply_orders WHERE supply_order_id = ? LIMIT 1 FOR UPDATE"
    );
    $orderStatement->execute([(int)$orderId]);
    $order = $orderStatement->fetch(PDO::FETCH_ASSOC);
    if (!$order || (int)$order['supplier_id'] !== $organizationId || $order['status'] !== 'SUBMITTED') {
        $pdo->rollBack(); respond_error(404, 'The submitted LPO was not found or is not awaiting verification by your organisation.');
    }
    $direction = (string)$order['order_direction'];
    $allowed = ($direction === 'RETAILER_TO_SUPPLIER' && in_array($type, ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true))
        || ($direction === 'SUPPLIER_TO_MANUFACTURER' && in_array($type, ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true));
    if (!$allowed) { $pdo->rollBack(); respond_error(403, 'Your organisation type cannot verify this LPO direction.'); }

    if (!empty($order['lpo_valid_until'])
        && $order['lpo_valid_until'] < date('Y-m-d')) {
        $pdo->rollBack();
        respond_error(409, 'This LPO expired on ' . $order['lpo_valid_until'] . ' and can no longer be approved. Ask the issuing organisation for a new LPO.');
    }
    if (!empty($order['lpo_valid_until'])
        && $order['lpo_valid_until'] < date('Y-m-d')) {
        $pdo->rollBack();
        respond_error(409, 'This LPO expired on ' . $order['lpo_valid_until'] . ' and can no longer be approved. Ask the issuing organisation for a new LPO.');
    }
    $update = $pdo->prepare(
        "UPDATE supply_orders
         SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP,
             rejection_reason = ?
         WHERE supply_order_id = ? AND supplier_id = ? AND status = 'SUBMITTED'"
    );
    $update->execute([$decision, (int)$actor['user_id'], $decision === 'REJECTED' && $reason !== '' ? $reason : null,
        (int)$orderId, $organizationId]);
    if ($update->rowCount() !== 1) { $pdo->rollBack(); respond_error(409, 'The LPO was updated by another user. Refresh and try again.'); }

    record_audit($pdo, $actor, 'SUPPLY_ORDER_' . $decision, 'SUPPLY_ORDER', (int)$orderId,
        ($direction === 'RETAILER_TO_SUPPLIER' ? 'Supplier' : 'Manufacturer') . ' ' . strtolower($decision) .
        ' LPO #' . (int)$orderId . ($reason !== '' && $decision === 'REJECTED' ? '. Reason: ' . $reason : '.'));
    $pdo->commit();
    respond(200, ['success' => true, 'message' => $decision === 'CONFIRMED' ? 'LPO confirmed.' : 'LPO rejected.',
        'supply_order_id' => (int)$orderId, 'status' => $decision]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('purchasing LPO review', $e);
    respond_error(500, 'Unable to verify the LPO.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('purchasing LPO review unexpected', $e);
    respond_error(500, 'Unable to verify the LPO.');
}