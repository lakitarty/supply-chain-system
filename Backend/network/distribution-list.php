<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Manufacturer organisation administrator access is required.');
    }

    $organizationId = (int)$actor['organization_id'];

    $statement = $pdo->prepare(
        "SELECT product_id, product_name, category
         FROM products
         WHERE manufacturer_id = ? AND status = 'ACTIVE'
         ORDER BY product_name"
    );
    $statement->execute([$organizationId]);
    $products = $statement->fetchAll(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare(
        "SELECT DISTINCT
                supplier.organization_id AS supplier_id,
                supplier.organization_name AS supplier_name,
                supplier.organization_type AS supplier_type
         FROM organization_relationships r
         INNER JOIN organizations supplier
            ON supplier.organization_id = r.target_organization_id
         WHERE r.source_organization_id = ?
           AND r.status = 'ACTIVE'
           AND supplier.status = 'ACTIVE'
           AND supplier.organization_type IN ('SUPPLIER', 'MANUFACTURER_SUPPLIER')
         ORDER BY supplier.organization_name"
    );
    $statement->execute([$organizationId]);
    $suppliers = $statement->fetchAll(PDO::FETCH_ASSOC);

    $statement = $pdo->query(
        "SELECT area_id, area_name
         FROM distribution_areas
         WHERE status = 'ACTIVE'
         ORDER BY area_name"
    );
    $areas = $statement->fetchAll(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare(
        "SELECT ps.product_supplier_id,
                ps.product_id,
                p.product_name,
                ps.supplier_id,
                supplier.organization_name AS supplier_name,
                ps.area_id,
                area.area_name,
                ps.is_exclusive,
                ps.start_date,
                ps.end_date,
                ps.status,
                ps.created_at
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN organizations supplier ON supplier.organization_id = ps.supplier_id
         LEFT JOIN distribution_areas area ON area.area_id = ps.area_id
         WHERE p.manufacturer_id = ?
         ORDER BY ps.start_date DESC, p.product_name, area.area_name, supplier.organization_name"
    );
    $statement->execute([$organizationId]);
    $appointments = $statement->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'products' => $products,
        'suppliers' => $suppliers,
        'areas' => $areas,
        'appointments' => $appointments
    ]);

} catch (PDOException $e) {
    log_error('regional distribution list', $e);
    respond_error(500, 'Unable to load regional distribution appointments.');
}