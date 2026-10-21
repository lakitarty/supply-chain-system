<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/network-rules.php';

try {
    $actor = require_admin($pdo);
    $relationships = fetch_relationships($pdo, $actor);

    respond(200, [
        'success' => true,
        'organization_id' => $actor['organization_id'],
        'relationships' => $relationships,
        'indirect' => fetch_indirect_connections($pdo, $actor, $relationships)
    ]);

} catch (PDOException $e) {
    log_error('network list', $e);

    respond_error(500, 'Unable to load the supply network.');
}