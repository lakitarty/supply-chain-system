<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_admin($pdo);

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

    respond(200, [
        'success' => true,
        'users' => $statement->fetchAll()
    ]);

} catch (PDOException $e) {
    log_error('users list', $e);

    respond_error(500, 'Unable to load users.');
}