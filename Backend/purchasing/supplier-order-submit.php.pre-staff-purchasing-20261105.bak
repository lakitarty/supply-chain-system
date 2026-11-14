<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';

require_post();

try {
    $actor = require_login($pdo);
    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)
        || $actor['role'] !== 'ADMIN') {
        respond_error(403, 'Supplier administrator access is required to submit an outgoing LPO.');
    }
    $body = read_json_body();
    $orderId = filter_var($body['orderId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($orderId === false || $orderId === null) respond_error(400, 'A valid outgoing LPO number is required.');
    $buyerId = (int)$actor['organization_id'];

    $pdo->beginTransaction();
    $orderStatement = $pdo->prepare(
        "SELECT so.supply_order_id, so.supplier_id, so.status, so.lpo_valid_until, so.lpo_valid_until
         FROM supply_orders so
         WHERE so.supply_order_id = ? AND so.retailer_id = ?
           AND so.order_direction = 'SUPPLIER_TO_MANUFACTURER'
         LIMIT 1 FOR UPDATE"
    );
    $orderStatement->execute([(int)$orderId, $buyerId]);
    $order = $orderStatement->fetch(PDO::FETCH_ASSOC);
    if (!$order || $order['status'] !== 'DRAFT') {
        $pdo->rollBack(); respond_error(409, 'This outgoing LPO is not an editable draft.');
    }

    if (!empty($order['lpo_valid_until'])
        && $order['lpo_valid_until'] < date('Y-m-d')) {
        $pdo->rollBack();
        respond_error(409, 'This LPO expired on ' . $order['lpo_valid_until'] . ' and can no longer be submitted. Create a new LPO.');
    }
    if (!empty($order['lpo_valid_until'])
        && $order['lpo_valid_until'] < date('Y-m-d')) {
        $pdo->rollBack();
        respond_error(409, 'This LPO expired on ' . $order['lpo_valid_until'] . ' and can no longer be submitted. Create a new LPO.');
    }
    $relationship = $pdo->prepare(
        "SELECT 1 FROM organizations m
         INNER JOIN organization_relationships r ON r.source_organization_id = m.organization_id
          AND r.target_organization_id = ? AND r.relationship_type = 'SUPPLIES'
          AND r.status = 'ACTIVE' AND r.start_date <= CURDATE()
          AND (r.end_date IS NULL OR r.end_date >= CURDATE())
         WHERE m.organization_id = ? AND m.status = 'ACTIVE'
           AND m.organization_type IN ('MANUFACTURER', 'MANUFACTURER_SUPPLIER') LIMIT 1 FOR UPDATE"
    );
    $relationship->execute([$buyerId, (int)$order['supplier_id']]);
    if (!$relationship->fetchColumn()) { $pdo->rollBack(); respond_error(409, 'The manufacturer relationship is no longer active.'); }

    $count = $pdo->prepare('SELECT COUNT(*) FROM supply_order_items WHERE supply_order_id = ?');
    $count->execute([(int)$orderId]);
    $lineCount = (int)$count->fetchColumn();
    if ($lineCount < 1) { $pdo->rollBack(); respond_error(400, 'Add at least one product before submitting the LPO.'); }

    $eligible = $pdo->prepare(
        "SELECT COUNT(*)
         FROM supply_order_items i
         INNER JOIN products p ON p.product_id = i.product_id
           AND p.manufacturer_id = ? AND p.status = 'ACTIVE' AND p.availability_status = 'AVAILABLE'
         INNER JOIN product_versions pv ON pv.product_id = i.product_id
           AND pv.product_version_id = i.product_version_id AND pv.is_current = 1
           AND pv.effective_from <= CURDATE() AND (pv.effective_to IS NULL OR pv.effective_to >= CURDATE())
         INNER JOIN product_prices pp ON pp.price_id = i.source_product_price_id
           AND pp.organization_id = ? AND pp.product_id = i.product_id
           AND pp.product_version_id = i.product_version_id AND pp.price_type = 'SELLING'
           AND pp.price_amount = i.unit_price
           AND (CASE WHEN pp.price_includes_vat = 1 THEN 'VAT_INCLUSIVE' ELSE 'VAT_EXCLUSIVE' END) = i.price_basis
           AND COALESCE((SELECT tc.tax_category FROM product_tax_classifications tc
                         WHERE tc.product_version_id = pv.product_version_id
                           AND tc.manufacturer_id = p.manufacturer_id
                           AND tc.effective_from <= CURDATE()
                           AND (tc.effective_to IS NULL OR tc.effective_to >= CURDATE())
                         ORDER BY tc.effective_from DESC, tc.product_tax_classification_id DESC LIMIT 1), pp.tax_category) = i.tax_category
           AND pp.effective_from <= CURDATE() AND (pp.effective_to IS NULL OR pp.effective_to >= CURDATE())
         WHERE i.supply_order_id = ?"
    );
    $eligible->execute([(int)$order['supplier_id'], (int)$order['supplier_id'], (int)$orderId]);
    if ((int)$eligible->fetchColumn() !== $lineCount) {
        $pdo->rollBack(); respond_error(409, 'One or more manufacturer prices or product versions changed or are not yet available. Reopen the draft and review it before submitting.');
    }

    $update = $pdo->prepare(
        "UPDATE supply_orders SET status = 'SUBMITTED'
         WHERE supply_order_id = ? AND retailer_id = ?
           AND order_direction = 'SUPPLIER_TO_MANUFACTURER' AND status = 'DRAFT'"
    );
    $update->execute([(int)$orderId, $buyerId]);
    if ($update->rowCount() !== 1) { $pdo->rollBack(); respond_error(409, 'The LPO status changed. Refresh and try again.'); }

    record_audit($pdo, $actor, 'SUPPLIER_LPO_SUBMITTED', 'SUPPLY_ORDER', (int)$orderId,
        'Submitted outgoing LPO #' . (int)$orderId . ' to manufacturer organisation #' . (int)$order['supplier_id'] .
        ' with ' . $lineCount . ' product lines.');
    $pdo->commit();
    respond(200, ['success' => true, 'message' => 'Outgoing LPO submitted to manufacturer.',
        'supply_order_id' => (int)$orderId, 'status' => 'SUBMITTED']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('supplier outgoing LPO submit', $e);
    respond_error(500, 'Unable to submit the outgoing LPO.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('supplier outgoing LPO submit unexpected', $e);
    respond_error(500, 'Unable to submit the outgoing LPO.');
}