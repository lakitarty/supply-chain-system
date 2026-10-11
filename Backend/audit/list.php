<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/audit-rules.php';

try {
    $actor = require_admin($pdo);
    $filters = clean_audit_filters($_GET);

    $error = first_audit_filter_error($filters);

    if ($error !== null) {
        respond_field_error(400, $error);
    }

    respond(200, [
        'success' => true,
        'entries' => fetch_audit_entries($pdo, $actor, $filters),
        'actions' => fetch_audit_actions($pdo, $actor),
        'limit' => AUDIT_ENTRY_LIMIT
    ]);

} catch (PDOException $e) {
    log_error('audit list', $e);

    respond_error(500, 'Unable to load the activity log.');
}