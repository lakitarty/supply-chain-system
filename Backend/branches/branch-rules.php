<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/validation.php';

const BRANCH_STATUSES = ['ACTIVE', 'INACTIVE'];

const BRANCH_MAX_LENGTHS = [
    'branchName' => 150,
    'location' => 255
];

function clean_branch_input(array $data): array
{
    $text = fn(string $key): string => trim((string)($data[$key] ?? ''));

    return [
        'branchId' => (int)($data['branchId'] ?? 0),
        'branchName' => $text('branchName'),
        'location' => $text('location'),
        'status' => $text('status')
    ];
}

function first_branch_error(array $input, bool $creating): ?array
{
    foreach (BRANCH_MAX_LENGTHS as $field => $maxLength) {
        if ($input[$field] === '') {
            return field_error($field, 'This field is required.');
        }

        if (mb_strlen($input[$field]) > $maxLength) {
            return field_error($field, "Must be $maxLength characters or fewer.");
        }
    }

    if (!$creating && !in_array($input['status'], BRANCH_STATUSES, true)) {
        return field_error('status', 'Choose Active or Inactive.');
    }

    return null;
}

function find_branch_name_conflict(PDO $pdo, array $actor, array $input): ?array
{
    return record_exists(
        $pdo,
        'SELECT 1 FROM branches WHERE organization_id = ? AND branch_name = ? AND branch_id <> ? LIMIT 1',
        [$actor['organization_id'], $input['branchName'], $input['branchId']]
    )
        ? field_error('branchName', 'A branch with this name already exists.')
        : null;
}

function find_branch(PDO $pdo, array $actor, int $branchId): array|false
{
    $statement = $pdo->prepare(
        'SELECT branch_id, branch_name, location, is_main_branch, status
         FROM branches
         WHERE branch_id = ? AND organization_id = ?
         LIMIT 1'
    );

    $statement->execute([$branchId, $actor['organization_id']]);

    return $statement->fetch();
}

function deactivation_block(PDO $pdo, array $actor, array $existing, array $input): ?string
{
    if ($existing['status'] !== 'ACTIVE' || $input['status'] !== 'INACTIVE') {
        return null;
    }

    if ((int)$existing['is_main_branch'] === 1) {
        return 'The main branch cannot be deactivated.';
    }

    $hasActiveUsers = record_exists(
        $pdo,
        "SELECT 1 FROM users WHERE branch_id = ? AND organization_id = ? AND status = 'ACTIVE' LIMIT 1",
        [$existing['branch_id'], $actor['organization_id']]
    );

    return $hasActiveUsers ? 'Move or deactivate this branch\'s users first.' : null;
}

function insert_branch(PDO $pdo, array $actor, array $input): int
{
    $statement = $pdo->prepare(
        "INSERT INTO branches (organization_id, branch_name, location, is_main_branch, status)
         VALUES (?, ?, ?, 0, 'ACTIVE')"
    );

    $statement->execute([$actor['organization_id'], $input['branchName'], $input['location']]);

    return (int)$pdo->lastInsertId();
}

function update_branch(PDO $pdo, array $actor, array $input): void
{
    $statement = $pdo->prepare(
        'UPDATE branches
         SET branch_name = ?, location = ?, status = ?
         WHERE branch_id = ? AND organization_id = ?'
    );

    $statement->execute([
        $input['branchName'],
        $input['location'],
        $input['status'],
        $input['branchId'],
        $actor['organization_id']
    ]);
}