<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/lpo-number.php';
require_once __DIR__ . '/lpo-number.php';

require_post();

try {
    $actor = require_login($pdo);

    if ($actor['organization_type'] !== 'RETAILER'
        || !in_array($actor['role'], ['ADMIN', 'BRANCH_ADMIN'], true)) {
        respond_error(403, 'Retailer administrator or branch administrator access is required.');
    }

    $body = read_json_body();
    $supplierId = filter_var($body['supplierId'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    $branchId = filter_var($body['branchId'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    $expectedDate = trim((string)($body['expectedDeliveryDate'] ?? ''));
    $items = $body['items'] ?? [];

    if ($supplierId === false || $supplierId === null) {
        respond_error(400, 'Choose a supplier.');
    }
    if ($branchId === false || $branchId === null) {
        respond_error(400, 'Choose a branch.');
    }
    if ($actor['role'] === 'BRANCH_ADMIN' && (int)$branchId !== (int)$actor['branch_id']) {
        respond_error(403, 'You can only create orders for your assigned branch.');
    }
    if ($expectedDate !== '' && !valid_date($expectedDate)) {
        respond_error(400, 'Enter a valid expected delivery date.');
    }
    if ($expectedDate !== '' && $expectedDate < date('Y-m-d')) {
        respond_error(400, 'Expected delivery date cannot be in the past.');
    }
    if (!is_array($items) || count($items) < 1 || count($items) > 100) {
        respond_error(400, 'Add between 1 and 100 product lines.');
    }

    $organizationId = (int)$actor['organization_id'];

    $pdo->beginTransaction();

    $branchStatement = $pdo->prepare(
        "SELECT branch_id, area_id
         FROM branches
         WHERE branch_id = ?
           AND organization_id = ?
           AND status = 'ACTIVE'
         LIMIT 1 FOR UPDATE"
    );
    $branchStatement->execute([(int)$branchId, $organizationId]);
    $branch = $branchStatement->fetch(PDO::FETCH_ASSOC);

    if (!$branch || empty($branch['area_id'])) {
        $pdo->rollBack();
        respond_error(409, 'The selected branch must be active and assigned to a distribution area.');
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
    $relationshipStatement->execute([$organizationId, $organizationId, (int)$supplierId]);

    if (!$relationshipStatement->fetchColumn()) {
        $pdo->rollBack();
        respond_error(403, 'This supplier does not have an active approved supply relationship with your organisation.');
    }

    $normalisedItems = [];
    $seenProducts = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            $pdo->rollBack();
            respond_error(400, 'Each order line must contain a product and quantity.');
        }

        $productId = filter_var($item['productId'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 999999999]
        ]);

        if ($productId === false || $productId === null || $quantity === false || $quantity === null) {
            $pdo->rollBack();
            respond_error(400, 'Every line needs a valid product and a whole-number quantity greater than zero.');
        }
        if (isset($seenProducts[(int)$productId])) {
            $pdo->rollBack();
            respond_error(400, 'A product may only appear once in an LPO. Combine its quantities into one line.');
        }

        $seenProducts[(int)$productId] = true;
        $normalisedItems[] = [
            'product_id' => (int)$productId,
            'quantity' => (int)$quantity
        ];
    }

    $priceStatement = $pdo->prepare(
        "SELECT offer.supplier_product_offer_id, offer.unit_price, offer.price_basis,
                COALESCE(supply.minimum_order_quantity, 1) AS minimum_order_quantity,
                COALESCE(supply.order_multiple, 1) AS order_multiple,
                p.product_name
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id AND p.status = 'ACTIVE'
         INNER JOIN organizations manufacturer
            ON manufacturer.organization_id = p.manufacturer_id
           AND manufacturer.status = 'ACTIVE'
         INNER JOIN product_versions pv
            ON pv.product_id = p.product_id
           AND pv.is_current = 1
         INNER JOIN supplier_product_offers offer
            ON offer.supplier_id = ps.supplier_id
           AND offer.product_id = p.product_id
           AND offer.product_version_id = pv.product_version_id
           AND offer.area_id = ps.area_id
           AND offer.status = 'ACTIVE'
           AND offer.currency_code = 'KES'
           AND offer.effective_from <= CURDATE()
           AND (offer.effective_to IS NULL OR offer.effective_to >= CURDATE())
         LEFT JOIN supplier_product_supply supply
            ON supply.supplier_id = ps.supplier_id
           AND supply.product_id = p.product_id
         WHERE ps.product_id = ?
           AND ps.supplier_id = ?
           AND ps.area_id = ?
           AND ps.status = 'ACTIVE'
           AND ps.start_date <= CURDATE()
           AND (ps.end_date IS NULL OR ps.end_date >= CURDATE())
           AND (supply.availability_status IS NULL
                OR supply.availability_status IN ('AVAILABLE', 'LIMITED'))
         ORDER BY offer.effective_from DESC, offer.supplier_product_offer_id DESC
         LIMIT 2
         FOR UPDATE"
    );

    foreach ($normalisedItems as &$line) {
        $priceStatement->execute([
            $line['product_id'],
            (int)$supplierId,
            (int)$branch['area_id']
        ]);
        $matchingPrices = $priceStatement->fetchAll(PDO::FETCH_ASSOC);

        if (count($matchingPrices) > 1) {
            $pdo->rollBack();
            respond_error(409, 'Multiple current offers match this product and supplier. Ask the supplier administrator to resolve the overlapping offers before submitting the LPO.');
        }

        $price = $matchingPrices[0] ?? false;

        if (!$price) {
            $pdo->rollBack();
            respond_error(409, 'One or more products no longer have an eligible, current offer from this supplier for the selected branch.');
        }

        $minimum = (int)$price['minimum_order_quantity'];
        $multiple = max(1, (int)$price['order_multiple']);

        if ($line['quantity'] < $minimum || $line['quantity'] % $multiple !== 0) {
            $pdo->rollBack();
            respond_error(
                400,
                $price['product_name'] . ' requires a minimum order of ' . $minimum .
                ' units in multiples of ' . $multiple . '.'
            );
        }

        $line['unit_price'] = (string)$price['unit_price'];
    }
    unset($line);

    $lpoDocument = allocate_lpo_document($pdo, $organizationId);

    $insertOrder = $pdo->prepare(
        "INSERT INTO supply_orders
            (retailer_id, lpo_number, lpo_valid_until, branch_id,
             supplier_id, order_date, expected_delivery_date, status, created_by)
         VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 'SUBMITTED', ?)"
    );
    $insertOrder->execute([
        $organizationId,
        $lpoDocument['lpo_number'],
        $lpoDocument['lpo_valid_until'],
        (int)$branchId,
        (int)$supplierId,
        $expectedDate !== '' ? $expectedDate : null,
        (int)$actor['user_id']
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $insertItem = $pdo->prepare(
        "INSERT INTO supply_order_items
            (supply_order_id, product_id, quantity_ordered, quantity_received, unit_price)
         VALUES (?, ?, ?, 0, ?)"
    );

    foreach ($normalisedItems as $line) {
        $insertItem->execute([
            $orderId,
            $line['product_id'],
            $line['quantity'],
            $line['unit_price']
        ]);
    }

    record_audit(
        $pdo,
        $actor,
        'SUPPLY_ORDER_SUBMITTED',
        'SUPPLY_ORDER',
        $orderId,
        'Submitted LPO #' . $orderId . ' to supplier organisation #' . (int)$supplierId .
        ' for branch #' . (int)$branchId . ' with ' . count($normalisedItems) . ' product lines.'
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'LPO submitted successfully.',
        'supply_order_id' => $orderId
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'The LPO conflicts with an existing record. Refresh and try again.');
    }
    log_error('purchasing order save', $e);
    respond_error(500, 'Unable to submit the LPO.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('purchasing order save unexpected', $e);
    respond_error(500, 'Unable to submit the LPO.');
}
