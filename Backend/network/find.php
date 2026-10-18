<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/network-rules.php';

try {
    $actor = require_admin($pdo);
    $input = clean_network_input($_GET);
    $organization = find_organization_by_term($pdo, $actor, $input['term']);

    if ($organization === false) {
        respond_error(404, 'No organisation matches that exact name or KRA PIN.');
    }

    respond(200, ['success' => true, 'organization' => $organization]);

} catch (PDOException $e) {
    log_error('network find', $e);

    respond_error(500, 'Unable to search.');
}