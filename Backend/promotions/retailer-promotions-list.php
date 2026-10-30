<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/retailer-promotion-rules.php';

try {
    $actor = retailer_promotion_actor($pdo, false);
    $retailerId = (int)$actor['organization_id'];
    $today = retailer_promotion_today();
    $branchClause = '';
    $params = [$retailerId];

    if (in_array((string)$actor['role'], ['BRANCH_ADMIN', 'STAFF'], true)) {
        $branchClause = " AND (rp.created_by = ? OR EXISTS (
            SELECT 1 FROM retailer_promotion_items i
            WHERE i.retailer_promotion_id = rp.retailer_promotion_id
              AND i.retailer_id = rp.retailer_id AND i.branch_id = ?
        ))";
        $params[] = (int)$actor['user_id'];
        $params[] = (int)($actor['branch_id'] ?? 0);
    }

    $statement = $pdo->prepare(
        "SELECT rp.retailer_promotion_id, rp.promotion_name, rp.description,
                rp.start_date, rp.end_date, rp.status, rp.created_by, rp.created_at,
                (SELECT COUNT(*) FROM retailer_promotion_items i
                 WHERE i.retailer_promotion_id = rp.retailer_promotion_id
                   AND i.retailer_id = rp.retailer_id" .
            (in_array((string)$actor['role'], ['BRANCH_ADMIN', 'STAFF'], true) ? " AND i.branch_id = ?" : '') .
            ") AS item_count
         FROM retailer_promotions rp
         WHERE rp.retailer_id = ? $branchClause
         ORDER BY rp.updated_at DESC, rp.retailer_promotion_id DESC"
    );

    // Build positional parameters in SQL order: scalar subquery appears before WHERE.
    $orderedParams = [];
    if (in_array((string)$actor['role'], ['BRANCH_ADMIN', 'STAFF'], true)) {
        $orderedParams[] = (int)($actor['branch_id'] ?? 0);
    }
    $orderedParams = array_merge($orderedParams, $params);
    $statement->execute($orderedParams);
    $promotions = $statement->fetchAll(PDO::FETCH_ASSOC);

    $itemSql = "SELECT i.retailer_promotion_item_id, i.retailer_promotion_id,
                       i.branch_id, b.branch_name, i.product_id, i.product_version_id,
                       p.product_name, pv.pack_size, pv.barcode,
                       i.regular_price_snapshot, i.promotion_price, i.price_basis
                FROM retailer_promotion_items i
                INNER JOIN branches b ON b.branch_id = i.branch_id AND b.organization_id = i.retailer_id
                INNER JOIN products p ON p.product_id = i.product_id
                INNER JOIN product_versions pv ON pv.product_id = i.product_id
                    AND pv.product_version_id = i.product_version_id
                WHERE i.retailer_id = ?";
    $itemParams = [$retailerId];
    if (in_array((string)$actor['role'], ['BRANCH_ADMIN', 'STAFF'], true)) {
        $itemSql .= ' AND i.branch_id = ?';
        $itemParams[] = (int)($actor['branch_id'] ?? 0);
    }
    $itemSql .= ' ORDER BY b.branch_name, p.product_name';
    $itemStatement = $pdo->prepare($itemSql);
    $itemStatement->execute($itemParams);
    $itemsByPromotion = [];
    foreach ($itemStatement->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $itemsByPromotion[(int)$item['retailer_promotion_id']][] = $item;
    }

    foreach ($promotions as &$promotion) {
        $promotion['display_status'] = retailer_promotion_display_status($promotion, $today);
        $promotion['items'] = $itemsByPromotion[(int)$promotion['retailer_promotion_id']] ?? [];
        $promotion['can_manage'] = retailer_promotion_scope_allowed(
            $pdo, $actor, (int)$promotion['retailer_promotion_id']
        );
    }
    unset($promotion);

    respond(200, [
        'success' => true,
        'today' => $today,
        'role' => $actor['role'],
        'branch_id' => $actor['branch_id'],
        'promotions' => $promotions
    ]);
} catch (Throwable $e) {
    log_error('retailer promotions list', $e);
    respond_error(500, 'Unable to load your branch promotions.');
}
