<?php
declare(strict_types=1);

function finance_today(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');
}

function finance_money(mixed $value, string $label, bool $allowZero = true): float
{
    if (!is_numeric($value)) {
        throw new InvalidArgumentException("{$label} must be a number.");
    }

    $amount = (float)$value;
    if (!is_finite($amount) || $amount < 0 || (!$allowZero && $amount <= 0) || $amount > 999999999999.99) {
        throw new InvalidArgumentException("{$label} is outside the supported range.");
    }

    return round($amount, 2);
}

function finance_relationship_is_active(PDO $pdo, int $firstOrganizationId, int $secondOrganizationId, string $date): bool
{
    $statement = $pdo->prepare(
        "SELECT relationship_id
         FROM organization_relationships
         WHERE relationship_type = 'SUPPLIES'
           AND status = 'ACTIVE'
           AND start_date <= ?
           AND (end_date IS NULL OR end_date >= ?)
           AND (
               (source_organization_id = ? AND target_organization_id = ?)
               OR (source_organization_id = ? AND target_organization_id = ?)
           )
         LIMIT 1"
    );
    $statement->execute([
        $date, $date,
        $firstOrganizationId, $secondOrganizationId,
        $secondOrganizationId, $firstOrganizationId
    ]);

    return $statement->fetchColumn() !== false;
}

const FINANCE_DEFAULT_ACCOUNTS = [
    ['1000', 'Cash on hand', 'ASSET', 'DEBIT'],
    ['1010', 'Bank account', 'ASSET', 'DEBIT'],
    ['1020', 'M-Pesa and mobile money', 'ASSET', 'DEBIT'],
    ['1030', 'Other payment clearing', 'ASSET', 'DEBIT'],
    ['1100', 'Accounts receivable', 'ASSET', 'DEBIT'],
    ['1200', 'Inventory', 'ASSET', 'DEBIT'],
    ['1300', 'Input tax receivable', 'ASSET', 'DEBIT'],
    ['1400', 'Supplier advances', 'ASSET', 'DEBIT'],
    ['2000', 'Accounts payable', 'LIABILITY', 'CREDIT'],
    ['2100', 'Tax payable', 'LIABILITY', 'CREDIT'],
    ['2200', 'Customer advances', 'LIABILITY', 'CREDIT'],
    ['3000', 'Opening balance equity', 'EQUITY', 'CREDIT'],
    ['4000', 'Sales revenue', 'INCOME', 'CREDIT'],
    ['5000', 'Purchases and cost of goods sold', 'EXPENSE', 'DEBIT'],
    ['5100', 'Payment and bank fees', 'EXPENSE', 'DEBIT']
];

function finance_ensure_accounts(PDO $pdo, int $organizationId): void
{
    $existing = $pdo->prepare('SELECT COUNT(*) FROM finance_accounts WHERE organization_id = ?');
    $existing->execute([$organizationId]);
    if ((int)$existing->fetchColumn() > 0) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT IGNORE INTO finance_accounts (organization_id, account_code, account_name, account_type, normal_balance)
         VALUES (?, ?, ?, ?, ?)'
    );
    foreach (FINANCE_DEFAULT_ACCOUNTS as [$code, $name, $type, $balance]) {
        $insert->execute([$organizationId, $code, $name, $type, $balance]);
    }
}

function finance_account(PDO $pdo, int $organizationId, string $accountCode): array
{
    $statement = $pdo->prepare(
        "SELECT account_id, account_code, account_name, account_type, normal_balance
         FROM finance_accounts
         WHERE organization_id = ? AND account_code = ? AND status = 'ACTIVE'
         LIMIT 1"
    );
    $statement->execute([$organizationId, $accountCode]);
    $account = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        throw new DomainException("Required finance account {$accountCode} is missing or inactive for this organisation.");
    }

    return $account;
}

/** @param array<int,array{code:string,debit:float,credit:float,description?:string}> $lines */
function finance_post_journal(
    PDO $pdo,
    int $organizationId,
    int $userId,
    string $entryNumber,
    string $sourceType,
    ?int $sourceId,
    string $entryDate,
    string $description,
    array $lines
): int {
    finance_ensure_accounts($pdo, $organizationId);

    $preparedLines = [];
    $debitCents = 0;
    $creditCents = 0;

    foreach ($lines as $line) {
        $debit = round((float)($line['debit'] ?? 0), 2);
        $credit = round((float)($line['credit'] ?? 0), 2);
        if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0)) {
            throw new DomainException('Journal lines must contain either a non-negative debit or a non-negative credit, not both.');
        }
        if ($debit === 0.0 && $credit === 0.0) {
            continue;
        }

        $account = finance_account($pdo, $organizationId, (string)$line['code']);
        $debitCents += (int)round($debit * 100);
        $creditCents += (int)round($credit * 100);
        $preparedLines[] = [
            'account_id' => (int)$account['account_id'],
            'description' => isset($line['description']) ? (string)$line['description'] : null,
            'debit' => $debit,
            'credit' => $credit
        ];
    }

    if ($preparedLines === [] || $debitCents !== $creditCents) {
        throw new DomainException('The journal entry is empty or its debits and credits do not balance.');
    }

    $entry = $pdo->prepare(
        "INSERT INTO finance_journal_entries
            (organization_id, entry_number, source_type, source_id, entry_date,
             description, status, created_by_user_id, posted_at)
         VALUES (?, ?, ?, ?, ?, ?, 'POSTED', ?, NOW())"
    );
    $entry->execute([
        $organizationId,
        $entryNumber,
        $sourceType,
        $sourceId,
        $entryDate,
        mb_substr($description, 0, 500),
        $userId
    ]);
    $entryId = (int)$pdo->lastInsertId();

    $lineInsert = $pdo->prepare(
        "INSERT INTO finance_journal_lines
            (journal_entry_id, account_id, line_description, debit_amount, credit_amount)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($preparedLines as $line) {
        $lineInsert->execute([
            $entryId,
            $line['account_id'],
            $line['description'],
            $line['debit'],
            $line['credit']
        ]);
    }

    return $entryId;
}

function finance_reverse_journal(
    PDO $pdo,
    int $organizationId,
    int $userId,
    string $sourceType,
    int $sourceId,
    string $entryDate,
    string $description
): int {
    $originalStatement = $pdo->prepare(
        "SELECT journal_entry_id, entry_number
         FROM finance_journal_entries
         WHERE organization_id = ? AND source_type = ? AND source_id = ? AND status = 'POSTED'
         LIMIT 1 FOR UPDATE"
    );
    $originalStatement->execute([$organizationId, $sourceType, $sourceId]);
    $original = $originalStatement->fetch(PDO::FETCH_ASSOC);
    if (!$original) {
        throw new DomainException('The original journal entry was not found, so it cannot be reversed.');
    }

    $linesStatement = $pdo->prepare(
        'SELECT account_id, line_description, debit_amount, credit_amount
         FROM finance_journal_lines WHERE journal_entry_id = ?'
    );
    $linesStatement->execute([(int)$original['journal_entry_id']]);
    $originalLines = $linesStatement->fetchAll(PDO::FETCH_ASSOC);
    if ($originalLines === []) {
        throw new DomainException('The original journal entry has no lines, so it cannot be reversed.');
    }

    $entryStatement = $pdo->prepare(
        "INSERT INTO finance_journal_entries
            (organization_id, entry_number, source_type, source_id, entry_date,
             description, status, reversal_of_entry_id, created_by_user_id, posted_at)
         VALUES (?, ?, 'REVERSAL', ?, ?, ?, 'POSTED', ?, ?, NOW())"
    );
    $entryStatement->execute([
        $organizationId,
        'REV-' . (string)$original['entry_number'],
        $sourceId,
        $entryDate,
        mb_substr($description, 0, 500),
        (int)$original['journal_entry_id'],
        $userId
    ]);
    $reversalId = (int)$pdo->lastInsertId();

    $lineInsert = $pdo->prepare(
        'INSERT INTO finance_journal_lines
            (journal_entry_id, account_id, line_description, debit_amount, credit_amount)
         VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($originalLines as $line) {
        $lineInsert->execute([
            $reversalId,
            (int)$line['account_id'],
            mb_substr('Reversal - ' . (string)$line['line_description'], 0, 500),
            $line['credit_amount'],
            $line['debit_amount']
        ]);
    }

    return $reversalId;
}

function finance_update_document_status(PDO $pdo, int $documentId): void
{
    $statement = $pdo->prepare(
        "SELECT d.total_amount, d.due_date, d.status,
                COALESCE(SUM(CASE WHEN p.status = 'CONFIRMED' THEN a.allocated_amount ELSE 0 END), 0) AS paid_amount
         FROM finance_documents d
         LEFT JOIN finance_payment_allocations a ON a.document_id = d.document_id
         LEFT JOIN finance_payments p ON p.payment_id = a.payment_id
         WHERE d.document_id = ?
         GROUP BY d.document_id, d.total_amount, d.due_date, d.status"
    );
    $statement->execute([$documentId]);
    $document = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$document || in_array((string)$document['status'], ['DRAFT', 'VOID'], true)) {
        return;
    }

    $total = (float)$document['total_amount'];
    $paid = (float)$document['paid_amount'];
    if ($paid >= $total - 0.005) {
        $status = 'PAID';
    } elseif ($paid > 0.005) {
        $status = 'PARTIALLY_PAID';
    } elseif ($document['due_date'] !== null && (string)$document['due_date'] < finance_today()) {
        $status = 'OVERDUE';
    } else {
        $status = 'ISSUED';
    }

    $update = $pdo->prepare(
        "UPDATE finance_documents
         SET status = ?
         WHERE document_id = ? AND status NOT IN ('DRAFT', 'VOID')"
    );
    $update->execute([$status, $documentId]);
}

function finance_payment_asset_code(string $method): string
{
    return match ($method) {
        'CASH' => '1000',
        'BANK_TRANSFER', 'CHEQUE' => '1010',
        'MPESA' => '1020',
        'OTHER' => '1030',
        default => throw new InvalidArgumentException('Choose a supported payment method.')
    };
}
