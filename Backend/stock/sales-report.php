<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/stock-rules.php';

try {
    $actor = require_login($pdo);
    stock_require_retailer($actor);

    $period = strtoupper(trim((string)($_GET['period'] ?? 'TODAY')));
    $periods = [
        'TODAY' => [
            'start_sql' => 'CURDATE()',
            'label' => 'Today'
        ],
        'LAST_7_DAYS' => [
            'start_sql' => 'DATE_SUB(CURDATE(), INTERVAL 6 DAY)',
            'label' => 'Last 7 days'
        ],
        'THIS_MONTH' => [
            'start_sql' => "DATE_FORMAT(CURDATE(), '%Y-%m-01')",
            'label' => 'This month'
        ]
    ];
    if (!isset($periods[$period])) {
        respond_field_error(400, field_error('period', 'Choose today, the last 7 days, or this month.'));
    }

    $requestedBranchRaw = $_GET['branchId'] ?? null;
    $allBranches = $actor['role'] === 'ADMIN'
        && ($requestedBranchRaw === null || $requestedBranchRaw === '' || strtoupper((string)$requestedBranchRaw) === 'ALL');

    if ($allBranches) {
        $branch = null;
    } else {
        $requestedBranchId = null;
        if ($requestedBranchRaw !== null && $requestedBranchRaw !== '' && strtoupper((string)$requestedBranchRaw) !== 'ALL') {
            $requestedBranchId = stock_optional_id($requestedBranchRaw);
            if ($requestedBranchId === null) {
                respond_field_error(400, field_error('branchId', 'Select a valid branch.'));
            }
        } elseif (strtoupper((string)$requestedBranchRaw) === 'ALL' && $actor['role'] !== 'ADMIN') {
            respond_error(403, 'Only organisation administrators can view the all-branches report.');
        }
        $branch = stock_resolve_branch($pdo, $actor, $requestedBranchId);
    }

    $scopeSql = $allBranches ? 'sh.branch_id IS NOT NULL' : 'sh.branch_id = ?';
    $scopeParams = $allBranches
        ? [(int)$actor['organization_id']]
        : [(int)$actor['organization_id'], (int)$branch['branch_id']];
    $startSql = $periods[$period]['start_sql'];
    $dateFilter = "sh.sale_date >= {$startSql} AND sh.sale_date <= CURDATE()";
    $validVersionFilter = 'sh.product_version_id IS NOT NULL AND pv.product_version_id IS NOT NULL AND pv.product_id = sh.product_id';

    $summary = $pdo->prepare(
        "SELECT COUNT(*) AS entry_count,
                COALESCE(SUM(sh.units_sold), 0) AS units_sold,
                COALESCE(SUM(CASE WHEN sh.sales_amount IS NOT NULL THEN sh.sales_amount ELSE 0 END), 0.00) AS amount_entered,
                COALESCE(SUM(CASE WHEN sh.tax_amount IS NOT NULL THEN sh.tax_amount ELSE 0 END), 0.00) AS tax_calculated_total,
                COALESCE(SUM(CASE WHEN sh.tax_category_snapshot = 'ZERO_RATED' THEN sh.net_sales_amount ELSE 0 END), 0.00) AS zero_rated_total,
                COALESCE(SUM(CASE WHEN sh.tax_category_snapshot = 'EXEMPT' THEN sh.net_sales_amount ELSE 0 END), 0.00) AS exempt_total,
                COALESCE(SUM(CASE WHEN sh.net_sales_amount IS NOT NULL THEN sh.net_sales_amount ELSE 0 END), 0.00) AS net_sales_total,
                COALESCE(SUM(CASE 
                    WHEN sh.sales_amount_basis = 'VAT_INCLUSIVE' THEN sh.sales_amount 
                    WHEN sh.sales_amount_basis = 'VAT_EXCLUSIVE' THEN sh.sales_amount + COALESCE(sh.tax_amount, 0)
                    ELSE NULL END), 0.00) AS customer_total_incl_vat,
                COALESCE(SUM(CASE WHEN sh.sales_amount IS NULL THEN 1 ELSE 0 END), 0) AS entries_missing_amount,
                COALESCE(SUM(CASE WHEN sh.sales_amount IS NOT NULL AND (sh.sales_amount_basis IS NULL OR sh.tax_category_snapshot IS NULL OR sh.tax_rate_snapshot IS NULL OR sh.tax_amount IS NULL OR sh.net_sales_amount IS NULL) THEN 1 ELSE 0 END), 0) AS entries_missing_tax_calculation,
                COALESCE(SUM(CASE WHEN sh.sales_amount IS NOT NULL AND sh.sales_amount_basis IS NOT NULL AND sh.tax_category_snapshot IS NOT NULL AND sh.tax_rate_snapshot IS NOT NULL AND sh.tax_amount IS NOT NULL AND sh.net_sales_amount IS NOT NULL THEN 1 ELSE 0 END), 0) AS entries_tax_calculated
         FROM sales_history sh
         INNER JOIN branches report_branch
            ON report_branch.branch_id = sh.branch_id
           AND report_branch.organization_id = sh.retailer_id
         LEFT JOIN product_versions pv ON pv.product_version_id = sh.product_version_id
         WHERE sh.retailer_id = ? AND {$scopeSql} AND {$dateFilter} AND {$validVersionFilter}"
    );
    $summary->execute($scopeParams);
    $totals = $summary->fetch(PDO::FETCH_ASSOC) ?: [
        'entry_count' => 0,
        'units_sold' => 0,
        'amount_entered' => '0.00',
        'tax_calculated_total' => '0.00',
        'net_sales_total' => '0.00',
        'customer_total_incl_vat' => '0.00',
        'entries_missing_amount' => 0,
        'entries_missing_tax_calculation' => 0,
        'entries_tax_calculated' => 0
    ];
    $totals['entry_count'] = (int)$totals['entry_count'];
    $totals['units_sold'] = (int)$totals['units_sold'];
    foreach (['amount_entered', 'tax_calculated_total', 'net_sales_total', 'customer_total_incl_vat', 'zero_rated_total', 'exempt_total'] as $moneyKey) {
        $totals[$moneyKey] = (string)$totals[$moneyKey];
    }
    foreach (['entries_missing_amount', 'entries_missing_tax_calculation', 'entries_tax_calculated'] as $countKey) {
        $totals[$countKey] = (int)$totals[$countKey];
    }

    $details = $pdo->prepare(
        "SELECT sh.branch_id, b.branch_name,
                sh.product_id, p.product_name, sh.product_version_id,
                pv.version_number, pv.pack_size, pv.packaging_description, pv.barcode,
                COUNT(*) AS entry_count,
                SUM(sh.units_sold) AS units_sold,
                COALESCE(SUM(CASE WHEN sh.sales_amount IS NOT NULL THEN sh.sales_amount ELSE 0 END), 0.00) AS amount_entered,
                COALESCE(SUM(CASE WHEN sh.tax_amount IS NOT NULL THEN sh.tax_amount ELSE 0 END), 0.00) AS tax_calculated,
                COALESCE(SUM(CASE WHEN sh.net_sales_amount IS NOT NULL THEN sh.net_sales_amount ELSE 0 END), 0.00) AS net_sales_calculated,
                COALESCE(SUM(CASE 
                    WHEN sh.sales_amount_basis = 'VAT_INCLUSIVE' THEN sh.sales_amount 
                    WHEN sh.sales_amount_basis = 'VAT_EXCLUSIVE' THEN sh.sales_amount + COALESCE(sh.tax_amount, 0)
                    ELSE NULL END), 0.00) AS customer_total_incl_vat,
                SUM(CASE WHEN sh.sales_amount IS NULL THEN 1 ELSE 0 END) AS entries_missing_amount,
                SUM(CASE WHEN sh.sales_amount IS NOT NULL AND (sh.sales_amount_basis IS NULL OR sh.tax_category_snapshot IS NULL OR sh.tax_rate_snapshot IS NULL OR sh.tax_amount IS NULL OR sh.net_sales_amount IS NULL) THEN 1 ELSE 0 END) AS entries_missing_tax_calculation,
                SUM(CASE WHEN sh.sales_amount IS NOT NULL AND sh.sales_amount_basis IS NOT NULL AND sh.tax_category_snapshot IS NOT NULL AND sh.tax_rate_snapshot IS NOT NULL AND sh.tax_amount IS NOT NULL AND sh.net_sales_amount IS NOT NULL THEN 1 ELSE 0 END) AS entries_tax_calculated
         FROM sales_history sh
         INNER JOIN products p ON p.product_id = sh.product_id
         INNER JOIN branches b
            ON b.branch_id = sh.branch_id
           AND b.organization_id = sh.retailer_id
         LEFT JOIN product_versions pv ON pv.product_version_id = sh.product_version_id
         WHERE sh.retailer_id = ? AND {$scopeSql} AND {$dateFilter} AND {$validVersionFilter}
         GROUP BY sh.branch_id, b.branch_name, sh.product_id, p.product_name,
                  sh.product_version_id, pv.version_number, pv.pack_size,
                  pv.packaging_description, pv.barcode
         ORDER BY sh.branch_id, p.product_name, pv.version_number DESC"
    );
    $details->execute($scopeParams);
    $rows = $details->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['branch_id'] = (int)$row['branch_id'];
        $row['product_id'] = (int)$row['product_id'];
        $row['product_version_id'] = (int)$row['product_version_id'];
        $row['version_number'] = (int)$row['version_number'];
        $row['entry_count'] = (int)$row['entry_count'];
        $row['units_sold'] = (int)$row['units_sold'];
        foreach (['amount_entered', 'tax_calculated', 'net_sales_calculated', 'customer_total_incl_vat'] as $moneyKey) {
            $row[$moneyKey] = (string)$row[$moneyKey];
        }
        foreach (['entries_missing_amount', 'entries_missing_tax_calculation', 'entries_tax_calculated'] as $countKey) {
            $row[$countKey] = (int)$row[$countKey];
        }
    }
    unset($row);

    $excluded = $pdo->prepare(
        "SELECT COUNT(*)
         FROM sales_history sh
         LEFT JOIN branches br ON br.branch_id = sh.branch_id
         LEFT JOIN product_versions pv ON pv.product_version_id = sh.product_version_id
         WHERE sh.retailer_id = ? AND {$dateFilter}
           AND (sh.branch_id IS NULL OR br.branch_id IS NULL OR br.organization_id <> sh.retailer_id
                OR sh.product_version_id IS NULL OR pv.product_version_id IS NULL OR pv.product_id <> sh.product_id)"
    );
    $excluded->execute([(int)$actor['organization_id']]);
    $excludedEntries = (int)$excluded->fetchColumn();

    $range = $pdo->query(
        "SELECT DATE_FORMAT({$startSql}, '%Y-%m-%d') AS start_date,
                DATE_FORMAT(CURDATE(), '%Y-%m-%d') AS end_date"
    )->fetch(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'period' => $period,
        'period_label' => $periods[$period]['label'],
        'start_date' => $range['start_date'] ?? null,
        'end_date' => $range['end_date'] ?? null,
        'all_branches' => $allBranches,
        'branch' => $branch,
        'branches' => stock_branch_options($pdo, $actor),
        'totals' => $totals,
        'rows' => $rows,
        'excluded_legacy_entries' => $excludedEntries,
        'tax_calculation_note' => 'Tax and net sales totals include only entries with a complete VAT basis, manufacturer classification, effective rate, and saved calculations. Entries missing these details remain recorded but are excluded from calculated totals.'
    ]);
} catch (PDOException $e) {
    log_error('stock sales report', $e);
    respond_error(500, 'Unable to load sales reports.');
} catch (Throwable $e) {
    log_error('stock sales report unexpected', $e);
    respond_error(500, 'Unable to load sales reports.');
}
