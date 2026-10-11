<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/organization-rules.php';

try {
    $actor = require_admin($pdo);

    respond(200, [
        'success' => true,
        'organization' => find_organization($pdo, $actor)
    ]);

} catch (PDOException $e) {
    log_error('profile', $e);

    respond_error(500, 'Unable to load the profile.');
}