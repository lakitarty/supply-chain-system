<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_post();

$data = read_json_body();



$organizationName    = trim((string)($data['organizationName'] ?? ''));
$organizationType    = trim((string)($data['organizationType'] ?? ''));
$kraPin              = strtoupper(trim((string)($data['kraPin'] ?? '')));
$organizationEmail   = strtolower(trim((string)($data['organizationEmail'] ?? '')));
$organizationPhone   = trim((string)($data['organizationPhone'] ?? ''));
$organizationAddress = trim((string)($data['organizationAddress'] ?? ''));

$branchName     = trim((string)($data['branchName'] ?? ''));
$branchLocation = trim((string)($data['branchLocation'] ?? ''));

$firstName  = trim((string)($data['firstName'] ?? ''));
$lastName   = trim((string)($data['lastName'] ?? ''));
$adminEmail = strtolower(trim((string)($data['adminEmail'] ?? '')));
$password   = (string)($data['password'] ?? '');



function fail(int $status, string $message, ?string $field = null): void
{
    $payload = ['success' => false, 'error' => $message];

    if ($field !== null) {
        $payload['field'] = $field; 

    respond($status, $payload);
    }
}
$required = [
    'organizationName' => $organizationName,
    'organizationType' => $organizationType,
    'branchName'       => $branchName,
    'branchLocation'   => $branchLocation,
    'firstName'        => $firstName,
    'lastName'         => $lastName,
    'adminEmail'       => $adminEmail,
    'password'         => $password
];

foreach ($required as $field => $value) {
    if ($value === '') {
        fail(400, 'This field is required.', $field);
    }
}

$allowedTypes = ['MANUFACTURER', 'SUPPLIER', 'MANUFACTURER_SUPPLIER', 'RETAILER'];

if (!in_array($organizationType, $allowedTypes, true)) {
    fail(400, 'Choose a valid organization type.', 'organizationType');
}

// Maximum lengths (match the maxlength attributes in register.html).
$maxLengths = [
    'organizationName'    => [$organizationName, 150],
    'kraPin'              => [$kraPin, 30],
    'organizationEmail'   => [$organizationEmail, 150],
    'organizationPhone'   => [$organizationPhone, 30],
    'organizationAddress' => [$organizationAddress, 255],
    'branchName'          => [$branchName, 150],
    'branchLocation'      => [$branchLocation, 255],
    'firstName'           => [$firstName, 100],
    'lastName'            => [$lastName, 100],
    'adminEmail'          => [$adminEmail, 150]
];

foreach ($maxLengths as $field => [$value, $max]) {
    if (mb_strlen($value) > $max) {
        fail(400, "Must be $max characters or fewer.", $field);
    }
}

if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    fail(400, 'Enter a valid email address.', 'adminEmail');
}

if ($organizationEmail !== '' && !filter_var($organizationEmail, FILTER_VALIDATE_EMAIL)) {
    fail(400, 'Enter a valid email address.', 'organizationEmail');
}

// KRA PIN format: one letter, nine digits, one letter (e.g. A123456789Z).
if ($kraPin !== '' && !preg_match('/^[A-Z][0-9]{9}[A-Z]$/', $kraPin)) {
    fail(400, 'KRA PIN must look like A123456789Z.', 'kraPin');
}

if ($organizationPhone !== '' && !preg_match('/^\+?[0-9][0-9\s\-()]{6,19}$/', $organizationPhone)) {
    fail(400, 'Enter a valid phone number.', 'organizationPhone');
}

if (strlen($password) < 8) {
    fail(400, 'Password must be at least 8 characters.', 'password');
}

// bcrypt only uses the first 72 bytes, so longer passwords are rejected.
if (strlen($password) > 72) {
    fail(400, 'Password must be 72 characters or fewer.', 'password');
}

if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    fail(400, 'Password must include at least one letter and one number.', 'password');
}

/* ---------- Save ---------- */

try {
    $emailCheck = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $emailCheck->execute([$adminEmail]);

    if ($emailCheck->fetch()) {
        fail(409, 'This email is already registered.', 'adminEmail');
    }

    if ($kraPin !== '') {
        $kraCheck = $pdo->prepare(
            'SELECT organization_id FROM organizations WHERE kra_pin = ? LIMIT 1'
        );
        $kraCheck->execute([$kraPin]);

        if ($kraCheck->fetch()) {
            fail(409, 'This KRA PIN is already registered.', 'kraPin');
        }
    }

    $pdo->beginTransaction();

    $organizationStmt = $pdo->prepare(
        'INSERT INTO organizations
            (organization_name, organization_type, kra_pin, email, phone, address, status)
         VALUES (?, ?, ?, ?, ?, ?, "ACTIVE")'
    );

    $organizationStmt->execute([
        $organizationName,
        $organizationType,
        $kraPin !== '' ? $kraPin : null,
        $organizationEmail !== '' ? $organizationEmail : null,
        $organizationPhone !== '' ? $organizationPhone : null,
        $organizationAddress !== '' ? $organizationAddress : null
    ]);

    $organizationId = (int)$pdo->lastInsertId();

    $branchStmt = $pdo->prepare(
        'INSERT INTO branches
            (organization_id, branch_name, location, is_main_branch, status)
         VALUES (?, ?, ?, 1, "ACTIVE")'
    );

    $branchStmt->execute([$organizationId, $branchName, $branchLocation]);

    $branchId = (int)$pdo->lastInsertId();

    $userStmt = $pdo->prepare(
        'INSERT INTO users
            (organization_id, branch_id, first_name, last_name, email, password_hash, role, status)
         VALUES (?, ?, ?, ?, ?, ?, "ADMIN", "ACTIVE")'
    );

    $userStmt->execute([
        $organizationId,
        $branchId,
        $firstName,
        $lastName,
        $adminEmail,
        password_hash($password, PASSWORD_DEFAULT)
    ]);

    $userId = (int)$pdo->lastInsertId();

    $pdo->commit();

    start_secure_session();
    session_regenerate_id(true);

    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = $userId;
    $_SESSION['organization_id'] = $organizationId;
    $_SESSION['branch_id'] = $branchId;
    $_SESSION['role'] = 'ADMIN';
    $_SESSION['organization_type'] = $organizationType;

    respond(201, [
        'success' => true,
        'status' => 'success',
        'message' => 'Organization registered successfully.',
        'data' => [
            'organization_id' => $organizationId,
            'branch_id' => $branchId,
            'user_id' => $userId,
            'organization_type' => $organizationType
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // 23000 = a UNIQUE rule in the database was broken (e.g. two people
    // registering the same email at the same moment).
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
