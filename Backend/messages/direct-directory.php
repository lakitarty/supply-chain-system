<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../network/network-rules.php';

try {
    $actor = require_login($pdo);

    $connectedIds = connected_organization_ids(
        $pdo,
        (int)$actor['organization_id']
    );

    $organizations = [];

    if ($connectedIds !== []) {
        $placeholders = implode(',', array_fill(0, count($connectedIds), '?'));

        $statement = $pdo->prepare(
            "SELECT organization_id, organization_name, organization_type, logo_path
             FROM organizations
             WHERE status = 'ACTIVE'
               AND organization_id IN ($placeholders)
             ORDER BY organization_name ASC"
        );

        $statement->execute($connectedIds);
        $organizations = $statement->fetchAll();
    }

    respond(200, [
        'success' => true,
        'organizations' => $organizations
    ]);
} catch (PDOException $e) {
    log_error('direct conversation directory', $e);
    respond_error(500, 'Unable to load organisations.');
}
