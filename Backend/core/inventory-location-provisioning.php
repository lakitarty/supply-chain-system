<?php

declare(strict_types=1);

function provision_default_inventory_location(
    PDO $pdo,
    int $organizationId,
    int $branchId
): void {
    $organization = $pdo->prepare(
        'SELECT organization_type
         FROM organizations
         WHERE organization_id = ?
         LIMIT 1'
    );
    $organization->execute([$organizationId]);

    $organizationType = $organization->fetchColumn();

    [$locationCode, $locationName, $locationType] = match ($organizationType) {
        'RETAILER' => ['SHOP-FLOOR', 'Shop Floor', 'SHOP_FLOOR'],
        'SUPPLIER',
        'MANUFACTURER',
        'MANUFACTURER_SUPPLIER' => [
            'CENTRAL-WAREHOUSE',
            'Central Warehouse',
            'CENTRAL_WAREHOUSE'
        ],
        default => throw new RuntimeException(
            'Unsupported organization type for inventory location provisioning.'
        )
    };

    $existing = $pdo->prepare(
        'SELECT inventory_location_id
         FROM inventory_locations
         WHERE organization_id = ?
           AND branch_id = ?
           AND location_code = ?
         LIMIT 1'
    );
    $existing->execute([$organizationId, $branchId, $locationCode]);

    if ($existing->fetchColumn()) {
        return;
    }

    $insert = $pdo->prepare(
        "INSERT INTO inventory_locations
            (organization_id, branch_id, location_code, location_name,
             location_type, is_default, status)
         VALUES (?, ?, ?, ?, ?, 1, 'ACTIVE')"
    );

    $insert->execute([
        $organizationId,
        $branchId,
        $locationCode,
        $locationName,
        $locationType
    ]);
}