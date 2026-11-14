<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_login($pdo);

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM notifications
         WHERE organization_id = ?
           AND status = 'UNREAD'
           AND (user_id IS NULL OR user_id = ?)"
    );

    $stmt->execute([
        $actor['organization_id'],
        $actor['user_id']
    ]);

    respond(200, [
        'success' => true,
        'unread_count' => (int)$stmt->fetchColumn()
    ]);
} catch (PDOException $e) {
    log_error('notifications count', $e);
    respond_error(500, 'Unable to retrieve notification count.');
}