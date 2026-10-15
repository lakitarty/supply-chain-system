<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/announcement-rules.php';

$actor = require_login($pdo);

if (can_publish_announcements($actor)) {
    $statement = $pdo->prepare(
        "SELECT
            a.announcement_id,
            a.announcement_title,
            a.announcement_message,
            a.published_at,
            a.status,
            p.product_id,
            p.product_name,
            p.availability_status,
            p.category,
            p.description AS product_description,
            p.image_path,
            v.barcode,
            v.pack_size,
            v.packaging_description,
            (SELECT COUNT(*)
             FROM product_announcement_recipients r
             WHERE r.announcement_id = a.announcement_id
            ) AS recipient_count,
            (SELECT COUNT(*)
             FROM product_announcement_recipients r
             WHERE r.announcement_id = a.announcement_id
               AND r.acknowledgement_status = 'ACKNOWLEDGED'
            ) AS acknowledged_count
         FROM product_announcements a
         INNER JOIN products p ON p.product_id = a.product_id
         LEFT JOIN product_versions v
            ON v.product_id = p.product_id
            AND v.is_current = 1
         WHERE a.manufacturer_id = ?
         ORDER BY a.published_at DESC, a.announcement_id DESC"
    );

    $statement->execute([$actor['organization_id']]);
    $announcements = $statement->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'direction' => 'outgoing',
        'announcements' => $announcements
    ]);
}

if (in_array($actor['organization_type'], ['SUPPLIER', 'RETAILER'], true)) {
    $statement = $pdo->prepare(
        "SELECT
            a.announcement_id,
            a.announcement_title,
            a.announcement_message,
            a.published_at,
            p.product_id,
            p.product_name,
            p.availability_status,
            p.category,
            p.description AS product_description,
            p.image_path,
            v.barcode,
            v.pack_size,
            v.packaging_description,
            o.organization_id AS manufacturer_id,
            o.organization_name AS manufacturer_name,
            r.acknowledgement_status,
            r.acknowledged_at
         FROM product_announcement_recipients r
         INNER JOIN product_announcements a
            ON a.announcement_id = r.announcement_id
         INNER JOIN products p ON p.product_id = a.product_id
         INNER JOIN organizations o
            ON o.organization_id = a.manufacturer_id
         LEFT JOIN product_versions v
            ON v.product_id = p.product_id
            AND v.is_current = 1
         WHERE r.organization_id = ?
           AND a.status = 'PUBLISHED'
         ORDER BY a.published_at DESC, a.announcement_id DESC"
    );

    $statement->execute([$actor['organization_id']]);
    $announcements = $statement->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'direction' => 'incoming',
        'announcements' => $announcements
    ]);
}

respond(200, [
    'success' => true,
    'direction' => 'none',
    'announcements' => []
]);
