<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/stock-rules.php';

try {
    $actor = require_login($pdo);
    stock_require_retailer($actor);

    $requestedBranchId = null;
    if (isset($_GET['branchId']) && $_GET['branchId'] !== '') {
        $requestedBranchId = stock_optional_id($_GET['branchId']);
        if ($requestedBranchId === null) {
            respond_field_error(400, field_error('branchId', 'Select a valid branch.'));
        }
    }

    $branch = stock_resolve_branch($pdo, $actor, $requestedBranchId);
    $stock = stock_fetch_rows($pdo, $actor, $branch);

    respond(200, [
        'success' => true,
        'branch' => $branch,
        'branches' => stock_branch_options($pdo, $actor),
        'stock' => $stock,
        'data_issues' => stock_fetch_integrity_summary($pdo, $actor)
    ]);
} catch (PDOException $e) {
    log_error('stock list', $e);
    respond_error(500, 'Unable to load stock information.');
}
