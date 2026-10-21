<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/organization-rules.php';
require_once __DIR__ . '/logo-rules.php';

require_post();

try {
    $actor = require_admin($pdo);
    $organization = find_organization($pdo, $actor);

    if (!$organization) {
        respond_error(404, 'Organisation not found.');
    }

    $oldPath = $organization['logo_path'] ?? null;

    if (!$oldPath) {
        respond(200, [
            'success' => true,
            'message' => 'This organisation has no logo.',
            'logo_path' => null
        ]);
    }

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'UPDATE organizations SET logo_path = NULL WHERE organization_id = ?'
    );

    $statement->execute([$actor['organization_id']]);

    record_audit(
        $pdo,
        $actor,
        'ORGANIZATION_LOGO_REMOVED',
        'ORGANIZATION',
        $actor['organization_id'],
        'Removed organisation logo.'
    );

    $pdo->commit();

    delete_organization_logo_file($oldPath);

    respond(200, [
        'success' => true,
        'message' => 'Organisation logo removed.',
        'logo_path' => null
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('organisation logo removal', $e);

    respond_error(500, 'Unable to remove the organisation logo.');
}