<?php

declare(strict_types=1);

require_once __DIR__ . '/branch-communications-rules.php';

try {
    $actor = branch_communication_actor($pdo);

    if ($actor['role'] === 'BRANCH_ADMIN') {
        $actor = require_branch_admin($pdo);
    }

    $canManage = $actor['role'] === 'ADMIN';
    $canCompose = in_array($actor['role'], ['ADMIN', 'BRANCH_ADMIN'], true);

    $base = "
        SELECT a.announcement_id,
               a.sender_user_id,
               a.title,
               a.message,
               a.created_at,
               CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
               u.role AS sender_role";

    if ($canManage) {
        $statement = $pdo->prepare(
            $base . ",
                (SELECT COUNT(*)
                 FROM branch_announcement_recipients rr
                 WHERE rr.announcement_id = a.announcement_id
                   AND rr.organization_id = a.organization_id) AS recipient_count,
                (SELECT COUNT(*)
                 FROM branch_announcement_recipients rr
                 WHERE rr.announcement_id = a.announcement_id
                   AND rr.organization_id = a.organization_id
                   AND rr.status = 'ACKNOWLEDGED') AS acknowledged_count,
                NULL AS branch_status,
                NULL AS recipient_status
             FROM branch_announcements a
             INNER JOIN users u ON u.user_id = a.sender_user_id
             WHERE a.organization_id = ?
             ORDER BY a.created_at DESC, a.announcement_id DESC
             LIMIT 200"
        );

        $statement->execute([$actor['organization_id']]);
    } else {
        if (!$actor['branch_id']) {
            respond(200, [
                'success' => true,
                'can_manage' => false,
                'can_compose' => false,
                'current_user_id' => $actor['user_id'],
                'branch_id' => null,
                'announcements' => []
            ]);
        }

        $statement = $pdo->prepare(
            $base . ",
                1 AS recipient_count,
                CASE WHEN r.status = 'ACKNOWLEDGED' THEN 1 ELSE 0 END AS acknowledged_count,
                CASE
                    WHEN a.sender_user_id = ? THEN 'SENT'
                    ELSE r.status
                END AS branch_status,
                r.status AS recipient_status
             FROM branch_announcements a
             INNER JOIN users u ON u.user_id = a.sender_user_id
             INNER JOIN branch_announcement_recipients r
                ON r.announcement_id = a.announcement_id
               AND r.organization_id = a.organization_id
               AND r.branch_id = ?
             WHERE a.organization_id = ?
             ORDER BY a.created_at DESC, a.announcement_id DESC
             LIMIT 200"
        );

        $statement->execute([
            $actor['user_id'],
            $actor['branch_id'],
            $actor['organization_id']
        ]);
    }

    respond(200, [
        'success' => true,
        'can_manage' => $canManage,
        'can_compose' => $canCompose,
        'current_user_id' => $actor['user_id'],
        'role' => $actor['role'],
        'branch_id' => $actor['branch_id'],
        'announcements' => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (PDOException $e) {
    log_error('branch communications list', $e);
    respond_error(500, 'Unable to load branch announcements.');
}