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
    $partner = find_active_organization($pdo, $actor, $input['partnerId']);
    $error = first_request_error($pdo, $actor, $input, $partner);

    if ($error !== null) {
        respond_field_error(400, $error);
    }

    $pdo->beginTransaction();

    $relationshipId = insert_relationship($pdo, $actor, supply_pair($actor, $partner, $input['direction']));

    record_audit(
        $pdo,
        $actor,
        'RELATIONSHIP_REQUESTED',
        'RELATIONSHIP',
        $relationshipId,
        "Requested a supply relationship with {$partner['organization_name']}"
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Request sent. The other organisation must accept it.',
        'relationship_id' => $relationshipId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('network request', $e);

    respond_error(500, 'Unable to send the request.');
}