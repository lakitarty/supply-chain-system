<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $row = current_account_row($pdo);

    if ($row === null) {
        respond(200, ['authenticated' => false]);
    }

    respond(200, ['authenticated' => true] + account_payload($row));

} catch (PDOException $e) {
    log_error('session', $e);

    respond(500, [
        'authenticated' => false,
        'error' => 'Unable to retrieve session information.'
    ]);
}