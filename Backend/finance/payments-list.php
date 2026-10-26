<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_admin($pdo);
    $organizationId = (int)$actor['organization_id'];
    $status = strtoupper(trim((string)($_GET['status'] ?? '')));
    $allowed = ['PENDING', 'CONFIRMED', 'FAILED', 'CANCELLED', 'REVERSED'];
    if ($status !== '' && !in_array($status, $allowed, true)) {
        respond_error(400, 'Invalid payment status filter.');
    }
    $limit = filter_var($_GET['limit'] ?? 100, FILTER_VALIDATE_INT);
    $limit = max(1, min(200, $limit === false ? 100 : (int)$limit));

    $sql =
        "SELECT p.payment_id, p.payer_organization_id, p.payee_organization_id,
                p.payer_branch_id, p.amount, p.currency_code, p.payment_method,
                p.reference_number, p.payment_date, p.status, p.notes,
                p.recorded_by_organization_id, p.recorded_by_user_id,
                p.confirmed_by_organization_id, p.confirmed_by_user_id,
                p.confirmed_at, p.created_at,
                payer.organization_name AS payer_name,
                payee.organization_name AS payee_name,
                CASE WHEN p.payer_organization_id = ? THEN 'OUTGOING' ELSE 'INCOMING' END AS direction,
                CASE WHEN p.payer_organization_id = ? THEN payee.organization_name ELSE payer.organization_name END AS counterparty_name,
                COALESCE(a.allocated_to_own_documents, 0) AS allocated_to_own_documents
         FROM finance_payments p
         INNER JOIN organizations payer ON payer.organization_id = p.payer_organization_id
         INNER JOIN organizations payee ON payee.organization_id = p.payee_organization_id
         LEFT JOIN (
             SELECT a.payment_id, SUM(a.allocated_amount) AS allocated_to_own_documents
             FROM finance_payment_allocations a
             INNER JOIN finance_documents d ON d.document_id = a.document_id
             WHERE d.organization_id = ?
             GROUP BY a.payment_id
         ) a ON a.payment_id = p.payment_id
         WHERE (p.payer_organization_id = ? OR p.payee_organization_id = ?)";
    $params = [$organizationId, $organizationId, $organizationId, $organizationId, $organizationId];
    if ($status !== '') {
        $sql .= ' AND p.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY p.payment_date DESC, p.payment_id DESC LIMIT ' . $limit;

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    respond(200, ['success' => true, 'payments' => $statement->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    log_error('finance payments list', $e);
    respond_error(500, 'Unable to load payments.');
}
