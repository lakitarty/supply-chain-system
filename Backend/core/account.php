<?php

declare(strict_types=1);

const ACCOUNT_LOOKUP_COLUMNS = [
    'email' => 'u.email',
    'user_id' => 'u.user_id'
];

function fetch_account(PDO $pdo, string $lookup, string|int $value): array|false
{
    $column = ACCOUNT_LOOKUP_COLUMNS[$lookup];

    $statement = $pdo->prepare(
        "SELECT u.user_id,
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
         WHERE $column = ?
         LIMIT 1"
    );

    $statement->execute([$value]);

    return $statement->fetch();
}

function inactive_reason(array $row): ?string
{
    if ($row['user_status'] !== 'ACTIVE') {
        return 'This user account is inactive. Contact your administrator.';
    }

    if ($row['organization_status'] !== 'ACTIVE') {
        return 'Your organization account is inactive.';
    }

    return null;
}

function account_payload(array $row): array
{
    return [
        'user' => [
            'user_id' => (int)$row['user_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'role' => $row['role'],
            'branch_id' => $row['branch_id'] !== null ? (int)$row['branch_id'] : null,
            'branch_name' => $row['branch_name']
        ],
        'organization' => [
            'organization_id' => (int)$row['organization_id'],
            'organization_name' => $row['organization_name'],
            'organization_type' => $row['organization_type'],
            'email' => $row['organization_email'],
            'phone' => $row['organization_phone'],
            'address' => $row['organization_address']
        ]
    ];
}