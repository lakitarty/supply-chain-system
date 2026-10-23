<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/user-rules.php';

require_post();

try {
    $actor = require_login($pdo);

    if (!in_array($actor['role'], ['ADMIN', 'BRANCH_ADMIN'], true)) {
        respond_error(403, 'User management access is required.');
    }

    if ($actor['role'] === 'BRANCH_ADMIN') {
        $actor = require_branch_admin($pdo);
    }

    $input = clean_user_input(read_json_body());

    $existing = find_user($pdo, $actor, $input['userId']);

    if ($existing === false) {
        respond_error(404, 'User not found in your permitted scope.');
    }

    $error = first_user_error($pdo, $actor, $input, false);

    if ($error !== null) {
        respond_field_error(400, $error);
    }

    if (would_remove_last_admin($pdo, $actor, $existing, $input)) {
        respond_error(409, 'An organization must keep at least one active administrator.');
    }

    $changes = describe_changes([
        'name' => [
            "{$existing['first_name']} {$existing['last_name']}",
            "{$input['firstName']} {$input['lastName']}"
        ],
        'role' => [$existing['role'], $input['role']],
        'status' => [$existing['status'], $input['status']],
        'branch' => [(int)$existing['branch_id'], (int)$input['branchId']]
    ]);

    if ($input['password'] !== '') {
        $changes .= ', password reset';
    }

    $pdo->beginTransaction();

    update_user($pdo, $actor, $input);

    record_audit(
        $pdo,
        $actor,
        'USER_UPDATED',
        'USER',
        $input['userId'],
        "Updated {$existing['email']}: $changes"
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'User updated.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('users update', $e);
    respond_error(500, 'Unable to update the user.');
}