<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
require_once __DIR__ . '/db.php';
function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        respond(405, [
            'success' => false,
            'error' => 'Only POST requests are allowed.'
        ]);
    }
}

function read_json_body(): array
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        respond(400, [
            'success' => false,
            'error' => 'Invalid request data.'
        ]);
    }

    return $data;
}

function start_secure_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_name('SCM_SESSION');

    session_name('SCM_SESSION');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $isHttps
    ]);

    session_start();
}
function log_error(string $where, Throwable $e): void
{
    error_log("[supply-chain] $where: " . $e->getMessage());
}
