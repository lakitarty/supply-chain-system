<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/network-rules.php';

require_post();

try {
    $actor = require_admin($pdo);
    $input = clean_network_input(read_json_body());
    $relationship = find_own_relationship($pdo, $actor, $input['relationshipId']);

    if ($relationship === false) {
        respond_error(404, 'Relationship not found.');
    }

    $blocked = end_block($relationship, $actor);

    if ($blocked !== null) {
        respond_error(409, $blocked);
    }

    $pdo->beginTransaction();

    set_relationship_status($pdo, $input['relationshipId'], 'INACTIVE');

    record_audit($pdo, $actor, 'RELATIONSHIP_ENDED', 'RELATIONSHIP', $input['relationshipId'], 'Ended a supply relationship');

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Relationship ended.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('network end', $e);

    respond_error(500, 'Unable to end the relationship.');
}