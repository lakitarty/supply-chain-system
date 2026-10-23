<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/validation.php';

const USER_ROLES = ['ADMIN', 'BRANCH_ADMIN', 'STAFF'];
const USER_STATUSES = ['ACTIVE', 'INACTIVE'];

const USER_MAX_LENGTHS = [
    'firstName' => 100,
    'lastName' => 100,
    'email' => 150
];

function clean_user_input(array $data): array
{
    $text = fn(string $key): string => trim((string)($data[$key] ?? ''));

    return [
        'userId' => (int)($data['userId'] ?? 0),
        'firstName' => $text('firstName'),
        'lastName' => $text('lastName'),
        'email' => strtolower($text('email')),
        'role' => $text('role'),
        'status' => $text('status'),
        'branchId' => ($data['branchId'] ?? '') !== '' ? (int)$data['branchId'] : null,
        'password' => (string)($data['password'] ?? '')
    ];
}

function first_user_error(PDO $pdo, array $actor, array $input, bool $creating): ?array
{
    $required = $creating
        ? ['firstName', 'lastName', 'email', 'role', 'password']
        : ['firstName', 'lastName', 'role', 'status'];

    foreach ($required as $field) {
        if ($input[$field] === '') {
            return field_error($field, 'This field is required.');
        }
    }

    foreach (USER_MAX_LENGTHS as $field => $maxLength) {
        if (mb_strlen($input[$field]) > $maxLength) {
            return field_error($field, "Must be $maxLength characters or fewer.");
        }
    }

    if ($input['email'] !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        return field_error('email', 'Enter a valid email address.');
    }

    if (!in_array($input['role'], USER_ROLES, true)) {
        return field_error('role', 'Choose Organisation admin, Branch admin or Staff.');
    }

    if ($actor['role'] === 'BRANCH_ADMIN' && $input['role'] !== 'STAFF') {
        return field_error('role', 'Branch administrators can only manage staff accounts.');
    }

    if ($input['role'] === 'BRANCH_ADMIN' && $input['branchId'] === null) {
        return field_error('branchId', 'A branch administrator must be assigned to a branch.');
    }

    if ($actor['role'] === 'BRANCH_ADMIN'
        && (int)$input['branchId'] !== (int)$actor['branch_id']) {
        return field_error('branchId', 'You can only manage users in your assigned branch.');
    }

    if (!$creating && !in_array($input['status'], USER_STATUSES, true)) {
        return field_error('status', 'Choose Active or Inactive.');
    }

    if ($input['branchId'] !== null && !record_exists(
        $pdo,
        "SELECT 1 FROM branches
         WHERE branch_id = ? AND organization_id = ? AND status = 'ACTIVE'
         LIMIT 1",
        [$input['branchId'], $actor['organization_id']]
    )) {
        return field_error('branchId', 'Choose an active branch from your organization.');
    }

    $passwordMessage = $creating || $input['password'] !== ''
        ? password_error($input['password'])
        : null;

    return $passwordMessage !== null ? field_error('password', $passwordMessage) : null;
}

function find_user_conflict(PDO $pdo, array $input): ?array
{
    return record_exists(
        $pdo,
        'SELECT 1 FROM users WHERE email = ? LIMIT 1',
        [$input['email']]
    )
        ? field_error('email', 'This email is already registered.')
        : null;
}

function insert_user(PDO $pdo, array $actor, array $input): int
{
    $statement = $pdo->prepare(
        "INSERT INTO users
            (organization_id, branch_id, first_name, last_name, email, password_hash, role, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVE')"
    );

    $statement->execute([
        $actor['organization_id'],
        $input['branchId'],
        $input['firstName'],
        $input['lastName'],
        $input['email'],
        password_hash($input['password'], PASSWORD_DEFAULT),
        $input['role']
    ]);

    return (int)$pdo->lastInsertId();
}

function find_user(PDO $pdo, array $actor, int $userId): array|false
{
    $sql = 'SELECT user_id, first_name, last_name, email, role, status, branch_id
            FROM users
            WHERE user_id = ? AND organization_id = ?';

    $params = [$userId, $actor['organization_id']];

    if ($actor['role'] === 'BRANCH_ADMIN') {
        $sql .= " AND branch_id = ? AND role = 'STAFF'";
        $params[] = $actor['branch_id'];
    }

    $sql .= ' LIMIT 1';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetch();
}

function would_remove_last_admin(PDO $pdo, array $actor, array $existing, array $input): bool
{
    $staysActiveAdmin = $input['role'] === 'ADMIN' && $input['status'] === 'ACTIVE';

    if ($existing['role'] !== 'ADMIN' || $existing['status'] !== 'ACTIVE' || $staysActiveAdmin) {
        return false;
    }

    return !record_exists(
        $pdo,
        "SELECT 1 FROM users
         WHERE organization_id = ? AND role = 'ADMIN' AND status = 'ACTIVE' AND user_id <> ?
         LIMIT 1",
        [$actor['organization_id'], $existing['user_id']]
    );
}

function update_user(PDO $pdo, array $actor, array $input): void
{
    $statement = $pdo->prepare(
        'UPDATE users
         SET first_name = ?, last_name = ?, role = ?, status = ?, branch_id = ?
         WHERE user_id = ? AND organization_id = ?'
    );

    $statement->execute([
        $input['firstName'],
        $input['lastName'],
        $input['role'],
        $input['status'],
        $input['branchId'],
        $input['userId'],
        $actor['organization_id']
    ]);

    if ($input['password'] !== '') {
        $reset = $pdo->prepare(
            'UPDATE users
             SET password_hash = ?
             WHERE user_id = ? AND organization_id = ?'
        );

        $reset->execute([
            password_hash($input['password'], PASSWORD_DEFAULT),
            $input['userId'],
            $actor['organization_id']
        ]);
    }
}