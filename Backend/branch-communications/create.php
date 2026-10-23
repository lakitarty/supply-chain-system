<?php

declare(strict_types=1);

require_once __DIR__ . '/branch-communications-rules.php';

require_post();

try {
    $actor = branch_communication_actor($pdo);
    $actor = branch_communication_require_composer($pdo, $actor);

    $input = read_json_body();
    $title = trim((string)($input['title'] ?? ''));
    $message = trim((string)($input['message'] ?? ''));
    $allBranches = ($input['allBranches'] ?? false) === true;
    $branchIds = $input['branchIds'] ?? [];

    if ($title === '' || mb_strlen($title) > 200) {
        respond_error(422, 'Enter a title of 1 to 200 characters.');
    }

    if ($message === '' || mb_strlen($message) > 5000) {
        respond_error(422, 'Enter a message of 1 to 5000 characters.');
    }

    if ($actor['role'] === 'BRANCH_ADMIN') {
        $allBranches = false;
        $branchIds = [(int)$actor['branch_id']];
    }

    if ($allBranches) {
        $statement = $pdo->prepare(
            "SELECT branch_id
             FROM branches
             WHERE organization_id = ? AND status = 'ACTIVE'
             ORDER BY is_main_branch DESC, branch_name"
        );
        $statement->execute([$actor['organization_id']]);
        $branchIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    } else {
        if (!is_array($branchIds) || $branchIds === []) {
            respond_error(422, 'Choose at least one branch.');
        }

        $validatedIds = [];

        foreach ($branchIds as $id) {
            $number = filter_var($id, FILTER_VALIDATE_INT);

            if (!$number || $number < 1) {
                respond_error(422, 'One or more branch selections are invalid.');
            }

            $validatedIds[] = (int)$number;
        }

        $branchIds = array_values(array_unique($validatedIds));

        if (count($branchIds) > 200) {
            respond_error(422, 'Too many branches were selected.');
        }

        $placeholders = implode(',', array_fill(0, count($branchIds), '?'));

        $statement = $pdo->prepare(
            "SELECT branch_id
             FROM branches
             WHERE organization_id = ?
               AND status = 'ACTIVE'
               AND branch_id IN ($placeholders)"
        );

        $statement->execute(
            array_merge([$actor['organization_id']], $branchIds)
        );

        $foundIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));

        if (count($foundIds) !== count($branchIds)) {
            respond_error(
                422,
                'A selected branch is inactive or does not belong to your organisation.'
            );
        }

        $branchIds = $foundIds;
    }

    if ($branchIds === []) {
        respond_error(422, 'There are no active branches to receive this announcement.');
    }

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'INSERT INTO branch_announcements
            (organization_id, sender_user_id, title, message)
         VALUES (?, ?, ?, ?)'
    );

    $statement->execute([
        $actor['organization_id'],
        $actor['user_id'],
        $title,
        $message
    ]);

    $announcementId = (int)$pdo->lastInsertId();

    $recipientInsert = $pdo->prepare(
        "INSERT INTO branch_announcement_recipients
            (announcement_id, organization_id, branch_id, status)
         VALUES (?, ?, ?, 'UNREAD')"
    );

    foreach ($branchIds as $branchId) {
        $recipientInsert->execute([
            $announcementId,
            $actor['organization_id'],
            $branchId
        ]);

        branch_communication_notify_branch(
            $pdo,
            $actor,
            $announcementId,
            $branchId,
            'Branch notice: ' . $title,
            $message
        );
    }

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Branch announcement sent.',
        'announcement_id' => $announcementId,
        'recipient_count' => count($branchIds)
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('branch communications create', $e);
    respond_error(500, 'Unable to send the branch announcement.');
}