<?php

declare(strict_types=1);

function field_error(string $field, string $message): array
{
    return ['field' => $field, 'message' => $message];
}

function password_error(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }

    if (strlen($password) > 72) {
        return 'Password must be 72 characters or fewer.';
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password must include at least one letter and one number.';
    }

    return null;
}

function record_exists(PDO $pdo, string $sql, array $params): bool
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchColumn() !== false;
}
function sql_placeholders(array $values): string
{
    return implode(', ', array_fill(0, count($values), '?'));
}
function valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

function optional_text(string $value): ?string
{
    return $value !== '' ? $value : null;
}