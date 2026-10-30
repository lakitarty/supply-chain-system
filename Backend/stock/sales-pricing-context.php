<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/stock-rules.php';

try {
    $actor = require_login($pdo);
    stock_require_retailer($actor);

    $branchId = stock_optional_id($_GET['branchId'] ?? null);
    $productId = stock_optional_id($_GET['productId'] ?? null);
    $versionId = stock_optional_id($_GET['productVersionId'] ?? null);
    $saleDate = trim((string)($_GET['saleDate'] ?? ''));

    if ($branchId === null) {
        respond_field_error(400, field_error('branchId', 'Select a branch.'));
    }
    if ($productId === null) {
        respond_field_error(400, field_error('productId', 'Select a product.'));
    }
    if ($versionId === null) {
        respond_field_error(400, field_error('productVersionId', 'Select a product package version.'));
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $saleDate) || !valid_date($saleDate) || $saleDate > date('Y-m-d')) {
        respond_field_error(400, field_error('saleDate', 'Choose a valid sale date that is not in the future.'));
    }

    $branch = stock_resolve_branch($pdo, $actor, $branchId);
    $product = stock_validate_product_version($pdo, $actor, $productId, $versionId);

    $price = stock_effective_price(
        $pdo,
        (int)$actor['organization_id'],
        (int)$branch['branch_id'],
        $productId,
        $versionId,
        $saleDate
    );

    $classificationStatement = $pdo->prepare(
        "SELECT product_tax_classification_id, tax_category
         FROM product_tax_classifications
         WHERE product_version_id = ? AND manufacturer_id = ?
           AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 2"
    );
    $classificationStatement->execute([
        $versionId,
        (int)$product['manufacturer_id'],
        $saleDate,
        $saleDate
    ]);
    $classifications = $classificationStatement->fetchAll(PDO::FETCH_ASSOC);

    $tax = [
        'status' => 'MISSING_CLASSIFICATION',
        'tax_category' => null,
        'rate_percent' => null,
        'taxable_supply' => null
    ];
    if (count($classifications) > 1) {
        $tax['status'] = 'AMBIGUOUS_CLASSIFICATION';
    } elseif (count($classifications) === 1) {
        $category = (string)$classifications[0]['tax_category'];
        $tax['tax_category'] = $category;
        $rateStatement = $pdo->prepare(
            "SELECT rate_percent, taxable_supply
             FROM tax_rate_schedule
             WHERE tax_category = ? AND effective_from <= ?
               AND (effective_to IS NULL OR effective_to >= ?)
             ORDER BY effective_from DESC
             LIMIT 2"
        );
        $rateStatement->execute([$category, $saleDate, $saleDate]);
        $rates = $rateStatement->fetchAll(PDO::FETCH_ASSOC);
        if (count($rates) === 0) {
            $tax['status'] = 'MISSING_RATE';
        } elseif (count($rates) > 1) {
            $tax['status'] = 'AMBIGUOUS_RATE';
        } else {
            $tax['status'] = 'CALCULATED';
            $tax['rate_percent'] = (string)$rates[0]['rate_percent'];
            $tax['taxable_supply'] = (int)$rates[0]['taxable_supply'];
        }
    }

    respond(200, [
        'success' => true,
        'sale_date' => $saleDate,
        'branch' => [
            'branch_id' => (int)$branch['branch_id'],
            'branch_name' => (string)$branch['branch_name'],
            'area_name' => $branch['area_name'] !== null ? (string)$branch['area_name'] : null
        ],
        'product' => [
            'product_id' => (int)$product['product_id'],
            'product_name' => (string)$product['product_name'],
            'manufacturer_id' => (int)$product['manufacturer_id'],
            'version_id' => (int)$product['product_version_id'],
            'version_number' => (int)$product['version_number']
        ],
        'price' => $price,
        'tax' => $tax
    ]);
} catch (PDOException $e) {
    log_error('stock sales pricing context', $e);
    respond_error(500, 'Unable to load effective selling price and tax information.');
} catch (Throwable $e) {
    log_error('stock sales pricing context unexpected', $e);
    respond_error(500, 'Unable to load effective selling price and tax information.');
}
