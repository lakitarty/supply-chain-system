<?php

declare(strict_types=1);

require_once __DIR__ . '/branch-communications-rules.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'POST') {
    require_post();
}

try {
    $actor = branch_communication_actor($pdo);

    if ($method === 'GET') {
        $announcementId = filter_var($_GET['announcementId'] ?? null, FILTER_VALIDATE_INT);
        $requestedBranch = filter_var($_GET['branchId'] ?? null, FILTER_VALIDATE_INT);
        $requestedBranch = $requestedBranch ? (int)$requestedBranch : null;
        $text = null;
    } elseif ($method === 'POST') {
        $input = read_json_body();
        $announcementId = filter_var($input['announcementId'] ?? null, FILTER_VALIDATE_INT);
        $requestedBranch = filter_var($input['branchId'] ?? null, FILTER_VALIDATE_INT);
        $requestedBranch = $requestedBranch ? (int)$requestedBranch : null;
        $text = trim((string)($input['text'] ?? ''));
    } else {
        respond_error(405, 'Method not allowed.');
    }

    if (!$announcementId || $announcementId < 1) {
        respond_error(422, 'A valid announcement ID is required.');
    }

    if ($method === 'POST' && ($text === '' || mb_strlen($text) > 2000)) {
        respond_error(422, 'Write a reply of 1 to 2000 characters.');
    }

    $branchId = branch_communication_thread_branch(
        $pdo,
        $actor,
        (int)$announcementId,
        $requestedBranch
    );

    $statement = $pdo->prepare(
        'SELECT sender_user_id, title
         FROM branch_announcements
         WHERE announcement_id = ? AND organization_id = ?
         LIMIT 1'
    );
    $statement->execute([
        $announcementId,
        $actor['organization_id']
    ]);

    $announcement = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$announcement) {
        respond_error(404, 'Branch announcement not found.');
    }

    if ($method === 'GET') {
        $statement = $pdo->prepare(
            "SELECT r.reply_id, r.branch_id, r.message_text, r.sent_at,
                    CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
                    u.role AS sender_role,
                    b.branch_name
             FROM branch_announcement_replies r
             INNER JOIN users u ON u.user_id = r.sender_user_id
             INNER JOIN branches b
                ON b.branch_id = r.branch_id
               AND b.organization_id = r.organization_id
             WHERE r.announcement_id = ?
               AND r.organization_id = ?
               AND r.branch_id = ?
             ORDER BY r.reply_id ASC"
        );

        $statement->execute([
            $announcementId,
            $actor['organization_id'],
            $branchId
        ]);

        respond(200, [
            'success' => true,
            'replies' => $statement->fetchAll(PDO::FETCH_ASSOC),
            'branch_id' => $branchId
        ]);
    }

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'INSERT INTO branch_announcement_replies
            (announcement_id, organization_id, branch_id, sender_user_id, message_text)
         VALUES (?, ?, ?, ?, ?)'
    );

    $statement->execute([
        $announcementId,
        $actor['organization_id'],
        $branchId,
        $actor['user_id'],
        $text
    ]);

    $replyId = (int)$pdo->lastInsertId();
    $announcementSenderId = (int)$announcement['sender_user_id'];

    if ($actor['user_id'] === $announcementSenderId) {
        branch_communication_notify_branch(
            $pdo,
            $actor,
            (int)$announcementId,
            $branchId,
            'Branch reply: ' . $announcement['title'],
            mb_substr($text, 0, 5000)
        );
    } else {
        $statement = $pdo->prepare(
            "INSERT INTO notifications
                (organization_id, user_id, notification_type, reference_id, title, message, status)
             SELECT u.organization_id, u.user_id, 'GENERAL', ?, ?, ?, 'UNREAD'
             FROM users u
             WHERE u.user_id = ?
               AND u.organization_id = ?
               AND u.status = 'ACTIVE'
               AND u.user_id <> ?"
        );

        $statement->execute([
            $announcementId,
            mb_substr('Reply to: ' . $announcement['title'], 0, 200),
            mb_substr($text, 0, 5000),
            $announcementSenderId,
            $actor['organization_id'],
            $actor['user_id']
        ]);
    }

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Reply sent.',
        'reply_id' => $replyId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('branch communications replies', $e);
    respond_error(500, 'Unable to process the branch reply.');
}