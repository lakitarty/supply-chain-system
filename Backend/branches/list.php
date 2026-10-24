<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_login($pdo);

    if ($actor['role'] === 'ADMIN') {
        $statement = $pdo->prepare(
            'SELECT b.branch_id, b.branch_name, b.location, b.area_id,
                    da.area_name, b.is_main_branch, b.status
             FROM branches b
             LEFT JOIN distribution_areas da ON da.area_id = b.area_id
             WHERE b.organization_id = ?
             ORDER BY b.is_main_branch DESC, b.branch_name'
        );

        $statement->execute([$actor['organization_id']]);
    } elseif ($actor['role'] === 'BRANCH_ADMIN') {
        $actor = require_branch_admin($pdo);

        $statement = $pdo->prepare(
            "SELECT b.branch_id, b.branch_name, b.location, b.area_id,
                    da.area_name, b.is_main_branch, b.status
             FROM branches b
             LEFT JOIN distribution_areas da ON da.area_id = b.area_id
             WHERE b.organization_id = ?
               AND b.branch_id = ?
               AND b.status = 'ACTIVE'
             LIMIT 1"
        );

        $statement->execute([
            $actor['organization_id'],
            $actor['branch_id']
        ]);
    } else {
        respond_error(403, 'Branch list access is not permitted.');
    }

    respond(200, [
        'success' => true,
        'branches' => $statement->fetchAll()
    ]);

} catch (PDOException $e) {
    log_error('branches list', $e);
    respond_error(500, 'Unable to load branches.');
}