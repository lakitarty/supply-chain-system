<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/change-rules.php';

require_post();

try {
    $actor = require_login($pdo);
    $input = clean_change_input(read_json_body());

    $recipient = find_recipient($pdo, $actor, $input['changeId']);

    if ($recipient === false) {
        respond_error(404, 'This change was not sent to your organisation.');
    }

    if ($recipient['notification_status'] === 'ACKNOWLEDGED') {
        respond_error(409, 'This change is already acknowledged.');
    }

    $pdo->beginTransaction();

    acknowledge_change($pdo, $actor, $input['changeId']);

    record_audit($pdo, $actor, 'CHANGE_ACKNOWLEDGED', 'PRODUCT_CHANGE', $input['changeId'], 'Acknowledged a product change');

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Change acknowledged.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('changes acknowledge', $e);

    respond_error(500, 'Unable to acknowledge the change.');
}