<?php

declare(strict_types=1);

require_once __DIR__ . '/account.php';

function current_account_row(PDO $pdo): ?array
{
    start_secure_session();

    $userId = $_SESSION['user_id'] ?? null;

    if (($_SESSION['authenticated'] ?? false) !== true || !$userId) {
        return null;
    }

    $row = fetch_account($pdo, 'user_id', (int)$userId);

    if (!$row || inactive_reason($row) !== null) {
        sign_out();
        return null;
    }

    return $row;
}

function require_login(PDO $pdo): array
{
    $row = current_account_row($pdo);

    if ($row === null) {
        respond_error(401, 'Authentication required.');
    }

    return [
        'user_id' => (int)$row['user_id'],
        'organization_id' => (int)$row['organization_id'],
        'organization_type' => $row['organization_type'],
        'role' => $row['role'],
        'branch_id' => $row['branch_id'] !== null ? (int)$row['branch_id'] : null
    ];
}

function require_admin(PDO $pdo): array
{
    $actor = require_login($pdo);

    if ($actor['role'] !== 'ADMIN') {
        respond_error(403, 'Organisation administrator access is required.');
    }

    return $actor;
}

function require_branch_admin(PDO $pdo): array
{
    $actor = require_login($pdo);

    if ($actor['role'] !== 'BRANCH_ADMIN') {
        respond_error(403, 'Branch administrator access is required.');
    }

    if ($actor['branch_id'] === null) {
        respond_error(403, 'This account must be assigned to a branch.');
    }

    $statement = $pdo->prepare(
        "SELECT 1
         FROM branches
         WHERE branch_id = ?
           AND organization_id = ?
           AND status = 'ACTIVE'
         LIMIT 1"
    );

    $statement->execute([
        $actor['branch_id'],
        $actor['organization_id']
    ]);

    if (!$statement->fetchColumn()) {
        respond_error(403, 'Your assigned branch is not active.');
    }

    return $actor;
}