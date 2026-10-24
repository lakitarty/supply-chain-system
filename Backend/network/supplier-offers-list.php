<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Supplier organisation administrator access is required.');
    }

    $supplierId = (int)$actor['organization_id'];

    $eligibilityStatement = $pdo->prepare(
        "SELECT DISTINCT
                p.product_id, p.product_name,
                p.manufacturer_id, manufacturer.organization_name AS manufacturer_name,
                pv.product_version_id, pv.version_number, pv.barcode,
                pv.pack_size, pv.packaging_description,
                ps.area_id, area.area_name,
                ps.start_date AS appointment_start,
                ps.end_date AS appointment_end
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN product_versions pv ON pv.product_id = p.product_id
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
         INNER JOIN organizations supplier ON supplier.organization_id = ps.supplier_id
         INNER JOIN organization_relationships r
            ON r.source_organization_id = p.manufacturer_id
           AND r.target_organization_id = ps.supplier_id
           AND r.status = 'ACTIVE'
         INNER JOIN distribution_areas area ON area.area_id = ps.area_id
         WHERE ps.supplier_id = ?
           AND ps.area_id IS NOT NULL
           AND ps.status = 'ACTIVE'
           AND p.status = 'ACTIVE'
           AND manufacturer.status = 'ACTIVE'
           AND supplier.status = 'ACTIVE'
           AND area.status = 'ACTIVE'
         ORDER BY p.product_name, pv.version_number DESC, area.area_name, ps.start_date DESC"
    );
    $eligibilityStatement->execute([$supplierId]);

    $offerStatement = $pdo->prepare(
        "SELECT o.supplier_product_offer_id, o.product_id, o.product_version_id,
                o.area_id, o.unit_price, o.price_basis, o.currency_code,
                o.effective_from, o.effective_to, o.status, o.offer_note,
                o.created_at, p.product_name, p.manufacturer_id,
                manufacturer.organization_name AS manufacturer_name,
                pv.version_number, pv.barcode, pv.pack_size,
                pv.packaging_description, area.area_name
         FROM supplier_product_offers o
         INNER JOIN products p ON p.product_id = o.product_id
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
         INNER JOIN product_versions pv ON pv.product_version_id = o.product_version_id
         INNER JOIN distribution_areas area ON area.area_id = o.area_id
         WHERE o.supplier_id = ?
         ORDER BY o.effective_from DESC, p.product_name, area.area_name"
    );
    $offerStatement->execute([$supplierId]);

    respond(200, [
        'success' => true,
        'eligibilities' => $eligibilityStatement->fetchAll(PDO::FETCH_ASSOC),
        'offers' => $offerStatement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    log_error('supplier product offers list', $e);
    respond_error(500, 'Unable to load supplier product offers.');
} catch (Throwable $e) {
    log_error('supplier product offers list unexpected', $e);
    respond_error(500, 'Unable to load supplier product offers.');
}
