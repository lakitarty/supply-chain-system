<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../products/pricing-rules.php';
require_once __DIR__ . '/../products/product-rules.php';

require_post();

try {
    $actor = require_login($pdo);
    if ($actor['organization_type'] !== 'RETAILER') {
        respond_error(403, 'Retailer organisation access is required.');
    }
    if ($actor['role'] === 'BRANCH_ADMIN') {
        $actor = require_branch_admin($pdo);
    } elseif ($actor['role'] !== 'ADMIN') {
        respond_error(403, 'Retailer administrator or branch administrator access is required.');
    }

    $body = read_json_body();
    $branchId = filter_var($body['branchId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $productId = filter_var($body['productId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $versionId = filter_var($body['productVersionId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $priceText = trim((string)($body['sellingPrice'] ?? ''));
    $priceBasis = trim((string)($body['priceBasis'] ?? ''));
    $effectiveFrom = trim((string)($body['effectiveFrom'] ?? ''));
    $effectiveToText = trim((string)($body['effectiveTo'] ?? ''));
    $effectiveTo = $effectiveToText === '' ? null : $effectiveToText;

    if ($branchId === false || $branchId === null) {
        respond_field_error(400, field_error('branchId', 'Select a branch.'));
    }
    if ($productId === false || $productId === null) {
        respond_field_error(400, field_error('productId', 'Select a product.'));
    }
    if ($versionId === false || $versionId === null) {
        respond_field_error(400, field_error('productVersionId', 'Select a package version.'));
    }
    if (parse_price_amount($priceText) === null) {
        respond_field_error(400, field_error('sellingPrice', 'Enter a selling price above zero with at most two decimal places.'));
    }
    if (!in_array($priceBasis, ['VAT_INCLUSIVE', 'VAT_EXCLUSIVE'], true)) {
        respond_field_error(400, field_error('priceBasis', 'Choose whether the selling price includes VAT.'));
    }
    if (!valid_date($effectiveFrom)) {
        respond_field_error(400, field_error('effectiveFrom', 'Enter a valid price start date.'));
    }
    if ($effectiveTo !== null && !valid_date($effectiveTo)) {
        respond_field_error(400, field_error('effectiveTo', 'Enter a valid end date or leave it blank.'));
    }
    if ($effectiveTo !== null && $effectiveTo < $effectiveFrom) {
        respond_field_error(400, field_error('effectiveTo', 'The price end date cannot be earlier than its start date.'));
    }

    $retailerId = (int)$actor['organization_id'];
    $branchId = (int)$branchId;
    if ($actor['role'] === 'BRANCH_ADMIN' && $branchId !== (int)$actor['branch_id']) {
        respond_error(403, 'Branch administrators can only manage prices for their assigned branch.');
    }

    $manufacturerIds = visible_manufacturer_ids($pdo, $actor);
    $manufacturerPlaceholders = sql_placeholders($manufacturerIds);
    $unitPrice = number_format((float)$priceText, 2, '.', '');
    $candidateEnd = $effectiveTo ?? '9999-12-31';

    $pdo->beginTransaction();

    $branchStatement = $pdo->prepare(
        "SELECT branch_id, branch_name
         FROM branches
         WHERE branch_id = ? AND organization_id = ? AND status = 'ACTIVE'
         LIMIT 1 FOR UPDATE"
    );
    $branchStatement->execute([$branchId, $retailerId]);
    $branch = $branchStatement->fetch(PDO::FETCH_ASSOC);
    if (!$branch) {
        $pdo->rollBack();
        respond_field_error(400, field_error('branchId', 'Select an active branch belonging to your organisation.'));
    }

    $productStatement = $pdo->prepare(
        "SELECT p.product_id, p.product_name, pv.product_version_id,
                pv.version_number, pv.barcode, manufacturer.organization_name AS manufacturer_name
         FROM products p
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
         INNER JOIN product_versions pv ON pv.product_id = p.product_id
         WHERE p.product_id = ? AND pv.product_version_id = ?
           AND p.status = 'ACTIVE' AND manufacturer.status = 'ACTIVE'
           AND p.manufacturer_id IN ({$manufacturerPlaceholders})
         LIMIT 1 FOR UPDATE"
    );
    $productStatement->execute(array_merge([(int)$productId, (int)$versionId], $manufacturerIds));
    $product = $productStatement->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        $pdo->rollBack();
        respond_field_error(400, field_error('productVersionId', 'This product version is not in your organisation’s visible manufacturer catalogue.'));
    }

    $overlapStatement = $pdo->prepare(
        "SELECT retailer_branch_price_id
         FROM retailer_branch_prices
         WHERE retailer_id = ? AND branch_id = ? AND product_id = ? AND product_version_id = ?
           AND status = 'ACTIVE'
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         LIMIT 1 FOR UPDATE"
    );
    $overlapStatement->execute([
        $retailerId,
        $branchId,
        (int)$productId,
        (int)$versionId,
        $candidateEnd,
        $effectiveFrom
    ]);
    if ($overlapStatement->fetchColumn() !== false) {
        $pdo->rollBack();
        respond_error(409, 'An active selling-price period already overlaps these dates for this branch and product version. End the earlier period before adding another price.');
    }

    $insert = $pdo->prepare(
        "INSERT INTO retailer_branch_prices
            (retailer_id, branch_id, product_id, product_version_id, selling_price,
             price_basis, effective_from, effective_to, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', ?)"
    );
    $insert->execute([
        $retailerId,
        $branchId,
        (int)$productId,
        (int)$versionId,
        $unitPrice,
        $priceBasis,
        $effectiveFrom,
        $effectiveTo,
        (int)$actor['user_id']
    ]);

    $priceId = (int)$pdo->lastInsertId();
    record_audit(
        $pdo,
        $actor,
        'RETAILER_BRANCH_PRICE_CREATED',
        'RETAILER_BRANCH_PRICE',
        $priceId,
        "Set {$branch['branch_name']} selling price to KSh {$unitPrice} {$priceBasis} for {$product['product_name']} version {$product['version_number']} from {$effectiveFrom}" . ($effectiveTo !== null ? " through {$effectiveTo}." : ' with no planned end date.')
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Branch selling price saved.',
        'price_id' => $priceId
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'This price conflicts with an existing record. Refresh and review the price periods.');
    }
    log_error('retailer branch price save', $e);
    respond_error(500, 'Unable to save the branch selling price.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('retailer branch price save unexpected', $e);
    respond_error(500, 'Unable to save the branch selling price.');
}
