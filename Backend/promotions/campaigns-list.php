<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/promotion-rules.php';

try {
    $actor = promotion_require_manufacturer($pdo);
    $organizationId = (int)$actor['organization_id'];
    $today = finance_today();

    $statement = $pdo->prepare(
        'SELECT p.promotion_id, p.promotion_name, p.start_date, p.end_date, p.status,
                (SELECT COUNT(*) FROM promotion_products pp WHERE pp.promotion_id = p.promotion_id) AS product_count,
                (SELECT COUNT(*) FROM promotion_participants pr WHERE pr.promotion_id = p.promotion_id) AS retailer_count
         FROM promotions p
         WHERE p.organization_id = ?
         ORDER BY p.created_at DESC, p.promotion_id DESC'
    );
    $statement->execute([$organizationId]);
    $campaigns = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($campaigns as &$campaign) {
        $campaign['display_status'] = promotion_display_status($campaign, $today);
    }
    unset($campaign);

    $selectedId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($selectedId === false || $selectedId === null) {
        $selectedId = $campaigns === [] ? 0 : (int)$campaigns[0]['promotion_id'];
    }

    $selected = null;
    if ($selectedId > 0) {
        $promotion = promotion_load($pdo, $organizationId, (int)$selectedId);
        if ($promotion === null) {
            respond_error(404, 'The campaign was not found in your organisation.');
        }
        $selected = promotion_detail($pdo, $promotion, $today);
        $selected['can_approve'] = promotion_can_approve($pdo, $actor, $promotion);
    }

    respond(200, [
        'success' => true,
        'today' => $today,
        'campaigns' => $campaigns,
        'selected' => $selected
    ]);
} catch (Throwable $e) {
    log_error('promotion list', $e);
    respond_error(500, 'Unable to load campaigns.');
}