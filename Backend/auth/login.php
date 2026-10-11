<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/account.php';

require_post();

$data = read_json_body();

$email = strtolower(trim((string)($data['email'] ?? '')));
$password = (string)($data['password'] ?? '');

if ($email === '' || $password === '') {
    respond(400, [
        'success' => false,
        'error' => 'Email and password are required.'
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, [
        'success' => false,
        'error' => 'Enter a valid email address.'
    ]);
}

try {
    $row = fetch_account($pdo, 'email', $email);

    $hashToCheck = $row['password_hash']
        ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

    if (!password_verify($password, $hashToCheck) || !$row) {
        respond(401, [
            'success' => false,
            'error' => 'Invalid email or password.'
        ]);
    }

    $inactiveReason = inactive_reason($row);

    if ($inactiveReason !== null) {
        respond(403, [
            'success' => false,
            'error' => $inactiveReason
        ]);
    }

    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        $rehash->execute([password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
    }

    $account = account_payload($row);
    sign_in($account);

    respond(200, [
        'success' => true,
        'status' => 'success',
        'message' => 'Login successful.',
        'data' => $account
    ]);

} catch (PDOException $e) {
    log_error('login', $e);

    respond(500, [
        'success' => false,
        'error' => 'Login failed. Please try again.'
    ]);
}