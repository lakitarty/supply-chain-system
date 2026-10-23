<?php

declare(strict_types=1);

require_once __DIR__ . '/branch-communications-rules.php';

require_post();

$actor = branch_communication_actor($pdo);
$input = read_json_body();
$announcementId = filter_var($input['announcementId'] ?? null, FILTER_VALIDATE_INT);

if (!$announcementId || $announcementId < 1 || !$actor['branch_id']) {
    respond_error(422, 'A valid announcement and assigned branch are required.');
}

try {
    $branchId = branch_communication_thread_branch(
        $pdo,
        $actor,
        (int)$announcementId,
        $actor['branch_id']
    );

    $statement = $pdo->prepare(
        "UPDATE branch_announcement_recipients
         SET status = 'READ', read_at = COALESCE(read_at, NOW())
         WHERE announcement_id = ?
           AND organization_id = ?
           AND branch_id = ?
           AND status = 'UNREAD'"
    );
    $statement->execute([
        $announcementId,
        $actor['organization_id'],
        $branchId
    ]);

    $notification = $pdo->prepare(
        "UPDATE notifications
         SET status = 'READ', read_at = COALESCE(read_at, NOW())
         WHERE organization_id = ?
           AND user_id = ?
           AND reference_id = ?
           AND notification_type = 'GENERAL'
           AND title LIKE 'Branch notice:%'"
    );
    $notification->execute([
        $actor['organization_id'],
        $actor['user_id'],
        $announcementId
    ]);

    respond(200, ['success' => true, 'message' => 'Announcement marked as read.']);
} catch (PDOException $e) {
    log_error('branch communications read', $e);
    respond_error(500, 'Unable to mark the announcement as read.');
}