<?php

declare(strict_types=1);

function record_audit(
    PDO $pdo,
    array $actor,
    string $action,
    string $entityType,
    ?int $entityId,
    string $description
): void {
    $statement = $pdo->prepare(
        'INSERT INTO audit_logs
            (user_id, organization_id, action_type, entity_type, entity_id, description)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $statement->execute([
        $actor['user_id'],
        $actor['organization_id'],
        $action,
        $entityType,
        $entityId,
        $description
    ]);
}

function describe_changes(array $pairs): string
{
    $changes = [];

    foreach ($pairs as $label => [$before, $after]) {
        if ((string)$before !== (string)$after) {
            $changes[] = "$label $before to $after";
        }
    }

    return $changes === [] ? 'no changes' : implode(', ', $changes);
}