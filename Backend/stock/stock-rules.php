<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../products/product-rules.php';

const NEXORA_STOCK_SALES_WINDOW_DAYS = 14;
const NEXORA_STOCK_COVER_DAYS = 7;
const NEXORA_STOCK_DEFAULT_LEAD_TIME_DAYS = 3;

function stock_require_retailer(array $actor): void
{
    if (($actor['organization_type'] ?? null) !== 'RETAILER') {
        respond_error(403, 'Stock is available to retailers only.');
    }

    if (!in_array($actor['role'] ?? '', ['ADMIN', 'BRANCH_ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Your account does not have access to stock management.');
    }
}

function stock_whole_number(mixed $value): ?int
{
    if (!is_int($value) && !is_string($value)) {
        return null;
    }

    $text = trim((string)$value);
    if ($text === '' || !preg_match('/^\d{1,9}$/', $text)) {
        return null;
    }

    return (int)$text;
}

function stock_optional_id(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : (int)$id;
}

function stock_branch_options(PDO $pdo, array $actor): array
{
    if ($actor['role'] === 'ADMIN') {
        $statement = $pdo->prepare(
            "SELECT b.branch_id, b.branch_name, b.area_id, area.area_name,
                    b.is_main_branch
             FROM branches b
             LEFT JOIN distribution_areas area ON area.area_id = b.area_id
             WHERE b.organization_id = ? AND b.status = 'ACTIVE'
             ORDER BY b.is_main_branch DESC, b.branch_name"
        );
        $statement->execute([$actor['organization_id']]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    $branchId = $actor['branch_id'] ?? null;
    if ($branchId === null) {
        return [];
    }

    $statement = $pdo->prepare(
        "SELECT b.branch_id, b.branch_name, b.area_id, area.area_name,
                b.is_main_branch
         FROM branches b
         LEFT JOIN distribution_areas area ON area.area_id = b.area_id
         WHERE b.branch_id = ?
           AND b.organization_id = ?
           AND b.status = 'ACTIVE'
         LIMIT 1"
    );
    $statement->execute([(int)$branchId, $actor['organization_id']]);
    $branch = $statement->fetch(PDO::FETCH_ASSOC);

    return $branch ? [$branch] : [];
}

function stock_resolve_branch(PDO $pdo, array $actor, ?int $requestedBranchId): array
{
    stock_require_retailer($actor);

    if ($actor['role'] === 'BRANCH_ADMIN') {
        $actor = require_branch_admin($pdo);
    }

    if ($actor['role'] === 'ADMIN') {
        $branchId = $requestedBranchId;
        if ($branchId === null) {
            $statement = $pdo->prepare(
                "SELECT branch_id
                 FROM branches
                 WHERE organization_id = ? AND status = 'ACTIVE'
                 ORDER BY is_main_branch DESC, branch_id
                 LIMIT 1"
            );
            $statement->execute([$actor['organization_id']]);
            $defaultId = $statement->fetchColumn();
            if ($defaultId === false) {
                respond_error(409, 'Create or activate a branch before managing stock.');
            }
            $branchId = (int)$defaultId;
        }
    } else {
        $branchId = $actor['branch_id'] !== null ? (int)$actor['branch_id'] : null;
        if ($branchId === null) {
            respond_error(403, 'Your account must be assigned to an active branch.');
        }
        if ($requestedBranchId !== null && $requestedBranchId !== $branchId) {
            respond_error(403, 'You can only manage stock for your assigned branch.');
        }
    }

    $statement = $pdo->prepare(
        "SELECT b.branch_id, b.organization_id, b.branch_name, b.area_id,
                area.area_name, b.is_main_branch
         FROM branches b
         LEFT JOIN distribution_areas area ON area.area_id = b.area_id
         WHERE b.branch_id = ?
           AND b.organization_id = ?
           AND b.status = 'ACTIVE'
         LIMIT 1"
    );
    $statement->execute([$branchId, $actor['organization_id']]);
    $branch = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$branch) {
        respond_error(404, 'An active branch belonging to your organisation was not found.');
    }

    $branch['branch_id'] = (int)$branch['branch_id'];
    $branch['organization_id'] = (int)$branch['organization_id'];
    $branch['area_id'] = $branch['area_id'] !== null ? (int)$branch['area_id'] : null;
    $branch['is_main_branch'] = (int)$branch['is_main_branch'];

    return $branch;
}

function stock_validate_product_version(PDO $pdo, array $actor, int $productId, int $versionId): array
{
    $manufacturerIds = array_map('intval', visible_manufacturer_ids($pdo, $actor));
    if ($manufacturerIds === []) {
        respond_field_error(400, field_error('productId', 'No products are available to this organisation.'));
    }

    $statement = $pdo->prepare(
        "SELECT p.product_id, p.product_name, p.manufacturer_id,
                v.product_version_id, v.version_number, v.barcode, v.pack_size,
                v.packaging_description, v.is_current
         FROM products p
         INNER JOIN product_versions v ON v.product_id = p.product_id
         WHERE p.product_id = ?
           AND v.product_version_id = ?
           AND p.status = 'ACTIVE'
           AND p.manufacturer_id IN (" . sql_placeholders($manufacturerIds) . ")
         LIMIT 1"
    );
    $statement->execute(array_merge([$productId, $versionId], $manufacturerIds));
    $product = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        respond_error(404, 'The selected product version is not available to your organisation.');
    }

    return $product;
}

function stock_status(int $onHand, int $reorderLevel, float $averageDailySales, int $leadTimeDays, bool $recorded): string
{
    if (!$recorded) {
        return 'NOT_TRACKED';
    }

    if ($onHand <= 0) {
        return 'OUT_OF_STOCK';
    }

    $daysLeft = $averageDailySales > 0 ? $onHand / $averageDailySales : null;
    $runsOutBeforeDelivery = $daysLeft !== null && $daysLeft <= $leadTimeDays;

    return ($onHand <= $reorderLevel || $runsOutBeforeDelivery) ? 'LOW_STOCK' : 'NORMAL';
}

function stock_recommended_quantity(
    string $status,
    int $onHand,
    int $reorderLevel,
    float $averageDailySales,
    int $leadTimeDays,
    int $minimumOrderQuantity,
    int $orderMultiple,
    bool $supplierAvailable
): int {
    if (!in_array($status, ['LOW_STOCK', 'OUT_OF_STOCK'], true)) {
        return 0;
    }

    $minimumOrderQuantity = max(1, $minimumOrderQuantity);
    $orderMultiple = max(1, $orderMultiple);
    $coverDemand = round($averageDailySales * ($leadTimeDays + NEXORA_STOCK_COVER_DAYS), 6);
    $target = max($coverDemand, (float)$reorderLevel, (float)($onHand + 1));
    $needed = max(1, (int)ceil($target - $onHand));

    if (!$supplierAvailable) {
        return $needed;
    }

    $quantity = max($needed, $minimumOrderQuantity);

    return (int)(ceil($quantity / $orderMultiple) * $orderMultiple);
}

function stock_analyze_row(array $row): array
{
    $onHand = (int)$row['on_hand'];
    $reorderLevel = (int)$row['reorder_level'];
    $recorded = $row['retailer_stock_id'] !== null;
    $leadTimeDays = (int)$row['lead_time_days'];
    $minimumOrderQuantity = (int)$row['minimum_order_quantity'];
    $orderMultiple = (int)$row['order_multiple'];
    $rawAverage = ((float)$row['sold_recently']) / NEXORA_STOCK_SALES_WINDOW_DAYS;
    $averageDailySales = round($rawAverage, 2);
    $status = stock_status($onHand, $reorderLevel, $rawAverage, $leadTimeDays, $recorded);
    $supplierAvailable = $row['supplier_id'] !== null;

    $row['product_id'] = (int)$row['product_id'];
    $row['product_version_id'] = (int)$row['product_version_id'];
    $row['version_number'] = (int)$row['version_number'];
    $row['is_current'] = (int)$row['is_current'];
    $row['on_hand'] = $onHand;
    $row['reorder_level'] = $reorderLevel;
    $row['stock_recorded'] = $recorded;
    $row['sold_recently'] = (int)$row['sold_recently'];
    $row['average_daily_sales'] = $averageDailySales;
    $row['days_left'] = $rawAverage > 0 ? round($onHand / $rawAverage, 1) : null;
    $row['lead_time_days'] = $leadTimeDays;
    $row['lead_time_is_estimate'] = !$supplierAvailable;
    $row['minimum_order_quantity'] = $minimumOrderQuantity;
    $row['order_multiple'] = $orderMultiple;
    $row['supplier_available'] = $supplierAvailable;
    $row['supplier_id'] = $row['supplier_id'] !== null ? (int)$row['supplier_id'] : null;
    $row['recommended_quantity'] = stock_recommended_quantity(
        $status,
        $onHand,
        $reorderLevel,
        $rawAverage,
        $leadTimeDays,
        $minimumOrderQuantity,
        $orderMultiple,
        $supplierAvailable
    );
    $row['status'] = $status;

    return $row;
}

function stock_fetch_rows(
    PDO $pdo,
    array $actor,
    array $branch,
    ?int $productId = null,
    ?int $versionId = null
): array {
    $manufacturerIds = array_map('intval', visible_manufacturer_ids($pdo, $actor));
    if ($manufacturerIds === []) {
        return [];
    }

    $productFilter = $productId === null ? '' : ' AND p.product_id = ?';
    $versionFilter = $versionId === null ? '' : ' AND v.product_version_id = ?';
    $windowStartDays = NEXORA_STOCK_SALES_WINDOW_DAYS - 1;

    $sql =
        "SELECT p.product_id, p.product_name, p.category,
                manufacturer.organization_name AS manufacturer_name,
                v.product_version_id, v.version_number, v.barcode, v.pack_size,
                v.packaging_description, v.effective_from, v.effective_to, v.is_current,
                rs.retailer_stock_id, rs.last_updated,
                COALESCE(rs.quantity_on_hand, 0) AS on_hand,
                COALESCE(rs.reorder_level, 0) AS reorder_level,
                COALESCE(recent_sales.sold_recently, 0) AS sold_recently,
                COALESCE(sps.lead_time_days, " . NEXORA_STOCK_DEFAULT_LEAD_TIME_DAYS . ") AS lead_time_days,
                COALESCE(sps.minimum_order_quantity, 1) AS minimum_order_quantity,
                COALESCE(sps.order_multiple, 1) AS order_multiple,
                sps.supplier_id, supplier.organization_name AS supplier_name,
                sps.availability_status AS supplier_availability_status
         FROM products p
         INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id
         INNER JOIN product_versions v ON v.product_id = p.product_id
         LEFT JOIN retailer_stock rs
            ON rs.retailer_id = ?
           AND rs.branch_id = ?
           AND rs.product_id = p.product_id
           AND rs.product_version_id = v.product_version_id
         LEFT JOIN (
            SELECT sh.product_id, sh.product_version_id, SUM(sh.units_sold) AS sold_recently
            FROM sales_history sh
            WHERE sh.retailer_id = ?
              AND sh.branch_id = ?
              AND sh.sale_date >= DATE_SUB(CURDATE(), INTERVAL " . $windowStartDays . " DAY)
              AND sh.sale_date < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
              AND sh.product_version_id IS NOT NULL
            GROUP BY sh.product_id, sh.product_version_id
         ) recent_sales
            ON recent_sales.product_id = p.product_id
           AND recent_sales.product_version_id = v.product_version_id
         LEFT JOIN supplier_product_supply sps
            ON sps.supplier_product_supply_id = (
                SELECT sps2.supplier_product_supply_id
                FROM product_suppliers ps2
                INNER JOIN supplier_product_supply sps2
                    ON sps2.product_id = ps2.product_id
                   AND sps2.supplier_id = ps2.supplier_id
                WHERE ps2.product_id = p.product_id
                  AND ps2.area_id = ?
                  AND ps2.status = 'ACTIVE'
                  AND ps2.start_date <= CURDATE()
                  AND (ps2.end_date IS NULL OR ps2.end_date >= CURDATE())
                  AND sps2.availability_status IN ('AVAILABLE', 'LIMITED')
                ORDER BY sps2.lead_time_days ASC, ps2.is_exclusive DESC,
                         ps2.start_date DESC, ps2.product_supplier_id DESC
                LIMIT 1
            )
         LEFT JOIN organizations supplier ON supplier.organization_id = sps.supplier_id
         WHERE p.status = 'ACTIVE'
           AND p.manufacturer_id IN (" . sql_placeholders($manufacturerIds) . ")" .
           $productFilter . $versionFilter .
         " ORDER BY p.product_name, v.is_current DESC, v.version_number DESC";

    $params = [
        (int)$actor['organization_id'],
        (int)$branch['branch_id'],
        (int)$actor['organization_id'],
        (int)$branch['branch_id'],
        $branch['area_id']
    ];
    $params = array_merge($params, $manufacturerIds);
    if ($productId !== null) {
        $params[] = $productId;
    }
    if ($versionId !== null) {
        $params[] = $versionId;
    }

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return array_map('stock_analyze_row', $statement->fetchAll(PDO::FETCH_ASSOC));
}

function stock_fetch_integrity_summary(PDO $pdo, array $actor): array
{
    $statement = $pdo->prepare(
        "SELECT rs.retailer_stock_id, rs.product_id, p.product_name,
                rs.product_version_id, version_product.product_name AS version_product_name,
                pv.version_number, pv.barcode, rs.branch_id,
                rs.quantity_on_hand, rs.reorder_level, rs.last_updated,
                CASE
                    WHEN pv.product_version_id IS NULL THEN 'MISSING_PRODUCT_VERSION'
                    WHEN pv.product_id <> rs.product_id THEN 'PRODUCT_VERSION_MISMATCH'
                    WHEN rs.branch_id IS NULL THEN 'BRANCH_UNALLOCATED'
                    ELSE 'REVIEW_REQUIRED'
                END AS issue_code
         FROM retailer_stock rs
         INNER JOIN products p ON p.product_id = rs.product_id
         LEFT JOIN product_versions pv ON pv.product_version_id = rs.product_version_id
         LEFT JOIN products version_product ON version_product.product_id = pv.product_id
         WHERE rs.retailer_id = ?
           AND (rs.branch_id IS NULL OR pv.product_version_id IS NULL OR pv.product_id <> rs.product_id)
         ORDER BY rs.retailer_stock_id"
    );
    $statement->execute([$actor['organization_id']]);
    $stockIssues = $statement->fetchAll(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare(
        "SELECT COUNT(*) FROM sales_history
         WHERE retailer_id = ? AND (branch_id IS NULL OR product_version_id IS NULL)"
    );
    $statement->execute([$actor['organization_id']]);
    $legacySalesCount = (int)$statement->fetchColumn();

    $statement = $pdo->prepare(
        "SELECT a.stock_alert_id, a.product_id, p.product_name, a.product_version_id,
                version_product.product_name AS version_product_name, a.branch_id,
                a.alert_type, a.status, a.current_stock, a.generated_at,
                CASE
                    WHEN pv.product_version_id IS NULL THEN 'VERSION_UNASSIGNED_OR_MISSING'
                    WHEN pv.product_id <> a.product_id THEN 'PRODUCT_VERSION_MISMATCH'
                    WHEN a.branch_id IS NULL THEN 'BRANCH_UNALLOCATED'
                    ELSE 'REVIEW_REQUIRED'
                END AS issue_code
         FROM stock_alerts a
         INNER JOIN products p ON p.product_id = a.product_id
         LEFT JOIN product_versions pv ON pv.product_version_id = a.product_version_id
         LEFT JOIN products version_product ON version_product.product_id = pv.product_id
         WHERE a.retailer_id = ? AND a.status = 'ACTIVE'
           AND (a.branch_id IS NULL OR pv.product_version_id IS NULL OR pv.product_id <> a.product_id)
         ORDER BY a.generated_at DESC"
    );
    $statement->execute([$actor['organization_id']]);
    $legacyAlerts = $statement->fetchAll(PDO::FETCH_ASSOC);

    return [
        'stock_records_needing_review' => $stockIssues,
        'unallocated_or_unversioned_sales_count' => $legacySalesCount,
        'active_legacy_alerts' => $legacyAlerts
    ];
}

function stock_sync_alert(PDO $pdo, array $actor, array $branch, array $stock): void
{
    if (empty($stock['stock_recorded'])) {
        return;
    }

    $desiredType = in_array($stock['status'], ['LOW_STOCK', 'OUT_OF_STOCK'], true)
        ? $stock['status']
        : null;

    $statement = $pdo->prepare(
        "SELECT stock_alert_id
         FROM stock_alerts
         WHERE retailer_id = ? AND branch_id = ? AND product_id = ?
           AND product_version_id = ? AND status = 'ACTIVE'
         LIMIT 1 FOR UPDATE"
    );
    $statement->execute([
        $actor['organization_id'],
        $branch['branch_id'],
        $stock['product_id'],
        $stock['product_version_id']
    ]);
    $alertId = $statement->fetchColumn();

    if ($desiredType === null) {
        if ($alertId !== false) {
            $resolve = $pdo->prepare(
                "UPDATE stock_alerts SET status = 'RESOLVED', resolved_at = NOW()
                 WHERE stock_alert_id = ? AND status = 'ACTIVE'"
            );
            $resolve->execute([(int)$alertId]);
        }
        return;
    }

    $values = [
        $desiredType,
        (int)$stock['on_hand'],
        (int)$stock['reorder_level'],
        (float)$stock['average_daily_sales'],
        (int)$stock['lead_time_days'],
        round(((int)$stock['sold_recently'] * (int)$stock['lead_time_days']) / NEXORA_STOCK_SALES_WINDOW_DAYS, 2),
        (int)$stock['recommended_quantity']
    ];

    if ($alertId !== false) {
        $update = $pdo->prepare(
            'UPDATE stock_alerts
             SET alert_type = ?, current_stock = ?, reorder_level = ?, average_daily_sales = ?,
                 lead_time_days = ?, expected_demand = ?, recommended_order_quantity = ?,
                 resolved_at = NULL
             WHERE stock_alert_id = ?'
        );
        $update->execute(array_merge($values, [(int)$alertId]));
        return;
    }

    $insert = $pdo->prepare(
        "INSERT INTO stock_alerts
            (retailer_id, branch_id, product_id, product_version_id, current_stock,
             reorder_level, average_daily_sales, lead_time_days, expected_demand,
             recommended_order_quantity, alert_type, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE')"
    );
    $insert->execute([
        $actor['organization_id'],
        $branch['branch_id'],
        $stock['product_id'],
        $stock['product_version_id'],
        $values[1],
        $values[2],
        $values[3],
        $values[4],
        $values[5],
        $values[6],
        $values[0]
    ]);
}

function stock_fetch_alert_rows(PDO $pdo, array $actor, array $branch): array
{
    $statement = $pdo->prepare(
        "SELECT a.stock_alert_id, a.alert_type, a.current_stock, a.reorder_level,
                a.average_daily_sales, a.lead_time_days, a.expected_demand,
                a.recommended_order_quantity, a.generated_at, a.product_id,
                p.product_name, a.product_version_id, pv.version_number,
                pv.barcode, pv.pack_size, a.branch_id
         FROM stock_alerts a
         INNER JOIN products p ON p.product_id = a.product_id
         INNER JOIN product_versions pv
            ON pv.product_version_id = a.product_version_id
           AND pv.product_id = a.product_id
         WHERE a.retailer_id = ? AND a.branch_id = ? AND a.status = 'ACTIVE'
         ORDER BY a.generated_at DESC"
    );
    $statement->execute([$actor['organization_id'], $branch['branch_id']]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function stock_effective_price(
    PDO $pdo,
    int $retailerId,
    int $branchId,
    int $productId,
    int $versionId,
    string $saleDate
): array {
    $statement = $pdo->prepare(
        "SELECT selling_price, price_basis, effective_from, effective_to
         FROM retailer_branch_prices
         WHERE retailer_id = ? AND branch_id = ? AND product_id = ? AND product_version_id = ?
           AND status = 'ACTIVE' AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 2"
    );
    $statement->execute([$retailerId, $branchId, $productId, $versionId, $saleDate, $saleDate]);
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {
        return ['status' => 'MISSING_PRICE', 'selling_price' => null, 'price_basis' => null];
    }
    if (count($rows) > 1) {
        return ['status' => 'AMBIGUOUS_PRICE', 'selling_price' => null, 'price_basis' => null];
    }
    return [
        'status'         => 'EFFECTIVE',
        'selling_price'  => (string)$rows[0]['selling_price'],
        'price_basis'    => (string)$rows[0]['price_basis'],
        'effective_from' => (string)$rows[0]['effective_from'],
        'effective_to'   => $rows[0]['effective_to'] !== null ? (string)$rows[0]['effective_to'] : null
    ];
}