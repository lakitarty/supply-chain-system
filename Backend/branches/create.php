<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/branch-rules.php';
require_once __DIR__ . '/../users/user-rules.php';

require_post();

try {
    $actor = require_admin($pdo);
    $body = read_json_body();
    $input = clean_branch_input($body);

    $error = first_branch_error($input, true);

    if ($error !== null) {
        respond_field_error(400, $error);
    }

    $conflict = find_branch_name_conflict($pdo, $actor, $input);

    if ($conflict !== null) {
        respond_field_error(409, $conflict);
    }

    $pdo->beginTransaction();

    $branchId = insert_branch($pdo, $actor, $input);

    $adminInput = clean_user_input([
        'firstName' => $body['firstName'] ?? '',
        'lastName' => $body['lastName'] ?? '',
        'email' => $body['email'] ?? '',
        'password' => $body['password'] ?? '',
        'role' => 'BRANCH_ADMIN',
        'status' => 'ACTIVE',
        'branchId' => $branchId
    ]);

    $adminError = first_user_error($pdo, $actor, $adminInput, true);

    if ($adminError !== null) {
        $pdo->rollBack();
        respond_field_error(400, $adminError);
    }

    $adminConflict = find_user_conflict($pdo, $adminInput);

    if ($adminConflict !== null) {
        $pdo->rollBack();
        respond_field_error(409, $adminConflict);
    }

    $adminUserId = insert_user($pdo, $actor, $adminInput);

    record_audit(
        $pdo,
        $actor,
        'BRANCH_CREATED',
        'BRANCH',
        $branchId,
        "Created branch {$input['branchName']}"
    );

    record_audit(
        $pdo,
        $actor,
        'USER_CREATED',
        'USER',
        $adminUserId,
        "Created branch administrator {$adminInput['email']} for {$input['branchName']}"
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Branch and branch administrator created.',
        'branch_id' => $branchId,
        'user_id' => $adminUserId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === '23000') {
        respond_error(409, 'A branch or user with these details already exists.');
    }

    log_error('branches create', $e);
    respond_error(500, 'Unable to create the branch and its administrator.');
}