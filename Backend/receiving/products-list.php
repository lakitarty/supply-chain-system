<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/receiving-rules.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        respond_error(405, 'Method not allowed.');
    }

    $actor = require_login($pdo);

    if (!in_array($actor['role'] ?? '', ['ADMIN', 'BRANCH_ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Your account does not have access to receiving.');
    }

    $grnId = filter_var(
        $_GET['grnId'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($grnId === false) {
        respond_error(400, 'A valid GRN ID is required.');
    }

    $grnStatement = $pdo->prepare(
        "SELECT branch_id, supplier_id
         FROM goods_received_notes
         WHERE grn_id = ?
           AND retailer_id = ?
         LIMIT 1"
    );

    $grnStatement->execute([(int)$grnId, (int)$actor['organization_id']]);
    $grn = $grnStatement->fetch(PDO::FETCH_ASSOC);

    if (!$grn) {
        respond_error(404, 'The requested GRN was not found.');
    }

    receiving_resolve_branch($pdo, $actor, (int)$grn['branch_id']);

    $productsStatement = $pdo->prepare(
        "SELECT
            p.product_id,
            p.product_name,
            pv.barcode,
            pv.pack_size
         FROM products p
         INNER JOIN product_versions pv
            ON pv.product_id = p.product_id
           AND pv.is_current = 1
         WHERE p.status = 'ACTIVE'
           AND EXISTS (
                SELECT 1
                FROM supplier_product_offers o
                WHERE o.supplier_id = ?
                  AND o.product_id = p.product_id
                  AND o.status = 'ACTIVE'
           )
         ORDER BY p.product_name"
    );

    $productsStatement->execute([(int)$grn['supplier_id']]);
    $products = $productsStatement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as &$product) {
        $product['product_id'] = (int)$product['product_id'];
    }
    unset($product);

    respond(200, [
        'success' => true,
        'products' => $products
    ]);

} catch (PDOException $e) {
    log_error('GRN product lookup', $e);
    respond_error(500, 'Unable to load the supplier products.');
} catch (Throwable $e) {
    log_error('GRN product lookup unexpected', $e);
    respond_error(500, 'Unable to load the supplier products.');
}