<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/helpers.php';

try {
    $actor = require_admin($pdo);
    $organizationId = (int)$actor['organization_id'];

    $statement = $pdo->prepare(
        "SELECT a.account_id, a.account_code, a.account_name, a.account_type,
                a.normal_balance, a.status,
                COALESCE(SUM(CASE WHEN j.journal_entry_id IS NOT NULL
                    THEN CASE WHEN a.normal_balance = 'DEBIT'
                        THEN jl.debit_amount - jl.credit_amount
                        ELSE jl.credit_amount - jl.debit_amount
                    END
                    ELSE 0 END), 0) AS balance
         FROM finance_accounts a
         LEFT JOIN finance_journal_lines jl ON jl.account_id = a.account_id
         LEFT JOIN finance_journal_entries j
            ON j.journal_entry_id = jl.journal_entry_id
           AND j.organization_id = a.organization_id
           AND j.status = 'POSTED'
         WHERE a.organization_id = ? AND a.status = 'ACTIVE'
         GROUP BY a.account_id, a.account_code, a.account_name,
                  a.account_type, a.normal_balance, a.status
         ORDER BY a.account_code"
    );
    $statement->execute([$organizationId]);

    respond(200, [
        'success' => true,
        'accounts' => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    log_error('finance accounts list', $e);
    respond_error(500, 'Unable to load financial accounts.');
}
