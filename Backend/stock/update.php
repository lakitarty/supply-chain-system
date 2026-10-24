<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/stock-rules.php';

require_post();

try {
    $actor = require_login($pdo);
    stock_require_retailer($actor);
    $input = read_json_body();

    $productId = stock_optional_id($input['productId'] ?? null);
    $versionId = stock_optional_id($input['productVersionId'] ?? null);
    $branchId = stock_optional_id($input['branchId'] ?? null);
    $onHand = stock_whole_number($input['onHand'] ?? null);
    $reorderLevel = stock_whole_number($input['reorderLevel'] ?? null);

    if ($productId === null) {
        respond_field_error(400, field_error('productId', 'Select a product.'));
    }
    if ($versionId === null) {
        respond_field_error(400, field_error('productVersionId', 'Select the package or barcode version being counted.'));
    }
    if ($branchId === null) {
        respond_field_error(400, field_error('branchId', 'Select a branch for this stock count.'));
    }
    if ($onHand === null) {
        respond_field_error(400, field_error('onHand', 'Enter the stock count as a whole number.'));
    }
    if ($reorderLevel === null) {
        respond_field_error(400, field_error('reorderLevel', 'Enter the reorder level as a whole number.'));
    }

    $branch = stock_resolve_branch($pdo, $actor, $branchId);
    $product = stock_validate_product_version($pdo, $actor, $productId, $versionId);

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'INSERT INTO retailer_stock
            (retailer_id, branch_id, product_id, product_version_id, quantity_on_hand, reorder_level, last_updated)
         VALUES (?, ?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            quantity_on_hand = VALUES(quantity_on_hand),
            reorder_level = VALUES(reorder_level),
            last_updated = NOW()'
    );
    $statement->execute([
        $actor['organization_id'],
        $branch['branch_id'],
        $productId,
        $versionId,
        $onHand,
        $reorderLevel
    ]);

    $updated = stock_fetch_rows($pdo, $actor, $branch, $productId, $versionId)[0] ?? null;
    if ($updated === null || !$updated['stock_recorded']) {
        throw new RuntimeException('Saved stock could not be reloaded.');
    }

    stock_sync_alert($pdo, $actor, $branch, $updated);
    record_audit(
        $pdo,
        $actor,
        'STOCK_UPDATED',
        'PRODUCT',
        $productId,
        "Set {$product['product_name']} version {$product['version_number']} stock at {$branch['branch_name']} to {$onHand} units; reorder level {$reorderLevel}"
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Stock updated.',
        'branch' => $branch,
        'stock' => $updated
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('stock update', $e);
    respond_error(500, 'Unable to update stock.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e instanceof RuntimeException && $e->getMessage() === 'Saved stock could not be reloaded.') {
        log_error('stock update consistency', $e);
        respond_error(500, 'Stock was saved but could not be reloaded. Check the audit log before retrying.');
    }
    log_error('stock update unexpected', $e);
    respond_error(500, 'Unable to update stock.');
}
