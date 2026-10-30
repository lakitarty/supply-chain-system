<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/promotion-rules.php';
require_once __DIR__ . '/retailer-promotion-rules.php';

try {
    $actor = retailer_promotion_actor($pdo, false);
    $retailerId = (int)$actor['organization_id'];
    $today = retailer_promotion_today();

    $statement = $pdo->prepare(
        "SELECT p.promotion_id, p.organization_id AS manufacturer_id,
                p.promotion_name, p.description, p.start_date, p.end_date, p.status,
                p.created_at, m.organization_name AS manufacturer_name,
                pp.status AS participant_status, pp.joined_at
         FROM promotion_participants pp
         INNER JOIN promotions p ON p.promotion_id = pp.promotion_id
         INNER JOIN organizations m ON m.organization_id = p.organization_id
         WHERE pp.organization_id = ?
           AND p.status IN ('APPROVED', 'CANCELLED')
         ORDER BY p.created_at DESC, p.promotion_id DESC"
    );
    $statement->execute([$retailerId]);
    $offers = $statement->fetchAll(PDO::FETCH_ASSOC);

    $productsStatement = $pdo->prepare(
        "SELECT pp.promotion_product_id, pp.product_id, p.product_name,
                pp.product_version_id, pv.pack_size, pv.barcode,
                pp.rrp_snapshot, pp.promotion_price, pp.compensation_per_unit
         FROM promotion_products pp
         INNER JOIN products p ON p.product_id = pp.product_id
         LEFT JOIN product_versions pv ON pv.product_version_id = pp.product_version_id
         WHERE pp.promotion_id = ?
         ORDER BY p.product_name"
    );
    $branchSql = "SELECT pb.branch_id, b.branch_name, pb.status
                  FROM promotion_branches pb
                  INNER JOIN branches b ON b.branch_id = pb.branch_id
                  WHERE pb.promotion_id = ? AND b.organization_id = ?";
    if (in_array((string)$actor['role'], ['BRANCH_ADMIN', 'STAFF'], true)) {
        $branchSql .= ' AND b.branch_id = ?';
    }
    $branchSql .= ' ORDER BY b.branch_name';
    $branchesStatement = $pdo->prepare($branchSql);

    foreach ($offers as &$offer) {
        $offer['display_status'] = promotion_display_status($offer, $today);
        $productsStatement->execute([(int)$offer['promotion_id']]);
        $offer['products'] = $productsStatement->fetchAll(PDO::FETCH_ASSOC);
        $branchParams = [(int)$offer['promotion_id'], $retailerId];
        if (in_array((string)$actor['role'], ['BRANCH_ADMIN', 'STAFF'], true)) {
            $branchParams[] = (int)($actor['branch_id'] ?? 0);
        }
        $branchesStatement->execute($branchParams);
        $offer['branches'] = $branchesStatement->fetchAll(PDO::FETCH_ASSOC);
        $offer['can_respond'] = $actor['role'] === 'ADMIN'
            && $offer['participant_status'] === 'PENDING'
            && $offer['status'] === 'APPROVED'
            && $today <= (string)$offer['end_date'];
        $offer['can_start'] = in_array((string)$actor['role'], ['ADMIN', 'BRANCH_ADMIN'], true)
            && $offer['participant_status'] === 'ACCEPTED'
            && $offer['status'] === 'APPROVED'
            && $today >= (string)$offer['start_date']
            && $today <= (string)$offer['end_date'];
        $offer['can_complete'] = in_array((string)$actor['role'], ['ADMIN', 'BRANCH_ADMIN'], true)
            && $today > (string)$offer['end_date'];
    }
    unset($offer);

    respond(200, [
        'success' => true,
        'today' => $today,
        'role' => $actor['role'],
        'branch_id' => $actor['branch_id'],
        'branches' => retailer_promotion_allowed_branches($pdo, $actor),
        'offers' => $offers
    ]);
} catch (Throwable $e) {
    log_error('retailer campaign list', $e);
    respond_error(500, 'Unable to load manufacturer campaigns offered to your organisation.');
}
