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
            grn.grn_id,
            grn.grn_number,
            grn.branch_id,
            grn.inventory_location_id,
            location.location_name,
            grn.supplier_id,
            supplier.organization_name AS supplier_name,
            grn.supply_order_id,
            grn.lpo_number_snapshot,
            grn.supplier_invoice_number,
            grn.supplier_invoice_date,
            grn.received_date,
            grn.invoice_total,
            grn.calculated_total,
            grn.reconciliation_difference,
            grn.reconciliation_status,
            grn.status,
            grn.created_at,
            grn.posted_at,
            (
                SELECT COUNT(*)
                FROM goods_received_note_items item
                WHERE item.grn_id = grn.grn_id
            ) AS item_count,
            (
                SELECT COALESCE(SUM(item.quantity_delivered_each), 0)
                FROM goods_received_note_items item
                WHERE item.grn_id = grn.grn_id
            ) AS delivered_quantity,
            (
                SELECT COALESCE(SUM(item.quantity_accepted_each), 0)
                FROM goods_received_note_items item
                WHERE item.grn_id = grn.grn_id
            ) AS accepted_quantity,
            (
                SELECT COALESCE(SUM(item.quantity_rejected_each), 0)
                FROM goods_received_note_items item
                WHERE item.grn_id = grn.grn_id
            ) AS rejected_quantity
         FROM goods_received_notes grn
         INNER JOIN organizations supplier
            ON supplier.organization_id = grn.supplier_id
         INNER JOIN branches branch
            ON branch.branch_id = grn.branch_id
           AND branch.organization_id = grn.retailer_id
         LEFT JOIN inventory_locations location
            ON location.inventory_location_id = grn.inventory_location_id
           AND location.organization_id = grn.retailer_id
           AND location.branch_id = grn.branch_id
         WHERE grn.retailer_id = ?
           AND grn.branch_id = ?
         ORDER BY grn.created_at DESC, grn.grn_id DESC"
    );

    $statement->execute([
        (int)$actor['organization_id'],
        (int)$branch['branch_id']
    ]);

    $grns = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($grns as &$grn) {
        $grn['grn_id'] = (int)$grn['grn_id'];
        $grn['branch_id'] = (int)$grn['branch_id'];
        $grn['inventory_location_id'] = $grn['inventory_location_id'] !== null
            ? (int)$grn['inventory_location_id']
            : null;
        $grn['supplier_id'] = (int)$grn['supplier_id'];
        $grn['supply_order_id'] = (int)$grn['supply_order_id'];
        $grn['item_count'] = (int)$grn['item_count'];
        $grn['delivered_quantity'] = (int)$grn['delivered_quantity'];
        $grn['accepted_quantity'] = (int)$grn['accepted_quantity'];
        $grn['rejected_quantity'] = (int)$grn['rejected_quantity'];
    }
    unset($grn);

    respond(200, [
        'success' => true,
        'branch' => $branch,
        'grns' => $grns
    ]);

} catch (PDOException $e) {
    log_error('receiving GRN list', $e);
    respond_error(500, 'Unable to load Goods Received Notes.');
} catch (Throwable $e) {
    log_error('receiving GRN list unexpected', $e);
    respond_error(500, 'Unable to load Goods Received Notes.');
}