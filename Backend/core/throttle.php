<?php

declare(strict_types=1);

const THROTTLE_WINDOW_MINUTES = 15;
const THROTTLE_IDENTIFIER_LIMIT = 5;
const THROTTLE_IP_LIMIT = 20;

function throttle_client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

function throttle_wait_seconds(PDO $pdo, string $scope, string $column, string $value, int $limit): int
{
    $statement = $pdo->prepare(
        "SELECT COUNT(*) AS failures,
                TIMESTAMPDIFF(SECOND, NOW(), MIN(attempted_at) + INTERVAL " . THROTTLE_WINDOW_MINUTES . " MINUTE) AS wait
         FROM auth_attempts
         WHERE scope = ?
           AND {$column} = ?
           AND attempted_at > NOW() - INTERVAL " . THROTTLE_WINDOW_MINUTES . " MINUTE"
    );
    $statement->execute([$scope, $value]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return (int)$row['failures'] >= $limit ? max((int)$row['wait'], 1) : 0;
}

function throttle_block(PDO $pdo, string $scope, string $identifier, bool $byIp = false): void
{
    $wait = throttle_wait_seconds($pdo, $scope, 'identifier', substr($identifier, 0, 190), THROTTLE_IDENTIFIER_LIMIT);

    if ($byIp) {
        $wait = max($wait, throttle_wait_seconds($pdo, $scope, 'ip_address', throttle_client_ip(), THROTTLE_IP_LIMIT));
    }

    if ($wait > 0) {
        header('Retry-After: ' . $wait);
        respond_error(429, 'Too many failed attempts. Try again in ' . (int)ceil($wait / 60) . ' minute(s).');
    }
}

function throttle_record_failure(PDO $pdo, string $scope, string $identifier): void
{
    $pdo->prepare('INSERT INTO auth_attempts (scope, identifier, ip_address) VALUES (?, ?, ?)')
        ->execute([$scope, substr($identifier, 0, 190), throttle_client_ip()]);
    $pdo->exec('DELETE FROM auth_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY');
}

function throttle_clear(PDO $pdo, string $scope, string $identifier): void
{
    $pdo->prepare('DELETE FROM auth_attempts WHERE scope = ? AND identifier = ?')
        ->execute([$scope, substr($identifier, 0, 190)]);
}
