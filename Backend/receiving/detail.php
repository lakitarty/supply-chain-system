<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/receiving-rules.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        respond_error(405, 'Method not allowed.');
    }

    $actor = require_login($pdo);

    if (!in_array($actor['role'] ?? '', ['ADMIN', 'BRANCH_ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Your account does not have access to receiving.');
    }

    $grnId = filter_var(
        $_GET['grnId'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($grnId === false) {
        respond_error(400, 'A valid GRN ID is required.');
    }

    $headerStatement = $pdo->prepare(
        "SELECT
            grn.grn_id,
            grn.grn_number,
            grn.retailer_id,
            grn.branch_id,
            branch.branch_name,
            grn.inventory_location_id,
            location.location_name,
            grn.supplier_id,
            grn.supply_order_id,
            grn.lpo_number_snapshot,
            grn.supplier_invoice_number,
            grn.supplier_invoice_date,
            grn.received_date,
            grn.delivery_note_number,
            grn.dispatch_advice_number,
            grn.vehicle_registration,
            grn.driver_name,
            grn.invoice_goods_value,
            grn.invoice_vat_amount,
            grn.invoice_total,
            grn.calculated_goods_value,
            grn.calculated_vat_amount,
            grn.calculated_total,
            grn.reconciliation_difference,
            grn.reconciliation_status,
            grn.variance_reason,
            grn.remarks,
            grn.status,
            grn.created_by,
            grn.posted_by,
            grn.created_at,
            grn.posted_at
         FROM goods_received_notes grn
         INNER JOIN branches branch
            ON branch.branch_id = grn.branch_id
           AND branch.organization_id = grn.retailer_id
         LEFT JOIN inventory_locations location
            ON location.inventory_location_id = grn.inventory_location_id
           AND location.organization_id = grn.retailer_id
           AND location.branch_id = grn.branch_id
         WHERE grn.grn_id = ?
           AND grn.retailer_id = ?
         LIMIT 1"
    );

    $headerStatement->execute([
        (int)$grnId,
        (int)$actor['organization_id']
    ]);

    $grn = $headerStatement->fetch(PDO::FETCH_ASSOC);

    if (!$grn) {
        respond_error(404, 'The requested GRN was not found.');
    }

    receiving_resolve_branch(
        $pdo,
        $actor,
        (int)$grn['branch_id']
    );

    $itemsStatement = $pdo->prepare(
        "SELECT
            item.grn_item_id,
            p.manufacturer_id,
            item.supply_order_item_id,
            item.product_id,
            item.product_version_id,
            item.product_name_snapshot,
            item.barcode_snapshot,
            item.pack_size_snapshot,
            item.lpo_quantity_each,
            item.lpo_unit_price_snapshot,
            item.previous_cost_snapshot,
            item.invoice_quantity,
            item.invoice_unit,
            item.units_per_invoice_unit,
            item.invoice_unit_price,
            item.price_basis,
            item.tax_category,
            item.tax_rate_percent,
            item.taxable_supply,
            item.tax_calculation_status,
            item.invoice_goods_value,
            item.invoice_vat_amount,
            item.invoice_line_total,
            item.quantity_delivered_each,
            item.quantity_accepted_each,
            item.quantity_rejected_each,
            item.unit_cost_each,
            item.discrepancy_reason,
            COALESCE(
                (
                    SELECT SUM(previous_item.quantity_accepted_each)
                    FROM goods_received_note_items previous_item
                    INNER JOIN goods_received_notes previous_grn
                        ON previous_grn.grn_id = previous_item.grn_id
                    WHERE previous_item.supply_order_item_id = item.supply_order_item_id
                      AND previous_grn.status = 'POSTED'
                      AND previous_grn.grn_id <> item.grn_id
                ),
                0
            ) AS previously_received_each
         FROM goods_received_note_items item
         INNER JOIN products p
            ON p.product_id = item.product_id
         WHERE item.grn_id = ?
         ORDER BY item.grn_item_id"
    );

    $itemsStatement->execute([(int)$grnId]);
    $items = $itemsStatement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $item['grn_item_id'] = (int)$item['grn_item_id'];
        $item['supply_order_item_id'] = $item['supply_order_item_id'] !== null ? (int)$item['supply_order_item_id'] : null;
        $item['product_id'] = (int)$item['product_id'];
        $item['product_version_id'] = $item['product_version_id'] !== null
            ? (int)$item['product_version_id']
            : null;

        $item['lpo_quantity_each'] = (int)$item['lpo_quantity_each'];
        $item['invoice_quantity'] = (float)$item['invoice_quantity'];
        $item['units_per_invoice_unit'] = (int)$item['units_per_invoice_unit'];
        $item['quantity_delivered_each'] = (int)$item['quantity_delivered_each'];
        $item['quantity_accepted_each'] = (int)$item['quantity_accepted_each'];
        $item['quantity_rejected_each'] = (int)$item['quantity_rejected_each'];
        $item['previously_received_each'] = (int)$item['previously_received_each'];

        $item['remaining_before_this_grn_each'] = max(
            0,
            $item['lpo_quantity_each'] - $item['previously_received_each']
        );
        if ($grn['status'] === 'DRAFT') {
            $tax = receiving_line_tax(
                $pdo,
                $item['product_version_id'],
                (int)$item['manufacturer_id'],
                (string)($grn['supplier_invoice_date'] ?? $grn['received_date'])
            );

            $item['tax_category'] = $tax['tax_category'];
            $item['tax_rate_percent'] = $tax['tax_rate_percent'];
            $item['taxable_supply'] = $tax['taxable_supply'];
            $item['tax_calculation_status'] = $tax['tax_calculation_status'];
        }

        unset($item['manufacturer_id']);
    }
    unset($item);

    $grn['grn_id'] = (int)$grn['grn_id'];
    $grn['retailer_id'] = (int)$grn['retailer_id'];
    $grn['branch_id'] = (int)$grn['branch_id'];
    $grn['inventory_location_id'] = $grn['inventory_location_id'] !== null
        ? (int)$grn['inventory_location_id']
        : null;
    $grn['supplier_id'] = (int)$grn['supplier_id'];
    $grn['supply_order_id'] = (int)$grn['supply_order_id'];

    respond(200, [
        'success' => true,
        'grn' => $grn,
        'items' => $items
    ]);

} catch (PDOException $e) {
    log_error('GRN detail', $e);
    respond_error(500, 'Unable to load the GRN details.');
} catch (Throwable $e) {
    log_error('GRN detail unexpected', $e);
    respond_error(500, 'Unable to load the GRN details.');
}
