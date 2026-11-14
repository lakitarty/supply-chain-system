<?php

declare(strict_types=1);

const SESSION_NAME = 'SCM_SESSION';

function start_secure_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    session_name(SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $isHttps
    ]);

    session_start();
}

function sign_in(array $account): void
{
    start_secure_session();
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = $account['user']['user_id'];
    $_SESSION['organization_id'] = $account['organization']['organization_id'];
    $_SESSION['branch_id'] = $account['user']['branch_id'];
    $_SESSION['role'] = $account['user']['role'];
    $_SESSION['organization_type'] = $account['organization']['organization_type'];
}

function sign_out(): void
{
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
}

function csrf_token(): string
{
    start_secure_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION['csrf_token'];
}
