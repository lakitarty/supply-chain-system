<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_admin($pdo);

    $statement = $pdo->prepare(
        'SELECT branch_id, branch_name, location, is_main_branch, status
         FROM branches
         WHERE organization_id = ?
         ORDER BY is_main_branch DESC, branch_name'
    );

    $statement->execute([$actor['organization_id']]);

    respond(200, [
        'success' => true,
        'branches' => $statement->fetchAll()
    ]);

} catch (PDOException $e) {
    log_error('branches list', $e);

    respond_error(500, 'Unable to load branches.');
}