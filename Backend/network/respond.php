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

    $blocked = answer_block($relationship, $actor, $input['decision']);

    if ($blocked !== null) {
        respond_error(409, $blocked);
    }

    $accepted = $input['decision'] === 'ACCEPT';

    $pdo->beginTransaction();

    set_relationship_status($pdo, $input['relationshipId'], $accepted ? 'ACTIVE' : 'INACTIVE');

    record_audit(
        $pdo,
        $actor,
        $accepted ? 'RELATIONSHIP_ACCEPTED' : 'RELATIONSHIP_DECLINED',
        'RELATIONSHIP',
        $input['relationshipId'],
        ($accepted ? 'Accepted' : 'Declined') . ' a supply relationship request'
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => $accepted ? 'Relationship accepted.' : 'Request declined.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('network respond', $e);

    respond_error(500, 'Unable to answer the request.');
}