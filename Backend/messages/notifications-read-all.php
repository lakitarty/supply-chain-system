<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

require_post();

$actor = require_login($pdo);

try {
    $stmt = $pdo->prepare(
        "UPDATE notifications
         SET status = 'READ',
             read_at = COALESCE(read_at, NOW())
         WHERE organization_id = ?
           AND (user_id IS NULL OR user_id = ?)
           AND status = 'UNREAD'"
    );

    $stmt->execute([
        $actor['organization_id'],
        $actor['user_id']
    ]);

    respond(200, [
        'success' => true,
        'message' => 'All notifications marked as read.',
        'updated_count' => $stmt->rowCount()
    ]);
} catch (PDOException $e) {
    log_error('notifications read all', $e);
    respond_error(500, 'Unable to mark all notifications as read.');
}