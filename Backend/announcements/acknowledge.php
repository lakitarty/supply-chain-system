<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';

require_post();

$actor = require_login($pdo);
$body = read_json_body();

$announcementId = filter_var(
    $body['announcementId'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $announcementId === false
    || $announcementId === null
    || $announcementId < 1
) {
    respond_error(422, 'Select a valid announcement.');
}

try {
    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        "SELECT r.recipient_id, r.acknowledgement_status
         FROM product_announcement_recipients r
         INNER JOIN product_announcements a
            ON a.announcement_id = r.announcement_id
         WHERE r.announcement_id = ?
           AND r.organization_id = ?
           AND a.status = 'PUBLISHED'
         LIMIT 1
         FOR UPDATE"
    );

    $statement->execute([
        $announcementId,
        $actor['organization_id']
    ]);

    $recipient = $statement->fetch(PDO::FETCH_ASSOC);

    if ($recipient === false) {
        $pdo->rollBack();
        respond_error(404, 'This announcement is not available to your organization.');
    }

    if ($recipient['acknowledgement_status'] === 'ACKNOWLEDGED') {
        $pdo->commit();

        respond(200, [
            'success' => true,
            'message' => 'This announcement has already been acknowledged.',
            'announcementId' => $announcementId,
            'acknowledged' => true
        ]);
    }

    $update = $pdo->prepare(
        "UPDATE product_announcement_recipients
         SET acknowledgement_status = 'ACKNOWLEDGED',
             acknowledged_by = ?,
             acknowledged_at = NOW()
         WHERE recipient_id = ?"
    );

    $update->execute([
        $actor['user_id'],
        $recipient['recipient_id']
    ]);

    record_audit(
        $pdo,
        $actor,
        'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED',
        'PRODUCT_ANNOUNCEMENT',
        $announcementId,
        'Acknowledged receipt of product announcement #' . $announcementId . '.'
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Announcement acknowledged successfully.',
        'announcementId' => $announcementId,
        'acknowledged' => true
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Product announcement acknowledgement failed: ' . $error->getMessage());

    respond_error(500, 'The announcement could not be acknowledged.');
}
