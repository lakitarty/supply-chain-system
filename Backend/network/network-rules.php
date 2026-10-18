<?php

declare(strict_types=1);
require_once __DIR__ . '/../core/validation.php';
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
function fetch_relationships(PDO $pdo, array $actor): array
{
    $organizationId = $actor['organization_id'];

    $statement = $pdo->prepare(
        "SELECT r.relationship_id,
                r.status,
                r.start_date,
                r.end_date,
                r.requested_by_organization_id,
                r.source_organization_id = ? AS i_supply,
                partner.organization_name AS partner_name,
                partner.organization_type AS partner_type
         FROM organization_relationships r
         INNER JOIN organizations partner
            ON partner.organization_id = IF(r.source_organization_id = ?, r.target_organization_id, r.source_organization_id)
         WHERE r.source_organization_id = ? OR r.target_organization_id = ?
         ORDER BY FIELD(r.status, 'PENDING', 'ACTIVE', 'INACTIVE'), partner.organization_name"
    );

    $statement->execute([$organizationId, $organizationId, $organizationId, $organizationId]);

    return $statement->fetchAll();
}
const SUPPLYING_TYPES = ['MANUFACTURER', 'SUPPLIER', 'MANUFACTURER_SUPPLIER'];
const SUPPLIED_TYPES = ['SUPPLIER', 'RETAILER', 'MANUFACTURER_SUPPLIER'];
const SUPPLY_DIRECTIONS = ['THEY_SUPPLY_ME', 'I_SUPPLY_THEM'];

function clean_network_input(array $data): array
{
    return [
        'term' => trim((string)($data['term'] ?? '')),
        'partnerId' => (int)($data['partnerId'] ?? 0),
        'direction' => trim((string)($data['direction'] ?? '')),
        'relationshipId' => (int)($data['relationshipId'] ?? 0),
        'decision' => trim((string)($data['decision'] ?? ''))
    ];
}

function find_organization_by_term(PDO $pdo, array $actor, string $term): array|false
{
    if ($term === '') {
        return false;
    }

    $statement = $pdo->prepare(
        "SELECT organization_id, organization_name, organization_type
         FROM organizations
         WHERE status = 'ACTIVE' AND organization_id <> ? AND (organization_name = ? OR kra_pin = ?)
         LIMIT 1"
    );

    $statement->execute([$actor['organization_id'], $term, strtoupper($term)]);

    return $statement->fetch();
}

function find_active_organization(PDO $pdo, array $actor, int $organizationId): array|false
{
    $statement = $pdo->prepare(
        "SELECT organization_id, organization_name, organization_type
         FROM organizations
         WHERE organization_id = ? AND organization_id <> ? AND status = 'ACTIVE'
         LIMIT 1"
    );

    $statement->execute([$organizationId, $actor['organization_id']]);

    return $statement->fetch();
}

function supply_pair(array $actor, array $partner, string $direction): array
{
    $me = ['organization_id' => $actor['organization_id'], 'organization_type' => $actor['organization_type']];

    return $direction === 'THEY_SUPPLY_ME'
        ? ['source' => $partner, 'target' => $me]
        : ['source' => $me, 'target' => $partner];
}

function first_request_error(PDO $pdo, array $actor, array $input, array|false $partner): ?array
{
    if ($partner === false) {
        return field_error('partnerId', 'Find an organisation first.');
    }

    if (!in_array($input['direction'], SUPPLY_DIRECTIONS, true)) {
        return field_error('direction', 'Choose who supplies whom.');
    }

    $pair = supply_pair($actor, $partner, $input['direction']);

    if (
        !in_array($pair['source']['organization_type'], SUPPLYING_TYPES, true) ||
        !in_array($pair['target']['organization_type'], SUPPLIED_TYPES, true)
    ) {
        return field_error('direction', 'These organisation types cannot have that supply relationship.');
    }

    $mine = $actor['organization_id'];
    $theirs = $partner['organization_id'];

    $exists = record_exists(
        $pdo,
        "SELECT 1 FROM organization_relationships
         WHERE status IN ('PENDING', 'ACTIVE')
           AND ((source_organization_id = ? AND target_organization_id = ?)
             OR (source_organization_id = ? AND target_organization_id = ?))
         LIMIT 1",
        [$mine, $theirs, $theirs, $mine]
    );

    return $exists
        ? field_error('partnerId', 'You already have an active or pending relationship with this organisation.')
        : null;
}

function insert_relationship(PDO $pdo, array $actor, array $pair): int
{
    $statement = $pdo->prepare(
        "INSERT INTO organization_relationships
            (source_organization_id, target_organization_id, relationship_type, status, requested_by_organization_id, start_date)
         VALUES (?, ?, 'SUPPLIES', 'PENDING', ?, CURDATE())"
    );

    $statement->execute([
        $pair['source']['organization_id'],
        $pair['target']['organization_id'],
        $actor['organization_id']
    ]);

    return (int)$pdo->lastInsertId();
}

function find_own_relationship(PDO $pdo, array $actor, int $relationshipId): array|false
{
    $statement = $pdo->prepare(
        'SELECT relationship_id, status, requested_by_organization_id
         FROM organization_relationships
         WHERE relationship_id = ? AND (source_organization_id = ? OR target_organization_id = ?)
         LIMIT 1'
    );

    $statement->execute([$relationshipId, $actor['organization_id'], $actor['organization_id']]);

    return $statement->fetch();
}

function answer_block(array $relationship, array $actor, string $decision): ?string
{
    if (!in_array($decision, ['ACCEPT', 'DECLINE'], true)) {
        return 'Choose accept or decline.';
    }

    if ($relationship['status'] !== 'PENDING') {
        return 'Only pending requests can be answered.';
    }

    return (int)$relationship['requested_by_organization_id'] === $actor['organization_id']
        ? 'You sent this request. The other organisation must answer it.'
        : null;
}

function end_block(array $relationship, array $actor): ?string
{
    $withdrawing = $relationship['status'] === 'PENDING'
        && (int)$relationship['requested_by_organization_id'] === $actor['organization_id'];

    return $relationship['status'] === 'ACTIVE' || $withdrawing
        ? null
        : 'This relationship cannot be ended.';
}

function set_relationship_status(PDO $pdo, int $relationshipId, string $status): void
{
    $statement = $pdo->prepare(
        "UPDATE organization_relationships
         SET status = ?,
             start_date = IF(? = 'ACTIVE', CURDATE(), start_date),
             end_date = IF(? = 'INACTIVE', CURDATE(), end_date)
         WHERE relationship_id = ?"
    );

    $statement->execute([$status, $status, $status, $relationshipId]);
}