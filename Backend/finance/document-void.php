<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/helpers.php';

require_post();

try {
    $actor = require_admin($pdo);
    $organizationId = (int)$actor['organization_id'];
    $body = read_json_body();
    $documentId = filter_var($body['document_id'] ?? null, FILTER_VALIDATE_INT);
    if ($documentId === false || $documentId === null || $documentId < 1) {
        respond_error(400, 'Select a valid issued document to void.');
    }
    $reason = trim((string)($body['reason'] ?? ''));
    if (mb_strlen($reason) < 3 || mb_strlen($reason) > 300) {
        respond_error(400, 'Enter a reason of 3 to 300 characters.');
    }

    $pdo->beginTransaction();
    $documentStatement = $pdo->prepare(
        'SELECT document_id, document_number, document_type, status
         FROM finance_documents
         WHERE document_id = ? AND organization_id = ?
         LIMIT 1 FOR UPDATE'
    );
    $documentStatement->execute([(int)$documentId, $organizationId]);
    $document = $documentStatement->fetch(PDO::FETCH_ASSOC);
    if (!$document) {
        $pdo->rollBack();
        respond_error(404, 'The document was not found in your organisation.');
    }
    if ((string)$document['status'] === 'DRAFT') {
        $pdo->rollBack();
        respond_error(409, 'Draft documents are deleted, not voided.');
    }
    if ((string)$document['status'] === 'VOID') {
        $pdo->rollBack();
        respond_error(409, 'This document is already void.');
    }

    $allocationStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM finance_payment_allocations WHERE document_id = ?'
    );
    $allocationStatement->execute([(int)$documentId]);
    if ((int)$allocationStatement->fetchColumn() > 0) {
        $pdo->rollBack();
        respond_error(409, 'This document already has confirmed payments applied to it, so it cannot be voided.');
    }

    $label = (string)$document['document_type'] . ' ' . (string)$document['document_number'];
    finance_reverse_journal(
        $pdo,
        $organizationId,
        (int)$actor['user_id'],
        'DOCUMENT',
        (int)$documentId,
        finance_today(),
        'Void ' . $label
    );

    $voidStatement = $pdo->prepare(
        "UPDATE finance_documents SET status = 'VOID', voided_at = NOW()
         WHERE document_id = ? AND organization_id = ? AND status NOT IN ('DRAFT', 'VOID')"
    );
    $voidStatement->execute([(int)$documentId, $organizationId]);
    if ($voidStatement->rowCount() !== 1) {
        throw new RuntimeException('The document status changed while it was being voided.');
    }

    record_audit(
        $pdo,
        $actor,
        'FINANCE_DOCUMENT_VOIDED',
        'FINANCE_DOCUMENT',
        (int)$documentId,
        'Voided ' . $label . '. Reason: ' . $reason
    );
    $pdo->commit();

    respond(200, [
        'success' => true,
        'document_id' => (int)$documentId,
        'status' => 'VOID',
        'message' => $label . ' was voided and its journal entry was reversed.'
    ]);
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(409, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance document void', $e);
    respond_error(500, 'Unable to void the document.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance document void unexpected', $e);
    respond_error(500, 'Unable to void the document.');
}