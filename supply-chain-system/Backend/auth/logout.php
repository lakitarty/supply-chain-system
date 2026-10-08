<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_post();
start_secure_session();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?: 'Lax'
    ]);
}

session_destroy();

respond(200, [
    'success' => true,
    'message' => 'Signed out.'
]);
