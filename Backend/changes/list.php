<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/change-rules.php';

try {
    $actor = require_login($pdo);

    respond(200, [
        'success' => true,
        'can_create' => can_manufacture($actor),
        'can_publish' => can_manufacture($actor) && $actor['role'] === 'ADMIN',
        'changes' => fetch_changes($pdo, $actor)
    ]);

} catch (PDOException $e) {
    log_error('changes list', $e);

    respond_error(500, 'Unable to load the changes.');
}