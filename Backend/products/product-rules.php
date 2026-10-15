<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../network/network-rules.php';

function visible_manufacturer_ids(PDO $pdo, array $actor): array
{
    return array_merge(
        [$actor['organization_id']],
        reachable_organization_ids($pdo, $actor['organization_id'], 'upstream')
    );
}

function fetch_visible_products(PDO $pdo, array $actor): array
{
    $manufacturerIds = visible_manufacturer_ids($pdo, $actor);

    $statement = $pdo->prepare(
                'SELECT p.product_id,
                p.manufacturer_id,
                p.product_name,
                p.category,
                p.description,
                p.image_path,
                o.organization_name AS manufacturer_name
         FROM products p
         INNER JOIN organizations o
            ON o.organization_id = p.manufacturer_id
         WHERE p.status = \'ACTIVE\'
           AND p.manufacturer_id IN (' . sql_placeholders($manufacturerIds) . ')
         ORDER BY p.product_name'
    );

    $statement->execute($manufacturerIds);
    $products = $statement->fetchAll();

    if ($products === []) {
        return [];
    }

    $productIds = array_column($products, 'product_id');

    $statement = $pdo->prepare(
        'SELECT product_id, version_number, barcode, pack_size, packaging_description,
                effective_from, effective_to, is_current
         FROM product_versions
         WHERE product_id IN (' . sql_placeholders($productIds) . ')
         ORDER BY product_id, version_number DESC'
    );

    $statement->execute($productIds);

    $versionsByProduct = [];

    foreach ($statement->fetchAll() as $version) {
        $versionsByProduct[$version['product_id']][] = $version;
    }

    return array_map(
        fn(array $product) => $product + ['versions' => $versionsByProduct[$product['product_id']] ?? []],
        $products
    );
}
