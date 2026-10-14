<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../network/network-rules.php';

const CHANGE_TYPES = ['BARCODE_CHANGE', 'PACKAGING_CHANGE', 'PRODUCT_INFORMATION_CHANGE'];
const MANUFACTURING_TYPES = ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'];

const CHANGE_SELECT = "SELECT c.product_change_id,
        c.change_type,
        c.reason,
        c.effective_date,
        c.status,
        c.created_at,
        c.published_at,
        p.product_name,
        o.organization_name AS manufacturer_name,
        ov.barcode AS old_barcode,
        ov.pack_size AS old_pack_size,
        ov.packaging_description AS old_packaging,
        nv.barcode AS new_barcode,
        nv.pack_size AS new_pack_size,
        nv.packaging_description AS new_packaging,
        CONCAT(u.first_name, ' ', u.last_name) AS created_by_name,
        my.notification_status AS my_status,
        my.acknowledged_at AS my_acknowledged_at
    FROM product_changes c
    INNER JOIN products p ON p.product_id = c.product_id
    INNER JOIN organizations o ON o.organization_id = p.manufacturer_id
    INNER JOIN product_versions nv ON nv.product_version_id = c.new_product_version_id
    LEFT JOIN product_versions ov ON ov.product_version_id = c.old_product_version_id
    INNER JOIN users u ON u.user_id = c.created_by
    LEFT JOIN change_recipients my
        ON my.product_change_id = c.product_change_id AND my.organization_id = ?";

function can_manufacture(array $actor): bool
{
    return in_array($actor['organization_type'], MANUFACTURING_TYPES, true);
}

function clean_change_input(array $data): array
{
    $text = fn(string $key): string => trim((string)($data[$key] ?? ''));

    return [
        'changeId' => (int)($data['changeId'] ?? 0),
        'productId' => (int)($data['productId'] ?? 0),
        'changeType' => $text('changeType'),
        'barcode' => $text('barcode'),
        'packSize' => $text('packSize'),
        'packaging' => $text('packaging'),
        'effectiveDate' => $text('effectiveDate'),
        'reason' => $text('reason')
    ];
}

function find_own_product(PDO $pdo, array $actor, int $productId): array|false
{
    $statement = $pdo->prepare(
        "SELECT p.product_id,
                p.product_name,
                v.product_version_id,
                v.barcode,
                v.effective_from
         FROM products p
         INNER JOIN product_versions v
            ON v.product_id = p.product_id AND v.is_current = 1
         WHERE p.product_id = ? AND p.manufacturer_id = ? AND p.status = 'ACTIVE'
         LIMIT 1"
    );

    $statement->execute([$productId, $actor['organization_id']]);

    return $statement->fetch();
}

function first_change_error(PDO $pdo, array $input, array|false $product): ?array
{
    if ($product === false) {
        return field_error('productId', 'Choose one of your products.');
    }

    if (!in_array($input['changeType'], CHANGE_TYPES, true)) {
        return field_error('changeType', 'Choose a change type.');
    }

    if (!preg_match('/^[0-9]{8,14}$/', $input['barcode'])) {
        return field_error('barcode', 'Enter the new barcode as 8 to 14 digits.');
    }

    if (record_exists($pdo, 'SELECT 1 FROM product_versions WHERE barcode = ? LIMIT 1', [$input['barcode']])) {
        return field_error('barcode', 'This barcode is already used by another product version.');
    }

    foreach (['packSize' => 100, 'packaging' => 255, 'reason' => 1000] as $field => $maxLength) {
        if (mb_strlen($input[$field]) > $maxLength) {
            return field_error($field, "Must be $maxLength characters or fewer.");
        }
    }

    if (!valid_date($input['effectiveDate'])) {
        return field_error('effectiveDate', 'Enter the effective date.');
    }

    if ($input['effectiveDate'] <= $product['effective_from']) {
        return field_error('effectiveDate', 'The effective date must be after the current version starts.');
    }

    return $input['reason'] === '' ? field_error('reason', 'Explain why the product is changing.') : null;
}

function insert_change(PDO $pdo, array $actor, array $product, array $input): int
{
    $next = $pdo->prepare('SELECT COALESCE(MAX(version_number), 0) + 1 FROM product_versions WHERE product_id = ?');
    $next->execute([$product['product_id']]);

    $version = $pdo->prepare(
        'INSERT INTO product_versions
            (product_id, version_number, barcode, pack_size, packaging_description, effective_from, is_current)
         VALUES (?, ?, ?, ?, ?, ?, 0)'
    );

    $version->execute([
        $product['product_id'],
        (int)$next->fetchColumn(),
        $input['barcode'],
        optional_text($input['packSize']),
        optional_text($input['packaging']),
        $input['effectiveDate']
    ]);

    $newVersionId = (int)$pdo->lastInsertId();

    $change = $pdo->prepare(
        "INSERT INTO product_changes
            (product_id, old_product_version_id, new_product_version_id, change_type, reason, effective_date, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, 'PENDING_APPROVAL', ?)"
    );

    $change->execute([
        $product['product_id'],
        $product['product_version_id'],
        $newVersionId,
        $input['changeType'],
        $input['reason'],
        $input['effectiveDate'],
        $actor['user_id']
    ]);

    return (int)$pdo->lastInsertId();
}

function find_own_change(PDO $pdo, array $actor, int $changeId): array|false
{
    $statement = $pdo->prepare(
        'SELECT c.product_change_id,
                c.old_product_version_id,
                c.new_product_version_id,
                c.effective_date,
                c.status,
                p.product_name
         FROM product_changes c
         INNER JOIN products p ON p.product_id = c.product_id
         WHERE c.product_change_id = ? AND p.manufacturer_id = ?
         LIMIT 1'
    );

    $statement->execute([$changeId, $actor['organization_id']]);

    return $statement->fetch();
}

function publish_block(PDO $pdo, array $change): ?string
{
    if ($change['status'] !== 'PENDING_APPROVAL') {
        return 'Only changes waiting for approval can be published.';
    }

    $stillCurrent = record_exists(
        $pdo,
        'SELECT 1 FROM product_versions WHERE product_version_id = ? AND is_current = 1 LIMIT 1',
        [$change['old_product_version_id']]
    );

    return $stillCurrent ? null : 'This change is based on a version that is no longer current.';
}

function publish_change(PDO $pdo, array $actor, array $change): int
{
    $retire = $pdo->prepare(
        'UPDATE product_versions SET is_current = 0, effective_to = DATE_SUB(?, INTERVAL 1 DAY) WHERE product_version_id = ?'
    );
    $retire->execute([$change['effective_date'], $change['old_product_version_id']]);

    $activate = $pdo->prepare('UPDATE product_versions SET is_current = 1 WHERE product_version_id = ?');
    $activate->execute([$change['new_product_version_id']]);

    $publish = $pdo->prepare(
        "UPDATE product_changes SET status = 'PUBLISHED', published_by = ?, published_at = NOW() WHERE product_change_id = ?"
    );
    $publish->execute([$actor['user_id'], $change['product_change_id']]);

    $recipientIds = reachable_organization_ids($pdo, $actor['organization_id'], 'downstream');

    $notify = $pdo->prepare(
        "INSERT INTO change_recipients (product_change_id, organization_id, notification_status) VALUES (?, ?, 'SENT')"
    );

    foreach ($recipientIds as $recipientId) {
        $notify->execute([$change['product_change_id'], $recipientId]);
    }

    return count($recipientIds);
}

function query_changes(PDO $pdo, string $sql, array $params, string $direction): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return array_map(fn(array $row) => $row + ['direction' => $direction], $statement->fetchAll());
}

function attach_recipients(PDO $pdo, array $changes): array
{
    $outgoingIds = array_values(array_column(
        array_filter($changes, fn(array $change) => $change['direction'] === 'OUTGOING'),
        'product_change_id'
    ));

    $recipientsByChange = [];

    if ($outgoingIds !== []) {
        $statement = $pdo->prepare(
            'SELECT r.product_change_id, o.organization_name, r.notification_status, r.acknowledged_at
             FROM change_recipients r
             INNER JOIN organizations o ON o.organization_id = r.organization_id
             WHERE r.product_change_id IN (' . sql_placeholders($outgoingIds) . ')
             ORDER BY o.organization_name'
        );

        $statement->execute($outgoingIds);

        foreach ($statement->fetchAll() as $recipient) {
            $recipientsByChange[$recipient['product_change_id']][] = $recipient;
        }
    }

    return array_map(
        fn(array $change) => $change + ['recipients' => $recipientsByChange[$change['product_change_id']] ?? []],
        $changes
    );
}

function fetch_changes(PDO $pdo, array $actor): array
{
    $organizationId = $actor['organization_id'];

    $outgoing = can_manufacture($actor)
        ? query_changes($pdo, CHANGE_SELECT . ' WHERE p.manufacturer_id = ?', [$organizationId, $organizationId], 'OUTGOING')
        : [];

    $incoming = query_changes(
        $pdo,
        CHANGE_SELECT . " WHERE my.change_recipient_id IS NOT NULL AND c.status = 'PUBLISHED' AND p.manufacturer_id <> ?",
        [$organizationId, $organizationId],
        'INCOMING'
    );

    $changes = array_merge($outgoing, $incoming);

    usort($changes, fn(array $a, array $b) => strcmp($b['created_at'], $a['created_at']));

    return attach_recipients($pdo, $changes);
}

function find_recipient(PDO $pdo, array $actor, int $changeId): array|false
{
    $statement = $pdo->prepare(
        'SELECT notification_status FROM change_recipients WHERE product_change_id = ? AND organization_id = ? LIMIT 1'
    );

    $statement->execute([$changeId, $actor['organization_id']]);

    return $statement->fetch();
}

function acknowledge_change(PDO $pdo, array $actor, int $changeId): void
{
    $statement = $pdo->prepare(
        "UPDATE change_recipients
         SET notification_status = 'ACKNOWLEDGED', acknowledged_at = NOW(), acknowledged_by = ?
         WHERE product_change_id = ? AND organization_id = ?"
    );

    $statement->execute([$actor['user_id'], $changeId, $actor['organization_id']]);
}