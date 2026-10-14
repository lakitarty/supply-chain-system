<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/product-rules.php';

try {
    $actor = require_login($pdo);

    respond(200, [
        'success' => true,
        'products' => fetch_visible_products($pdo, $actor)
    ]);

} catch (PDOException $e) {
    log_error('products list', $e);

    respond_error(500, 'Unable to load the catalogue.');
}