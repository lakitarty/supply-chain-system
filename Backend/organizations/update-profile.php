<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/organization-rules.php';

require_post();

try {
    $actor = require_admin($pdo);
    $input = clean_organization_input(read_json_body());

    $error = first_organization_error($input);

    if ($error !== null) {
        respond_field_error(400, $error);
    }

    $conflict = kra_pin_conflict($pdo, $input['kraPin'], $actor['organization_id']);

    if ($conflict !== null) {
        respond_field_error(409, $conflict);
    }

    $existing = find_organization($pdo, $actor);

    $changes = describe_changes([
        'name' => [$existing['organization_name'], $input['organizationName']],
        'KRA PIN' => [$existing['kra_pin'], $input['kraPin']],
        'email' => [$existing['email'], $input['organizationEmail']],
        'phone' => [$existing['phone'], $input['organizationPhone']],
        'address' => [$existing['address'], $input['organizationAddress']]
    ]);

    $pdo->beginTransaction();

    update_organization($pdo, $actor, $input);

    record_audit($pdo, $actor, 'ORGANIZATION_UPDATED', 'ORGANIZATION', $actor['organization_id'], "Updated organisation profile: $changes");

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Organisation profile updated.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('profile update', $e);

    respond_error(500, 'Unable to update the profile.');
}