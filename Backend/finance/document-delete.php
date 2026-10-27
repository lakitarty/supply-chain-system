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
        respond_error(400, 'Select a valid draft document to delete.');
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
    if ((string)$document['status'] !== 'DRAFT') {
        $pdo->rollBack();
        respond_error(409, 'Only draft documents can be deleted. Void an issued document instead.');
    }

    $pdo->prepare('DELETE FROM finance_document_lines WHERE document_id = ?')
        ->execute([(int)$documentId]);
    $pdo->prepare('DELETE FROM finance_documents WHERE document_id = ? AND organization_id = ?')
        ->execute([(int)$documentId, $organizationId]);

    record_audit(
        $pdo,
        $actor,
        'FINANCE_DOCUMENT_DRAFT_DELETED',
        'FINANCE_DOCUMENT',
        (int)$documentId,
        'Deleted ' . $document['document_type'] . ' draft ' . $document['document_number'] . '.'
    );
    $pdo->commit();

    respond(200, [
        'success' => true,
        'document_id' => (int)$documentId,
        'message' => 'Draft ' . $document['document_number'] . ' was deleted.'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance document delete', $e);
    respond_error(500, 'Unable to delete the draft document.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance document delete unexpected', $e);
    respond_error(500, 'Unable to delete the draft document.');
}