<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_login($pdo);
    $versionId = filter_var($_GET['product_version_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$versionId) respond_error(400, 'Product version ID is required.');

    $stmt = $pdo->prepare("
        SELECT pp.*, o.organization_name, o.organization_type 
        FROM product_prices pp
        JOIN organizations o ON o.organization_id = pp.organization_id
        WHERE pp.product_version_id = ?
          AND pp.organization_id = ? 
          AND (pp.effective_to IS NULL OR pp.effective_to >= CURDATE())
        ORDER BY pp.price_type DESC, pp.effective_from DESC
    ");
    $stmt->execute([$versionId, $actor['organization_id']]);
    
    respond(200, ['success' => true, 'prices' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    respond_error(500, 'Error loading prices.');
}