<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/stock-rules.php';

require_post();

try {
    $actor = require_login($pdo);
    stock_require_retailer($actor);
    $input = read_json_body();

    $productId = stock_optional_id($input['productId'] ?? null);
    $versionId = stock_optional_id($input['productVersionId'] ?? null);
    $branchId = stock_optional_id($input['branchId'] ?? null);
    $unitsSold = stock_whole_number($input['unitsSold'] ?? null);
    $saleDate = trim((string)($input['saleDate'] ?? ''));
    $salesAmountInput = $input['salesAmount'] ?? null;
    $salesAmountBasis = trim((string)($input['salesAmountBasis'] ?? ''));

    if ($productId === null) {
        respond_field_error(400, field_error('productId', 'Select a product.'));
    }
    if ($versionId === null) {
        respond_field_error(400, field_error('productVersionId', 'Select the package or barcode version sold.'));
    }
    if ($branchId === null) {
        respond_field_error(400, field_error('branchId', 'Select the branch where the sale occurred.'));
    }
    if ($unitsSold === null) {
        respond_field_error(400, field_error('unitsSold', 'Enter total units sold as a whole number from zero upwards.'));
    }
    if (!is_string($salesAmountInput) && !is_int($salesAmountInput) && !is_float($salesAmountInput)) {
        respond_field_error(400, field_error('salesAmount', 'Enter the total sales amount in KSh, to two decimal places.'));
    }
    $salesAmountText = trim((string)$salesAmountInput);
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $salesAmountText)) {
        respond_field_error(400, field_error('salesAmount', 'Enter a non-negative KSh amount with no more than two decimal places.'));
    }
    $salesAmount = number_format((float)$salesAmountText, 2, '.', '');
    if (!in_array($salesAmountBasis, ['VAT_INCLUSIVE', 'VAT_EXCLUSIVE'], true)) {
        respond_field_error(400, field_error('salesAmountBasis', 'Choose whether the entered sales amount includes or excludes VAT.'));
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $saleDate) || !valid_date($saleDate) || $saleDate > date('Y-m-d')) {
        respond_field_error(400, field_error('saleDate', 'Enter a valid sale date that is not in the future.'));
    }

    $branch = stock_resolve_branch($pdo, $actor, $branchId);
    $product = stock_validate_product_version($pdo, $actor, $productId, $versionId);
    $isToday = $saleDate === date('Y-m-d');

    $pdo->beginTransaction();

    // Lock the selected branch to serialize writes to its daily sales summaries.
    $branchLock = $pdo->prepare(
        "SELECT branch_id FROM branches
         WHERE branch_id = ? AND organization_id = ? AND status = 'ACTIVE'
         LIMIT 1 FOR UPDATE"
    );
    $branchLock->execute([$branch['branch_id'], $actor['organization_id']]);
    if (!$branchLock->fetchColumn()) {
        $pdo->rollBack();
        respond_error(409, 'The selected branch is no longer active. Refresh and try again.');
    }

    // Resolve the manufacturer declaration and applicable rate for this sale date.
    // Historical snapshots are written only when the effective records are unambiguous.
    $taxCategorySnapshot = null;
    $taxRateSnapshot = null;
    $taxAmount = null;
    $netSalesAmount = null;
    $taxableSupply = null;
    $taxCalculationStatus = 'MISSING_CLASSIFICATION';
    $taxCalculationMessage = 'No manufacturer tax classification is effective for this product version on the sale date. The sale was saved, but tax and net sales remain uncalculated.';

    $classificationStatement = $pdo->prepare(
        'SELECT product_tax_classification_id, tax_category
         FROM product_tax_classifications
         WHERE product_version_id = ? AND manufacturer_id = ?
           AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 2'
    );
    $classificationStatement->execute([
        $versionId,
        (int)$product['manufacturer_id'],
        $saleDate,
        $saleDate
    ]);
    $classifications = $classificationStatement->fetchAll(PDO::FETCH_ASSOC);

    if (count($classifications) > 1) {
        $taxCalculationStatus = 'AMBIGUOUS_CLASSIFICATION';
        $taxCalculationMessage = 'More than one manufacturer tax classification overlaps this sale date. The sale was saved, but tax and net sales remain uncalculated until the classifications are reconciled.';
    } elseif (count($classifications) === 1) {
        $taxCategorySnapshot = (string)$classifications[0]['tax_category'];
        $rateStatement = $pdo->prepare(
            'SELECT tax_rate_id, rate_percent, taxable_supply
             FROM tax_rate_schedule
             WHERE tax_category = ? AND effective_from <= ?
               AND (effective_to IS NULL OR effective_to >= ?)
             ORDER BY effective_from DESC
             LIMIT 2'
        );
        $rateStatement->execute([$taxCategorySnapshot, $saleDate, $saleDate]);
        $taxRates = $rateStatement->fetchAll(PDO::FETCH_ASSOC);

        if (count($taxRates) === 0) {
            $taxCalculationStatus = 'MISSING_RATE';
            $taxCalculationMessage = 'The manufacturer classification exists, but no tax rate is configured for this category on the sale date. The sale was saved without a tax calculation.';
        } elseif (count($taxRates) > 1) {
            $taxCalculationStatus = 'AMBIGUOUS_RATE';
            $taxCalculationMessage = 'More than one tax-rate period applies to this category and date. The sale was saved, but tax and net sales remain uncalculated until the rate schedule is reconciled.';
        } else {
            $rate = $taxRates[0];
            $ratePercent = (float)$rate['rate_percent'];
            $taxRateSnapshot = number_format($ratePercent, 2, '.', '');
            $taxableSupply = (int)$rate['taxable_supply'];
            $amountValue = (float)$salesAmount;

            if ($taxableSupply === 0 || $ratePercent === 0.0) {
                $calculatedTax = 0.0;
                $calculatedNet = $amountValue;
            } elseif ($salesAmountBasis === 'VAT_INCLUSIVE') {
                $calculatedTax = round($amountValue * $ratePercent / (100.0 + $ratePercent), 2, PHP_ROUND_HALF_UP);
                $calculatedNet = round($amountValue - $calculatedTax, 2, PHP_ROUND_HALF_UP);
            } else {
                $calculatedTax = round($amountValue * $ratePercent / 100.0, 2, PHP_ROUND_HALF_UP);
                $calculatedNet = $amountValue;
            }

            $taxAmount = number_format($calculatedTax, 2, '.', '');
            $netSalesAmount = number_format($calculatedNet, 2, '.', '');
            $taxCalculationStatus = 'CALCULATED';
            $taxCalculationMessage = 'Tax and net sales were calculated from the manufacturer declaration and the tax rate effective on the sale date.';
        }
    }

    $existingStatement = $pdo->prepare(
        'SELECT sales_history_id, units_sold, sales_amount
         FROM sales_history
         WHERE retailer_id = ? AND branch_id = ? AND product_id = ?
           AND product_version_id = ? AND sale_date = ?
         LIMIT 1 FOR UPDATE'
    );
    $existingStatement->execute([
        $actor['organization_id'],
        $branch['branch_id'],
        $productId,
        $versionId,
        $saleDate
    ]);
    $existingSale = $existingStatement->fetch(PDO::FETCH_ASSOC);
    $previousUnits = $existingSale ? (int)$existingSale['units_sold'] : 0;
    $unitDelta = $unitsSold - $previousUnits;

    $currentStock = null;
    if ($isToday) {
        $stockStatement = $pdo->prepare(
            'SELECT retailer_stock_id, quantity_on_hand
             FROM retailer_stock
             WHERE retailer_id = ? AND branch_id = ? AND product_id = ? AND product_version_id = ?
             LIMIT 1 FOR UPDATE'
        );
        $stockStatement->execute([
            $actor['organization_id'],
            $branch['branch_id'],
            $productId,
            $versionId
        ]);
        $currentStock = $stockStatement->fetch(PDO::FETCH_ASSOC);

        if (!$currentStock) {
            $pdo->rollBack();
            respond_error(409, 'Set an opening stock count before saving today\'s sales summary.');
        }
        if ($unitDelta > (int)$currentStock['quantity_on_hand']) {
            $pdo->rollBack();
            respond_error(409, 'The additional units sold exceed stock currently on hand. Correct the daily total or update the stock count first.');
        }

        if ($unitDelta !== 0) {
            $stockUpdate = $pdo->prepare(
                'UPDATE retailer_stock
                 SET quantity_on_hand = quantity_on_hand - ?, last_updated = NOW()
                 WHERE retailer_stock_id = ?'
            );
            $stockUpdate->execute([$unitDelta, (int)$currentStock['retailer_stock_id']]);
        }
    }

    if ($existingSale) {
        $saveSale = $pdo->prepare(
            'UPDATE sales_history
             SET units_sold = ?, sales_amount = ?, sales_amount_basis = ?,
                 tax_category_snapshot = ?, tax_rate_snapshot = ?, tax_amount = ?,
                 net_sales_amount = ?
             WHERE sales_history_id = ?'
        );
        $saveSale->execute([
            $unitsSold,
            $salesAmount,
            $salesAmountBasis,
            $taxCategorySnapshot,
            $taxRateSnapshot,
            $taxAmount,
            $netSalesAmount,
            (int)$existingSale['sales_history_id']
        ]);
        $savedSaleId = (int)$existingSale['sales_history_id'];
    } else {
        $saveSale = $pdo->prepare(
            'INSERT INTO sales_history
                (retailer_id, branch_id, product_id, product_version_id, sale_date,
                 units_sold, sales_amount, sales_amount_basis, tax_category_snapshot,
                 tax_rate_snapshot, tax_amount, net_sales_amount)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $saveSale->execute([
            $actor['organization_id'],
            $branch['branch_id'],
            $productId,
            $versionId,
            $saleDate,
            $unitsSold,
            $salesAmount,
            $salesAmountBasis,
            $taxCategorySnapshot,
            $taxRateSnapshot,
            $taxAmount,
            $netSalesAmount
        ]);
        $savedSaleId = (int)$pdo->lastInsertId();
    }

    $updated = stock_fetch_rows($pdo, $actor, $branch, $productId, $versionId)[0] ?? null;
    if ($updated !== null && $updated['stock_recorded']) {
        stock_sync_alert($pdo, $actor, $branch, $updated);
    }

    $auditDescription = "Daily sales summary #{$savedSaleId}: {$product['product_name']} version {$product['version_number']} at {$branch['branch_name']}; units {$previousUnits} to {$unitsSold}; KSh {$salesAmount} ({$salesAmountBasis}); tax status {$taxCalculationStatus}";
    if ($isToday) {
        $auditDescription .= "; stock adjusted by " . (-$unitDelta) . ' units';
    } else {
        $auditDescription .= '; historical summary saved without changing current stock';
    }
    record_audit($pdo, $actor, 'DAILY_SALES_SUMMARY_SAVED', 'PRODUCT', $productId, $auditDescription);

    $pdo->commit();

    $baseMessage = $existingSale ? 'Daily sales summary updated.' : 'Daily sales summary saved.';
    $displayMessage = $baseMessage . ' ' . $taxCalculationMessage;

    respond(200, [
        'success' => true,
        'message' => $displayMessage,
        'branch' => $branch,
        'sale_summary' => [
            'sales_history_id' => $savedSaleId,
            'sale_date' => $saleDate,
            'units_sold' => $unitsSold,
            'sales_amount' => $salesAmount,
            'sales_amount_basis' => $salesAmountBasis,
            'tax_category_snapshot' => $taxCategorySnapshot,
            'tax_rate_snapshot' => $taxRateSnapshot,
            'tax_amount' => $taxAmount,
            'net_sales_amount' => $netSalesAmount,
            'tax_calculation_status' => $taxCalculationStatus,
            'tax_calculation_message' => $taxCalculationMessage,
            'was_updated' => (bool)$existingSale,
            'units_delta' => $unitDelta
        ],
        'tax_calculation_status' => $taxCalculationStatus,
        'tax_calculation_message' => $taxCalculationMessage,
        'stock' => $updated
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('stock sale summary', $e);
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'A daily summary for this product and date changed at the same time. Refresh and try again.');
    }
    respond_error(500, 'Unable to save the daily sales summary.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('stock sale summary unexpected', $e);
    respond_error(500, 'Unable to save the daily sales summary.');
}
