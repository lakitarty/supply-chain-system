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

function log_error(string $where, Throwable $e): void
{
    error_log("[supply-chain] $where: " . $e->getMessage());
}