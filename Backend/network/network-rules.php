<?php

declare(strict_types=1);

const REACH_COLUMNS = [
    'downstream' => ['from' => 'source_organization_id', 'to' => 'target_organization_id'],
    'upstream' => ['from' => 'target_organization_id', 'to' => 'source_organization_id']
];

function reachable_organization_ids(PDO $pdo, int $organizationId, string $direction): array
{
    ['from' => $from, 'to' => $to] = REACH_COLUMNS[$direction];

    $statement = $pdo->prepare(
        "WITH RECURSIVE reach (organization_id) AS (
            SELECT $to
            FROM organization_relationships
            WHERE $from = ? AND status = 'ACTIVE'
            UNION
            SELECT r.$to
            FROM organization_relationships r
            INNER JOIN reach ON r.$from = reach.organization_id
            WHERE r.status = 'ACTIVE'
         )
         SELECT organization_id FROM reach WHERE organization_id <> ?"
    );

    $statement->execute([$organizationId, $organizationId]);

    return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
}