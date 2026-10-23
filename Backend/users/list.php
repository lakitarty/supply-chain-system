<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_login($pdo);

    if (!in_array($actor['role'], ['ADMIN', 'BRANCH_ADMIN'], true)) {
        respond_error(403, 'User management access is required.');
    }

    if ($actor['role'] === 'BRANCH_ADMIN') {
        $actor = require_branch_admin($pdo);

        $statement = $pdo->prepare(
            "SELECT u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.role,
                    u.status,
                    u.created_at,
                    u.branch_id,
                    b.branch_name
             FROM users u
             LEFT JOIN branches b
                ON b.branch_id = u.branch_id
             WHERE u.organization_id = ?
               AND u.branch_id = ?
               AND u.role = 'STAFF'
             ORDER BY u.first_name, u.last_name"
        );

        $statement->execute([
            $actor['organization_id'],
            $actor['branch_id']
        ]);
    } else {
        $statement = $pdo->prepare(
            'SELECT u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.role,
                    u.status,
                    u.created_at,
                    u.branch_id,
                    b.branch_name
             FROM users u
             LEFT JOIN branches b
                ON b.branch_id = u.branch_id
             WHERE u.organization_id = ?
             ORDER BY u.first_name, u.last_name'
        );

        $statement->execute([$actor['organization_id']]);
    }

    respond(200, [
        'success' => true,
        'users' => $statement->fetchAll()
    ]);

} catch (PDOException $e) {
    log_error('users list', $e);
    respond_error(500, 'Unable to load users.');
}