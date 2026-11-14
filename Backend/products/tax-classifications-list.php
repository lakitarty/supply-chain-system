<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/product-rules.php';

try {
    $actor = require_login($pdo);
    $canDeclare = $actor['role'] === 'ADMIN'
        && in_array($actor['organization_type'], ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true);
    $organizationId = (int)$actor['organization_id'];
    $visibleIds = visible_manufacturer_ids($pdo, $actor);

    $productStatement = $pdo->prepare(
        "SELECT p.product_id, p.product_name, p.category,
                pv.product_version_id, pv.version_number, pv.barcode,
                pv.pack_size, pv.packaging_description, pv.effective_from AS version_effective_from,
                pv.effective_to AS version_effective_to, pv.is_current
         FROM products p
         INNER JOIN product_versions pv ON pv.product_id = p.product_id
         WHERE p.manufacturer_id = ? AND p.status = 'ACTIVE'
         ORDER BY p.product_name, pv.version_number DESC"
    );
    $productStatement->execute([$organizationId]);
    $versions = $productStatement->fetchAll(PDO::FETCH_ASSOC);

    $classificationStatement = $pdo->prepare(
        "SELECT c.product_tax_classification_id, c.product_version_id,
                c.tax_category, c.effective_from, c.effective_to,
                c.declaration_note, c.created_at,
                p.product_id, p.product_name,
                pv.version_number, pv.barcode, pv.pack_size,
                pv.packaging_description,
                (SELECT trs.rate_percent
                 FROM tax_rate_schedule trs
                 WHERE trs.tax_category = c.tax_category
                   AND trs.effective_from <= c.effective_from
                   AND (trs.effective_to IS NULL OR trs.effective_to >= c.effective_from)
                 ORDER BY trs.effective_from DESC
                 LIMIT 1) AS rate_percent_at_start,
                (SELECT trs.taxable_supply
                 FROM tax_rate_schedule trs
                 WHERE trs.tax_category = c.tax_category
                   AND trs.effective_from <= c.effective_from
                   AND (trs.effective_to IS NULL OR trs.effective_to >= c.effective_from)
                 ORDER BY trs.effective_from DESC
                 LIMIT 1) AS taxable_supply_at_start
         FROM product_tax_classifications c
         INNER JOIN product_versions pv ON pv.product_version_id = c.product_version_id
         INNER JOIN products p ON p.product_id = pv.product_id
         WHERE p.manufacturer_id IN (" . sql_placeholders($visibleIds) . ")
         ORDER BY p.product_name, pv.version_number DESC, c.effective_from DESC"
    );
    $classificationStatement->execute($visibleIds);
    $classifications = $classificationStatement->fetchAll(PDO::FETCH_ASSOC);

    $rateStatement = $pdo->query(
        "SELECT tax_rate_id, tax_category, rate_percent, taxable_supply,
                effective_from, effective_to, source_note
         FROM tax_rate_schedule
         ORDER BY tax_category, effective_from DESC"
    );

    respond(200, [
        'success' => true,
        'versions' => $versions,
        'can_declare' => $canDeclare,
        'classifications' => $classifications,
        'tax_rates' => $rateStatement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    log_error('product tax classifications list', $e);
    respond_error(500, 'Unable to load product tax classifications.');
} catch (Throwable $e) {
    log_error('product tax classifications list unexpected', $e);
    respond_error(500, 'Unable to load product tax classifications.');
}
