<?php
declare(strict_types=1);

function require_price_confirmation_password(
    PDO $pdo,
    array $actor,
    string $password,
    string $action = 'price change'
): void {
    if (($actor['role'] ?? '') !== 'ADMIN') {
        respond_error(403, "Organisation administrator access is required to confirm this {$action}.");
    }

    if ($password === '') {
        respond_error(401, "Re-enter your administrator password to authorize this {$action}.");
    }

    throttle_block($pdo, 'ADMIN_CONFIRM', (string)$actor['user_id']);

    $statement = $pdo->prepare(
        "SELECT password_hash
         FROM users
         WHERE user_id = ?
           AND organization_id = ?
           AND role = 'ADMIN'
           AND status = 'ACTIVE'
         LIMIT 1"
    );

    $statement->execute([
        (int)$actor['user_id'],
        (int)$actor['organization_id']
    ]);

    $hash = $statement->fetchColumn();

    $valid = is_string($hash) && $hash !== '' && password_verify($password, $hash);

    if (!$valid) {
        throttle_record_failure($pdo, 'ADMIN_CONFIRM', (string)$actor['user_id']);
        respond_error(401, "Administrator password verification failed. No {$action} was saved.");
    }

    throttle_clear($pdo, 'ADMIN_CONFIRM', (string)$actor['user_id']);
}
