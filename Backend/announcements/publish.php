<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/announcement-rules.php';

require_post();

$actor = require_admin($pdo);

if (!can_publish_announcements($actor)) {
    respond_error(403, 'Only manufacturer administrators can publish product announcements.');
}

$body = read_json_body();

$productId = filter_var(
    $body['productId'] ?? null,
    FILTER_VALIDATE_INT
);

$title = trim((string) ($body['title'] ?? ''));
$message = trim((string) ($body['message'] ?? ''));

if ($productId === false || $productId === null || $productId < 1) {
    respond_error(422, 'Select a valid product.');
}

if ($title === '' || mb_strlen($title) > 200) {
    respond_error(422, 'The announcement title is required and must not exceed 200 characters.');
}

if (mb_strlen($message) > 5000) {
    respond_error(422, 'The announcement message must not exceed 5000 characters.');
}

$product = find_announcement_product($pdo, $actor, $productId);

if ($product === false) {
    respond_error(404, 'The active product was not found under your organization.');
}

$recipients = find_announcement_recipients(
    $pdo,
    $actor['organization_id']
);

if ($recipients === []) {
    respond_error(409, 'No connected, active retailer organizations were found for this announcement.');
}

try {
    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        "INSERT INTO product_announcements
            (product_id, manufacturer_id, announcement_title, announcement_message, status)
         VALUES (?, ?, ?, ?, 'PUBLISHED')"
    );

    $statement->execute([
        $productId,
        $actor['organization_id'],
        $title,
        $message === '' ? null : $message
    ]);

    $announcementId = (int) $pdo->lastInsertId();

    $recipientStatement = $pdo->prepare(
        "INSERT INTO product_announcement_recipients
            (announcement_id, organization_id, acknowledgement_status)
         VALUES (?, ?, 'PENDING')"
    );

    foreach ($recipients as $organizationId) {
        $recipientStatement->execute([
            $announcementId,
            $organizationId
        ]);
    }

    record_audit(
        $pdo,
        $actor,
        'PRODUCT_ANNOUNCEMENT_PUBLISHED',
        'PRODUCT_ANNOUNCEMENT',
        $announcementId,
        'Published product announcement for ' . $product['product_name']
            . ' to ' . count($recipients) . ' retailer organization(s).'
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Product announcement published successfully.',
        'announcementId' => $announcementId,
        'recipientCount' => count($recipients)
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Product announcement publishing failed: ' . $error->getMessage());

    respond_error(500, 'The announcement could not be published. No changes were saved.');
}
