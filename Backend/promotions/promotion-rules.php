<?php

declare(strict_types=1);
require_once __DIR__ . '/../finance/helpers.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../changes/change-rules.php';

const PROMOTION_MAX_PRODUCTS = 50;
const PROMOTION_MAX_RETAILERS = 100;

function promotion_require_manufacturer(PDO $pdo): array
{
    $actor = require_admin($pdo);

    if (!can_manufacture($actor)) {
        respond_error(403, 'Only a manufacturer administrator can manage promotions.');
    }

    return $actor;
}

function promotion_display_status(array $promotion, string $today): string
{
    if ((string)$promotion['status'] !== 'APPROVED') {
        return (string)$promotion['status'];
    }
    if ($today < (string)$promotion['start_date']) {
        return 'SCHEDULED';
    }
    if ($today > (string)$promotion['end_date']) {
        return 'EXPIRED';
    }

    return 'ACTIVE';
}

function promotion_money_cents(string $text): ?int
{
    if (!preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/', $text, $parts)) {
        return null;
    }

    return (int)$parts[1] * 100 + (int)str_pad($parts[2] ?? '', 2, '0');
}

function promotion_cents_to_money(int $cents): string
{
    return intdiv($cents, 100) . '.' . str_pad((string)($cents % 100), 2, '0', STR_PAD_LEFT);
}

function promotion_clean_input(array $data): array
{
    $products = [];
    $rawProducts = is_array($data['products'] ?? null) ? $data['products'] : [];
    foreach ($rawProducts as $product) {
        if (!is_array($product)) {
            continue;
        }
        $products[] = [
            'productId' => (int)($product['productId'] ?? 0),
            'promotionPrice' => trim((string)($product['promotionPrice'] ?? '')),
            'compensation' => trim((string)($product['compensation'] ?? ''))
        ];
    }

    $rawRetailers = is_array($data['retailers'] ?? null) ? $data['retailers'] : [];

    return [
        'promotionId' => (int)($data['promotionId'] ?? 0),
        'name' => trim((string)($data['name'] ?? '')),
        'description' => trim((string)($data['description'] ?? '')),
        'startDate' => trim((string)($data['startDate'] ?? '')),
        'endDate' => trim((string)($data['endDate'] ?? '')),
        'products' => $products,
        'retailers' => array_values(array_unique(array_map('intval', $rawRetailers)))
    ];
}

function promotion_validate_input(array $input): array
{
    if ($input['name'] === '' || mb_strlen($input['name']) > 200) {
        respond_field_error(400, field_error('name', 'Enter a campaign name of up to 200 characters.'));
    }
    if (mb_strlen($input['description']) > 2000) {
        respond_field_error(400, field_error('description', 'The description can be up to 2000 characters.'));
    }
    foreach (['startDate' => 'start date', 'endDate' => 'end date'] as $field => $label) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input[$field]) || !valid_date($input[$field])) {
            respond_field_error(400, field_error($field, "Enter a valid $label."));
        }
    }
    if ($input['endDate'] < $input['startDate']) {
        respond_field_error(400, field_error('endDate', 'The end date cannot be before the start date.'));
    }

    if ($input['products'] === [] || count($input['products']) > PROMOTION_MAX_PRODUCTS) {
        respond_field_error(400, field_error('products', 'Add between 1 and ' . PROMOTION_MAX_PRODUCTS . ' products.'));
    }
    if ($input['retailers'] === [] || count($input['retailers']) > PROMOTION_MAX_RETAILERS) {
        respond_field_error(400, field_error('retailers', 'Invite between 1 and ' . PROMOTION_MAX_RETAILERS . ' retailers.'));
    }

    $seen = [];
    $products = [];
    foreach ($input['products'] as $index => $product) {
        $row = $index + 1;
        if ($product['productId'] < 1 || isset($seen[$product['productId']])) {
            respond_field_error(400, field_error('products', "Product row $row is missing or repeated."));
        }
        $seen[$product['productId']] = true;

        $promotion = promotion_money_cents($product['promotionPrice']);
        $compensation = promotion_money_cents($product['compensation']);
        if ($promotion === null || $compensation === null) {
            respond_field_error(400, field_error('products', "Product row $row: enter a promotion price and compensation in KSh with no more than two decimal places."));
        }
        if ($promotion < 1) {
            respond_field_error(400, field_error('products', "Product row $row: the promotion price must be greater than zero."));
        }

        $products[] = [
            'productId' => $product['productId'],
            'promotionPrice' => promotion_cents_to_money($promotion),
            'compensation' => promotion_cents_to_money($compensation)
        ];
    }

    return array_merge($input, ['products' => $products]);
}

function promotion_own_products(PDO $pdo, int $manufacturerId, ?string $effectiveDate = null): array
{
    $effectiveDate ??= (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');

    $statement = $pdo->prepare(
        "SELECT p.product_id, p.product_name, p.category,
                pv.product_version_id,
                recommendation.recommendation_id AS rrp_recommendation_id,
                recommendation.amount AS rrp_amount,
                recommendation.effective_from AS rrp_effective_from
         FROM products p
         INNER JOIN product_versions pv
            ON pv.product_version_id = (
                SELECT pv2.product_version_id
                FROM product_versions pv2
                WHERE pv2.product_id = p.product_id
                  AND pv2.effective_from <= ?
                  AND (pv2.effective_to IS NULL OR pv2.effective_to >= ?)
                ORDER BY pv2.effective_from DESC, pv2.version_number DESC
                LIMIT 1
            )
         LEFT JOIN product_price_recommendations recommendation
            ON recommendation.recommendation_id = (
                SELECT r.recommendation_id
                FROM product_price_recommendations r
                WHERE r.product_version_id = pv.product_version_id
                  AND r.recommendation_type = 'RRP'
                  AND r.effective_from <= ?
                ORDER BY r.effective_from DESC, r.created_at DESC, r.recommendation_id DESC
                LIMIT 1
            )
         WHERE p.manufacturer_id = ? AND p.status = 'ACTIVE'
         ORDER BY p.product_name"
    );
    $statement->execute([$effectiveDate, $effectiveDate, $effectiveDate, $manufacturerId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function promotion_add_rrp_snapshots(PDO $pdo, int $manufacturerId, array $input): array
{
    $available = [];
    foreach (promotion_own_products($pdo, $manufacturerId, (string)$input['startDate']) as $product) {
        $available[(int)$product['product_id']] = $product;
    }

    $products = [];
    foreach ($input['products'] as $index => $product) {
        $row = $index + 1;
        $current = $available[(int)$product['productId']] ?? null;
        if ($current === null) {
            respond_field_error(400, field_error('products', "Product row $row is not active for the campaign's effective date."));
        }
        if ($current['rrp_recommendation_id'] === null || $current['rrp_amount'] === null) {
            respond_field_error(409, field_error('products', "{$current['product_name']} has no manufacturer RRP effective on {$input['startDate']}. Publish the RRP in Product Catalogue before adding it to Promotions."));
        }

        $rrpCents = promotion_money_cents((string)$current['rrp_amount']);
        $promotionCents = promotion_money_cents((string)$product['promotionPrice']);
        $compensationCents = promotion_money_cents((string)$product['compensation']);
        if ($rrpCents === null || $promotionCents === null || $compensationCents === null) {
            respond_field_error(400, field_error('products', "Product row $row contains an invalid price."));
        }
        if ($promotionCents >= $rrpCents) {
            respond_field_error(400, field_error('products', "Product row $row: the promotional price must be below the RRP of KSh " . promotion_cents_to_money($rrpCents) . '.'));
        }
        if ($compensationCents > $rrpCents - $promotionCents) {
            respond_field_error(400, field_error(
                'products',
                "Product row $row: compensation cannot exceed the RRP reduction of KSh " . promotion_cents_to_money($rrpCents - $promotionCents) . '.'
            ));
        }

        $products[] = $product + [
            'productVersionId' => (int)$current['product_version_id'],
            'rrpRecommendationId' => (int)$current['rrp_recommendation_id'],
            'rrpAmount' => promotion_cents_to_money($rrpCents),
            'normalPrice' => promotion_cents_to_money($rrpCents)
        ];
    }

    return array_merge($input, ['products' => $products]);
}

function promotion_eligible_retailers(PDO $pdo, int $manufacturerId): array
{
    $reachable = reachable_organization_ids($pdo, $manufacturerId, 'downstream');
    if ($reachable === []) {
        return [];
    }

    $statement = $pdo->prepare(
        "SELECT organization_id, organization_name
         FROM organizations
         WHERE organization_type = 'RETAILER'
           AND status = 'ACTIVE'
           AND organization_id IN (" . sql_placeholders($reachable) . ")
         ORDER BY organization_name"
    );
    $statement->execute($reachable);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function promotion_assert_choices(PDO $pdo, int $manufacturerId, array $input): void
{
    $productStatement = $pdo->prepare(
        "SELECT product_id FROM products WHERE manufacturer_id = ? AND status = 'ACTIVE'"
    );
    $productStatement->execute([$manufacturerId]);
    $productIds = array_map('intval', $productStatement->fetchAll(PDO::FETCH_COLUMN));
    foreach ($input['products'] as $product) {
        if (!in_array($product['productId'], $productIds, true)) {
            respond_field_error(400, field_error('products', 'Every product must be an active product of your organisation.'));
        }
    }

    $retailerIds = array_map('intval', array_column(promotion_eligible_retailers($pdo, $manufacturerId), 'organization_id'));
    foreach ($input['retailers'] as $retailerId) {
        if (!in_array($retailerId, $retailerIds, true)) {
            respond_field_error(400, field_error('retailers', 'Every retailer must be active and currently supplied by you.'));
        }
    }
}

function promotion_load(PDO $pdo, int $organizationId, int $promotionId): ?array
{
    $statement = $pdo->prepare(
        "SELECT p.promotion_id, p.organization_id, p.promotion_name, p.description,
                p.start_date, p.end_date, p.status, p.created_by, p.approved_by,
                p.approved_at, p.created_at,
                CONCAT(c.first_name, ' ', c.last_name) AS created_by_name,
                CONCAT(a.first_name, ' ', a.last_name) AS approved_by_name
         FROM promotions p
         INNER JOIN users c ON c.user_id = p.created_by
         LEFT JOIN users a ON a.user_id = p.approved_by
         WHERE p.promotion_id = ? AND p.organization_id = ?
         LIMIT 1"
    );
    $statement->execute([$promotionId, $organizationId]);
    $promotion = $statement->fetch(PDO::FETCH_ASSOC);

    return $promotion === false ? null : $promotion;
}

function promotion_detail(PDO $pdo, array $promotion, string $today): array
{
    $productsStatement = $pdo->prepare(
        'SELECT pp.product_id, p.product_name, pp.product_version_id,
                pp.rrp_snapshot, pp.rrp_recommendation_id,
                pp.normal_price, pp.promotion_price, pp.compensation_per_unit
         FROM promotion_products pp
         INNER JOIN products p ON p.product_id = pp.product_id
         WHERE pp.promotion_id = ?
         ORDER BY p.product_name'
    );
    $productsStatement->execute([(int)$promotion['promotion_id']]);

    $retailersStatement = $pdo->prepare(
        'SELECT pr.organization_id, o.organization_name, pr.status
         FROM promotion_participants pr
         INNER JOIN organizations o ON o.organization_id = pr.organization_id
         WHERE pr.promotion_id = ?
         ORDER BY o.organization_name'
    );
    $retailersStatement->execute([(int)$promotion['promotion_id']]);

    $promotion['display_status'] = promotion_display_status($promotion, $today);
    $promotion['products'] = $productsStatement->fetchAll(PDO::FETCH_ASSOC);
    $promotion['retailers'] = $retailersStatement->fetchAll(PDO::FETCH_ASSOC);

    return $promotion;
}

function promotion_assert_campaign_rrp_snapshots(PDO $pdo, int $promotionId): void
{
    $statement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM promotion_products pp
         LEFT JOIN product_price_recommendations r
            ON r.recommendation_id = pp.rrp_recommendation_id
           AND r.product_version_id = pp.product_version_id
           AND r.recommendation_type = 'RRP'
           AND r.amount = pp.rrp_snapshot
         WHERE pp.promotion_id = ?
           AND (pp.product_version_id IS NULL OR pp.rrp_snapshot IS NULL OR r.recommendation_id IS NULL)"
    );
    $statement->execute([$promotionId]);
    if ((int)$statement->fetchColumn() > 0) {
        throw new DomainException(
            'This campaign contains a legacy product without an RRP snapshot. Return it to draft, then edit and save it again after publishing an RRP for each product.',
            409
        );
    }
}

function promotion_conflicts(PDO $pdo, int $promotionId): array
{
    $statement = $pdo->prepare(
        "SELECT DISTINCT other.promotion_name, retailer.organization_name, product.product_name
         FROM promotions mine
         INNER JOIN promotion_products mine_product ON mine_product.promotion_id = mine.promotion_id
         INNER JOIN promotion_participants mine_retailer ON mine_retailer.promotion_id = mine.promotion_id
         INNER JOIN promotion_participants other_retailer
            ON other_retailer.organization_id = mine_retailer.organization_id
           AND other_retailer.promotion_id <> mine.promotion_id
           AND other_retailer.status <> 'DECLINED'
         INNER JOIN promotion_products other_product
            ON other_product.promotion_id = other_retailer.promotion_id
           AND other_product.product_id = mine_product.product_id
         INNER JOIN promotions other ON other.promotion_id = other_retailer.promotion_id
         INNER JOIN organizations retailer ON retailer.organization_id = mine_retailer.organization_id
         INNER JOIN products product ON product.product_id = mine_product.product_id
         WHERE mine.promotion_id = ?
           AND other.status IN ('SUBMITTED', 'APPROVED')
           AND other.start_date <= mine.end_date
           AND other.end_date >= mine.start_date
         LIMIT 5"
    );
    $statement->execute([$promotionId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function promotion_conflict_message(array $conflicts): string
{
    $first = $conflicts[0];

    return sprintf(
        '%s already has "%s" running a promotion on %s during these dates%s. Change the dates, products or retailers.',
        $first['organization_name'],
        $first['promotion_name'],
        $first['product_name'],
        count($conflicts) > 1 ? ' (and other overlaps)' : ''
    );
}

function promotion_other_admin_exists(PDO $pdo, int $organizationId, int $userId): bool
{
    $statement = $pdo->prepare(
        "SELECT COUNT(*) FROM users
         WHERE organization_id = ? AND role = 'ADMIN' AND status = 'ACTIVE' AND user_id <> ?"
    );
    $statement->execute([$organizationId, $userId]);

    return (int)$statement->fetchColumn() > 0;
}
function promotion_can_approve(PDO $pdo, array $actor, array $promotion): bool
{
    if ((string)$promotion['status'] !== 'SUBMITTED') {
        return false;
    }
    if ((int)$promotion['created_by'] !== (int)$actor['user_id']) {
        return true;
    }

    return !promotion_other_admin_exists($pdo, (int)$actor['organization_id'], (int)$actor['user_id']);
}