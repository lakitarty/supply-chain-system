<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/retailer-promotion-rules.php';

require_post();

try {
    $actor = retailer_promotion_actor($pdo, true);
    $body = read_json_body();
    $promotionId = filter_var($body['promotionId'] ?? null, FILTER_VALIDATE_INT);
    $action = trim((string)($body['action'] ?? ''));
    if ($promotionId === false || $promotionId === null || $promotionId < 1) {
        respond_error(400, 'Select a valid branch promotion.');
    }
    if (!in_array($action, ['publish', 'cancel'], true)) {
        respond_error(400, 'Choose a valid branch promotion action.');
    }

    $retailerId = (int)$actor['organization_id'];
    $today = retailer_promotion_today();
    $pdo->beginTransaction();
    $lock = $pdo->prepare(
        'SELECT retailer_promotion_id, retailer_id, promotion_name, start_date, end_date, status
         FROM retailer_promotions WHERE retailer_promotion_id = ? AND retailer_id = ? FOR UPDATE'
    );
    $lock->execute([(int)$promotionId, $retailerId]);
    $promotion = $lock->fetch(PDO::FETCH_ASSOC);
    if (!$promotion) {
        $pdo->rollBack();
        respond_error(404, 'The branch promotion was not found.');
    }
    if (!retailer_promotion_scope_allowed($pdo, $actor, (int)$promotionId)) {
        $pdo->rollBack();
        respond_error(403, 'You cannot manage a promotion that includes branches outside your permissions.');
    }

    if ($action === 'publish') {
        if ((string)$promotion['status'] !== 'DRAFT') {
            $pdo->rollBack();
            respond_error(409, 'Only a draft branch promotion can be published.');
        }
        if ((string)$promotion['start_date'] < $today || (string)$promotion['end_date'] < $today) {
            $pdo->rollBack();
            respond_error(409, 'Choose a start and end date today or in the future before publishing.');
        }
        if ((string)$promotion['end_date'] < (string)$promotion['start_date']) {
            $pdo->rollBack();
            respond_error(400, 'The end date cannot be before the start date.');
        }
        $items = $pdo->prepare(
            'SELECT COUNT(*) FROM retailer_promotion_items WHERE retailer_promotion_id = ? AND retailer_id = ?'
        );
        $items->execute([(int)$promotionId, $retailerId]);
        if ((int)$items->fetchColumn() < 1) {
            $pdo->rollBack();
            respond_error(409, 'Add at least one product and branch before publishing.');
        }
        $status = ((string)$promotion['start_date'] > $today) ? 'SCHEDULED' : 'ACTIVE';
        $pdo->prepare(
            'UPDATE retailer_promotions SET status = ? WHERE retailer_promotion_id = ? AND retailer_id = ?'
        )->execute([$status, (int)$promotionId, $retailerId]);
        $auditAction = 'RETAILER_PROMOTION_PUBLISHED';
        $message = $status === 'ACTIVE' ? 'Branch promotion published and active.' : 'Branch promotion published and scheduled.';
    } else {
        if (!in_array((string)$promotion['status'], ['DRAFT', 'SCHEDULED', 'ACTIVE'], true)
            || $today > (string)$promotion['end_date']) {
            $pdo->rollBack();
            respond_error(409, 'This branch promotion can no longer be cancelled.');
        }
        $pdo->prepare(
            "UPDATE retailer_promotions SET status = 'CANCELLED'
             WHERE retailer_promotion_id = ? AND retailer_id = ?"
        )->execute([(int)$promotionId, $retailerId]);
        $auditAction = 'RETAILER_PROMOTION_CANCELLED';
        $message = 'Branch promotion cancelled.';
        $status = 'CANCELLED';
    }

    record_audit($pdo, $actor, $auditAction, 'RETAILER_PROMOTION', (int)$promotionId,
        'Branch promotion "' . $promotion['promotion_name'] . '": ' . $message);
    $pdo->commit();

    respond(200, [
        'success' => true,
        'promotion_id' => (int)$promotionId,
        'status' => $status,
        'message' => $message
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('retailer promotion transition', $e);
    respond_error(500, 'Unable to update the branch promotion.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('retailer promotion transition unexpected', $e);
    respond_error(500, 'Unable to update the branch promotion.');
}
