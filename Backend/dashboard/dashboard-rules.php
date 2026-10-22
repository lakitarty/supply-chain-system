<?php

declare(strict_types=1);

require_once __DIR__ . '/../products/product-rules.php';
require_once __DIR__ . '/../changes/change-rules.php';

const UPDATES_PAGE = 'modules/products/updates/updates.html';
const ANNOUNCEMENTS_PAGE = 'modules/messages/announcements/announcements.html';
const NETWORK_PAGE = 'modules/network/relationships/relationships.html';
const ATTENTION_GROUP = 'Needs your attention';
const WAITING_GROUP = 'Waiting on others';

function dashboard_rows(PDO $pdo, string $sql, array $params): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function label_of(string $code): string
{
    return ucfirst(strtolower(str_replace('_', ' ', $code)));
}

function attention_item(string $group, string $title, string $detail, string $path): array
{
    return [
        'group' => $group,
        'title' => $title,
        'detail' => $detail,
        'path' => $path
    ];
}

function changes_to_acknowledge(PDO $pdo, array $actor): array
{
    $rows = dashboard_rows(
        $pdo,
        "SELECT p.product_name, o.organization_name AS manufacturer_name,
                c.change_type, c.effective_date
         FROM change_recipients r
         INNER JOIN product_changes c ON c.product_change_id = r.product_change_id
         INNER JOIN products p ON p.product_id = c.product_id
         INNER JOIN organizations o ON o.organization_id = p.manufacturer_id
         WHERE r.organization_id = ?
           AND c.status = 'PUBLISHED'
           AND r.acknowledged_at IS NULL
         ORDER BY c.published_at DESC
         LIMIT 20",
        [$actor['organization_id']]
    );

    return array_map(
        fn(array $row) => attention_item(
            ATTENTION_GROUP,
            'Acknowledge a product change',
            "{$row['product_name']}: " . label_of($row['change_type']) .
                " from {$row['manufacturer_name']}, effective {$row['effective_date']}",
            UPDATES_PAGE
        ),
        $rows
    );
}

function announcements_to_acknowledge(PDO $pdo, array $actor): array
{
    $rows = dashboard_rows(
        $pdo,
        "SELECT a.announcement_title, p.product_name,
                o.organization_name AS manufacturer_name
         FROM product_announcement_recipients r
         INNER JOIN product_announcements a ON a.announcement_id = r.announcement_id
         INNER JOIN products p ON p.product_id = a.product_id
         INNER JOIN organizations o ON o.organization_id = p.manufacturer_id
         WHERE r.organization_id = ?
           AND a.status = 'PUBLISHED'
           AND r.acknowledgement_status <> 'ACKNOWLEDGED'
         ORDER BY a.published_at DESC
         LIMIT 20",
        [$actor['organization_id']]
    );

    return array_map(
        fn(array $row) => attention_item(
            ATTENTION_GROUP,
            'Acknowledge an announcement',
            "{$row['announcement_title']} ({$row['product_name']}) from {$row['manufacturer_name']}",
            ANNOUNCEMENTS_PAGE
        ),
        $rows
    );
}

function relationship_requests(PDO $pdo, array $actor): array
{
    $organizationId = $actor['organization_id'];

    $rows = dashboard_rows(
        $pdo,
        "SELECT partner.organization_name
         FROM organization_relationships r
         INNER JOIN organizations partner
            ON partner.organization_id = IF(
                r.source_organization_id = ?,
                r.target_organization_id,
                r.source_organization_id
            )
         WHERE r.status = 'PENDING'
           AND (r.source_organization_id = ? OR r.target_organization_id = ?)
           AND r.requested_by_organization_id <> ?",
        [$organizationId, $organizationId, $organizationId, $organizationId]
    );

    return array_map(
        fn(array $row) => attention_item(
            ATTENTION_GROUP,
            'Answer a supply request',
            "{$row['organization_name']} wants to connect",
            NETWORK_PAGE
        ),
        $rows
    );
}

function changes_to_publish(PDO $pdo, array $actor): array
{
    $rows = dashboard_rows(
        $pdo,
        "SELECT p.product_name
         FROM product_changes c
         INNER JOIN products p ON p.product_id = c.product_id
         WHERE p.manufacturer_id = ?
           AND c.status = 'PENDING_APPROVAL'",
        [$actor['organization_id']]
    );

    return array_map(
        fn(array $row) => attention_item(
            ATTENTION_GROUP,
            'Publish a product change',
            "{$row['product_name']}: waiting for your approval",
            UPDATES_PAGE
        ),
        $rows
    );
}

function changes_waiting_on_others(PDO $pdo, array $actor): array
{
    $rows = dashboard_rows(
        $pdo,
        "SELECT p.product_name, COUNT(*) AS total,
                SUM(r.acknowledged_at IS NULL) AS waiting
         FROM product_changes c
         INNER JOIN products p ON p.product_id = c.product_id
         INNER JOIN change_recipients r ON r.product_change_id = c.product_change_id
         WHERE p.manufacturer_id = ?
           AND c.status = 'PUBLISHED'
         GROUP BY c.product_change_id, p.product_name
         HAVING waiting > 0
         ORDER BY c.product_change_id DESC
         LIMIT 20",
        [$actor['organization_id']]
    );

    return array_map(
        fn(array $row) => attention_item(
            WAITING_GROUP,
            'Changes not yet acknowledged',
            "{$row['product_name']}: {$row['waiting']} of {$row['total']} organisations have not acknowledged",
            UPDATES_PAGE
        ),
        $rows
    );
}

function dashboard_summary(PDO $pdo, array $actor, int $attentionCount): array
{
    $organizationId = (int)$actor['organization_id'];
    $manufacturerIds = array_values(array_unique(array_map(
        'intval',
        visible_manufacturer_ids($pdo, $actor)
    )));

    $productsTotal = 0;

    if ($manufacturerIds !== []) {
        $products = dashboard_rows(
            $pdo,
            "SELECT COUNT(*) AS total
             FROM products
             WHERE status = 'ACTIVE'
               AND manufacturer_id IN (" . sql_placeholders($manufacturerIds) . ')',
            $manufacturerIds
        );

        $productsTotal = (int)$products[0]['total'];
    }

    $connections = dashboard_rows(
        $pdo,
        "SELECT COUNT(*) AS total
         FROM organization_relationships
         WHERE status = 'ACTIVE'
           AND (source_organization_id = ? OR target_organization_id = ?)",
        [$organizationId, $organizationId]
    );

    $notifications = dashboard_rows(
        $pdo,
        "SELECT COUNT(*) AS total
         FROM notifications
         WHERE organization_id = ?
           AND (user_id IS NULL OR user_id = ?)
           AND status = 'UNREAD'",
        [$organizationId, $actor['user_id']]
    );

    return [
        ['label' => 'Products available', 'value' => $productsTotal],
        ['label' => 'Active connections', 'value' => (int)$connections[0]['total']],
        ['label' => 'Needs your attention', 'value' => $attentionCount],
        ['label' => 'Unread notifications', 'value' => (int)$notifications[0]['total']]
    ];
}

function dashboard_activity_item(
    string $title,
    string $detail,
    string $date,
    string $path
): array {
    return [
        'title' => $title,
        'detail' => $detail,
        'date' => $date,
        'path' => $path
    ];
}

function dashboard_recent_activity(PDO $pdo, array $actor): array
{
    $organizationId = (int)$actor['organization_id'];
    $manufacturerIds = array_values(array_unique(array_map(
        'intval',
        visible_manufacturer_ids($pdo, $actor)
    )));

    $activity = [];

    if ($manufacturerIds !== []) {
        $changes = dashboard_rows(
            $pdo,
            "SELECT p.product_name, c.change_type, c.effective_date,
                    COALESCE(c.published_at, c.created_at) AS activity_date,
                    o.organization_name AS manufacturer_name
             FROM product_changes c
             INNER JOIN products p ON p.product_id = c.product_id
             INNER JOIN organizations o ON o.organization_id = p.manufacturer_id
             WHERE c.status = 'PUBLISHED'
               AND p.manufacturer_id IN (" . sql_placeholders($manufacturerIds) . ")
             ORDER BY COALESCE(c.published_at, c.created_at) DESC
             LIMIT 15",
            $manufacturerIds
        );

        foreach ($changes as $row) {
            $activity[] = dashboard_activity_item(
                "{$row['product_name']}: " . label_of($row['change_type']),
                "From {$row['manufacturer_name']} · Effective {$row['effective_date']}",
                (string)$row['activity_date'],
                UPDATES_PAGE
            );
        }

        $announcements = dashboard_rows(
            $pdo,
            "SELECT a.announcement_title, a.published_at AS activity_date,
                    p.product_name,
                    o.organization_name AS manufacturer_name
             FROM product_announcements a
             INNER JOIN products p ON p.product_id = a.product_id
             INNER JOIN organizations o ON o.organization_id = a.manufacturer_id
             WHERE a.status = 'PUBLISHED'
               AND a.manufacturer_id IN (" . sql_placeholders($manufacturerIds) . ")
             ORDER BY a.published_at DESC
             LIMIT 15",
            $manufacturerIds
        );

        foreach ($announcements as $row) {
            $activity[] = dashboard_activity_item(
                $row['announcement_title'],
                "Product: {$row['product_name']} · From {$row['manufacturer_name']}",
                (string)$row['activity_date'],
                ANNOUNCEMENTS_PAGE
            );
        }
    }

    $relationships = dashboard_rows(
        $pdo,
        "SELECT partner.organization_name AS partner_name,
                r.status, r.relationship_type,
                r.created_at AS activity_date
         FROM organization_relationships r
         INNER JOIN organizations partner
            ON partner.organization_id = IF(
                r.source_organization_id = ?,
                r.target_organization_id,
                r.source_organization_id
            )
         WHERE r.source_organization_id = ?
            OR r.target_organization_id = ?
         ORDER BY r.created_at DESC
         LIMIT 15",
        [$organizationId, $organizationId, $organizationId]
    );

    foreach ($relationships as $row) {
        $activity[] = dashboard_activity_item(
            'Supply network relationship',
            "{$row['partner_name']} · " .
                label_of($row['relationship_type']) . ' · ' .
                label_of($row['status']),
            (string)$row['activity_date'],
            NETWORK_PAGE
        );
    }

    usort(
        $activity,
        fn(array $a, array $b) =>
            (strtotime($b['date']) ?: 0) <=> (strtotime($a['date']) ?: 0)
    );

    return array_slice($activity, 0, 8);
}

function fetch_dashboard(PDO $pdo, array $actor): array
{
    $isAdmin = $actor['role'] === 'ADMIN';
    $manufactures = can_manufacture($actor);

    $items = array_merge(
        changes_to_acknowledge($pdo, $actor),
        announcements_to_acknowledge($pdo, $actor),
        $isAdmin ? relationship_requests($pdo, $actor) : [],
        $isAdmin && $manufactures ? changes_to_publish($pdo, $actor) : [],
        $manufactures ? changes_waiting_on_others($pdo, $actor) : []
    );

    $attentionCount = count(array_filter(
        $items,
        fn(array $item) => $item['group'] === ATTENTION_GROUP
    ));

    return [
        'summary' => dashboard_summary($pdo, $actor, $attentionCount),
        'items' => $items,
        'activity' => dashboard_recent_activity($pdo, $actor)
    ];
}
