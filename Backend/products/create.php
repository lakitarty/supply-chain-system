<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../changes/change-rules.php';

require_post();

$uploadedPath = null;
$finalPath = null;

try {
    $actor = require_login($pdo);

    if (!can_manufacture($actor)) {
        respond_error(403, 'Only manufacturers can create products.');
    }

    $name = trim((string)($_POST['productName'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $barcode = trim((string)($_POST['barcode'] ?? ''));
    $packSize = trim((string)($_POST['packSize'] ?? ''));
    $packaging = trim((string)($_POST['packaging'] ?? ''));
    $effectiveDate = trim((string)($_POST['effectiveDate'] ?? ''));

    if ($name === '' || mb_strlen($name) > 150) {
        respond_field_error(400, field_error('productName', 'Enter a product name of up to 150 characters.'));
    }

    if (mb_strlen($category) > 100) {
        respond_field_error(400, field_error('category', 'Category must be 100 characters or fewer.'));
    }

    if (mb_strlen($description) > 10000) {
        respond_field_error(400, field_error('description', 'Description must be 10,000 characters or fewer.'));
    }

    if (!preg_match('/^[0-9]{8,14}$/', $barcode)) {
        respond_field_error(400, field_error('barcode', 'Enter a barcode containing 8 to 14 digits.'));
    }

    if (record_exists($pdo, 'SELECT 1 FROM product_versions WHERE barcode = ? LIMIT 1', [$barcode])) {
        respond_field_error(400, field_error('barcode', 'This barcode is already in use.'));
    }

    if (mb_strlen($packSize) > 100) {
        respond_field_error(400, field_error('packSize', 'Pack size must be 100 characters or fewer.'));
    }

    if (mb_strlen($packaging) > 255) {
        respond_field_error(400, field_error('packaging', 'Packaging description must be 255 characters or fewer.'));
    }

    if (!valid_date($effectiveDate)) {
        respond_field_error(400, field_error('effectiveDate', 'Enter a valid effective date.'));
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

        if ($imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
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

    $pdo->beginTransaction();

    $productStatement = $pdo->prepare(
        'INSERT INTO products
            (manufacturer_id, product_name, category, description, image_path, status)
         VALUES (?, ?, ?, ?, ?, \'ACTIVE\')'
    );

    $productStatement->execute([
        $actor['organization_id'],
        $name,
        optional_text($category),
        optional_text($description),
        $uploadedPath
    ]);

    $productId = (int)$pdo->lastInsertId();

    $versionStatement = $pdo->prepare(
        'INSERT INTO product_versions
            (product_id, version_number, barcode, pack_size, packaging_description, effective_from, is_current)
         VALUES (?, 1, ?, ?, ?, ?, 1)'
    );

    $versionStatement->execute([
        $productId,
        $barcode,
        optional_text($packSize),
        optional_text($packaging),
        $effectiveDate
    ]);

    record_audit(
        $pdo,
        $actor,
        'PRODUCT_CREATED',
        'PRODUCT',
        $productId,
        "Created product {$name} with initial barcode {$barcode}."
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Product created successfully.',
        'product_id' => $productId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($finalPath !== null && is_file($finalPath)) {
        @unlink($finalPath);
    }

    log_error('products create', $e);
    respond_error(500, 'Unable to create the product. Check that the barcode is unique and try again.');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($finalPath !== null && is_file($finalPath)) {
        @unlink($finalPath);
    }

    log_error('products create', $e);
    respond_error(500, 'Unable to create the product.');
}