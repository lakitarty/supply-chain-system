<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/dashboard-rules.php';

try {
    $actor = require_login($pdo);

    respond(200, ['success' => true] + fetch_dashboard($pdo, $actor));

} catch (PDOException $e) {
    log_error('dashboard', $e);

    respond_error(500, 'Unable to load the dashboard.');
}