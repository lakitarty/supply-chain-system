<?php

declare(strict_types=1);

require_once __DIR__ . '/branch-communications-rules.php';

try {
    $actor = branch_communication_actor($pdo);
    branch_communication_require_admin($actor);

    $announcementId = filter_var($_GET['announcementId'] ?? null, FILTER_VALIDATE_INT);

    if (!$announcementId || $announcementId < 1) {
        respond_error(422, 'A valid announcement ID is required.');
    }

    $statement = $pdo->prepare(
        'SELECT announcement_id
         FROM branch_announcements
         WHERE announcement_id = ? AND organization_id = ?
         LIMIT 1'
    );
    $statement->execute([$announcementId, $actor['organization_id']]);

    if (!$statement->fetchColumn()) {
        respond_error(404, 'Announcement not found.');
    }

    $statement = $pdo->prepare(
        'SELECT r.branch_id, b.branch_name, b.location, r.status,
                r.sent_at, r.read_at, r.acknowledged_at
         FROM branch_announcement_recipients r
         INNER JOIN branches b
            ON b.branch_id = r.branch_id
           AND b.organization_id = r.organization_id
         WHERE r.announcement_id = ? AND r.organization_id = ?
         ORDER BY b.is_main_branch DESC, b.branch_name'
    );
    $statement->execute([$announcementId, $actor['organization_id']]);

    respond(200, [
        'success' => true,
        'recipients' => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    log_error('branch communications recipients', $e);
    respond_error(500, 'Unable to load branch receipt statuses.');
}