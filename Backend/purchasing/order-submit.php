<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';

require_post();

try {
    $actor = require_login($pdo);

    if ($actor['organization_type'] !== 'RETAILER'
        || !in_array($actor['role'], ['ADMIN', 'BRANCH_ADMIN'], true)) {
        respond_error(403, 'Retailer administrator or branch administrator access is required.');
    }

    $body = read_json_body();
    $orderId = filter_var($body['orderId'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);

    if ($orderId === false || $orderId === null) {
        respond_error(400, 'A valid draft LPO number is required.');
    }

    $organizationId = (int)$actor['organization_id'];

    $pdo->beginTransaction();

    $orderStatement = $pdo->prepare(
        "SELECT so.supply_order_id, so.lpo_valid_until, so.branch_id, so.supplier_id,
                so.expected_delivery_date, so.status,
                b.area_id
         FROM supply_orders so
         INNER JOIN branches b
            ON b.branch_id = so.branch_id
           AND b.organization_id = so.retailer_id
           AND b.status = 'ACTIVE'
         WHERE so.supply_order_id = ?
           AND so.retailer_id = ?
           AND so.order_direction = 'RETAILER_TO_SUPPLIER'
         LIMIT 1 FOR UPDATE"
    );
    $orderStatement->execute([(int)$orderId, $organizationId]);
    $order = $orderStatement->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['status'] !== 'DRAFT') {
        $pdo->rollBack();
        respond_error(409, 'This LPO is not an editable draft.');
    }

    if ($actor['role'] === 'BRANCH_ADMIN'
        && (int)$order['branch_id'] !== (int)$actor['branch_id']) {
        $pdo->rollBack();
        respond_error(403, 'You cannot submit an LPO for another branch.');
    }

    if (empty($order['area_id'])) {
        $pdo->rollBack();
        respond_error(409, 'The delivery branch must be assigned to a distribution area.');
    }

    if (!empty($order['expected_delivery_date'])
        && $order['expected_delivery_date'] < date('Y-m-d')) {
        $pdo->rollBack();
        respond_error(409, 'The expected delivery date is now in the past. Edit the draft before submitting it.');
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
    $relationshipStatement = $pdo->prepare(
        "SELECT 1
         FROM organizations supplier
         INNER JOIN organization_relationships r
            ON r.status = 'ACTIVE'
           AND r.start_date <= CURDATE()
           AND (r.end_date IS NULL OR r.end_date >= CURDATE())
           AND r.relationship_type = 'SUPPLIES'
           AND (
                (r.source_organization_id = ? AND r.target_organization_id = supplier.organization_id)
                OR
                (r.target_organization_id = ? AND r.source_organization_id = supplier.organization_id)
           )
         WHERE supplier.organization_id = ?
           AND supplier.status = 'ACTIVE'
           AND supplier.organization_type IN ('SUPPLIER', 'MANUFACTURER_SUPPLIER')
         LIMIT 1 FOR UPDATE"
    );
    $relationshipStatement->execute([
        $organizationId,
        $organizationId,
        (int)$order['supplier_id']
    ]);

    if (!$relationshipStatement->fetchColumn()) {
        $pdo->rollBack();
        respond_error(409, 'The supplier relationship is no longer active. The draft was not submitted.');
    }

    $countStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM supply_order_items
         WHERE supply_order_id = ?"
    );
    $countStatement->execute([(int)$orderId]);
    $lineCount = (int)$countStatement->fetchColumn();

    if ($lineCount < 1) {
        $pdo->rollBack();
        respond_error(400, 'Add at least one product before submitting the LPO.');
    }

    $eligibleStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM supply_order_items i
         INNER JOIN products p
            ON p.product_id = i.product_id
           AND p.status = 'ACTIVE' AND p.availability_status = 'AVAILABLE'
         INNER JOIN product_versions pv
            ON pv.product_id = i.product_id
           AND pv.product_version_id = i.product_version_id
           AND pv.is_current = 1
           AND pv.effective_from <= CURDATE()
           AND (pv.effective_to IS NULL OR pv.effective_to >= CURDATE())
         INNER JOIN supplier_product_offers offer
            ON offer.supplier_product_offer_id = i.supplier_product_offer_id
           AND offer.supplier_id = ?
           AND offer.product_id = i.product_id
           AND offer.product_version_id = i.product_version_id
           AND offer.area_id = ?
           AND offer.unit_price = i.unit_price
           AND offer.price_basis = i.price_basis
           AND offer.currency_code = 'KES'
           AND offer.status = 'ACTIVE'
           AND offer.effective_from <= CURDATE()
           AND (offer.effective_to IS NULL OR offer.effective_to >= CURDATE())
         INNER JOIN product_suppliers ps
            ON ps.product_id = i.product_id
           AND ps.supplier_id = ?
           AND ps.area_id = ?
           AND ps.status = 'ACTIVE'
           AND ps.start_date <= CURDATE()
           AND (ps.end_date IS NULL OR ps.end_date >= CURDATE())
         LEFT JOIN supplier_product_supply supply
            ON supply.supplier_id = ?
           AND supply.product_id = i.product_id
         WHERE i.supply_order_id = ?
                      AND (
                supply.availability_status IS NULL
                OR supply.availability_status IN ('AVAILABLE', 'LIMITED')
           )
           AND EXISTS (
                SELECT 1
                FROM product_tax_classifications eligible_tax
                WHERE eligible_tax.product_version_id = pv.product_version_id
                  AND eligible_tax.manufacturer_id = p.manufacturer_id
                  AND eligible_tax.effective_from <= CURDATE()
                  AND (eligible_tax.effective_to IS NULL OR eligible_tax.effective_to >= CURDATE())
           )"
    );
    $eligibleStatement->execute([
        (int)$order['supplier_id'],
        (int)$order['area_id'],
        (int)$order['supplier_id'],
        (int)$order['area_id'],
        (int)$order['supplier_id'],
        (int)$orderId
    ]);
    $eligibleCount = (int)$eligibleStatement->fetchColumn();

    if ($eligibleCount !== $lineCount) {
        $pdo->rollBack();
        respond_error(
            409,
            'One or more saved offers have expired, changed, or are no longer eligible. Reopen the draft, review the supplier offers, and save it again before submitting.'
        );
    }

    $updateStatement = $pdo->prepare(
        "UPDATE supply_orders
         SET status = 'SUBMITTED'
         WHERE supply_order_id = ?
           AND retailer_id = ?
           AND status = 'DRAFT'"
    );
    $updateStatement->execute([(int)$orderId, $organizationId]);

    if ($updateStatement->rowCount() !== 1) {
        $pdo->rollBack();
        respond_error(409, 'The draft status changed. Refresh and try again.');
    }

    record_audit(
        $pdo,
        $actor,
        'SUPPLY_ORDER_SUBMITTED',
        'SUPPLY_ORDER',
        (int)$orderId,
        'Submitted LPO #' . (int)$orderId . ' to supplier organisation #' .
        (int)$order['supplier_id'] . ' with ' . $lineCount . ' product lines.'
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'LPO submitted successfully.',
        'supply_order_id' => (int)$orderId,
        'status' => 'SUBMITTED'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('purchasing order submit', $e);
    respond_error(500, 'Unable to submit the LPO.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('purchasing order submit unexpected', $e);
    respond_error(500, 'Unable to submit the LPO.');
}