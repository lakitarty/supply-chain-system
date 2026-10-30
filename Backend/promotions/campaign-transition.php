<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/promotion-rules.php';

const PROMOTION_TRANSITIONS = [
    'submit' => [
        'from' => ['DRAFT'],
        'to' => 'SUBMITTED',
        'audit' => 'PROMOTION_SUBMITTED',
        'message' => 'The campaign was submitted for approval.'
    ],
    'approve' => [
        'from' => ['SUBMITTED'],
        'to' => 'APPROVED',
        'audit' => 'PROMOTION_APPROVED',
        'message' => 'The campaign was approved.'
    ],
    'return' => [
        'from' => ['SUBMITTED'],
        'to' => 'DRAFT',
        'audit' => 'PROMOTION_RETURNED',
        'message' => 'The campaign was returned to draft.'
    ],
    'cancel' => [
        'from' => ['DRAFT', 'SUBMITTED', 'APPROVED'],
        'to' => 'CANCELLED',
        'audit' => 'PROMOTION_CANCELLED',
        'message' => 'The campaign was cancelled.'
    ]
];

require_post();

try {
    $actor = promotion_require_manufacturer($pdo);
    $organizationId = (int)$actor['organization_id'];
    $today = finance_today();

    $body = read_json_body();
    $promotionId = filter_var($body['promotionId'] ?? null, FILTER_VALIDATE_INT);
    $action = (string)($body['action'] ?? '');
    $reason = trim((string)($body['reason'] ?? ''));

    if ($promotionId === false || $promotionId === null || $promotionId < 1) {
        respond_error(400, 'Select a valid campaign.');
    }
    if (!isset(PROMOTION_TRANSITIONS[$action])) {
        respond_error(400, 'Choose a valid action.');
    }
    $needsReason = in_array($action, ['return', 'cancel'], true);
    if ($needsReason && (mb_strlen($reason) < 3 || mb_strlen($reason) > 300)) {
        respond_error(400, 'Enter a reason of 3 to 300 characters.');
    }
    $transition = PROMOTION_TRANSITIONS[$action];

    $pdo->beginTransaction();
    $lock = $pdo->prepare(
        'SELECT promotion_id, promotion_name, start_date, end_date, status, created_by
         FROM promotions WHERE organization_id = ? FOR UPDATE'
    );
    $lock->execute([$organizationId]);
    $promotion = null;
    foreach ($lock->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ((int)$row['promotion_id'] === $promotionId) {
            $promotion = $row;
            break;
        }
    }
    if ($promotion === null) {
        $pdo->rollBack();
        respond_error(404, 'The campaign was not found in your organisation.');
    }

    $displayStatus = promotion_display_status($promotion, $today);
    if (!in_array((string)$promotion['status'], $transition['from'], true)) {
        $pdo->rollBack();
        respond_error(409, 'This action is not available while the campaign is ' . strtolower($displayStatus) . '.');
    }
    if ($action === 'cancel' && $displayStatus === 'EXPIRED') {
        $pdo->rollBack();
        respond_error(409, 'An expired campaign cannot be cancelled.');
    }
    if ($action === 'approve' && !promotion_can_approve($pdo, $actor, $promotion)) {
        $pdo->rollBack();
        respond_error(403, 'A different administrator must approve this campaign.');
    }

    if (in_array($action, ['submit', 'approve'], true)) {
        if ((string)$promotion['start_date'] < $today) {
            $pdo->rollBack();
            respond_error(409, 'The start date has already passed. Edit the draft and set new dates.');
        }

        $detail = promotion_detail($pdo, $promotion, $today);
        promotion_assert_campaign_rrp_snapshots($pdo, $promotionId);
        promotion_assert_choices($pdo, $organizationId, [
            'products' => array_map(
                static fn(array $product): array => ['productId' => (int)$product['product_id']],
                $detail['products']
            ),
            'retailers' => array_map(
                static fn(array $retailer): int => (int)$retailer['organization_id'],
                $detail['retailers']
            )
        ]);

        $conflicts = promotion_conflicts($pdo, $promotionId);
        if ($conflicts !== []) {
            $pdo->rollBack();
            respond_error(409, promotion_conflict_message($conflicts));
        }
    }

    $setClause = 'status = ?';
    $params = [$transition['to']];
    if ($action === 'approve') {
        $setClause .= ', approved_by = ?, approved_at = NOW()';
        $params[] = (int)$actor['user_id'];
    } elseif ($action === 'return') {
        $setClause .= ', approved_by = NULL, approved_at = NULL';
    }
    $params[] = $promotionId;
    $pdo->prepare("UPDATE promotions SET $setClause WHERE promotion_id = ?")->execute($params);

    if ($action === 'cancel') {
        $pdo->prepare(
            "UPDATE promotion_branches SET status = 'CANCELLED'
             WHERE promotion_id = ? AND status IN ('PENDING', 'ACTIVE')"
        )->execute([$promotionId]);
    }

    record_audit(
        $pdo,
        $actor,
        $transition['audit'],
        'PROMOTION',
        $promotionId,
        'Campaign "' . $promotion['promotion_name'] . '": ' . $transition['message'] . ($needsReason ? ' Reason: ' . $reason : '')
    );
    if ($action === 'approve' || ($action === 'cancel' && (string)$promotion['status'] === 'APPROVED')) {
    $recipients = $pdo->prepare(
        'SELECT organization_id FROM promotion_participants WHERE promotion_id = ?'
    );
    $recipients->execute([$promotionId]);
    $notificationTitle = $action === 'approve'
        ? 'New manufacturer campaign available'
        : 'Manufacturer campaign cancelled';
    $notificationMessage = $action === 'approve'
        ? sprintf('Campaign "%s" is approved and runs from %s to %s. Review the offer in Promotions > Manufacturer campaigns.', $promotion['promotion_name'], $promotion['start_date'], $promotion['end_date'])
        : sprintf('Campaign "%s" has been cancelled by the manufacturer.', $promotion['promotion_name']);
    foreach ($recipients->fetchAll(PDO::FETCH_COLUMN) as $recipientOrganizationId) {
        create_organization_notification(
            $pdo,
            (int)$recipientOrganizationId,
            'PROMOTION',
            (int)$promotionId,
            $notificationTitle,
            $notificationMessage
        );
    }
}
$pdo->commit();

    $newStatus = promotion_display_status(array_merge($promotion, ['status' => $transition['to']]), $today);
    respond(200, [
        'success' => true,
        'promotion_id' => $promotionId,
        'status' => $transition['to'],
        'display_status' => $newStatus,
        'message' => $transition['message']
    ]);
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 409;
    respond_error($status, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('promotion transition', $e);
    respond_error(500, 'Unable to update the campaign.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('promotion transition unexpected', $e);
    respond_error(500, 'Unable to update the campaign.');
}