<?php

declare(strict_types=1);

require_once __DIR__ . '/branch-communications-rules.php';

try {
    $actor = branch_communication_actor($pdo);
    $actor = branch_communication_require_composer($pdo, $actor);

    if ($actor['role'] === 'BRANCH_ADMIN') {
        $statement = $pdo->prepare(
            "SELECT branch_id, branch_name, location, is_main_branch
             FROM branches
             WHERE organization_id = ?
               AND branch_id = ?
               AND status = 'ACTIVE'
             LIMIT 1"
        );

        $statement->execute([
            $actor['organization_id'],
            $actor['branch_id']
        ]);
    } else {
        $statement = $pdo->prepare(
            "SELECT branch_id, branch_name, location, is_main_branch
             FROM branches
             WHERE organization_id = ? AND status = 'ACTIVE'
             ORDER BY is_main_branch DESC, branch_name"
        );

        $statement->execute([$actor['organization_id']]);
    }

    respond(200, [
        'success' => true,
        'branches' => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (PDOException $e) {
    log_error('branch communications targets', $e);
    respond_error(500, 'Unable to load branches.');
}