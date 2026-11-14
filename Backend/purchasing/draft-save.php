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
        || !in_array($actor['role'], ['ADMIN', 'STAFF', 'BRANCH_ADMIN'], true)) {
        respond_error(403, 'Retailer purchasing access is required.');
    }

    $body = read_json_body();

    $draftIdRaw = $body['draftId'] ?? null;
    $draftId = ($draftIdRaw === null || $draftIdRaw === '')
        ? null
        : filter_var($draftIdRaw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
    $supplierId = filter_var($body['supplierId'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    $branchId = filter_var($body['branchId'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);

    if ($draftId === false) {
        respond_error(400, 'Invalid draft identifier.');
    }
    if ($supplierId === false || $supplierId === null) {
        respond_error(400, 'Choose a supplier.');
    }
    if ($branchId === false || $branchId === null) {
        respond_error(400, 'Choose a delivery branch.');
    }

    $expectedDate = null;

    $items = $body['items'] ?? [];
    if (!is_array($items) || count($items) > 100) {
        respond_error(400, 'A draft can contain at most 100 product lines.');
    }

    $organizationId = (int)$actor['organization_id'];
    $userId = (int)$actor['user_id'];

    if ($actor['role'] === 'BRANCH_ADMIN'
        && (int)$branchId !== (int)$actor['branch_id']) {
        respond_error(403, 'You can only create drafts for your assigned branch.');
    }

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
    $relationshipStatement->execute([
        $organizationId,
        $organizationId,
        (int)$supplierId
    ]);

    if (!$relationshipStatement->fetchColumn()) {
        $pdo->rollBack();
        respond_error(403, 'This supplier does not have an active approved supply relationship with your organisation.');
    }

    if ($draftId !== null) {
        $draftStatement = $pdo->prepare(
            "SELECT supply_order_id, branch_id, status
             FROM supply_orders
             WHERE supply_order_id = ?
               AND retailer_id = ?
             LIMIT 1 FOR UPDATE"
        );
        $draftStatement->execute([(int)$draftId, $organizationId]);
        $existingDraft = $draftStatement->fetch(PDO::FETCH_ASSOC);

        if (!$existingDraft || $existingDraft['status'] !== 'DRAFT') {
            $pdo->rollBack();
            respond_error(409, 'This LPO is not an editable draft.');
        }

        if ($actor['role'] === 'BRANCH_ADMIN'
            && (int)$existingDraft['branch_id'] !== (int)$actor['branch_id']) {
            $pdo->rollBack();
            respond_error(403, 'You cannot edit a draft belonging to another branch.');
        }

        $orderId = (int)$draftId;

        $updateOrder = $pdo->prepare(
            "UPDATE supply_orders
             SET branch_id = ?, supplier_id = ?, expected_delivery_date = ?
             WHERE supply_order_id = ?
               AND retailer_id = ?
               AND status = 'DRAFT'"
        );
        $updateOrder->execute([
            (int)$branchId,
            (int)$supplierId,
            $expectedDate,
            $orderId,
            $organizationId
        ]);

        $deleteItems = $pdo->prepare(
            "DELETE FROM supply_order_items WHERE supply_order_id = ?"
        );
        $deleteItems->execute([$orderId]);
    } else {
        $lpoDocument = allocate_lpo_document($pdo, $organizationId);

        $insertOrder = $pdo->prepare(
            "INSERT INTO supply_orders
                (retailer_id, lpo_number, lpo_valid_until, branch_id,
                 supplier_id, order_date, expected_delivery_date, status, created_by)
             VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 'DRAFT', ?)"
        );
        $insertOrder->execute([
            $organizationId,
            $lpoDocument['lpo_number'],
            $lpoDocument['lpo_valid_until'],
            (int)$branchId,
            (int)$supplierId,
            $expectedDate,
            $userId
        ]);
        $orderId = (int)$pdo->lastInsertId();
    }

    $priceStatement = $pdo->prepare(
        "SELECT offer.supplier_product_offer_id,
                offer.product_version_id,
                offer.unit_price,
                offer.price_basis,
                COALESCE(supply.minimum_order_quantity, 1) AS minimum_order_quantity,
                COALESCE(supply.order_multiple, 1) AS order_multiple,
                p.product_name,
                (SELECT eligible_tax.tax_category
                 FROM product_tax_classifications eligible_tax
                 WHERE eligible_tax.product_version_id = pv.product_version_id
                   AND eligible_tax.manufacturer_id = p.manufacturer_id
                   AND eligible_tax.effective_from <= CURDATE()
                   AND (eligible_tax.effective_to IS NULL OR eligible_tax.effective_to >= CURDATE())
                 ORDER BY eligible_tax.effective_from DESC, eligible_tax.product_tax_classification_id DESC
                 LIMIT 1) AS tax_category
         FROM product_suppliers ps
         INNER JOIN products p
            ON p.product_id = ps.product_id
           AND p.status = 'ACTIVE' AND p.availability_status = 'AVAILABLE'
         INNER JOIN organizations manufacturer
            ON manufacturer.organization_id = p.manufacturer_id
           AND manufacturer.status = 'ACTIVE'
         INNER JOIN product_versions pv
            ON pv.product_id = p.product_id
           AND pv.is_current = 1
           AND pv.effective_from <= CURDATE()
           AND (pv.effective_to IS NULL OR pv.effective_to >= CURDATE())
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
           )
         ORDER BY offer.effective_from DESC, offer.supplier_product_offer_id DESC
         LIMIT 2 FOR UPDATE"
    );

    $normalisedItems = [];
    $seenProducts = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            $pdo->rollBack();
            respond_error(400, 'Each product line must contain a product and quantity.');
        }

        $productId = filter_var($item['productId'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 999999999]
        ]);

        if ($productId === false || $productId === null
            || $quantity === false || $quantity === null) {
            $pdo->rollBack();
            respond_error(400, 'Each saved line requires a product and a positive whole-number quantity.');
        }

        if (isset($seenProducts[(int)$productId])) {
            $pdo->rollBack();
            respond_error(400, 'A product can appear only once in an LPO. Combine its quantities.');
        }
        $seenProducts[(int)$productId] = true;

        $priceStatement->execute([
            (int)$productId,
            (int)$supplierId,
            (int)$branch['area_id']
        ]);
        $matches = $priceStatement->fetchAll(PDO::FETCH_ASSOC);

        if (count($matches) > 1) {
            $pdo->rollBack();
            respond_error(409, 'Multiple current offers match a product. Ask the supplier administrator to resolve the overlapping offers.');
        }

        $offer = $matches[0] ?? null;
        if (!$offer) {
            $pdo->rollBack();
            respond_error(409, 'A product in this draft no longer has an eligible current offer. Remove it or choose another product.');
        }

        $minimum = (int)$offer['minimum_order_quantity'];
        $multiple = max(1, (int)$offer['order_multiple']);

        if ((int)$quantity < $minimum || (int)$quantity % $multiple !== 0) {
            $pdo->rollBack();
            respond_error(
                400,
                $offer['product_name'] . ' requires a minimum order of ' .
                $minimum . ' units in multiples of ' . $multiple . '.'
            );
        }

        $normalisedItems[] = [
            'product_id' => (int)$productId,
            'product_version_id' => (int)$offer['product_version_id'],
            'offer_id' => (int)$offer['supplier_product_offer_id'],
            'quantity' => (int)$quantity,
            'unit_price' => (string)$offer['unit_price'],
            'price_basis' => (string)$offer['price_basis'],
            'tax_category' => (string)$offer['tax_category']
        ];
    }

    $insertItem = $pdo->prepare(
        "INSERT INTO supply_order_items
            (supply_order_id, product_id, product_version_id,
             quantity_ordered, quantity_received, unit_price,
             price_basis, supplier_product_offer_id, tax_category)
         VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?)"
    );

    foreach ($normalisedItems as $line) {
        $insertItem->execute([
            $orderId,
            $line['product_id'],
            $line['product_version_id'],
            $line['quantity'],
            $line['unit_price'],
            $line['price_basis'],
            $line['offer_id'],
            $line['tax_category']
        ]);
    }

    record_audit(
        $pdo,
        $actor,
        'SUPPLY_ORDER_DRAFT_SAVED',
        'SUPPLY_ORDER',
        $orderId,
        'Saved draft LPO #' . $orderId . ' for supplier organisation #' .
        (int)$supplierId . ' with ' . count($normalisedItems) . ' product lines.'
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'LPO draft saved.',
        'supply_order_id' => $orderId,
        'status' => 'DRAFT',
        'item_count' => count($normalisedItems)
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'The draft conflicts with an existing record. Refresh and try again.');
    }
    log_error('purchasing draft save', $e);
    respond_error(500, 'Unable to save the LPO draft.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('purchasing draft save unexpected', $e);
    respond_error(500, 'Unable to save the LPO draft.');
}