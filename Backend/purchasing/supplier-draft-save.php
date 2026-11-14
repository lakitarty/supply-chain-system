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
    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)
        || !in_array($actor['role'], ['ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Supplier purchasing access is required to create an outgoing LPO.');
    }

    $body = read_json_body();
    $buyerId = (int)$actor['organization_id'];
    $userId = (int)$actor['user_id'];
    $manufacturerId = filter_var($body['manufacturerId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $draftRaw = $body['draftId'] ?? null;
    $draftId = ($draftRaw === null || $draftRaw === '') ? null : filter_var($draftRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $items = $body['items'] ?? [];

    if ($manufacturerId === false || $manufacturerId === null) respond_error(400, 'Choose a manufacturer.');
    if ($draftId === false) respond_error(400, 'Invalid draft identifier.');
    if (!is_array($items) || count($items) > 100) respond_error(400, 'An LPO can contain at most 100 product lines.');

    $pdo->beginTransaction();
    $manufacturerStatement = $pdo->prepare(
        "SELECT o.organization_id
         FROM organizations o
         INNER JOIN organization_relationships r
           ON r.source_organization_id = o.organization_id
          AND r.target_organization_id = ?
          AND r.relationship_type = 'SUPPLIES'
          AND r.status = 'ACTIVE'
          AND r.start_date <= CURDATE()
          AND (r.end_date IS NULL OR r.end_date >= CURDATE())
         WHERE o.organization_id = ? AND o.status = 'ACTIVE'
           AND o.organization_type IN ('MANUFACTURER', 'MANUFACTURER_SUPPLIER')
         LIMIT 1 FOR UPDATE"
    );
    $manufacturerStatement->execute([$buyerId, (int)$manufacturerId]);
    if (!$manufacturerStatement->fetchColumn()) {
        $pdo->rollBack();
        respond_error(403, 'This manufacturer does not have an active supply relationship with your organisation.');
    }

    if ($draftId !== null) {
        $existingStatement = $pdo->prepare(
            "SELECT supply_order_id, status FROM supply_orders
             WHERE supply_order_id = ? AND retailer_id = ?
               AND order_direction = 'SUPPLIER_TO_MANUFACTURER'
             LIMIT 1 FOR UPDATE"
        );
        $existingStatement->execute([(int)$draftId, $buyerId]);
        $existing = $existingStatement->fetch(PDO::FETCH_ASSOC);
        if (!$existing || $existing['status'] !== 'DRAFT') {
            $pdo->rollBack();
            respond_error(409, 'This outgoing LPO is not an editable draft.');
        }
        $orderId = (int)$draftId;
        $update = $pdo->prepare(
            "UPDATE supply_orders SET supplier_id = ?, branch_id = NULL, expected_delivery_date = NULL
             WHERE supply_order_id = ? AND retailer_id = ? AND order_direction = 'SUPPLIER_TO_MANUFACTURER' AND status = 'DRAFT'"
        );
        $update->execute([(int)$manufacturerId, $orderId, $buyerId]);
        $delete = $pdo->prepare('DELETE FROM supply_order_items WHERE supply_order_id = ?');
        $delete->execute([$orderId]);
    } else {
        $lpoDocument = allocate_lpo_document($pdo, $buyerId);

        $insert = $pdo->prepare(
            "INSERT INTO supply_orders
              (retailer_id, lpo_number, lpo_valid_until, branch_id,
               supplier_id, order_date, expected_delivery_date, status,
               created_by, order_direction)
             VALUES (?, ?, ?, NULL, ?, CURDATE(), NULL, 'DRAFT', ?, 'SUPPLIER_TO_MANUFACTURER')"
        );
        $insert->execute([
            $buyerId,
            $lpoDocument['lpo_number'],
            $lpoDocument['lpo_valid_until'],
            (int)$manufacturerId,
            $userId
        ]);
        $orderId = (int)$pdo->lastInsertId();
    }

    $priceStatement = $pdo->prepare(
        "SELECT p.product_name, pv.product_version_id, selling.price_id AS source_product_price_id,
                selling.price_amount AS unit_price,
                CASE WHEN selling.price_includes_vat = 1 THEN 'VAT_INCLUSIVE' ELSE 'VAT_EXCLUSIVE' END AS price_basis,
                COALESCE((SELECT tc.tax_category FROM product_tax_classifications tc
                          WHERE tc.product_version_id = pv.product_version_id
                            AND tc.manufacturer_id = p.manufacturer_id
                            AND tc.effective_from <= CURDATE()
                            AND (tc.effective_to IS NULL OR tc.effective_to >= CURDATE())
                          ORDER BY tc.effective_from DESC, tc.product_tax_classification_id DESC LIMIT 1),
                         selling.tax_category) AS tax_category
         FROM products p
         INNER JOIN product_versions pv ON pv.product_id = p.product_id AND pv.is_current = 1
           AND pv.effective_from <= CURDATE() AND (pv.effective_to IS NULL OR pv.effective_to >= CURDATE())
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
           AND manufacturer.organization_id = ? AND manufacturer.status = 'ACTIVE'
         INNER JOIN product_prices selling ON selling.price_id = (
             SELECT pp.price_id FROM product_prices pp
             WHERE pp.organization_id = p.manufacturer_id AND pp.product_id = p.product_id
               AND pp.product_version_id = pv.product_version_id AND pp.price_type = 'SELLING'
               AND pp.effective_from <= CURDATE() AND (pp.effective_to IS NULL OR pp.effective_to >= CURDATE())
             ORDER BY pp.effective_from DESC, pp.price_id DESC LIMIT 1
         )
         WHERE p.product_id = ? AND p.status = 'ACTIVE' AND p.availability_status = 'AVAILABLE'
         LIMIT 1 FOR UPDATE"
    );

    $normalised = [];
    $seen = [];
    foreach ($items as $item) {
        if (!is_array($item)) { $pdo->rollBack(); respond_error(400, 'Each product line must contain a product and quantity.'); }
        $productId = filter_var($item['productId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999999999]]);
        if ($productId === false || $productId === null || $quantity === false || $quantity === null) {
            $pdo->rollBack(); respond_error(400, 'Each saved line requires a product and a positive whole-number quantity.');
        }
        if (isset($seen[(int)$productId])) { $pdo->rollBack(); respond_error(400, 'A product can appear only once in an LPO. Combine its quantities.'); }
        $seen[(int)$productId] = true;
        $priceStatement->execute([(int)$manufacturerId, (int)$productId]);
        $price = $priceStatement->fetch(PDO::FETCH_ASSOC);
        if (!$price || !$price['source_product_price_id'] || !$price['tax_category']) {
            $pdo->rollBack(); respond_error(409, 'A selected product has no current manufacturer selling price or tax classification.');
        }
        $normalised[] = [
            'product_id' => (int)$productId,
            'product_version_id' => (int)$price['product_version_id'],
            'source_price_id' => (int)$price['source_product_price_id'],
            'quantity' => (int)$quantity,
            'unit_price' => (string)$price['unit_price'],
            'price_basis' => (string)$price['price_basis'],
            'tax_category' => (string)$price['tax_category']
        ];
    }

    $insertItem = $pdo->prepare(
        "INSERT INTO supply_order_items
          (supply_order_id, product_id, product_version_id, quantity_ordered, quantity_received,
           unit_price, price_basis, supplier_product_offer_id, tax_category, source_product_price_id)
         VALUES (?, ?, ?, ?, 0, ?, ?, NULL, ?, ?)"
    );
    foreach ($normalised as $line) {
        $insertItem->execute([$orderId, $line['product_id'], $line['product_version_id'], $line['quantity'],
            $line['unit_price'], $line['price_basis'], $line['tax_category'], $line['source_price_id']]);
    }

    record_audit($pdo, $actor, 'SUPPLIER_LPO_DRAFT_SAVED', 'SUPPLY_ORDER', $orderId,
        'Saved outgoing LPO #' . $orderId . ' to manufacturer organisation #' . (int)$manufacturerId .
        ' with ' . count($normalised) . ' product lines.');
    $pdo->commit();
    respond(200, ['success' => true, 'message' => 'Outgoing LPO draft saved.', 'supply_order_id' => $orderId,
        'status' => 'DRAFT', 'item_count' => count($normalised)]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ((string)$e->getCode() === '23000') respond_error(409, 'The draft conflicts with an existing record. Refresh and try again.');
    log_error('supplier outgoing LPO draft save', $e);
    respond_error(500, 'Unable to save the outgoing LPO draft.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('supplier outgoing LPO draft save unexpected', $e);
    respond_error(500, 'Unable to save the outgoing LPO draft.');
}