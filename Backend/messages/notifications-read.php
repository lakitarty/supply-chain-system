<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

require_post();

$actor = require_login($pdo);
$input = read_json_body();

$notificationId = filter_var(
    $input['notificationId'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$notificationId || $notificationId < 1) {
    respond_error(422, 'A valid notification ID is required.');
}

try {
    $stmt = $pdo->prepare(
        "UPDATE notifications
         SET status = 'READ',
             read_at = COALESCE(read_at, NOW())
         WHERE notification_id = ?
           AND organization_id = ?
           AND (user_id IS NULL OR user_id = ?)"
    );

    $stmt->execute([
        $notificationId,
        $actor['organization_id'],
        $actor['user_id']
    ]);

    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare(
            "SELECT status
             FROM notifications
             WHERE notification_id = ?
               AND organization_id = ?
               AND (user_id IS NULL OR user_id = ?)
             LIMIT 1"
        );

        $check->execute([
            $notificationId,
            $actor['organization_id'],
            $actor['user_id']
        ]);

        $notification = $check->fetch(PDO::FETCH_ASSOC);

        if (!$notification) {
            respond_error(404, 'Notification not found.');
        }

        if ($notification['status'] !== 'READ') {
            respond_error(500, 'Unable to mark notification as read.');
        }
    }

    respond(200, [
        'success' => true,
        'message' => 'Notification marked as read.'
    ]);
} catch (PDOException $e) {
    log_error('notifications read', $e);
    respond_error(500, 'Unable to update notification.');
}