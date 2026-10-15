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
        'SELECT product_id, product_name, image_path
         FROM products
         WHERE product_id = ? AND manufacturer_id = ? AND status = \'ACTIVE\'
         LIMIT 1'
    );
    $statement->execute([$productId, $actor['organization_id']]);
    $existing = $statement->fetch();

    if (!$existing) {
        respond_error(404, 'The product was not found in your organisation.');
    }

    $name = trim((string)($_POST['productName'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

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
             SET product_name = ?, category = ?, description = ?, image_path = ?
             WHERE product_id = ? AND manufacturer_id = ?'
        );

        $update->execute([
            $name,
            optional_text($category),
            optional_text($description),
            $uploadedPath,
            $productId,
            $actor['organization_id']
        ]);
    } else {
        $update = $pdo->prepare(
            'UPDATE products
             SET product_name = ?, category = ?, description = ?
             WHERE product_id = ? AND manufacturer_id = ?'
        );

        $update->execute([
            $name,
            optional_text($category),
            optional_text($description),
            $productId,
            $actor['organization_id']
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