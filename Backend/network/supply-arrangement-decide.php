<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/supply-arrangement-rules.php';

require_post();

try {
    $actor = require_admin($pdo);

    if ($actor['organization_type'] !== 'RETAILER') {
        respond_error(403, 'Retailer organisation administrator access is required.');
    }

    $body = read_json_body();
    $appointmentId = filter_var($body['appointmentId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $decision = (string)($body['decision'] ?? '');

    if ($appointmentId === false || $appointmentId === null) {
        respond_field_error(400, field_error('appointmentId', 'Select a supply arrangement.'));
    }

    if (!in_array($decision, ['ACCEPTED', 'DECLINED'], true)) {
        respond_field_error(400, field_error('decision', 'Choose accept or decline.'));
    }

    $retailerId = (int)$actor['organization_id'];
    $today = (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');
    $arrangements = supply_arrangements($pdo, $today, $retailerId, (int)$appointmentId);

    if ($arrangements === []) {
        respond_error(404, 'This supply arrangement is not available to your organisation.');
    }

    $arrangement = $arrangements[0];
    $verb = $decision === 'ACCEPTED' ? 'accepted' : 'declined';

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'INSERT INTO product_supply_acceptances (product_supplier_id, retailer_id, status, decided_by_user_id)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            decided_by_user_id = VALUES(decided_by_user_id),
            decided_at = NOW()'
    );
    $statement->execute([(int)$appointmentId, $retailerId, $decision, (int)$actor['user_id']]);

    record_audit(
        $pdo,
        $actor,
        'SUPPLY_ARRANGEMENT_' . $decision,
        'PRODUCT_SUPPLIER',
        (int)$appointmentId,
        "Retailer {$verb} the supply of {$arrangement['product_name']} by {$arrangement['supplier_name']} in {$arrangement['area_name']}."
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => "Supply arrangement {$verb}.",
        'decision' => $decision
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('supply arrangement decide', $e);
    respond_error(500, 'Unable to save your decision.');
}
