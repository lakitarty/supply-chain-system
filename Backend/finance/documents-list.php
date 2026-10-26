<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_admin($pdo);
    $organizationId = (int)$actor['organization_id'];

    $direction = strtoupper(trim((string)($_GET['direction'] ?? '')));
    if ($direction !== '' && !in_array($direction, ['RECEIVABLE', 'PAYABLE'], true)) {
        respond_error(400, 'Invalid document direction filter.');
    }
    $status = strtoupper(trim((string)($_GET['status'] ?? '')));
    $allowedStatuses = ['DRAFT', 'ISSUED', 'PARTIALLY_PAID', 'PAID', 'OVERDUE', 'VOID'];
    if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
        respond_error(400, 'Invalid document status filter.');
    }
    $limit = filter_var($_GET['limit'] ?? 100, FILTER_VALIDATE_INT);
    $limit = max(1, min(200, $limit === false ? 100 : (int)$limit));

    $sql =
        "SELECT d.document_id, d.counterparty_organization_id,
                d.branch_id, d.source_supply_order_id, d.document_number,
                d.document_type, d.direction, d.issue_date, d.due_date,
                d.currency_code, d.subtotal, d.tax_total, d.total_amount,
                d.status, d.notes, d.issued_at, d.created_at,
                cp.organization_name AS counterparty_name,
                COALESCE(paid.paid_amount, 0) AS paid_amount,
                GREATEST(d.total_amount - COALESCE(paid.paid_amount, 0), 0) AS balance_due,
                CASE
                    WHEN d.status IN ('DRAFT', 'VOID') THEN d.status
                    WHEN GREATEST(d.total_amount - COALESCE(paid.paid_amount, 0), 0) <= 0.005 THEN 'PAID'
                    WHEN COALESCE(paid.paid_amount, 0) > 0.005 THEN 'PARTIALLY_PAID'
                    WHEN d.due_date IS NOT NULL AND d.due_date < CURDATE() THEN 'OVERDUE'
                    ELSE 'ISSUED'
                END AS calculated_status
         FROM finance_documents d
         INNER JOIN organizations cp ON cp.organization_id = d.counterparty_organization_id
         LEFT JOIN (
             SELECT a.document_id,
                    SUM(CASE WHEN p.status = 'CONFIRMED' THEN a.allocated_amount ELSE 0 END) AS paid_amount
             FROM finance_payment_allocations a
             INNER JOIN finance_payments p ON p.payment_id = a.payment_id
             GROUP BY a.document_id
         ) paid ON paid.document_id = d.document_id
         WHERE d.organization_id = ?";
    $params = [$organizationId];
    if ($direction !== '') {
        $sql .= ' AND d.direction = ?';
        $params[] = $direction;
    }
    if ($status !== '') {
        $sql .= ' AND d.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY d.issue_date DESC, d.document_id DESC LIMIT ' . $limit;

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $documents = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($documents as &$document) {
        $linesStatement = $pdo->prepare(
            'SELECT document_line_id, line_number, product_id, description,
                    quantity, unit_price, tax_rate, line_subtotal, line_tax, line_total
             FROM finance_document_lines
             WHERE document_id = ?
             ORDER BY line_number'
        );
        $linesStatement->execute([(int)$document['document_id']]);
        $document['lines'] = $linesStatement->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($document);

    respond(200, ['success' => true, 'documents' => $documents]);
} catch (Throwable $e) {
    log_error('finance documents list', $e);
    respond_error(500, 'Unable to load financial documents.');
}
