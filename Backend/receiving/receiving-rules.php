<?php
declare(strict_types=1);

function receiving_resolve_branch(
    PDO $pdo,
    array $actor,
    ?int $requestedBranchId = null
): array {
    $role = $actor['role'] ?? '';

    if (!in_array($role, ['ADMIN', 'BRANCH_ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Your account does not have access to receiving.');
    }

    $organizationId = (int)($actor['organization_id'] ?? 0);

    if ($organizationId < 1) {
        respond_error(403, 'Your organisation could not be identified.');
    }

    if ($role === 'ADMIN') {
        if ($requestedBranchId !== null) {
            $branchId = $requestedBranchId;
        } else {
            $statement = $pdo->prepare(
                "SELECT branch_id
                 FROM branches
                 WHERE organization_id = ?
                   AND status = 'ACTIVE'
                   AND is_main_branch = 1
                 LIMIT 1"
            );

            $statement->execute([$organizationId]);
            $branchId = $statement->fetchColumn();

            if ($branchId === false) {
                respond_error(404, 'No active main branch was found.');
            }

            $branchId = (int)$branchId;
        }
    } else {
        $assignedBranchId = (int)($actor['branch_id'] ?? 0);

        if ($assignedBranchId < 1) {
            respond_error(403, 'Your account is not assigned to a branch.');
        }

        if (
            $requestedBranchId !== null
            && $requestedBranchId !== $assignedBranchId
        ) {
            respond_error(403, 'You cannot access the selected branch.');
        }

        $branchId = $assignedBranchId;
    }

    $statement = $pdo->prepare(
        "SELECT
            branch_id,
            organization_id,
            branch_name,
            is_main_branch
         FROM branches
         WHERE branch_id = ?
           AND organization_id = ?
           AND status = 'ACTIVE'
         LIMIT 1"
    );

    $statement->execute([$branchId, $organizationId]);
    $branch = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$branch) {
        respond_error(404, 'The selected active branch was not found.');
    }

    $branch['branch_id'] = (int)$branch['branch_id'];
    $branch['organization_id'] = (int)$branch['organization_id'];
    $branch['is_main_branch'] = (bool)$branch['is_main_branch'];

    return $branch;
}
function receiving_line_tax(PDO $pdo, ?int $productVersionId, int $manufacturerId, string $date): array
{
    $tax = [
        'tax_category' => null,
        'tax_rate_percent' => null,
        'taxable_supply' => null,
        'tax_calculation_status' => 'MISSING_CLASSIFICATION'
    ];

    if ($productVersionId === null) {
        return $tax;
    }

    $classificationStatement = $pdo->prepare(
        "SELECT tax_category
         FROM product_tax_classifications
         WHERE product_version_id = ?
           AND manufacturer_id = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 2"
    );

    $classificationStatement->execute([$productVersionId, $manufacturerId, $date, $date]);
    $classifications = $classificationStatement->fetchAll(PDO::FETCH_ASSOC);

    if (count($classifications) > 1) {
        $tax['tax_calculation_status'] = 'AMBIGUOUS_CLASSIFICATION';
        return $tax;
    }

    if ($classifications === []) {
        return $tax;
    }

    $tax['tax_category'] = (string)$classifications[0]['tax_category'];

    $rateStatement = $pdo->prepare(
        "SELECT rate_percent, taxable_supply
         FROM tax_rate_schedule
         WHERE tax_category = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 2"
    );

    $rateStatement->execute([$tax['tax_category'], $date, $date]);
    $rates = $rateStatement->fetchAll(PDO::FETCH_ASSOC);

    if (count($rates) !== 1) {
        $tax['tax_calculation_status'] = $rates === [] ? 'MISSING_RATE' : 'AMBIGUOUS_RATE';
        return $tax;
    }

    $tax['tax_rate_percent'] = number_format((float)$rates[0]['rate_percent'], 2, '.', '');
    $tax['taxable_supply'] = (int)$rates[0]['taxable_supply'];
    $tax['tax_calculation_status'] = 'CALCULATED';

    return $tax;
}

function receiving_line_amounts(float $quantity, float $unitPrice, string $priceBasis, array $tax): array
{
    $amount = round($quantity * $unitPrice, 2, PHP_ROUND_HALF_UP);

    if ($tax['tax_calculation_status'] !== 'CALCULATED') {
        return ['net' => null, 'vat' => null, 'total' => $amount];
    }

    $rate = (float)$tax['tax_rate_percent'];

    if ((int)$tax['taxable_supply'] === 0 || $rate === 0.0) {
        return ['net' => $amount, 'vat' => 0.0, 'total' => $amount];
    }

    if ($priceBasis === 'VAT_INCLUSIVE') {
        $vat = round($amount * $rate / (100.0 + $rate), 2, PHP_ROUND_HALF_UP);

        return [
            'net' => round($amount - $vat, 2, PHP_ROUND_HALF_UP),
            'vat' => $vat,
            'total' => $amount
        ];
    }

    $vat = round($amount * $rate / 100.0, 2, PHP_ROUND_HALF_UP);

    return [
        'net' => $amount,
        'vat' => $vat,
        'total' => round($amount + $vat, 2, PHP_ROUND_HALF_UP)
    ];
}