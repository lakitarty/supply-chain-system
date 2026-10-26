<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/helpers.php';

require_post();

try {
    $actor = require_admin($pdo);
    $organizationId = (int)$actor['organization_id'];
    $body = read_json_body();
    $documentId = filter_var($body['document_id'] ?? null, FILTER_VALIDATE_INT);
    if ($documentId === false || $documentId === null || $documentId < 1) {
        respond_error(400, 'Select a valid draft document to issue.');
    }

    $pdo->beginTransaction();
    $documentStatement = $pdo->prepare(
        'SELECT * FROM finance_documents WHERE document_id = ? AND organization_id = ? LIMIT 1 FOR UPDATE'
    );
    $documentStatement->execute([(int)$documentId, $organizationId]);
    $document = $documentStatement->fetch(PDO::FETCH_ASSOC);
    if (!$document) {
        $pdo->rollBack();
        respond_error(404, 'The document was not found in your organisation.');
    }
    if ((string)$document['status'] !== 'DRAFT') {
        $pdo->rollBack();
        respond_error(409, 'Only draft documents can be issued.');
    }
    if (!in_array((string)$document['document_type'], ['INVOICE', 'BILL'], true)) {
        $pdo->rollBack();
        respond_error(409, 'Only invoices and bills can be issued in this release.');
    }

    $lineStatement = $pdo->prepare(
        'SELECT COALESCE(SUM(line_subtotal), 0) AS subtotal,
                COALESCE(SUM(line_tax), 0) AS tax_total,
                COALESCE(SUM(line_total), 0) AS total_amount,
                COUNT(*) AS line_count
         FROM finance_document_lines WHERE document_id = ?'
    );
    $lineStatement->execute([(int)$documentId]);
    $totals = $lineStatement->fetch(PDO::FETCH_ASSOC);
    if (!$totals || (int)$totals['line_count'] < 1 || (float)$totals['total_amount'] <= 0) {
        $pdo->rollBack();
        respond_error(409, 'The document must have at least one line and a total greater than zero.');
    }
    foreach (['subtotal', 'tax_total', 'total_amount'] as $field) {
        if (abs((float)$totals[$field] - (float)$document[$field]) > 0.005) {
            $pdo->rollBack();
            respond_error(409, 'The document totals do not match its lines. Reopen or recreate the draft before issuing it.');
        }
    }

    $subtotal = round((float)$document['subtotal'], 2);
    $taxTotal = round((float)$document['tax_total'], 2);
    $total = round((float)$document['total_amount'], 2);
    if ((string)$document['document_type'] === 'INVOICE' && (string)$document['direction'] === 'RECEIVABLE') {
        $journalLines = [
            ['code' => '1100', 'debit' => $total, 'credit' => 0, 'description' => 'Accounts receivable - ' . $document['document_number']],
            ['code' => '4000', 'debit' => 0, 'credit' => $subtotal, 'description' => 'Sales revenue - ' . $document['document_number']]
        ];
        if ($taxTotal > 0) {
            $journalLines[] = ['code' => '2100', 'debit' => 0, 'credit' => $taxTotal, 'description' => 'Tax payable - ' . $document['document_number']];
        }
    } elseif ((string)$document['document_type'] === 'BILL' && (string)$document['direction'] === 'PAYABLE') {
        $journalLines = [
            ['code' => '5000', 'debit' => $subtotal, 'credit' => 0, 'description' => 'Purchases and cost of goods sold - ' . $document['document_number']],
            ['code' => '2000', 'debit' => 0, 'credit' => $total, 'description' => 'Accounts payable - ' . $document['document_number']]
        ];
        if ($taxTotal > 0) {
            $journalLines[] = ['code' => '1300', 'debit' => $taxTotal, 'credit' => 0, 'description' => 'Input tax receivable - ' . $document['document_number']];
        }
    } else {
        $pdo->rollBack();
        respond_error(409, 'The document type and direction do not match.');
    }

    finance_post_journal(
        $pdo,
        $organizationId,
        (int)$actor['user_id'],
        'DOC-' . (int)$documentId,
        'DOCUMENT',
        (int)$documentId,
        (string)$document['issue_date'],
        (string)$document['document_type'] . ' ' . (string)$document['document_number'],
        $journalLines
    );

    $updateDocument = $pdo->prepare(
        "UPDATE finance_documents SET status = 'ISSUED', issued_at = NOW()
         WHERE document_id = ? AND organization_id = ? AND status = 'DRAFT'"
    );
    $updateDocument->execute([(int)$documentId, $organizationId]);
    if ($updateDocument->rowCount() !== 1) {
        throw new RuntimeException('The document status changed while it was being issued.');
    }

    record_audit(
        $pdo,
        $actor,
        'FINANCE_DOCUMENT_ISSUED',
        'FINANCE_DOCUMENT',
        (int)$documentId,
        "Issued {$document['document_type']} {$document['document_number']} for KES " . number_format($total, 2, '.', '') . ' and posted its journal entry.'
    );
    $pdo->commit();

    respond(200, [
        'success' => true,
        'document_id' => (int)$documentId,
        'status' => 'ISSUED',
        'message' => 'Document issued and balanced journal entry posted.'
    ]);
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(409, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance document issue', $e);
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'A journal entry for this document already exists or an account constraint was violated.');
    }
    respond_error(500, 'Unable to issue the financial document.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance document issue unexpected', $e);
    respond_error(500, 'Unable to issue the financial document.');
}
