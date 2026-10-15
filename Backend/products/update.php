<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../changes/change-rules.php';

require_post();

$finalPath = null;
$uploadedPath = null;

try {
    $actor = require_login($pdo);

    if (!can_manufacture($actor)) {
        respond_error(403, 'Only manufacturers can update products.');
    }

    $productId = filter_var(
        $_POST['productId'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($productId === false || $productId === null) {
        respond_field_error(400, field_error('productId', 'Select a valid product.'));
    }

    $statement = $pdo->prepare(
        'SELECT product_id, product_name, image_path, availability_status
         FROM products
         WHERE product_id = ? AND manufacturer_id = ? AND status = \'ACTIVE\'
         LIMIT 1'
    );
    $statement->execute([$productId, $actor['organization_id']]);
    $existing = $statement->fetch();

    if (!$existing) {
        respond_error(404, 'The product was not found in your organisation.');
    }

    $versionStatement = $pdo->prepare(
        'SELECT version_number, barcode
         FROM product_versions
         WHERE product_id = ? AND is_current = 1
         LIMIT 1'
    );
    $versionStatement->execute([$productId]);
    $currentVersion = $versionStatement->fetch();

    if (!$currentVersion) {
        respond_error(409, 'The product has no current version to update.');
    }

    $name = trim((string)($_POST['productName'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $availabilityStatus = trim((string)(
        $_POST['availabilityStatus'] ?? $existing['availability_status'] ?? 'AVAILABLE'
    ));

    if (!in_array($availabilityStatus, ['COMING_SOON', 'AVAILABLE'], true)) {
        respond_field_error(400, field_error('availabilityStatus', 'Select a valid launch availability.'));
    }

    $submittedBarcode = trim((string)($_POST['barcode'] ?? ''));
    $canAssignInitialBarcode = $currentVersion['barcode'] === null
        || $currentVersion['barcode'] === '';

    if ($canAssignInitialBarcode) {
        if ($availabilityStatus === 'AVAILABLE' && $submittedBarcode === '') {
            respond_field_error(400, field_error('barcode', 'A barcode is required for an available product.'));
        }

        if ($submittedBarcode !== '' && !preg_match('/^[0-9]{8,14}$/', $submittedBarcode)) {
            respond_field_error(400, field_error('barcode', 'Enter a barcode containing 8 to 14 digits.'));
        }

        if ($submittedBarcode !== '' && record_exists(
            $pdo,
            'SELECT 1 FROM product_versions WHERE barcode = ? LIMIT 1',
            [$submittedBarcode]
        )) {
            respond_field_error(400, field_error('barcode', 'This barcode is already in use.'));
        }

        $packSize = trim((string)($_POST['packSize'] ?? ''));
        $packaging = trim((string)($_POST['packaging'] ?? ''));
        $effectiveDate = trim((string)($_POST['effectiveDate'] ?? ''));

        if (mb_strlen($packSize) > 100) {
            respond_field_error(400, field_error('packSize', 'Pack size must be 100 characters or fewer.'));
        }

        if (mb_strlen($packaging) > 255) {
            respond_field_error(400, field_error('packaging', 'Packaging description must be 255 characters or fewer.'));
        }

        if (!valid_date($effectiveDate)) {
            respond_field_error(400, field_error('effectiveDate', 'Enter a valid effective date.'));
        }
    }

    if ($name === '' || mb_strlen($name) > 150) {
        respond_field_error(400, field_error('productName', 'Enter a product name of up to 150 characters.'));
    }

    if (mb_strlen($category) > 100) {
        respond_field_error(400, field_error('category', 'Category must be 100 characters or fewer.'));
    }

    if (mb_strlen($description) > 10000) {
        respond_field_error(400, field_error('description', 'Description must be 10,000 characters or fewer.'));
    }

    $image = $_FILES['productImage'] ?? null;

    if ($image !== null && $image['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($image['error'] !== UPLOAD_ERR_OK) {
            respond_field_error(400, field_error('productImage', 'The image upload failed. Please try again.'));
        }

        if ((int)$image['size'] > 5 * 1024 * 1024) {
            respond_field_error(400, field_error('productImage', 'The image must be 5 MB or smaller.'));
        }

        if (!is_uploaded_file($image['tmp_name'])) {
            respond_field_error(400, field_error('productImage', 'The uploaded file could not be verified.'));
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($image['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedTypes[$mime])) {
            respond_field_error(400, field_error('productImage', 'Upload a JPG, PNG, or WebP image.'));
        }

        $imageInfo = @getimagesize($image['tmp_name']);

        if (
            $imageInfo === false ||
            ($imageInfo['mime'] ?? '') !== $mime
        ) {
            respond_field_error(400, field_error('productImage', 'The uploaded file is not a valid supported image.'));
        }

        if ($imageInfo[0] > 6000 || $imageInfo[1] > 6000) {
            respond_field_error(400, field_error('productImage', 'Image dimensions must not exceed 6000 by 6000 pixels.'));
        }

        $uploadDirectory = __DIR__ . '/../../frontend/uploads/products';

        if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
            respond_error(500, 'The product image directory is unavailable.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
        $finalPath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
        $uploadedPath = 'uploads/products/' . $filename;

        if (!move_uploaded_file($image['tmp_name'], $finalPath)) {
            $finalPath = null;
            $uploadedPath = null;
            respond_error(500, 'Unable to save the product image.');
        }
    }

    $oldImagePath = $existing['image_path'];

    if ($uploadedPath !== null) {
        $update = $pdo->prepare(
            'UPDATE products
             SET product_name = ?, category = ?, description = ?, image_path = ?, availability_status = ?
             WHERE product_id = ? AND manufacturer_id = ?'
        );

        $update->execute([
            $name,
            optional_text($category),
            optional_text($description),
            $uploadedPath,
            $availabilityStatus,
            $productId,
            $actor['organization_id']
        ]);
    } else {
        $update = $pdo->prepare(
            'UPDATE products
             SET product_name = ?, category = ?, description = ?, availability_status = ?
             WHERE product_id = ? AND manufacturer_id = ?'
        );

        $update->execute([
            $name,
            optional_text($category),
            optional_text($description),
            $availabilityStatus,
            $productId,
            $actor['organization_id']
        ]);
    }

    if ($canAssignInitialBarcode && $submittedBarcode !== '') {
        $versionUpdate = $pdo->prepare(
            "UPDATE product_versions
             SET barcode = ?, pack_size = ?, packaging_description = ?, effective_from = ?
             WHERE product_id = ? AND version_number = ? AND (barcode IS NULL OR barcode = '')"
        );

        $versionUpdate->execute([
            $submittedBarcode,
            optional_text($packSize),
            optional_text($packaging),
            $effectiveDate,
            $productId,
            $currentVersion['version_number']
        ]);
    }

    record_audit(
        $pdo,
        $actor,
        'PRODUCT_UPDATED',
        'PRODUCT',
        (int)$productId,
        "Updated product {$name}" . ($uploadedPath !== null ? ' and replaced its image.' : '.')
    );

    if (
        $uploadedPath !== null &&
        is_string($oldImagePath) &&
        str_starts_with($oldImagePath, 'uploads/products/')
    ) {
        $oldFilename = basename($oldImagePath);
        $oldFile = __DIR__ . '/../../frontend/uploads/products/' . $oldFilename;

        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    respond(200, [
        'success' => true,
        'message' => 'Product updated successfully.',
        'product_id' => (int)$productId
    ]);

} catch (PDOException $e) {
    if ($finalPath !== null && is_file($finalPath)) {
        @unlink($finalPath);
    }

    log_error('products update', $e);
    respond_error(500, 'Unable to update the product.');

} catch (Throwable $e) {
    if ($finalPath !== null && is_file($finalPath)) {
        @unlink($finalPath);
    }

    log_error('products update', $e);
    respond_error(500, 'Unable to update the product.');
}