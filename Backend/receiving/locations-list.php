<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/receiving-rules.php';

try {
    $actor = require_login($pdo);

    if (!in_array($actor['role'] ?? '', ['ADMIN', 'BRANCH_ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Your account does not have access to inventory locations.');
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
            inventory_location_id,
            location_code,
            location_name,
            location_type,
            is_default
         FROM inventory_locations
         WHERE organization_id = ?
           AND branch_id = ?
           AND status = 'ACTIVE'
         ORDER BY is_default DESC, location_name ASC"
    );

    $statement->execute([
        (int)$actor['organization_id'],
        (int)$branch['branch_id']
    ]);

    $locations = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($locations as &$location) {
        $location['inventory_location_id'] = (int)$location['inventory_location_id'];
        $location['is_default'] = (bool)$location['is_default'];
    }
    unset($location);

    respond(200, [
        'success' => true,
        'branch' => $branch,
        'locations' => $locations
    ]);

} catch (PDOException $e) {
    log_error('inventory locations list', $e);
    respond_error(500, 'Unable to load inventory locations.');
} catch (Throwable $e) {
    log_error('inventory locations list unexpected', $e);
    respond_error(500, 'Unable to load inventory locations.');
}