<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/supply-arrangement-rules.php';

try {
    $actor = require_admin($pdo);

    if ($actor['organization_type'] !== 'RETAILER') {
        respond_error(403, 'Retailer organisation administrator access is required.');
    }

    $today = (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');

    respond(200, [
        'success' => true,
        'arrangements' => supply_arrangements($pdo, $today, (int)$actor['organization_id'])
    ]);
} catch (Throwable $e) {
    log_error('supply arrangements list', $e);
    respond_error(500, 'Unable to load supply arrangements.');
}
