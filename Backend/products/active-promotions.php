<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/product-rules.php';
require_once __DIR__ . '/../promotions/retailer-promotion-rules.php';

try {
    $actor = retailer_promotion_actor($pdo, false);
    $retailerId = (int)$actor['organization_id'];
    $role = (string)$actor['role'];
    $today = retailer_promotion_today();

    $versionId = filter_var($_GET['product_version_id'] ?? null, FILTER_VALIDATE_INT);
    if ($versionId === false || $versionId === null || $versionId < 1) {
        respond_error(400, 'A valid product version ID is required.');
    }

    // Confirm the requested version belongs to a product visible to this retailer.
    $visibleProductId = null;
    foreach (fetch_visible_products($pdo, $actor) as $product) {
        foreach (($product['versions'] ?? []) as $version) {
            if ((int)$version['product_version_id'] === (int)$versionId) {
                $visibleProductId = (int)$product['product_id'];
                break 2;
            }
        }
    }

    if ($visibleProductId === null) {
        respond_error(404, 'This product version is not available in your catalogue.');
    }

    $branchScoped = in_array($role, ['BRANCH_ADMIN', 'STAFF'], true);
    $branchId = (int)($actor['branch_id'] ?? 0);
    if ($branchScoped && $branchId < 1) {
        respond_error(403, 'Your account must be assigned to an active branch.');
    }
    $branchSuffixLocal = $branchScoped ? ' AND b.branch_id = ?' : '';
    $branchSuffixManufacturer = $branchScoped ? ' AND pb.branch_id = ?' : '';

    // Retailer-created promotions: the branch price is visible only while
    // the campaign is published and today's Nairobi date is inside its period.
    $localSql = "SELECT
                    'RETAILER' AS source_type,
                    rp.retailer_promotion_id AS promotion_id,
                    rp.promotion_name,
                    rp.start_date,
                    rp.end_date,
                    i.promotion_price,
                    i.regular_price_snapshot AS reference_price,
                    i.branch_id,
                    b.branch_name
                 FROM retailer_promotions rp
                 INNER JOIN retailer_promotion_items i
                    ON i.retailer_promotion_id = rp.retailer_promotion_id
                   AND i.retailer_id = rp.retailer_id
                 INNER JOIN branches b
                    ON b.branch_id = i.branch_id
                   AND b.organization_id = i.retailer_id
                   AND b.status = 'ACTIVE'
                 INNER JOIN products prod
                    ON prod.product_id = i.product_id
                   AND prod.status = 'ACTIVE'
                 INNER JOIN product_versions pv
                    ON pv.product_id = i.product_id
                   AND pv.product_version_id = i.product_version_id
                 WHERE rp.retailer_id = ?
                   AND i.product_id = ?
                   AND i.product_version_id = ?
                   AND rp.status IN ('ACTIVE', 'SCHEDULED')
                   AND rp.start_date <= ?
                   AND rp.end_date >= ?
                   AND pv.effective_from <= ?
                   AND (pv.effective_to IS NULL OR pv.effective_to >= ?)" . $branchSuffixLocal . "
                 ORDER BY rp.end_date, b.branch_name, rp.promotion_name";

    $localParams = [
        $retailerId,
        $visibleProductId,
        (int)$versionId,
        $today,
        $today,
        $today,
        $today
    ];
    if ($branchScoped) {
        $localParams[] = $branchId;
    }
    $localStatement = $pdo->prepare($localSql);
    $localStatement->execute($localParams);
    $localPromotions = $localStatement->fetchAll(PDO::FETCH_ASSOC);

    // Manufacturer campaigns only appear for branches that have accepted and
    // actually commenced the campaign. Approved-but-pending offers are not live.
    $manufacturerSql = "SELECT
                            'MANUFACTURER' AS source_type,
                            p.promotion_id,
                            p.promotion_name,
                            p.start_date,
                            p.end_date,
                            pp.promotion_price,
                            pp.rrp_snapshot AS reference_price,
                            pb.branch_id,
                            b.branch_name
                        FROM promotions p
                        INNER JOIN promotion_participants participant
                            ON participant.promotion_id = p.promotion_id
                        INNER JOIN promotion_branches pb
                            ON pb.promotion_id = p.promotion_id
                           AND pb.status = 'ACTIVE'
                        INNER JOIN branches b
                            ON b.branch_id = pb.branch_id
                           AND b.organization_id = ?
                           AND b.status = 'ACTIVE'
                        INNER JOIN promotion_products pp
                            ON pp.promotion_id = p.promotion_id
                           AND pp.product_id = ?
                           AND pp.product_version_id = ?
                        INNER JOIN products prod
                            ON prod.product_id = pp.product_id
                           AND prod.status = 'ACTIVE'
                        INNER JOIN product_versions pv
                            ON pv.product_id = pp.product_id
                           AND pv.product_version_id = pp.product_version_id
                        WHERE participant.organization_id = ?
                          AND participant.status = 'ACTIVE'
                          AND p.status IN ('APPROVED', 'SCHEDULED', 'ACTIVE')
                          AND p.start_date <= ?
                          AND p.end_date >= ?
                          AND pv.effective_from <= ?
                          AND (pv.effective_to IS NULL OR pv.effective_to >= ?)" . $branchSuffixManufacturer . "
                        ORDER BY p.end_date, b.branch_name, p.promotion_name";

    $manufacturerParams = [
        $retailerId,
        $visibleProductId,
        (int)$versionId,
        $retailerId,
        $today,
        $today,
        $today,
        $today
    ];
    if ($branchScoped) {
        $manufacturerParams[] = $branchId;
    }
    $manufacturerStatement = $pdo->prepare($manufacturerSql);
    $manufacturerStatement->execute($manufacturerParams);
    $manufacturerPromotions = $manufacturerStatement->fetchAll(PDO::FETCH_ASSOC);

    $promotions = array_merge($localPromotions, $manufacturerPromotions);
    $promotions = array_values(array_filter(
        $promotions,
        static fn(array $row): bool =>
            (string)$row['start_date'] <= $today &&
            (string)$row['end_date'] >= $today &&
            (float)$row['promotion_price'] > 0
    ));

    usort($promotions, static function (array $a, array $b): int {
        $endCompare = strcmp((string)$a['end_date'], (string)$b['end_date']);
        if ($endCompare !== 0) {
            return $endCompare;
        }
        $branchCompare = strcmp((string)$a['branch_name'], (string)$b['branch_name']);
        if ($branchCompare !== 0) {
            return $branchCompare;
        }
        return strcmp((string)$a['promotion_name'], (string)$b['promotion_name']);
    });

    respond(200, [
        'success' => true,
        'today' => $today,
        'product_id' => $visibleProductId,
        'product_version_id' => (int)$versionId,
        'branch_scoped' => $branchScoped,
        'promotions' => $promotions
    ]);
} catch (PDOException $e) {
    log_error('active catalogue promotions', $e);
    respond_error(500, 'Unable to load active product promotions.');
} catch (Throwable $e) {
    log_error('active catalogue promotions unexpected', $e);
    respond_error(500, 'Unable to load active product promotions.');
}
