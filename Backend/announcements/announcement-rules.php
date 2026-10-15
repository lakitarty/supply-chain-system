<?php
declare(strict_types=1);

require_once __DIR__ . '/../network/network-rules.php';

const ANNOUNCEMENT_MANUFACTURING_TYPES = [
    'MANUFACTURER',
    'MANUFACTURER_SUPPLIER'
];

function can_publish_announcements(array $actor): bool
{
    return $actor['role'] === 'ADMIN'
        && in_array(
            $actor['organization_type'],
            ANNOUNCEMENT_MANUFACTURING_TYPES,
            true
        );
}

function find_announcement_product(
    PDO $pdo,
    array $actor,
    int $productId
): array|false {
    $statement = $pdo->prepare(
        "SELECT p.product_id,
                p.product_name,
                p.category,
                p.description,
                p.image_path,
                v.barcode,
                v.pack_size,
                v.packaging_description
         FROM products p
         INNER JOIN product_versions v
            ON v.product_id = p.product_id
            AND v.is_current = 1
         WHERE p.product_id = ?
           AND p.manufacturer_id = ?
           AND p.status = 'ACTIVE'
         LIMIT 1"
    );

    $statement->execute([
        $productId,
        $actor['organization_id']
    ]);

    return $statement->fetch();
}

function find_announcement_recipients(
    PDO $pdo,
    int $manufacturerId
): array {
    $reachableIds = reachable_organization_ids(
        $pdo,
        $manufacturerId,
        'downstream'
    );

    if ($reachableIds === []) {
        return [];
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($reachableIds), '?')
    );

    $statement = $pdo->prepare(
        "SELECT organization_id
         FROM organizations
         WHERE organization_id IN ($placeholders)
           AND organization_type = 'RETAILER'
           AND status = 'ACTIVE'
         ORDER BY organization_id"
    );

    $statement->execute($reachableIds);

    return array_map(
        'intval',
        $statement->fetchAll(PDO::FETCH_COLUMN)
    );
}
