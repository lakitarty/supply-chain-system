<?php

declare(strict_types=1);

const TAX_CATEGORIES = ['STANDARD_RATED', 'ZERO_RATED', 'EXEMPT'];

function parse_price_amount(string $raw): ?string
{
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $raw) || (float)$raw <= 0) {
        return null;
    }

    return number_format((float)$raw, 2, '.', '');
}

function parse_tax_basis(string $raw): ?int
{
    return match ($raw) {
        'INCLUSIVE' => 1,
        'EXCLUSIVE' => 0,
        default => null
    };
}

function insert_selling_price_with_tax(
    PDO $pdo,
    array $actor,
    int $productId,
    int $versionId,
    array $pricing,
    string $effectiveFrom
): void {
    $price = $pdo->prepare(
        "INSERT INTO product_prices
            (organization_id, product_id, product_version_id, price_type,
             price_amount, price_includes_vat, tax_category, effective_from)
         VALUES (?, ?, ?, 'SELLING', ?, ?, ?, ?)"
    );

    $price->execute([
        (int)$actor['organization_id'],
        $productId,
        $versionId,
        $pricing['amount'],
        $pricing['includesVat'],
        $pricing['taxCategory'],
        $effectiveFrom
    ]);

    $classification = $pdo->prepare(
        'INSERT INTO product_tax_classifications
            (product_version_id, manufacturer_id, tax_category, effective_from, declared_by)
         VALUES (?, ?, ?, ?, ?)'
    );

    $classification->execute([
        $versionId,
        (int)$actor['organization_id'],
        $pricing['taxCategory'],
        $effectiveFrom,
        (int)$actor['user_id']
    ]);
}

function price_rule_row(PDO $pdo, string $sql, array $params): ?array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

function amount_to_cents(mixed $amount): int
{
    return (int)round((float)$amount * 100);
}

function manufacturer_price_in_effect(PDO $pdo, int $manufacturerId, int $versionId, string $type, string $date): ?array
{
    return price_rule_row(
        $pdo,
        'SELECT price_amount, price_includes_vat
         FROM product_prices
         WHERE organization_id = ?
           AND product_version_id = ?
           AND price_type = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC, price_id DESC
         LIMIT 1',
        [$manufacturerId, $versionId, $type, $date, $date]
    );
}

function recommendation_in_effect(PDO $pdo, int $versionId, string $type, string $date): ?array
{
    return price_rule_row(
        $pdo,
        'SELECT amount
         FROM product_price_recommendations
         WHERE product_version_id = ?
           AND recommendation_type = ?
           AND effective_from <= ?
         ORDER BY effective_from DESC, created_at DESC, recommendation_id DESC
         LIMIT 1',
        [$versionId, $type, $date]
    );
}

function product_price_problems(PDO $pdo, int $productId, string $date): array
{
    $product = price_rule_row(
        $pdo,
        'SELECT p.manufacturer_id, v.product_version_id
         FROM products p
         INNER JOIN product_versions v
            ON v.product_id = p.product_id AND v.is_current = 1
         WHERE p.product_id = ?
         LIMIT 1',
        [$productId]
    );

    if ($product === null) {
        return ['The product has no current version.'];
    }

    $manufacturerId = (int)$product['manufacturer_id'];
    $versionId = (int)$product['product_version_id'];

    $cost = manufacturer_price_in_effect($pdo, $manufacturerId, $versionId, 'COST', $date);
    $selling = manufacturer_price_in_effect($pdo, $manufacturerId, $versionId, 'SELLING', $date);
    $rsp = recommendation_in_effect($pdo, $versionId, 'RSP', $date);
    $rrp = recommendation_in_effect($pdo, $versionId, 'RRP', $date);
    $tax = price_rule_row(
        $pdo,
        'SELECT tax_category
         FROM product_tax_classifications
         WHERE product_version_id = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 1',
        [$versionId, $date, $date]
    );

    $amounts = [
        'Production cost' => $cost['price_amount'] ?? null,
        'Manufacturer selling price' => $selling['price_amount'] ?? null,
        'RSP (recommended supplier price)' => $rsp['amount'] ?? null,
        'RRP (recommended retail price)' => $rrp['amount'] ?? null
    ];

    $problems = [];

    foreach ($amounts as $label => $amount) {
        if ($amount === null) {
            $problems[] = "{$label} is missing.";
        } elseif (amount_to_cents($amount) <= 0) {
            $problems[] = "{$label} must be above zero.";
        }
    }

    if ($tax === null) {
        $problems[] = 'The tax classification is missing.';
    }

    if ($problems !== []) {
        return $problems;
    }

    $sellingCents = amount_to_cents($amounts['Manufacturer selling price']);
    $rspCents = amount_to_cents($amounts['RSP (recommended supplier price)']);
    $rrpCents = amount_to_cents($amounts['RRP (recommended retail price)']);

    if ($sellingCents > $rspCents) {
        $problems[] = 'The manufacturer selling price is above the RSP.';
    }

    if ($rspCents > $rrpCents) {
        $problems[] = 'The RSP is above the RRP.';
    }

    return $problems;
}

function nairobi_today(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');
}

function copy_version_pricing(PDO $pdo, int $oldVersionId, int $newVersionId, string $effectiveDate): void
{
    $copies = [
        [
            "INSERT INTO product_prices
                (organization_id, product_id, product_version_id, price_type, price_amount,
                 price_includes_vat, tax_category, effective_from, effective_to)
             SELECT organization_id, product_id, ?, price_type, price_amount,
                    price_includes_vat, tax_category, GREATEST(effective_from, CAST(? AS DATE)), effective_to
             FROM product_prices
             WHERE product_version_id = ?
               AND (effective_to IS NULL OR effective_to >= CAST(? AS DATE))",
            [$newVersionId, $effectiveDate, $oldVersionId, $effectiveDate]
        ],
        [
            "INSERT INTO product_price_recommendations
                (manufacturer_id, product_id, product_version_id, recommendation_type, amount,
                 currency, effective_from, created_by_user_id)
             SELECT r.manufacturer_id, r.product_id, ?, r.recommendation_type, r.amount,
                    r.currency, GREATEST(r.effective_from, CAST(? AS DATE)), r.created_by_user_id
             FROM product_price_recommendations r
             WHERE r.product_version_id = ?
               AND (
                   r.effective_from > CAST(? AS DATE)
                   OR r.recommendation_id = (
                       SELECT r2.recommendation_id
                       FROM product_price_recommendations r2
                       WHERE r2.product_version_id = r.product_version_id
                         AND r2.recommendation_type = r.recommendation_type
                         AND r2.effective_from <= CAST(? AS DATE)
                       ORDER BY r2.effective_from DESC, r2.created_at DESC, r2.recommendation_id DESC
                       LIMIT 1
                   )
               )",
            [$newVersionId, $effectiveDate, $oldVersionId, $effectiveDate, $effectiveDate]
        ],
        [
            "INSERT INTO product_tax_classifications
                (product_version_id, manufacturer_id, tax_category, effective_from, effective_to,
                 declared_by, declaration_note)
             SELECT ?, manufacturer_id, tax_category, GREATEST(effective_from, CAST(? AS DATE)), effective_to,
                    declared_by, declaration_note
             FROM product_tax_classifications
             WHERE product_version_id = ?
               AND (effective_to IS NULL OR effective_to >= CAST(? AS DATE))",
            [$newVersionId, $effectiveDate, $oldVersionId, $effectiveDate]
        ],
        [
            "INSERT INTO supplier_product_offers
                (supplier_id, product_id, product_version_id, area_id, unit_price, price_basis,
                 currency_code, effective_from, effective_to, status, offer_note, created_by)
             SELECT supplier_id, product_id, ?, area_id, unit_price, price_basis,
                    currency_code, GREATEST(effective_from, CAST(? AS DATE)), effective_to, status, offer_note, created_by
             FROM supplier_product_offers
             WHERE product_version_id = ?
               AND status = 'ACTIVE'
               AND (effective_to IS NULL OR effective_to >= CAST(? AS DATE))",
            [$newVersionId, $effectiveDate, $oldVersionId, $effectiveDate]
        ],
        [
            "INSERT INTO retailer_products
                (retailer_id, product_id, product_version_id, selling_price, status)
             SELECT retailer_id, product_id, ?, selling_price, status
             FROM retailer_products
             WHERE product_version_id = ?
               AND status = 'ACTIVE'",
            [$newVersionId, $oldVersionId]
        ],
        [
            "INSERT INTO retailer_branch_prices
                (retailer_id, branch_id, product_id, product_version_id, selling_price, price_basis,
                 effective_from, effective_to, status, created_by)
             SELECT retailer_id, branch_id, product_id, ?, selling_price, price_basis,
                    GREATEST(effective_from, CAST(? AS DATE)), effective_to, status, created_by
             FROM retailer_branch_prices
             WHERE product_version_id = ?
               AND status = 'ACTIVE'
               AND (effective_to IS NULL OR effective_to >= CAST(? AS DATE))",
            [$newVersionId, $effectiveDate, $oldVersionId, $effectiveDate]
        ]
    ];

    foreach ($copies as [$sql, $params]) {
        $pdo->prepare($sql)->execute($params);
    }
}

function promotion_lock_message(PDO $pdo, int $manufacturerId, int $productId, string $date): ?string
{
    $lock = price_rule_row(
        $pdo,
        "SELECT pr.promotion_name, pr.end_date, p.product_name
         FROM promotions pr
         INNER JOIN promotion_products pp ON pp.promotion_id = pr.promotion_id
         INNER JOIN products p ON p.product_id = pp.product_id
         WHERE pr.organization_id = ?
           AND pp.product_id = ?
           AND pr.status IN ('APPROVED', 'SCHEDULED', 'ACTIVE')
           AND pr.start_date <= ?
           AND pr.end_date >= ?
         ORDER BY pr.end_date DESC
         LIMIT 1",
        [$manufacturerId, $productId, $date, $date]
    );

    if ($lock === null) {
        return null;
    }

    return "{$lock['product_name']} is locked because the manufacturer promotion '{$lock['promotion_name']}' is active until {$lock['end_date']}. Normal price changes and price cascading resume after it expires.";
}

function version_vat_percent(PDO $pdo, int $versionId, string $date): ?float
{
    $tax = price_rule_row(
        $pdo,
        'SELECT tax_category
         FROM product_tax_classifications
         WHERE product_version_id = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 1',
        [$versionId, $date, $date]
    );

    if ($tax === null) {
        return null;
    }

    $rate = price_rule_row(
        $pdo,
        'SELECT rate_percent, taxable_supply
         FROM tax_rate_schedule
         WHERE tax_category = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 1',
        [$tax['tax_category'], $date, $date]
    );

    if ($rate === null) {
        return null;
    }

    return (int)$rate['taxable_supply'] === 1 ? (float)$rate['rate_percent'] : 0.0;
}

function reprice_for_tax_change(
    PDO $pdo,
    int $manufacturerId,
    int $versionId,
    string $category,
    string $effectiveFrom,
    float $oldPercent,
    float $newPercent,
    int $userId
): array {
    $convert = static fn(float $amount): float => round(
        $amount / (1 + $oldPercent / 100) * (1 + $newPercent / 100),
        2
    );

    $dayBefore = (new DateTimeImmutable($effectiveFrom))->modify('-1 day')->format('Y-m-d');
    $changes = [];
    $selling = null;

    foreach (['COST', 'SELLING'] as $priceType) {
        $price = price_rule_row(
            $pdo,
            "SELECT *
             FROM product_prices
             WHERE organization_id = ?
               AND product_version_id = ?
               AND price_type = ?
               AND effective_from <= ?
               AND (effective_to IS NULL OR effective_to >= ?)
             ORDER BY effective_from DESC, price_id DESC
             LIMIT 1
             FOR UPDATE",
            [$manufacturerId, $versionId, $priceType, $effectiveFrom, $effectiveFrom]
        );

        if ($priceType === 'SELLING') {
            $selling = $price;
        }

        if ($price === null || (int)$price['price_includes_vat'] !== 1) {
            continue;
        }

        $oldAmount = (float)$price['price_amount'];
        $newAmount = $convert($oldAmount);
        $newIncludesVat = $priceType === 'COST'
            ? ($newPercent > 0 ? 1 : 0)
            : 1;

        if ((string)$price['effective_from'] >= $effectiveFrom) {
            $pdo->prepare(
                'UPDATE product_prices
                 SET price_amount = ?, price_includes_vat = ?, tax_category = ?
                 WHERE price_id = ?'
            )->execute([
                $newAmount,
                $newIncludesVat,
                $category,
                (int)$price['price_id']
            ]);
        } else {
            $pdo->prepare(
                'UPDATE product_prices SET effective_to = ? WHERE price_id = ?'
            )->execute([$dayBefore, (int)$price['price_id']]);

            $pdo->prepare(
                'INSERT INTO product_prices
                    (organization_id, product_id, product_version_id, price_type,
                     price_amount, price_includes_vat, tax_category, effective_from, effective_to)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $manufacturerId,
                (int)$price['product_id'],
                $versionId,
                $priceType,
                $newAmount,
                $newIncludesVat,
                $category,
                $effectiveFrom,
                $price['effective_to']
            ]);
        }

        $label = $priceType === 'COST'
            ? 'Production cost'
            : 'Manufacturer selling price';

        $changes[] = sprintf(
            '%s KSh %s to KSh %s',
            $label,
            number_format($oldAmount, 2, '.', ''),
            number_format($newAmount, 2, '.', '')
        );
    }

    if ($selling === null || (int)$selling['price_includes_vat'] !== 1) {
        return $changes;
    }

    $insert = $pdo->prepare(
        "INSERT INTO product_price_recommendations
            (manufacturer_id, product_id, product_version_id, recommendation_type,
             amount, currency, effective_from, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, 'KES', ?, ?)"
    );

    foreach (['RSP', 'RRP'] as $type) {
        $recommendation = recommendation_in_effect($pdo, $versionId, $type, $effectiveFrom);

        if ($recommendation === null) {
            continue;
        }

        $oldAmount = (float)$recommendation['amount'];
        $newAmount = $convert($oldAmount);

        $insert->execute([
            $manufacturerId,
            (int)$selling['product_id'],
            $versionId,
            $type,
            $newAmount,
            $effectiveFrom,
            $userId
        ]);

        $changes[] = sprintf(
            '%s KSh %s to KSh %s',
            $type,
            number_format($oldAmount, 2, '.', ''),
            number_format($newAmount, 2, '.', '')
        );
    }

    return $changes;
}
