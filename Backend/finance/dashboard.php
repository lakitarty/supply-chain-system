<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/helpers.php';

try {
    $actor = require_admin($pdo);
    $organizationId = (int)$actor['organization_id'];

    $balanceStatement = $pdo->prepare(
        "SELECT d.direction,
                COUNT(*) AS document_count,
                COALESCE(SUM(GREATEST(d.total_amount - COALESCE(paid.paid_amount, 0), 0)), 0) AS outstanding_amount
         FROM finance_documents d
         LEFT JOIN (
             SELECT a.document_id,
                    SUM(CASE WHEN p.status = 'CONFIRMED' THEN a.allocated_amount ELSE 0 END) AS paid_amount
             FROM finance_payment_allocations a
             INNER JOIN finance_payments p ON p.payment_id = a.payment_id
             GROUP BY a.document_id
         ) paid ON paid.document_id = d.document_id
         WHERE d.organization_id = ?
           AND d.status IN ('ISSUED', 'PARTIALLY_PAID', 'OVERDUE')
         GROUP BY d.direction"
    );
    $balanceStatement->execute([$organizationId]);
    $receivables = 0.0;
    $payables = 0.0;
    $receivableDocuments = 0;
    $payableDocuments = 0;
    foreach ($balanceStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['direction'] === 'RECEIVABLE') {
            $receivables = (float)$row['outstanding_amount'];
            $receivableDocuments = (int)$row['document_count'];
        } elseif ($row['direction'] === 'PAYABLE') {
            $payables = (float)$row['outstanding_amount'];
            $payableDocuments = (int)$row['document_count'];
        }
    }

    $overdueStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM finance_documents d
         LEFT JOIN (
             SELECT a.document_id,
                    SUM(CASE WHEN p.status = 'CONFIRMED' THEN a.allocated_amount ELSE 0 END) AS paid_amount
             FROM finance_payment_allocations a
             INNER JOIN finance_payments p ON p.payment_id = a.payment_id
             GROUP BY a.document_id
         ) paid ON paid.document_id = d.document_id
         WHERE d.organization_id = ?
           AND d.status IN ('ISSUED', 'PARTIALLY_PAID', 'OVERDUE')
           AND d.due_date IS NOT NULL AND d.due_date < CURDATE()
           AND GREATEST(d.total_amount - COALESCE(paid.paid_amount, 0), 0) > 0"
    );
    $overdueStatement->execute([$organizationId]);
    $overdueCount = (int)$overdueStatement->fetchColumn();

    $pendingStatement = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN payee_organization_id = ? AND status = 'PENDING' THEN 1 ELSE 0 END) AS incoming_count,
            SUM(CASE WHEN payer_organization_id = ? AND status = 'PENDING' THEN 1 ELSE 0 END) AS outgoing_count,
            SUM(CASE WHEN payee_organization_id = ? AND status = 'PENDING' THEN amount ELSE 0 END) AS incoming_amount,
            SUM(CASE WHEN payer_organization_id = ? AND status = 'PENDING' THEN amount ELSE 0 END) AS outgoing_amount
         FROM finance_payments
         WHERE payer_organization_id = ? OR payee_organization_id = ?"
    );
    $pendingStatement->execute([
        $organizationId, $organizationId, $organizationId,
        $organizationId, $organizationId, $organizationId
    ]);
    $pending = $pendingStatement->fetch(PDO::FETCH_ASSOC) ?: [];

    $recentStatement = $pdo->prepare(
        "SELECT p.payment_id, p.amount, p.currency_code, p.payment_method,
                p.reference_number, p.payment_date, p.status,
                CASE WHEN p.payer_organization_id = ? THEN 'OUTGOING' ELSE 'INCOMING' END AS direction,
                CASE WHEN p.payer_organization_id = ? THEN payee.organization_name ELSE payer.organization_name END AS counterparty_name
         FROM finance_payments p
         INNER JOIN organizations payer ON payer.organization_id = p.payer_organization_id
         INNER JOIN organizations payee ON payee.organization_id = p.payee_organization_id
         WHERE p.payer_organization_id = ? OR p.payee_organization_id = ?
         ORDER BY p.payment_date DESC, p.payment_id DESC
         LIMIT 8"
    );
    $recentStatement->execute([$organizationId, $organizationId, $organizationId, $organizationId]);

    respond(200, [
        'success' => true,
        'summary' => [
            'receivables' => round($receivables, 2),
            'payables' => round($payables, 2),
            'net_position' => round($receivables - $payables, 2),
            'receivable_documents' => $receivableDocuments,
            'payable_documents' => $payableDocuments,
            'overdue_documents' => $overdueCount,
            'pending_incoming_count' => (int)($pending['incoming_count'] ?? 0),
            'pending_outgoing_count' => (int)($pending['outgoing_count'] ?? 0),
            'pending_incoming_amount' => round((float)($pending['incoming_amount'] ?? 0), 2),
            'pending_outgoing_amount' => round((float)($pending['outgoing_amount'] ?? 0), 2)
        ],
        'recent_payments' => $recentStatement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    log_error('finance dashboard', $e);
    respond_error(500, 'Unable to load the financial dashboard.');
}
