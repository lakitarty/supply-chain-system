<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_admin($pdo);
    $organizationId = (int)$actor['organization_id'];
    $fromDate = trim((string)($_GET['from_date'] ?? ''));
    $toDate = trim((string)($_GET['to_date'] ?? ''));
    if ($fromDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $fromDate)) {
        respond_error(400, 'Invalid ledger start date.');
    }
    if ($toDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $toDate)) {
        respond_error(400, 'Invalid ledger end date.');
    }
    if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
        respond_error(400, 'The ledger start date must not be after its end date.');
    }
    $limit = filter_var($_GET['limit'] ?? 200, FILTER_VALIDATE_INT);
    $limit = max(1, min(500, $limit === false ? 200 : (int)$limit));

    $sql =
        "SELECT j.journal_entry_id, j.entry_number, j.source_type, j.source_id,
                j.entry_date, j.description AS entry_description, j.status,
                j.posted_at, l.journal_line_id, l.line_description,
                l.debit_amount, l.credit_amount,
                a.account_code, a.account_name, a.account_type
         FROM finance_journal_entries j
         INNER JOIN finance_journal_lines l ON l.journal_entry_id = j.journal_entry_id
         INNER JOIN finance_accounts a ON a.account_id = l.account_id
         WHERE j.organization_id = ? AND a.organization_id = ? AND j.status = 'POSTED'";
    $params = [$organizationId, $organizationId];
    if ($fromDate !== '') {
        $sql .= ' AND j.entry_date >= ?';
        $params[] = $fromDate;
    }
    if ($toDate !== '') {
        $sql .= ' AND j.entry_date <= ?';
        $params[] = $toDate;
    }
    $sql .= ' ORDER BY j.entry_date DESC, j.journal_entry_id DESC, l.journal_line_id LIMIT ' . $limit;

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    respond(200, ['success' => true, 'ledger_lines' => $statement->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    log_error('finance ledger list', $e);
    respond_error(500, 'Unable to load the financial ledger.');
}
