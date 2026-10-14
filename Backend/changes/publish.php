<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/change-rules.php';

require_post();

try {
    $actor = require_admin($pdo);

    if (!can_manufacture($actor)) {
        respond_error(403, 'Only manufacturers can publish product changes.');
    }

    $input = clean_change_input(read_json_body());
    $change = find_own_change($pdo, $actor, $input['changeId']);

    if ($change === false) {
        respond_error(404, 'Change not found.');
    }

    $blocked = publish_block($pdo, $change);

    if ($blocked !== null) {
        respond_error(409, $blocked);
    }

    $pdo->beginTransaction();

    $recipientCount = publish_change($pdo, $actor, $change);

    record_audit(
        $pdo,
        $actor,
        'CHANGE_PUBLISHED',
        'PRODUCT_CHANGE',
        $input['changeId'],
        "Published the change for {$change['product_name']} to $recipientCount organisations"
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => "Published to $recipientCount organisations.",
        'recipients' => $recipientCount
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('changes publish', $e);

    respond_error(500, 'Unable to publish the change.');
}