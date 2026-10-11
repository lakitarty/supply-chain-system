<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/account.php';
require_once __DIR__ . '/registration.php';

require_post();

$input = clean_registration_input(read_json_body());

$error = first_registration_error($input);

if ($error !== null) {
    respond_field_error(400, $error);
}

try {
    $conflict = find_registration_conflict($pdo, $input);

    if ($conflict !== null) {
        respond_field_error(409, $conflict);
    }

    $userId = create_registration($pdo, $input);

    $account = account_payload(fetch_account($pdo, 'user_id', $userId));
    sign_in($account);

    respond(201, [
        'success' => true,
        'status' => 'success',
        'message' => 'Organization registered successfully.',
        'data' => $account
    ]);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        respond(409, [
            'success' => false,
            'error' => 'An account with these details already exists.'
        ]);
    }

    log_error('register', $e);

    respond(500, [
        'success' => false,
        'error' => 'Registration failed. Please try again.'
    ]);
}