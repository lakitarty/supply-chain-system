<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Supplier organisation administrator access is required.');
    }

    $areasStatement = $pdo->query(
        "SELECT area_id, area_name
         FROM distribution_areas
         WHERE status = 'ACTIVE'
         ORDER BY area_name"
    );
    $areas = $areasStatement->fetchAll(PDO::FETCH_ASSOC);

    $coverageStatement = $pdo->prepare(
        "SELECT sda.supplier_area_id,
                sda.area_id,
                da.area_name,
                sda.start_date,
                sda.end_date,
                sda.status,
                sda.created_at
         FROM supplier_distribution_areas sda
         INNER JOIN distribution_areas da ON da.area_id = sda.area_id
         WHERE sda.supplier_id = ?
         ORDER BY sda.start_date DESC, da.area_name"
    );
    $coverageStatement->execute([(int)$actor['organization_id']]);

    respond(200, [
        'success' => true,
        'areas' => $areas,
        'coverage' => $coverageStatement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    log_error('supplier coverage list', $e);
    respond_error(500, 'Unable to load supplier coverage.');
}