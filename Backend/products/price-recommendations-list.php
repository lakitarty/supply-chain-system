<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/product-rules.php';

try {
    $actor = require_login($pdo);
    $versionId = filter_var($_GET['product_version_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$versionId || $versionId < 1) {
        respond_error(400, 'Product version ID is required.');
    }

    $today = (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');
    $visibleProducts = fetch_visible_products($pdo, $actor);
    $product = null;
    $version = null;

    foreach ($visibleProducts as $candidate) {
        foreach (($candidate['versions'] ?? []) as $candidateVersion) {
            if ((int)$candidateVersion['product_version_id'] === (int)$versionId) {
                $product = $candidate;
                $version = $candidateVersion;
                break 2;
            }
        }
    }

    if ($product === null || $version === null) {
        respond_error(404, 'The product version is not available in your catalogue.');
    }

    $manufacturerTypeStatement = $pdo->prepare(
        'SELECT organization_type FROM organizations WHERE organization_id = ? LIMIT 1'
    );
    $manufacturerTypeStatement->execute([(int)$product['manufacturer_id']]);
    $manufacturerType = (string)$manufacturerTypeStatement->fetchColumn();

    $ownsProduct = (int)$actor['organization_id'] === (int)$product['manufacturer_id']
        && in_array($actor['organization_type'], ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true)
        && $actor['organization_type'] === $manufacturerType;

    if ($ownsProduct) {
        $visibleTypes = ['RRP', 'RSP'];
    } elseif ($actor['organization_type'] === 'RETAILER') {
        $visibleTypes = ['RRP'];
    } elseif (in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        $visibleTypes = ['RSP'];
    } else {
        $visibleTypes = [];
    }

    $currentRecommendations = ['RRP' => null, 'RSP' => null];
    $history = [];

    if ($visibleTypes !== []) {
        $statement = $pdo->prepare(
            'SELECT r.recommendation_id, r.recommendation_type, r.amount, r.currency,
                    r.effective_from, r.created_at,
                    CONCAT(u.first_name, \' \', u.last_name) AS published_by_name
             FROM product_price_recommendations r
             INNER JOIN users u ON u.user_id = r.created_by_user_id
             WHERE r.product_version_id = ?
               AND r.recommendation_type = ?
               AND r.effective_from <= ?
             ORDER BY r.effective_from DESC, r.created_at DESC, r.recommendation_id DESC
             LIMIT 1'
        );
        foreach ($visibleTypes as $type) {
            $statement->execute([(int)$versionId, $type, $today]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            $currentRecommendations[$type] = $row ?: null;
        }
    }

    $canManage = $ownsProduct && $actor['role'] === 'ADMIN';
    if ($ownsProduct && $visibleTypes !== []) {
        $statement = $pdo->prepare(
            'SELECT r.recommendation_id, r.recommendation_type, r.amount, r.currency,
                    r.effective_from, r.created_at,
                    CONCAT(u.first_name, \' \', u.last_name) AS published_by_name
             FROM product_price_recommendations r
             INNER JOIN users u ON u.user_id = r.created_by_user_id
             WHERE r.product_version_id = ?
             ORDER BY r.effective_from DESC, r.created_at DESC, r.recommendation_id DESC
             LIMIT 12'
        );
        $statement->execute([(int)$versionId]);
        $history = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    $actualSellingPrice = null;
    $actualSellingPriceProblem = false;
    $sellingStatement = $pdo->prepare(
        "SELECT price_amount
         FROM product_prices
         WHERE organization_id = ?
           AND product_id = ?
           AND product_version_id = ?
           AND price_type = 'SELLING'
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC, price_id DESC
         LIMIT 2"
    );
    $sellingStatement->execute([
        (int)$actor['organization_id'],
        (int)$product['product_id'],
        (int)$versionId,
        $today,
        $today
    ]);
    $sellingRows = $sellingStatement->fetchAll(PDO::FETCH_COLUMN);
    if (count($sellingRows) === 1) {
        $actualSellingPrice = (string)$sellingRows[0];
    } elseif (count($sellingRows) > 1) {
        $actualSellingPriceProblem = true;
    }

    $comparisonType = null;
    if (!$ownsProduct && $actor['organization_type'] === 'RETAILER') {
        $comparisonType = 'RRP';
    } elseif (!$ownsProduct && in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        $comparisonType = 'RSP';
    }

    respond(200, [
        'success' => true,
        'product_id' => (int)$product['product_id'],
        'product_version_id' => (int)$versionId,
        'product_owner' => $ownsProduct,
        'can_manage' => $canManage,
        'visible_types' => $visibleTypes,
        'recommendations' => array_intersect_key($currentRecommendations, array_flip($visibleTypes)),
        'history' => $canManage ? $history : [],
        'comparison_type' => $comparisonType,
        'actual_selling_price' => $actualSellingPrice,
        'actual_selling_price_problem' => $actualSellingPriceProblem,
        'today' => $today
    ]);
} catch (PDOException $e) {
    log_error('product price recommendations list', $e);
    respond_error(500, 'Unable to load pricing guidance.');
} catch (Throwable $e) {
    log_error('product price recommendations list unexpected', $e);
    respond_error(500, 'Unable to load pricing guidance.');
}
