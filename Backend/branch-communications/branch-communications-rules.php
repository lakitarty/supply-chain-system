<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

function branch_communication_actor(PDO $pdo): array
{
    $actor = require_login($pdo);
    $statement = $pdo->prepare(
        'SELECT branch_id FROM users WHERE user_id = ? AND organization_id = ? AND status = ? LIMIT 1'
    );
    $statement->execute([$actor['user_id'], $actor['organization_id'], 'ACTIVE']);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        respond_error(401, 'Your account is no longer active.');
    }

    $actor['branch_id'] = $row['branch_id'] === null ? null : (int)$row['branch_id'];
    return $actor;
}

function branch_communication_require_admin(array $actor): void
{
    if ($actor['role'] !== 'ADMIN') {
        respond_error(403, 'Administrator access is required.');
    }
}
function branch_communication_require_composer(PDO $pdo, array $actor): array
{
    if ($actor['role'] === 'ADMIN') {
        return $actor;
    }

    if ($actor['role'] === 'BRANCH_ADMIN') {
        return require_branch_admin($pdo);
    }

    respond_error(403, 'Branch communication management access is required.');
}

function branch_communication_has_recipient(
    PDO $pdo,
    int $announcementId,
    int $organizationId,
    int $branchId
): bool {
    $statement = $pdo->prepare(
        'SELECT 1
         FROM branch_announcement_recipients
         WHERE announcement_id = ? AND organization_id = ? AND branch_id = ?
         LIMIT 1'
    );
    $statement->execute([$announcementId, $organizationId, $branchId]);
    return (bool)$statement->fetchColumn();
}

function branch_communication_thread_branch(
    PDO $pdo,
    array $actor,
    int $announcementId,
    ?int $requestedBranchId
): int {
    if ($actor['role'] === 'ADMIN') {
        $branchId = $requestedBranchId ?? $actor['branch_id'];
    } else {
        $branchId = $actor['branch_id'];

        if ($requestedBranchId !== null && $requestedBranchId !== $branchId) {
            respond_error(403, 'You can only access your assigned branch.');
        }
    }

    if (!$branchId || !branch_communication_has_recipient(
        $pdo,
        $announcementId,
        $actor['organization_id'],
        $branchId
    )) {
        respond_error(404, 'Branch announcement not found.');
    }

    return (int)$branchId;
}

function branch_communication_notify_branch(
    PDO $pdo,
    array $actor,
    int $announcementId,
    int $branchId,
    string $title,
    string $message
): void {
    $statement = $pdo->prepare(
        "INSERT INTO notifications
            (organization_id, user_id, notification_type, reference_id, title, message, status)
         SELECT u.organization_id, u.user_id, 'GENERAL', ?, ?, ?, 'UNREAD'
         FROM users u
         WHERE u.organization_id = ?
           AND u.branch_id = ?
           AND u.status = 'ACTIVE'
           AND u.user_id <> ?"
    );

    $statement->execute([
        $announcementId,
        mb_substr($title, 0, 200),
        $message,
        $actor['organization_id'],
        $branchId,
        $actor['user_id']
    ]);
}