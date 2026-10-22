<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_login($pdo);

    $stmt = $pdo->prepare(
        "SELECT notification_id, notification_type, reference_id, title,
                message, status, created_at, read_at
         FROM notifications
         WHERE organization_id = ?
           AND (user_id IS NULL OR user_id = ?)
         ORDER BY created_at DESC, notification_id DESC
         LIMIT 200"
    );

    $stmt->execute([$actor['organization_id'], $actor['user_id']]);

    respond(200, [
        'success' => true,
        'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    log_error('notifications list', $e);
    respond_error(500, 'Unable to load notifications.');
}