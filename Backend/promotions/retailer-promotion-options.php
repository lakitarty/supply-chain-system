<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/retailer-promotion-rules.php';

try {
    $actor = retailer_promotion_actor($pdo, true);
    $effectiveDate = trim((string)($_GET['start_date'] ?? retailer_promotion_today()));
    if (!valid_date($effectiveDate)) {
        respond_error(400, 'Choose a valid promotion start date.');
    }

    $branches = retailer_promotion_allowed_branches($pdo, $actor);
    $branchIds = array_map(static fn(array $row): int => (int)$row['branch_id'], $branches);
    $products = [];

    if ($branchIds !== []) {
        $marks = implode(',', array_fill(0, count($branchIds), '?'));
        $statement = $pdo->prepare(
            "SELECT rbp.branch_id, b.branch_name, rbp.product_id, rbp.product_version_id,
                    p.product_name, pv.pack_size, pv.barcode,
                    rbp.selling_price, rbp.price_basis
             FROM retailer_branch_prices rbp
             INNER JOIN branches b ON b.branch_id = rbp.branch_id
                 AND b.organization_id = rbp.retailer_id AND b.status = 'ACTIVE'
             INNER JOIN products p ON p.product_id = rbp.product_id AND p.status = 'ACTIVE'
             INNER JOIN product_versions pv ON pv.product_id = rbp.product_id
                 AND pv.product_version_id = rbp.product_version_id
             WHERE rbp.retailer_id = ?
               AND rbp.branch_id IN ($marks)
               AND rbp.status = 'ACTIVE'
               AND rbp.effective_from <= ?
               AND (rbp.effective_to IS NULL OR rbp.effective_to >= ?)
               AND pv.effective_from <= ?
               AND (pv.effective_to IS NULL OR pv.effective_to >= ?)
             ORDER BY b.branch_name, p.product_name, pv.pack_size"
        );
        $statement->execute(array_merge(
            [(int)$actor['organization_id']], $branchIds,
            [$effectiveDate, $effectiveDate, $effectiveDate, $effectiveDate]
        ));
        $products = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    respond(200, [
        'success' => true,
        'effective_date' => $effectiveDate,
        'branches' => $branches,
        'branch_products' => $products
    ]);
} catch (Throwable $e) {
    log_error('retailer promotion options', $e);
    respond_error(500, 'Unable to load branch promotion options.');
}
