<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../products/product-rules.php';

try {
    $actor = require_login($pdo);

    if ($actor['organization_type'] !== 'RETAILER') {
        respond_error(403, 'Retailer organisation access is required.');
    }
    if ($actor['role'] === 'BRANCH_ADMIN') {
        $actor = require_branch_admin($pdo);
    } elseif ($actor['role'] !== 'ADMIN') {
        respond_error(403, 'Retailer administrator or branch administrator access is required.');
    }

    $retailerId = (int)$actor['organization_id'];
    $branchSql = "SELECT b.branch_id, b.branch_name, b.location, b.area_id,
                         area.area_name, b.is_main_branch, b.status
                  FROM branches b
                  LEFT JOIN distribution_areas area ON area.area_id = b.area_id
                  WHERE b.organization_id = ? AND b.status = 'ACTIVE'";
    $branchParams = [$retailerId];
    if ($actor['role'] === 'BRANCH_ADMIN') {
        $branchSql .= ' AND b.branch_id = ?';
        $branchParams[] = (int)$actor['branch_id'];
    }
    $branchSql .= ' ORDER BY b.is_main_branch DESC, b.branch_name';
    $branchStatement = $pdo->prepare($branchSql);
    $branchStatement->execute($branchParams);
    $branches = $branchStatement->fetchAll(PDO::FETCH_ASSOC);

    // Use the same product/package-version membership as the retailer Catalogue.
    $catalogueProducts = fetch_visible_products($pdo, $actor);
    $catalogueVersionIds = [];

    foreach ($catalogueProducts as $catalogueProduct) {
        foreach (($catalogueProduct['versions'] ?? []) as $catalogueVersion) {
            $versionId = (int)($catalogueVersion['product_version_id'] ?? 0);
            if ($versionId > 0) {
                $catalogueVersionIds[$versionId] = $versionId;
            }
        }
    }

    $catalogueVersionIds = array_values($catalogueVersionIds);
    $versions = [];

    if ($catalogueVersionIds !== []) {
        $versionStatement = $pdo->prepare(
            "SELECT p.product_id, p.product_name, p.manufacturer_id,
                    manufacturer.organization_name AS manufacturer_name,
                    pv.product_version_id, pv.version_number, pv.barcode,
                    pv.pack_size, pv.packaging_description,
                    pv.effective_from AS version_effective_from,
                    pv.effective_to AS version_effective_to, pv.is_current
             FROM products p
             INNER JOIN organizations manufacturer
                ON manufacturer.organization_id = p.manufacturer_id
             INNER JOIN product_versions pv ON pv.product_id = p.product_id
             WHERE p.status = 'ACTIVE'
               AND pv.product_version_id IN (" . sql_placeholders($catalogueVersionIds) . ")
             ORDER BY p.product_name, pv.version_number DESC"
        );

        $versionStatement->execute($catalogueVersionIds);
        $versions = $versionStatement->fetchAll(PDO::FETCH_ASSOC);
    }
    $priceSql =
        "SELECT bp.retailer_branch_price_id, bp.retailer_id, bp.branch_id,
                bp.product_id, bp.product_version_id, bp.selling_price,
                bp.price_basis, bp.effective_from, bp.effective_to,
                bp.status, bp.created_at, b.branch_name, area.area_name,
                p.product_name, manufacturer.organization_name AS manufacturer_name,
                pv.version_number, pv.barcode, pv.pack_size, pv.packaging_description
         FROM retailer_branch_prices bp
         LEFT JOIN branches b ON b.branch_id = bp.branch_id
         LEFT JOIN distribution_areas area ON area.area_id = b.area_id
         INNER JOIN products p ON p.product_id = bp.product_id
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
         INNER JOIN product_versions pv ON pv.product_version_id = bp.product_version_id
         WHERE bp.retailer_id = ?";
    $priceParams = [$retailerId];
    if ($actor['role'] === 'BRANCH_ADMIN') {
        $priceSql .= ' AND bp.branch_id = ?';
        $priceParams[] = (int)$actor['branch_id'];
    }
    $priceSql .= ' ORDER BY bp.effective_from DESC, b.branch_name, p.product_name';
    $priceStatement = $pdo->prepare($priceSql);
    $priceStatement->execute($priceParams);

    respond(200, [
        'success' => true,
        'can_manage_all_branches' => $actor['role'] === 'ADMIN',
        'branches' => $branches,
        'versions' => $versions,
        'prices' => $priceStatement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    log_error('retailer branch prices list', $e);
    respond_error(500, 'Unable to load branch selling prices.');
} catch (Throwable $e) {
    log_error('retailer branch prices list unexpected', $e);
    respond_error(500, 'Unable to load branch selling prices.');
}
