<?php
declare(strict_types=1);

function require_price_confirmation_password(
    PDO $pdo,
    array $actor,
    string $password
): void {
    if (($actor['role'] ?? '') !== 'ADMIN') {
        respond_error(403, 'Organisation administrator access is required to confirm this price change.');
    }

    if ($password === '') {
        respond_error(401, 'Re-enter your administrator password to authorize this price change.');
    }

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

    if (
        !is_string($hash) ||
        $hash === '' ||
        !password_verify($password, $hash)
    ) {
        respond_error(401, 'Administrator password verification failed. No price change was saved.');
    }
}