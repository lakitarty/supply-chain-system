<?php

declare(strict_types=1);

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function respond_field_error(int $status, array $error): void
{
    respond($status, [
        'success' => false,
        'error' => $error['message'],
        'field' => $error['field']
    ]);
}

function reject_cross_site_request(): void
{
    $fetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? null;

    if ($fetchSite !== null && !in_array($fetchSite, ['same-origin', 'none'], true)) {
        respond_error(403, 'Cross-site requests are not allowed.');
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

    if ($origin === null) {
        return;
    }

    $host = parse_url($origin, PHP_URL_HOST);
    $port = parse_url($origin, PHP_URL_PORT);
    $authority = is_string($host) ? strtolower($host . ($port !== null ? ':' . $port : '')) : '';

    if ($authority === '' || $authority !== strtolower((string)($_SERVER['HTTP_HOST'] ?? ''))) {
        respond_error(403, 'Cross-site requests are not allowed.');
    }
}

function require_csrf_token(): void
{
    start_secure_session();

    if (($_SESSION['authenticated'] ?? false) !== true) {
        return;
    }

    $expected = (string)($_SESSION['csrf_token'] ?? '');
    $sent = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if ($expected === '' || !hash_equals($expected, $sent)) {
        respond_error(403, 'Your session security token is missing or has expired. Reload the page and try again.');
    }
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

    reject_cross_site_request();
    require_csrf_token();
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

function log_error(string $where, Throwable $e): void
{
    error_log("[supply-chain] $where: " . $e->getMessage());
}
function respond_error(int $status, string $message): void
{
    respond($status, [
        'success' => false,
        'error' => $message
    ]);
}