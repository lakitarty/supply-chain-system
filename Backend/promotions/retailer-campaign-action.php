<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/promotion-rules.php';
require_once __DIR__ . '/retailer-promotion-rules.php';

require_post();

try {
    $actor = retailer_promotion_actor($pdo, false);
    $body = read_json_body();
    $promotionId = filter_var($body['promotionId'] ?? null, FILTER_VALIDATE_INT);
    $action = trim((string)($body['action'] ?? ''));
    if ($promotionId === false || $promotionId === null || $promotionId < 1) {
        respond_error(400, 'Select a valid manufacturer campaign.');
    }
    if (!in_array($action, ['accept', 'decline', 'start_branch', 'complete_branch'], true)) {
        respond_error(400, 'Choose a valid manufacturer-campaign action.');
    }

    $role = (string)$actor['role'];
    if (in_array($action, ['accept', 'decline'], true) && $role !== 'ADMIN') {
        respond_error(403, 'Only your organisation administrator can accept or decline a manufacturer campaign.');
    }
    if (in_array($action, ['start_branch', 'complete_branch'], true)
        && !in_array($role, ['ADMIN', 'BRANCH_ADMIN'], true)) {
        respond_error(403, 'Your account cannot commence or complete a branch campaign.');
    }

    $retailerId = (int)$actor['organization_id'];
    $today = retailer_promotion_today();
    $branchId = filter_var($body['branchId'] ?? null, FILTER_VALIDATE_INT);
    $rawBranchIds = is_array($body['branchIds'] ?? null) ? $body['branchIds'] : [];

    $pdo->beginTransaction();
    $lock = $pdo->prepare(
        "SELECT p.promotion_id, p.organization_id AS manufacturer_id, p.promotion_name,
                p.start_date, p.end_date, p.status, pp.status AS participant_status
         FROM promotions p
         INNER JOIN promotion_participants pp ON pp.promotion_id = p.promotion_id
         WHERE p.promotion_id = ? AND pp.organization_id = ?
         LIMIT 1 FOR UPDATE"
    );
    $lock->execute([(int)$promotionId, $retailerId]);
    $campaign = $lock->fetch(PDO::FETCH_ASSOC);
    if (!$campaign) {
        $pdo->rollBack();
        respond_error(404, 'This campaign was not offered to your organisation.');
    }
    if ((string)$campaign['status'] !== 'APPROVED') {
        $pdo->rollBack();
        respond_error(409, 'Only an approved manufacturer campaign can be accepted or commenced.');
    }
    if ($today > (string)$campaign['end_date'] && $action !== 'complete_branch') {
        $pdo->rollBack();
        respond_error(409, 'This manufacturer campaign has expired.');
    }

    $manufacturerId = (int)$campaign['manufacturer_id'];
    $notificationTitle = '';
    $notificationMessage = '';
    $auditAction = '';

    if ($action === 'accept' || $action === 'decline') {
        if ((string)$campaign['participant_status'] !== 'PENDING') {
            $pdo->rollBack();
            respond_error(409, 'Your organisation has already responded to this campaign.');
        }

        if ($action === 'decline') {
            $pdo->prepare(
                "UPDATE promotion_participants SET status = 'DECLINED'
                 WHERE promotion_id = ? AND organization_id = ? AND status = 'PENDING'"
            )->execute([(int)$promotionId, $retailerId]);
            $auditAction = 'MANUFACTURER_CAMPAIGN_DECLINED';
            $notificationTitle = 'Retailer declined campaign';
            $notificationMessage = $actor['organization_id'] . ' declined campaign "' . $campaign['promotion_name'] . '".';
        } else {
            $allowedBranchIds = array_map(
                static fn(array $branch): int => (int)$branch['branch_id'],
                retailer_promotion_allowed_branches($pdo, $actor)
            );
            $branchIds = array_values(array_unique(array_filter(array_map('intval', $rawBranchIds))));
            if ($branchIds === []) {
                $pdo->rollBack();
                respond_error(400, 'Select at least one branch to participate.');
            }
            if (array_diff($branchIds, $allowedBranchIds) !== []) {
                $pdo->rollBack();
                respond_error(403, 'Every selected branch must belong to your organisation and be within your permissions.');
            }
            $existing = $pdo->prepare(
                'SELECT branch_id FROM promotion_branches WHERE promotion_id = ? AND branch_id IN (' .
                implode(',', array_fill(0, count($branchIds), '?')) . ')'
            );
            $existing->execute(array_merge([(int)$promotionId], $branchIds));
            if ($existing->fetchColumn() !== false) {
                $pdo->rollBack();
                respond_error(409, 'One or more branches have already been assigned to this campaign. Refresh the page.');
            }
            $insertBranch = $pdo->prepare(
                "INSERT INTO promotion_branches (promotion_id, branch_id, status) VALUES (?, ?, 'PENDING')"
            );
            foreach ($branchIds as $id) {
                $insertBranch->execute([(int)$promotionId, $id]);
            }
            $pdo->prepare(
                "UPDATE promotion_participants SET status = 'ACCEPTED', joined_at = NOW()
                 WHERE promotion_id = ? AND organization_id = ? AND status = 'PENDING'"
            )->execute([(int)$promotionId, $retailerId]);
            $auditAction = 'MANUFACTURER_CAMPAIGN_ACCEPTED';
            $notificationTitle = 'Retailer accepted campaign';
            $notificationMessage = 'A retailer accepted campaign "' . $campaign['promotion_name'] . '" and selected ' . count($branchIds) . ' branch(es).';
        }
    } else {
        if (!is_int($branchId) && !is_numeric($branchId)) {
            $pdo->rollBack();
            respond_error(400, 'Select a valid branch.');
        }
        $branchId = (int)$branchId;
        $branchCheck = $pdo->prepare(
            "SELECT b.branch_name, pb.status AS branch_promotion_status
             FROM branches b
             INNER JOIN promotion_branches pb ON pb.branch_id = b.branch_id
                AND pb.promotion_id = ?
             WHERE b.branch_id = ? AND b.organization_id = ? AND b.status = 'ACTIVE'
             LIMIT 1 FOR UPDATE"
        );
        $branchCheck->execute([(int)$promotionId, $branchId, $retailerId]);
        $branch = $branchCheck->fetch(PDO::FETCH_ASSOC);
        if (!$branch) {
            $pdo->rollBack();
            respond_error(403, 'This branch was not selected for the campaign or is not available to your organisation.');
        }
        if ($role === 'BRANCH_ADMIN' && (int)$actor['branch_id'] !== $branchId) {
            $pdo->rollBack();
            respond_error(403, 'A branch administrator can only manage the assigned branch.');
        }

        if ($action === 'start_branch') {
            if ((string)$campaign['participant_status'] !== 'ACCEPTED'
                && (string)$campaign['participant_status'] !== 'ACTIVE') {
                $pdo->rollBack();
                respond_error(409, 'Accept the campaign before commencing a branch.');
            }
            if ($today < (string)$campaign['start_date']) {
                $pdo->rollBack();
                respond_error(409, 'This campaign has not reached its start date.');
            }
            if ($today > (string)$campaign['end_date']) {
                $pdo->rollBack();
                respond_error(409, 'This campaign has expired.');
            }
            if ((string)$branch['branch_promotion_status'] !== 'PENDING') {
                $pdo->rollBack();
                respond_error(409, 'This branch is not waiting to commence the campaign.');
            }
            $pdo->prepare(
                "UPDATE promotion_branches SET status = 'ACTIVE'
                 WHERE promotion_id = ? AND branch_id = ? AND status = 'PENDING'"
            )->execute([(int)$promotionId, $branchId]);
            $pdo->prepare(
                "UPDATE promotion_participants SET status = 'ACTIVE'
                 WHERE promotion_id = ? AND organization_id = ? AND status IN ('ACCEPTED','ACTIVE')"
            )->execute([(int)$promotionId, $retailerId]);
            $auditAction = 'MANUFACTURER_CAMPAIGN_BRANCH_STARTED';
            $notificationTitle = 'Retailer commenced campaign';
            $notificationMessage = 'Campaign "' . $campaign['promotion_name'] . '" has commenced at branch ' . $branch['branch_name'] . '.';
        } else {
            if ($today <= (string)$campaign['end_date']) {
                $pdo->rollBack();
                respond_error(409, 'A branch can be completed after the campaign end date.');
            }
            if ((string)$branch['branch_promotion_status'] !== 'ACTIVE') {
                $pdo->rollBack();
                respond_error(409, 'Only an active branch campaign can be completed.');
            }
            $pdo->prepare(
                "UPDATE promotion_branches SET status = 'COMPLETED', completed_at = NOW()
                 WHERE promotion_id = ? AND branch_id = ? AND status = 'ACTIVE'"
            )->execute([(int)$promotionId, $branchId]);
            $remaining = $pdo->prepare(
                "SELECT COUNT(*) FROM promotion_branches
                 WHERE promotion_id = ? AND status IN ('PENDING','ACTIVE')"
            );
            $remaining->execute([(int)$promotionId]);
            if ((int)$remaining->fetchColumn() === 0) {
                $pdo->prepare(
                    "UPDATE promotion_participants SET status = 'COMPLETED'
                     WHERE promotion_id = ? AND organization_id = ? AND status IN ('ACCEPTED','ACTIVE')"
                )->execute([(int)$promotionId, $retailerId]);
            }
            $auditAction = 'MANUFACTURER_CAMPAIGN_BRANCH_COMPLETED';
            $notificationTitle = 'Retailer completed campaign';
            $notificationMessage = 'Campaign "' . $campaign['promotion_name'] . '" was marked complete at branch ' . $branch['branch_name'] . '.';
        }
    }

    record_audit(
        $pdo, $actor, $auditAction, 'PROMOTION', (int)$promotionId,
        'Campaign "' . $campaign['promotion_name'] . '" action: ' . $action . '.'
    );
    create_organization_notification(
        $pdo, $manufacturerId, 'PROMOTION', (int)$promotionId,
        $notificationTitle, $notificationMessage
    );
    $pdo->commit();

    respond(200, [
        'success' => true,
        'promotion_id' => (int)$promotionId,
        'action' => $action,
        'message' => $action === 'accept' ? 'Campaign accepted. You can commence selected branches on the campaign start date.' :
            ($action === 'decline' ? 'Campaign declined.' :
            ($action === 'start_branch' ? 'Campaign commenced at the selected branch.' :
                ($action === 'complete_branch' ? 'Branch campaign marked complete.' : 'Campaign response saved.')))
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'A branch has already been assigned to this campaign. Refresh and review the branch list.');
    }
    log_error('retailer campaign action', $e);
    respond_error(500, 'Unable to update retailer campaign participation.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('retailer campaign action unexpected', $e);
    respond_error(500, 'Unable to update retailer campaign participation.');
}
