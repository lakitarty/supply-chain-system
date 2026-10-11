<?php

declare(strict_types=1);
   require_once __DIR__ . '/../core/validation.php';
   require_once __DIR__ . '/organization-rules.php';
const ORGANIZATION_TYPES = ['MANUFACTURER', 'SUPPLIER', 'MANUFACTURER_SUPPLIER', 'RETAILER'];

const REGISTRATION_REQUIRED_FIELDS = [
    'organizationType',
    'branchName',
    'branchLocation',
    'firstName',
    'lastName',
    'adminEmail',
    'password'
];
   const REGISTRATION_MAX_LENGTHS = [
       'branchName' => 150,
       'branchLocation' => 255,
       'firstName' => 100,
       'lastName' => 100,
       'adminEmail' => 150
   ];


   function clean_registration_input(array $data): array
   {
       $text = fn(string $key): string => trim((string)($data[$key] ?? ''));

       return clean_organization_input($data) + [
           'organizationType' => $text('organizationType'),
           'branchName' => $text('branchName'),
           'branchLocation' => $text('branchLocation'),
           'firstName' => $text('firstName'),
           'lastName' => $text('lastName'),
           'adminEmail' => strtolower($text('adminEmail')),
           'password' => (string)($data['password'] ?? '')
       ];
   }



   function first_registration_error(array $input): ?array
   {
       foreach (REGISTRATION_REQUIRED_FIELDS as $field) {
           if ($input[$field] === '') {
               return field_error($field, 'This field is required.');
           }
       }

       if (!in_array($input['organizationType'], ORGANIZATION_TYPES, true)) {
           return field_error('organizationType', 'Choose a valid organization type.');
       }

       $organizationError = first_organization_error($input);

       if ($organizationError !== null) {
           return $organizationError;
       }

       foreach (REGISTRATION_MAX_LENGTHS as $field => $maxLength) {
           if (mb_strlen($input[$field]) > $maxLength) {
               return field_error($field, "Must be $maxLength characters or fewer.");
           }
       }

       if (!filter_var($input['adminEmail'], FILTER_VALIDATE_EMAIL)) {
           return field_error('adminEmail', 'Enter a valid email address.');
       }

       $passwordMessage = password_error($input['password']);

       return $passwordMessage !== null ? field_error('password', $passwordMessage) : null;
   }
   function find_registration_conflict(PDO $pdo, array $input): ?array
   {
       if (record_exists($pdo, 'SELECT 1 FROM users WHERE email = ? LIMIT 1', [$input['adminEmail']])) {
           return field_error('adminEmail', 'This email is already registered.');
       }

       return kra_pin_conflict($pdo, $input['kraPin']);
   }

function create_registration(PDO $pdo, array $input): int
{
    $pdo->beginTransaction();

    try {
        $organization = $pdo->prepare(
            "INSERT INTO organizations
                (organization_name, organization_type, kra_pin, email, phone, address, status)
             VALUES (?, ?, ?, ?, ?, ?, 'ACTIVE')"
        );
        $organization->execute([
            $input['organizationName'],
            $input['organizationType'],
            optional_text($input['kraPin']),
            optional_text($input['organizationEmail']),
            optional_text($input['organizationPhone']),
            optional_text($input['organizationAddress'])
        ]);
        $organizationId = (int)$pdo->lastInsertId();

        $branch = $pdo->prepare(
            "INSERT INTO branches
                (organization_id, branch_name, location, is_main_branch, status)
             VALUES (?, ?, ?, 1, 'ACTIVE')"
        );
        $branch->execute([$organizationId, $input['branchName'], $input['branchLocation']]);
        $branchId = (int)$pdo->lastInsertId();

        $user = $pdo->prepare(
            "INSERT INTO users
                (organization_id, branch_id, first_name, last_name, email, password_hash, role, status)
             VALUES (?, ?, ?, ?, ?, ?, 'ADMIN', 'ACTIVE')"
        );
        $user->execute([
            $organizationId,
            $branchId,
            $input['firstName'],
            $input['lastName'],
            $input['adminEmail'],
            password_hash($input['password'], PASSWORD_DEFAULT)
        ]);
        $userId = (int)$pdo->lastInsertId();

        $pdo->commit();

        return $userId;

    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}