<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/validation.php';

const AUDIT_ENTRY_LIMIT = 200;

function valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

function clean_audit_filters(array $query): array
{
    $text = fn(string $key): string => trim((string)($query[$key] ?? ''));

    return [
        'userId' => (int)($query['userId'] ?? 0),
        'action' => $text('action'),
        'from' => $text('from'),
        'to' => $text('to')
    ];
}

function first_audit_filter_error(array $filters): ?array
{
    foreach (['from', 'to'] as $field) {
        if ($filters[$field] !== '' && !valid_date($filters[$field])) {
            return field_error($field, 'Use the format YYYY-MM-DD.');
        }
    }

    return null;
}

function fetch_audit_entries(PDO $pdo, array $actor, array $filters): array
{
    $conditions = ['a.organization_id = ?'];
    $params = [$actor['organization_id']];

    if ($filters['userId'] > 0) {
        $conditions[] = 'a.user_id = ?';
        $params[] = $filters['userId'];
    }

    if ($filters['action'] !== '') {
        $conditions[] = 'a.action_type = ?';
        $params[] = $filters['action'];
    }

    if ($filters['from'] !== '') {
        $conditions[] = 'a.created_at >= ?';
        $params[] = $filters['from'];
    }

    if ($filters['to'] !== '') {
        $conditions[] = 'a.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
        $params[] = $filters['to'];
    }

    $where = implode(' AND ', $conditions);
    $limit = AUDIT_ENTRY_LIMIT;

    $statement = $pdo->prepare(
        "SELECT a.audit_log_id,
                a.action_type,
                a.entity_type,
                a.entity_id,
                a.description,
                a.created_at,
                u.first_name,
                u.last_name,
                u.email
         FROM audit_logs a
         LEFT JOIN users u
            ON u.user_id = a.user_id
         WHERE $where
         ORDER BY a.created_at DESC, a.audit_log_id DESC
         LIMIT $limit"
    );

    $statement->execute($params);

    return $statement->fetchAll();
}

function fetch_audit_actions(PDO $pdo, array $actor): array
{
    $statement = $pdo->prepare(
        'SELECT DISTINCT action_type FROM audit_logs WHERE organization_id = ? ORDER BY action_type'
    );

    $statement->execute([$actor['organization_id']]);

    return $statement->fetchAll(PDO::FETCH_COLUMN);
}