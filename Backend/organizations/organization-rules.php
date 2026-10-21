<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/validation.php';

const ORGANIZATION_MAX_LENGTHS = [
    'organizationName' => 150,
    'kraPin' => 30,
    'organizationEmail' => 150,
    'organizationPhone' => 30,
    'organizationAddress' => 255
];

function clean_organization_input(array $data): array
{
    $text = fn(string $key): string => trim((string)($data[$key] ?? ''));

    return [
        'organizationName' => $text('organizationName'),
        'kraPin' => strtoupper($text('kraPin')),
        'organizationEmail' => strtolower($text('organizationEmail')),
        'organizationPhone' => $text('organizationPhone'),
        'organizationAddress' => $text('organizationAddress')
    ];
}

function first_organization_error(array $input): ?array
{
    if ($input['organizationName'] === '') {
        return field_error('organizationName', 'This field is required.');
    }

    foreach (ORGANIZATION_MAX_LENGTHS as $field => $maxLength) {
        if (mb_strlen($input[$field]) > $maxLength) {
            return field_error($field, "Must be $maxLength characters or fewer.");
        }
    }

    if ($input['organizationEmail'] !== '' && !filter_var($input['organizationEmail'], FILTER_VALIDATE_EMAIL)) {
        return field_error('organizationEmail', 'Enter a valid email address.');
    }

    if ($input['kraPin'] !== '' && !preg_match('/^[A-Z][0-9]{9}[A-Z]$/', $input['kraPin'])) {
        return field_error('kraPin', 'KRA PIN must look like A123456789Z.');
    }

    if ($input['organizationPhone'] !== '' && !preg_match('/^\+?[0-9][0-9\s\-()]{6,19}$/', $input['organizationPhone'])) {
        return field_error('organizationPhone', 'Enter a valid phone number.');
    }

    return null;
}

function kra_pin_conflict(PDO $pdo, string $kraPin, int $excludedOrganizationId = 0): ?array
{
    if ($kraPin === '') {
        return null;
    }

    return record_exists(
        $pdo,
        'SELECT 1 FROM organizations WHERE kra_pin = ? AND organization_id <> ? LIMIT 1',
        [$kraPin, $excludedOrganizationId]
    )
        ? field_error('kraPin', 'This KRA PIN is already registered.')
        : null;
}


function find_organization(PDO $pdo, array $actor): array|false
{
    $statement = $pdo->prepare(
        'SELECT organization_name, organization_type, kra_pin, email, phone, address, logo_path, status
         FROM organizations
         WHERE organization_id = ?
         LIMIT 1'
    );

    $statement->execute([$actor['organization_id']]);

    return $statement->fetch();
}

function update_organization(PDO $pdo, array $actor, array $input): void
{
    $statement = $pdo->prepare(
        'UPDATE organizations
         SET organization_name = ?, kra_pin = ?, email = ?, phone = ?, address = ?
         WHERE organization_id = ?'
    );

    $statement->execute([
        $input['organizationName'],
        optional_text($input['kraPin']),
        optional_text($input['organizationEmail']),
        optional_text($input['organizationPhone']),
        optional_text($input['organizationAddress']),
        $actor['organization_id']
    ]);
}