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

    $existing = find_branch($pdo, $actor, $input['branchId']);

    if ($existing === false) {
        respond_error(404, 'Branch not found.');
    }

    $error = first_branch_error($input, false);

    if ($error !== null) {
        respond_field_error(400, $error);
    }

    $conflict = find_branch_name_conflict($pdo, $actor, $input);

    if ($conflict !== null) {
        respond_field_error(409, $conflict);
    }

    $blocked = deactivation_block($pdo, $actor, $existing, $input);

    if ($blocked !== null) {
        respond_error(409, $blocked);
    }

    $changes = describe_changes([
        'name' => [$existing['branch_name'], $input['branchName']],
        'location' => [$existing['location'], $input['location']],
        'status' => [$existing['status'], $input['status']]
    ]);

    $pdo->beginTransaction();

    update_branch($pdo, $actor, $input);

    record_audit($pdo, $actor, 'BRANCH_UPDATED', 'BRANCH', $input['branchId'], "Updated branch {$existing['branch_name']}: $changes");

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Branch updated.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('branches update', $e);

    respond_error(500, 'Unable to update the branch.');
}