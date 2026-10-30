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