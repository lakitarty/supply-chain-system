<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_post();
start_secure_session();

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
    $stmt = $pdo->prepare(
        'SELECT
            u.user_id,
            u.organization_id,
            u.branch_id,
            u.first_name,
            u.last_name,
            u.email,
            u.password_hash,
            u.role,
            u.status AS user_status,
            o.organization_name,
            o.organization_type,
            o.email AS organization_email,
            o.phone AS organization_phone,
            o.address AS organization_address,
            o.status AS organization_status,
            b.branch_name
         FROM users u
         INNER JOIN organizations o
            ON u.organization_id = o.organization_id
         LEFT JOIN branches b
            ON u.branch_id = b.branch_id
         WHERE u.email = ?
         LIMIT 1'
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always run a password check, even for unknown emails, so response
    // time does not reveal whether an account exists.
    $hashToCheck = $user['password_hash']
        ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

    $passwordOk = password_verify($password, $hashToCheck);

    if (!$user || !$passwordOk) {
        respond(401, [
            'success' => false,
            'error' => 'Invalid email or password.'
        ]);
    }

    // Status is only revealed after the password is proven correct.
    if ($user['user_status'] !== 'ACTIVE') {
        respond(403, [
            'success' => false,
            'error' => 'This user account is inactive. Contact your administrator.'
        ]);
    }

    if ($user['organization_status'] !== 'ACTIVE') {
        respond(403, [
            'success' => false,
            'error' => 'Your organization account is inactive.'
        ]);
    }

    // Upgrade old hashes automatically if PHP's default algorithm improves.
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        $rehash->execute([
            password_hash($password, PASSWORD_DEFAULT),
            $user['user_id']
        ]);
    }

    session_regenerate_id(true);

    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['organization_id'] = (int)$user['organization_id'];
    $_SESSION['branch_id'] = $user['branch_id'] !== null ? (int)$user['branch_id'] : null;
    $_SESSION['role'] = $user['role'];
    $_SESSION['organization_type'] = $user['organization_type'];

    // Same shape as session.php so the frontend only has to learn one format.
    respond(200, [
        'success' => true,
        'status' => 'success',
        'message' => 'Login successful.',
        'data' => [
            'user' => [
                'user_id' => (int)$user['user_id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'branch_id' => $user['branch_id'] !== null ? (int)$user['branch_id'] : null,
                'branch_name' => $user['branch_name']
            ],
            'organization' => [
                'organization_id' => (int)$user['organization_id'],
                'organization_name' => $user['organization_name'],
                'organization_type' => $user['organization_type'],
                'email' => $user['organization_email'],
                'phone' => $user['organization_phone'],
                'address' => $user['organization_address']
            ]
        ]
    ]);

} catch (PDOException $e) {
    log_error('login', $e);
    respond(500, [
        'success' => false,
        'error' => 'Login failed. Please try again.'
    ]);
}
