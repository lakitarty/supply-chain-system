<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

start_secure_session();

if (($_SESSION['authenticated'] ?? false) !== true) {
    respond_error(401, 'Authentication required.');
}

respond(200, [
    'success' => true,
    'csrf_token' => csrf_token()
]);
