<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/organization-rules.php';
require_once __DIR__ . '/logo-rules.php';

require_post();

$newFile = null;

try {
    $actor = require_admin($pdo);
    $organization = find_organization($pdo, $actor);

    if (!$organization) {
        respond_error(404, 'Organisation not found.');
    }

    if (!isset($_FILES['logo']) || !is_array($_FILES['logo'])) {
        respond_error(400, 'Choose a PNG, JPEG or WebP image.');
    }

    $upload = $_FILES['logo'];

    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $message = match ((int)$upload['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image exceeds the server upload limit.',
            UPLOAD_ERR_PARTIAL => 'The image upload was incomplete. Try again.',
            UPLOAD_ERR_NO_FILE => 'Choose an image first.',
            default => 'The image could not be uploaded.'
        };

        respond_error(400, $message);
    }

    $temporaryPath = (string)($upload['tmp_name'] ?? '');

    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        respond_error(400, 'The uploaded image could not be verified.');
    }

    $size = filesize($temporaryPath);

    if ($size === false || $size < 1 || $size > 2 * 1024 * 1024) {
        respond_error(400, 'Choose an image no larger than 2 MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if ($finfo === false) {
        respond_error(500, 'Image validation is unavailable.');
    }

    $mime = finfo_file($finfo, $temporaryPath);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => ['extension' => 'jpg', 'type' => IMAGETYPE_JPEG],
        'image/png' => ['extension' => 'png', 'type' => IMAGETYPE_PNG],
        'image/webp' => ['extension' => 'webp', 'type' => IMAGETYPE_WEBP]
    ];

    $image = @getimagesize($temporaryPath);

    if (
        !is_string($mime) ||
        !isset($allowed[$mime]) ||
        $image === false ||
        ($image['mime'] ?? '') !== $mime ||
        ($image[2] ?? 0) !== $allowed[$mime]['type']
    ) {
        respond_error(400, 'Use a valid PNG, JPEG or WebP image.');
    }

    $width = (int)$image[0];
    $height = (int)$image[1];

    if (
        $width < 1 ||
        $height < 1 ||
        $width > 6000 ||
        $height > 6000 ||
        $width * $height > 20000000
    ) {
        respond_error(400, 'The image dimensions are too large.');
    }

    $directory = organization_logo_directory();

    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        respond_error(500, 'Unable to prepare image storage.');
    }

    $filename = 'org-' . $actor['organization_id'] . '-' . bin2hex(random_bytes(16)) . '.' . $allowed[$mime]['extension'];
    $newRelativePath = 'uploads/organization-logos/' . $filename;
    $newFile = $directory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($temporaryPath, $newFile)) {
        $newFile = null;
        respond_error(500, 'Unable to store the uploaded image.');
    }

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'UPDATE organizations SET logo_path = ? WHERE organization_id = ?'
    );

    $statement->execute([$newRelativePath, $actor['organization_id']]);

    record_audit(
        $pdo,
        $actor,
        'ORGANIZATION_LOGO_UPDATED',
        'ORGANIZATION',
        $actor['organization_id'],
        'Updated organisation logo.'
    );

    $pdo->commit();

    delete_organization_logo_file($organization['logo_path'] ?? null);

    $newFile = null;

    respond(200, [
        'success' => true,
        'message' => 'Organisation logo uploaded.',
        'logo_path' => $newRelativePath
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($newFile !== null && is_file($newFile)) {
        @unlink($newFile);
    }

    log_error('organisation logo upload', $e);

    respond_error(500, 'Unable to upload the organisation logo.');
}