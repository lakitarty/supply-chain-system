<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/receiving-rules.php';

try {
    require_post();

    $actor = require_login($pdo);

    if (!in_array($actor['role'] ?? '', ['ADMIN', 'BRANCH_ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Your account does not have access to receiving.');
    }

    $body = read_json_body();

    $supplyOrderId = filter_var(
        $body['supply_order_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $locationId = filter_var(
        $body['inventory_location_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($supplyOrderId === false || $locationId === false) {
        respond_error(400, 'A valid LPO and receiving location are required.');
    }

    $pdo->beginTransaction();

    $orderStatement = $pdo->prepare(
        "SELECT
            supply_order_id,
            lpo_number,
            retailer_id,
            branch_id,
            supplier_id,
            order_direction,
            status
         FROM supply_orders
         WHERE supply_order_id = ?
           AND retailer_id = ?
           AND order_direction = 'RETAILER_TO_SUPPLIER'
         LIMIT 1
         FOR UPDATE"
    );

    $orderStatement->execute([
        (int)$supplyOrderId,
        (int)$actor['organization_id']
    ]);

    $order = $orderStatement->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $pdo->rollBack();
        respond_error(404, 'The selected LPO was not found.');
    }

    if (
        !in_array(
            $order['status'],
            ['CONFIRMED', 'PARTIALLY_DELIVERED'],
            true
        )
    ) {
        $pdo->rollBack();
        respond_error(422, 'This LPO is not available for receiving.');
    }

    if (empty($order['branch_id'])) {
        $pdo->rollBack();
        respond_error(422, 'The LPO does not have a receiving branch.');
    }

    $branch = receiving_resolve_branch(
        $pdo,
        $actor,
        (int)$order['branch_id']
    );

    $locationStatement = $pdo->prepare(
        "SELECT
            inventory_location_id,
            location_name
         FROM inventory_locations
         WHERE inventory_location_id = ?
           AND organization_id = ?
           AND branch_id = ?
           AND status = 'ACTIVE'
         LIMIT 1"
    );

    $locationStatement->execute([
        (int)$locationId,
        (int)$actor['organization_id'],
        (int)$branch['branch_id']
    ]);

    $location = $locationStatement->fetch(PDO::FETCH_ASSOC);

    if (!$location) {
        $pdo->rollBack();
        respond_error(
            422,
            'The selected inventory location does not belong to this branch.'
        );
    }

    $itemsStatement = $pdo->prepare(
        "SELECT
            i.supply_order_item_id,
            i.product_id,
            i.product_version_id,
            i.quantity_ordered,
            i.quantity_received,
            i.unit_price,
            i.price_basis,
            p.product_name,
            pv.barcode,
            pv.pack_size,
            pv.packaging_description
         FROM supply_order_items i
         INNER JOIN products p
            ON p.product_id = i.product_id
         LEFT JOIN product_versions pv
            ON pv.product_version_id = i.product_version_id
         WHERE i.supply_order_id = ?
           AND i.quantity_received < i.quantity_ordered
         ORDER BY i.supply_order_item_id
         FOR UPDATE"
    );

    $itemsStatement->execute([(int)$supplyOrderId]);
    $items = $itemsStatement->fetchAll(PDO::FETCH_ASSOC);

    if (!$items) {
        $pdo->rollBack();
        respond_error(409, 'This LPO has no outstanding quantities.');
    }

    $grnNumber = 'GRN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

    $headerStatement = $pdo->prepare(
        "INSERT INTO goods_received_notes (
            retailer_id,
            branch_id,
            inventory_location_id,
            supplier_id,
            supply_order_id,
            grn_number,
            lpo_number_snapshot,
            received_date,
            reconciliation_status,
            status,
            created_by
         ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_DATE, 'PENDING', 'DRAFT', ?)"
    );

    $headerStatement->execute([
        (int)$order['retailer_id'],
        (int)$branch['branch_id'],
        (int)$locationId,
        (int)$order['supplier_id'],
        (int)$order['supply_order_id'],
        $grnNumber,
        $order['lpo_number'],
        (int)$actor['user_id']
    ]);

    $grnId = (int)$pdo->lastInsertId();

    $itemStatement = $pdo->prepare(
        "INSERT INTO goods_received_note_items (
            grn_id,
            supply_order_item_id,
            product_id,
            product_version_id,
            product_name_snapshot,
            barcode_snapshot,
            pack_size_snapshot,
            lpo_quantity_each,
            lpo_unit_price_snapshot,
            previous_cost_snapshot,
            invoice_quantity,
            invoice_unit,
            units_per_invoice_unit,
            invoice_unit_price,
            price_basis,
            tax_category,
            tax_rate_percent,
            taxable_supply,
            tax_calculation_status,
            quantity_delivered_each,
            quantity_accepted_each,
            quantity_rejected_each
         ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL,
            0, 'EACH', 1, 0, ?, NULL, NULL, NULL, 'PENDING',
            0, 0, 0
         )"
    );

    foreach ($items as $item) {
        $itemStatement->execute([
            $grnId,
            (int)$item['supply_order_item_id'],
            (int)$item['product_id'],
            $item['product_version_id'] !== null
                ? (int)$item['product_version_id']
                : null,
            $item['product_name'],
            $item['barcode'],
            $item['pack_size'],
            (int)$item['quantity_ordered'],
            $item['unit_price'],
            $item['price_basis']
        ]);
    }

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'GRN draft created successfully.',
        'grn' => [
            'grn_id' => $grnId,
            'grn_number' => $grnNumber,
            'supply_order_id' => (int)$order['supply_order_id'],
            'lpo_number' => $order['lpo_number'],
            'branch_id' => (int)$branch['branch_id'],
            'branch_name' => $branch['branch_name'],
            'inventory_location_id' => (int)$locationId,
            'location_name' => $location['location_name'],
            'status' => 'DRAFT',
            'item_count' => count($items)
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('GRN draft creation', $e);
    respond_error(500, 'Unable to create the GRN draft.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('GRN draft creation unexpected', $e);
    respond_error(500, 'Unable to create the GRN draft.');
}