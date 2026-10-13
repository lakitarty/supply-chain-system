<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/user-rules.php';

require_post();

try {
    $actor = require_admin($pdo);
    $input = clean_user_input(read_json_body());

    $error = first_user_error($pdo, $actor, $input , true);


    if ($error !== null) {
        respond_field_error(400, $error);
    }

    $conflict = find_user_conflict($pdo, $input);

    if ($conflict !== null) {
        respond_field_error(409, $conflict);
    }

    $pdo->beginTransaction();

    $userId = insert_user($pdo, $actor, $input);

    record_audit(
        $pdo,
        $actor,
        'USER_CREATED',
        'USER',
        $userId,
        "Created {$input['role']} user {$input['email']}"
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'User created.',
        'user_id' => $userId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === '23000') {
        respond_error(409, 'A user with these details already exists.');
    }

    log_error('users create', $e);

    respond_error(500, 'Unable to create the user.');
}