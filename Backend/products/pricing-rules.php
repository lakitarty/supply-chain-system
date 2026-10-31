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

    $costCents = amount_to_cents($amounts['Production cost']);
    $sellingCents = amount_to_cents($amounts['Manufacturer selling price']);
    $rspCents = amount_to_cents($amounts['RSP (recommended supplier price)']);
    $rrpCents = amount_to_cents($amounts['RRP (recommended retail price)']);
    $sellingNetCents = $sellingCents;

    if ((int)$selling['price_includes_vat'] === 1 && $tax['tax_category'] === 'STANDARD_RATED') {
        $rate = price_rule_row(
            $pdo,
            "SELECT rate_percent
             FROM tax_rate_schedule
             WHERE tax_category = 'STANDARD_RATED'
               AND effective_from <= ?
               AND (effective_to IS NULL OR effective_to >= ?)
             ORDER BY effective_from DESC
             LIMIT 1",
            [$date, $date]
        );

        if ($rate === null) {
            return ['No VAT rate is recorded for standard-rated products on this date.'];
        }

        $sellingNetCents = (int)round($sellingCents / (1 + (float)$rate['rate_percent'] / 100));
    }

    if ($costCents > $sellingNetCents) {
        $problems[] = 'Production cost is above the manufacturer selling price after removing VAT.';
    }

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