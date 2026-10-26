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
    $paymentId = filter_var($body['payment_id'] ?? null, FILTER_VALIDATE_INT);
    if ($paymentId === false || $paymentId === null || $paymentId < 1) {
        respond_error(400, 'Select a valid confirmed payment.');
    }
    $items = $body['allocations'] ?? null;
    if (!is_array($items) || count($items) < 1 || count($items) > 100) {
        respond_error(400, 'Add between 1 and 100 document allocations.');
    }

    $allocations = [];
    $seen = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            respond_error(400, 'Each allocation must be an object.');
        }
        $documentId = filter_var($item['document_id'] ?? null, FILTER_VALIDATE_INT);
        if ($documentId === false || $documentId === null || $documentId < 1) {
            respond_error(400, 'Each allocation requires a valid document ID.');
        }
        $documentId = (int)$documentId;
        if (isset($seen[$documentId])) {
            respond_error(400, 'A document can appear only once in one allocation request.');
        }
        $seen[$documentId] = true;
        $amount = finance_money($item['allocated_amount'] ?? null, 'Allocated amount', false);
        $allocations[] = ['document_id' => $documentId, 'allocated_amount' => $amount];
    }
    usort($allocations, static fn(array $a, array $b): int => $a['document_id'] <=> $b['document_id']);

    $pdo->beginTransaction();
    $paymentStatement = $pdo->prepare('SELECT * FROM finance_payments WHERE payment_id = ? LIMIT 1 FOR UPDATE');
    $paymentStatement->execute([(int)$paymentId]);
    $payment = $paymentStatement->fetch(PDO::FETCH_ASSOC);
    if (!$payment) {
        $pdo->rollBack();
        respond_error(404, 'The payment was not found.');
    }
    if ((string)$payment['status'] !== 'CONFIRMED') {
        $pdo->rollBack();
        respond_error(409, 'Only confirmed payments can be allocated.');
    }
    $isPayer = (int)$payment['payer_organization_id'] === $organizationId;
    $isPayee = (int)$payment['payee_organization_id'] === $organizationId;
    if (!$isPayer && !$isPayee) {
        $pdo->rollBack();
        respond_error(403, 'This payment is not part of your organisation accounts.');
    }

    $alreadyAllocatedForSideStatement = $pdo->prepare(
        'SELECT COALESCE(SUM(a.allocated_amount), 0)
         FROM finance_payment_allocations a
         INNER JOIN finance_documents d ON d.document_id = a.document_id
         WHERE a.payment_id = ? AND d.organization_id = ?'
    );
    $alreadyAllocatedForSideStatement->execute([(int)$paymentId, $organizationId]);
    $alreadyAllocatedForSide = (float)$alreadyAllocatedForSideStatement->fetchColumn();
    $newAllocationTotal = 0.0;
    $prepared = [];

    foreach ($allocations as $allocation) {
        $documentStatement = $pdo->prepare(
            'SELECT document_id, organization_id, counterparty_organization_id,
                    document_type, direction, total_amount, due_date, status
             FROM finance_documents WHERE document_id = ? LIMIT 1 FOR UPDATE'
        );
        $documentStatement->execute([$allocation['document_id']]);
        $document = $documentStatement->fetch(PDO::FETCH_ASSOC);
        if (!$document) {
            throw new DomainException('An allocated document was not found.');
        }

        $validDocument = $isPayer
            ? ((int)$document['organization_id'] === (int)$payment['payer_organization_id']
                && (int)$document['counterparty_organization_id'] === (int)$payment['payee_organization_id']
                && (string)$document['document_type'] === 'BILL'
                && (string)$document['direction'] === 'PAYABLE')
            : ((int)$document['organization_id'] === (int)$payment['payee_organization_id']
                && (int)$document['counterparty_organization_id'] === (int)$payment['payer_organization_id']
                && (string)$document['document_type'] === 'INVOICE'
                && (string)$document['direction'] === 'RECEIVABLE');
        if (!$validDocument) {
            throw new DomainException('You can only allocate this payment to your own BILL for a payer, or your own INVOICE for a payee, for this exact counterparty.');
        }
        if (!in_array((string)$document['status'], ['ISSUED', 'PARTIALLY_PAID', 'OVERDUE'], true)) {
            throw new DomainException('Only issued, partially paid or overdue documents can receive allocations.');
        }

        $paidStatement = $pdo->prepare(
            "SELECT COALESCE(SUM(a.allocated_amount), 0)
             FROM finance_payment_allocations a
             INNER JOIN finance_payments p ON p.payment_id = a.payment_id
             WHERE a.document_id = ? AND p.status = 'CONFIRMED'"
        );
        $paidStatement->execute([$allocation['document_id']]);
        $alreadyPaid = (float)$paidStatement->fetchColumn();
        $remaining = round((float)$document['total_amount'] - $alreadyPaid, 2);
        if ($allocation['allocated_amount'] > $remaining + 0.005) {
            throw new DomainException('An allocation exceeds the remaining balance on a document. Refresh and try again.');
        }

        $newAllocationTotal = round($newAllocationTotal + $allocation['allocated_amount'], 2);
        $prepared[] = $allocation;
    }

    if ($alreadyAllocatedForSide + $newAllocationTotal > (float)$payment['amount'] + 0.005) {
        throw new DomainException('The total allocated to your organisation documents cannot exceed the payment amount.');
    }

    $insertOrIncrease = $pdo->prepare(
        'INSERT INTO finance_payment_allocations
            (payment_id, document_id, allocated_amount, created_by_user_id)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE allocated_amount = allocated_amount + VALUES(allocated_amount)'
    );
    foreach ($prepared as $allocation) {
        $insertOrIncrease->execute([
            (int)$paymentId,
            $allocation['document_id'],
            $allocation['allocated_amount'],
            (int)$actor['user_id']
        ]);
    }

    $adjustmentTotal = $newAllocationTotal;
    $suffix = bin2hex(random_bytes(3));
    $entryNumber = 'ALLOC-' . (int)$paymentId . '-' . (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('YmdHis') . '-' . $suffix;
    $description = 'Payment #' . (int)$paymentId . ' allocation adjustment';
    if ($isPayer) {
        $journalLines = [
            ['code' => '2000', 'debit' => $adjustmentTotal, 'credit' => 0, 'description' => 'Reduce accounts payable - payment #' . (int)$paymentId],
            ['code' => '1400', 'debit' => 0, 'credit' => $adjustmentTotal, 'description' => 'Use supplier advance - payment #' . (int)$paymentId]
        ];
    } else {
        $journalLines = [
            ['code' => '2200', 'debit' => $adjustmentTotal, 'credit' => 0, 'description' => 'Use customer advance - payment #' . (int)$paymentId],
            ['code' => '1100', 'debit' => 0, 'credit' => $adjustmentTotal, 'description' => 'Reduce accounts receivable - payment #' . (int)$paymentId]
        ];
    }

    finance_post_journal(
        $pdo,
        $organizationId,
        (int)$actor['user_id'],
        $entryNumber,
        'ADJUSTMENT',
        null,
        finance_today(),
        $description,
        $journalLines
    );

    foreach ($prepared as $allocation) {
        finance_update_document_status($pdo, (int)$allocation['document_id']);
    }
    record_audit(
        $pdo,
        $actor,
        'FINANCE_PAYMENT_ALLOCATED',
        'FINANCE_PAYMENT',
        (int)$paymentId,
        'Allocated an additional KES ' . number_format($adjustmentTotal, 2, '.', '') . ' of payment #' . (int)$paymentId . ' to this organisation\'s own documents.'
    );
    $pdo->commit();

    respond(200, [
        'success' => true,
        'payment_id' => (int)$paymentId,
        'allocated_amount' => $adjustmentTotal,
        'message' => 'Payment allocation saved and the corresponding balanced ledger adjustment was posted.'
    ]);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(400, $e->getMessage());
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(409, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance payment allocation', $e);
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'The allocation conflicts with an existing financial record. Refresh and retry.');
    }
    respond_error(500, 'Unable to allocate the payment.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance payment allocation unexpected', $e);
    respond_error(500, 'Unable to allocate the payment.');
}
