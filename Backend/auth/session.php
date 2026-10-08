<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

start_secure_session();

$userId = $_SESSION['user_id'] ?? null;

if (($_SESSION['authenticated'] ?? false) !== true || !$userId) {
    respond(200, ['authenticated' => false]);
}

try {
    $stmt = $pdo->prepare(
        'SELECT
            u.user_id,
            u.first_name,
            u.last_name,
            u.email,
            u.role,
            u.status,
            u.branch_id,
            b.branch_name,
            o.organization_id,
            o.organization_name,
            o.organization_type,
            o.email AS organization_email,
            o.phone,
            o.address,
            o.status AS organization_status
         FROM users u
         INNER JOIN organizations o
            ON u.organization_id = o.organization_id
         LEFT JOIN branches b
            ON u.branch_id = b.branch_id
         WHERE u.user_id = ?
         LIMIT 1'
    );

    $stmt->execute([$userId]);
    $data = $stmt->fetch();

    if (
        !$data ||
        $data['status'] !== 'ACTIVE' ||
        $data['organization_status'] !== 'ACTIVE'
    ) {
        // Account was disabled or deleted after login: end the session.
        $_SESSION = [];
        session_destroy();
        respond(200, ['authenticated' => false]);
    }

    respond(200, [
        'authenticated' => true,
        'user' => [
            'user_id' => (int)$data['user_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'],
            'branch_id' => $data['branch_id'] !== null ? (int)$data['branch_id'] : null,
            'branch_name' => $data['branch_name']
        ],
        'organization' => [
            'organization_id' => (int)$data['organization_id'],
            'organization_name' => $data['organization_name'],
            'organization_type' => $data['organization_type'],
            'email' => $data['organization_email'],
            'phone' => $data['phone'],
            'address' => $data['address']
        ]
    ]);

} catch (PDOException $e) {
    log_error('session', $e);
    respond(500, [
        'authenticated' => false,
        'error' => 'Unable to retrieve session information.'
    ]);
}
