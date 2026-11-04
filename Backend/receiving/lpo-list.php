<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/receiving-rules.php';

try {
    $actor = require_login($pdo);
    if (!in_array($actor['role'] ?? '', ['ADMIN', 'BRANCH_ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Your account does not have access to receiving.');
    }

    $requestedBranchId = null;

    if (isset($_GET['branchId']) && $_GET['branchId'] !== '') {
        $validatedBranchId = filter_var(
            $_GET['branchId'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($validatedBranchId === false) {
            respond_error(400, 'Invalid branch selection.');
        }

        $requestedBranchId = (int)$validatedBranchId;
    }

    $branch = receiving_resolve_branch($pdo, $actor, $requestedBranchId);

    $statement = $pdo->prepare(
        "SELECT
            so.supply_order_id,
            so.lpo_number,
            so.order_date,
            so.expected_delivery_date,
            so.status,
            so.branch_id,
            b.branch_name,
            seller.organization_id AS supplier_id,
            seller.organization_name AS supplier_name,
            i.supply_order_item_id,
            i.product_id,
            i.product_version_id,
            p.product_name,
            pv.barcode,
            pv.pack_size,
            pv.packaging_description,
            i.quantity_ordered,
            i.quantity_received,
            (i.quantity_ordered - i.quantity_received) AS outstanding_quantity,
            i.unit_price,
            i.price_basis
         FROM supply_orders so
         INNER JOIN organizations seller
            ON seller.organization_id = so.supplier_id
         INNER JOIN branches b
            ON b.branch_id = so.branch_id
           AND b.organization_id = so.retailer_id
           AND b.status = 'ACTIVE'
         INNER JOIN supply_order_items i
            ON i.supply_order_id = so.supply_order_id
         INNER JOIN products p
            ON p.product_id = i.product_id
         LEFT JOIN product_versions pv
            ON pv.product_version_id = i.product_version_id
         WHERE so.retailer_id = ?
           AND so.branch_id = ?
           AND so.order_direction = 'RETAILER_TO_SUPPLIER'
           AND so.status IN ('CONFIRMED', 'PARTIALLY_DELIVERED')
           AND i.quantity_received < i.quantity_ordered
         ORDER BY so.order_date DESC,
                  so.supply_order_id DESC,
                  p.product_name"
    );

    $statement->execute([
        (int)$actor['organization_id'],
        (int)$branch['branch_id']
    ]);

    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];

    foreach ($rows as $row) {
        $orderId = (int)$row['supply_order_id'];

        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'supply_order_id' => $orderId,
                'lpo_number' => $row['lpo_number'],
                'order_date' => $row['order_date'],
                'expected_delivery_date' => $row['expected_delivery_date'],
                'status' => $row['status'],
                'branch_id' => (int)$row['branch_id'],
                'branch_name' => $row['branch_name'],
                'supplier_id' => (int)$row['supplier_id'],
                'supplier_name' => $row['supplier_name'],
                'items' => []
            ];
        }

        $orders[$orderId]['items'][] = [
            'supply_order_item_id' => (int)$row['supply_order_item_id'],
            'product_id' => (int)$row['product_id'],
            'product_version_id' => $row['product_version_id'] !== null
                ? (int)$row['product_version_id']
                : null,
            'product_name' => $row['product_name'],
            'barcode' => $row['barcode'],
            'pack_size' => $row['pack_size'],
            'packaging_description' => $row['packaging_description'],
            'quantity_ordered' => (int)$row['quantity_ordered'],
            'quantity_received' => (int)$row['quantity_received'],
            'outstanding_quantity' => (int)$row['outstanding_quantity'],
            'unit_price' => $row['unit_price'],
            'price_basis' => $row['price_basis']
        ];
    }

    respond(200, [
        'success' => true,
        'branch' => $branch,
        'orders' => array_values($orders)
    ]);

} catch (PDOException $e) {
    log_error('receiving LPO list', $e);
    respond_error(500, 'Unable to load LPOs for receiving.');
} catch (Throwable $e) {
    log_error('receiving LPO list unexpected', $e);
    respond_error(500, 'Unable to load LPOs for receiving.');
}
