<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/branch-rules.php';

require_post();

try {
    $actor = require_admin($pdo);
    $input = clean_branch_input(read_json_body());

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

    record_audit($pdo, $actor, 'BRANCH_CREATED', 'BRANCH', $branchId, "Created branch {$input['branchName']}");

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Branch created.',
        'branch_id' => $branchId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('branches create', $e);

    respond_error(500, 'Unable to create the branch.');
}