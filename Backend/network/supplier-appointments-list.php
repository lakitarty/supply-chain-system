<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../products/pricing-rules.php';

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Supplier organisation administrator access is required.');
    }

    $statement = $pdo->prepare(
        "SELECT ps.product_supplier_id, p.product_name, manufacturer.organization_name AS manufacturer_name,
                area.area_name, ps.is_exclusive, ps.start_date, ps.end_date, ps.status
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
         INNER JOIN distribution_areas area ON area.area_id = ps.area_id
         WHERE ps.supplier_id = ?
           AND (
               ps.status IN ('PENDING', 'DECLINED')
               OR (ps.status = 'ACTIVE' AND (ps.end_date IS NULL OR ps.end_date >= ?))
           )
         ORDER BY FIELD(ps.status, 'PENDING', 'ACTIVE', 'DECLINED'), p.product_name, ps.product_supplier_id"
    );
    $statement->execute([(int)$actor['organization_id'], nairobi_today()]);

    respond(200, [
        'success' => true,
        'appointments' => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    log_error('supplier appointments list', $e);
    respond_error(500, 'Unable to load your appointments.');
}
