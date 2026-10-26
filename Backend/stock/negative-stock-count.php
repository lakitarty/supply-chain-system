<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/stock-rules.php';

try {
    $actor = require_login($pdo);
    stock_require_retailer($actor);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM retailer_stock WHERE retailer_id = ? AND quantity_on_hand < 0");
    $stmt->execute([$actor['organization_id']]);
    $count = (int)$stmt->fetchColumn();

    respond(200, ['success' => true, 'negative_count' => $count]);
} catch (Throwable $e) {
    respond_error(500, 'Unable to check stock alerts.');
}