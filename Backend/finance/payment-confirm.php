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
    $payeeOrganizationId = (int)$actor['organization_id'];
    $body = read_json_body();
    $paymentId = filter_var($body['payment_id'] ?? null, FILTER_VALIDATE_INT);
    if ($paymentId === false || $paymentId === null || $paymentId < 1) {
        respond_error(400, 'Select a valid pending payment.');
    }

    $allocations = $body['allocations'] ?? [];
    if (!is_array($allocations) || count($allocations) > 100) {
        respond_error(400, 'Allocations must be a list of at most 100 document allocations.');
    }

    $normalisedAllocations = [];
    $seenDocumentIds = [];
    foreach ($allocations as $allocation) {
        if (!is_array($allocation)) {
            respond_error(400, 'Each allocation must be an object.');
        }
        $documentId = filter_var($allocation['document_id'] ?? null, FILTER_VALIDATE_INT);
        if ($documentId === false || $documentId === null || $documentId < 1) {
            respond_error(400, 'Every allocation needs a valid document ID.');
        }
        $documentId = (int)$documentId;
        if (isset($seenDocumentIds[$documentId])) {
            respond_error(400, 'A document can appear only once in one payment allocation list.');
        }
        $seenDocumentIds[$documentId] = true;
        $allocationAmount = finance_money($allocation['allocated_amount'] ?? null, 'Allocated amount', false);
        $normalisedAllocations[] = ['document_id' => $documentId, 'allocated_amount' => $allocationAmount];
    }
    usort($normalisedAllocations, static fn(array $a, array $b): int => $a['document_id'] <=> $b['document_id']);

    $pdo->beginTransaction();
    $paymentStatement = $pdo->prepare('SELECT * FROM finance_payments WHERE payment_id = ? LIMIT 1 FOR UPDATE');
    $paymentStatement->execute([(int)$paymentId]);
    $payment = $paymentStatement->fetch(PDO::FETCH_ASSOC);
    if (!$payment) {
        $pdo->rollBack();
        respond_error(404, 'The payment was not found.');
    }
    if ((int)$payment['payee_organization_id'] !== $payeeOrganizationId) {
        $pdo->rollBack();
        respond_error(403, 'Only the receiving organisation can confirm receipt of this payment.');
    }
    if ((string)$payment['status'] !== 'PENDING') {
        $pdo->rollBack();
        respond_error(409, 'Only pending payments can be confirmed.');
    }
    if ((int)$payment['recorded_by_organization_id'] === $payeeOrganizationId) {
        $pdo->rollBack();
        respond_error(409, 'The organisation that recorded a payment cannot confirm its own receipt.');
    }

    $payerOrganizationId = (int)$payment['payer_organization_id'];
    $paymentAmount = round((float)$payment['amount'], 2);
    $payeeAllocated = 0.0;
    $allocationDocuments = [];

    foreach ($normalisedAllocations as $allocation) {
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

        $isPayeeInvoice = (int)$document['organization_id'] === $payeeOrganizationId
            && (int)$document['counterparty_organization_id'] === $payerOrganizationId
            && (string)$document['document_type'] === 'INVOICE'
            && (string)$document['direction'] === 'RECEIVABLE';
        if (!$isPayeeInvoice) {
            throw new DomainException('At payment confirmation, allocations must be invoices owned by your receiving organisation for this payer. The payer can allocate its own bills separately.');
        }
        if (!in_array((string)$document['status'], ['ISSUED', 'PARTIALLY_PAID', 'OVERDUE'], true)) {
            throw new DomainException('Only issued, partially paid or overdue invoices and bills can receive an allocation.');
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
            throw new DomainException('An allocation exceeds the outstanding balance on a document. Refresh the document list and try again.');
        }

        $payeeAllocated = round($payeeAllocated + $allocation['allocated_amount'], 2);
        $allocationDocuments[] = $allocation;
    }

    if ($payeeAllocated > $paymentAmount + 0.005) {
        throw new DomainException('Invoice allocations cannot exceed the payment amount.');
    }

    $confirmPayment = $pdo->prepare(
        "UPDATE finance_payments
         SET status = 'CONFIRMED', confirmed_by_organization_id = ?,
             confirmed_by_user_id = ?, confirmed_at = NOW()
         WHERE payment_id = ? AND status = 'PENDING'"
    );
    $confirmPayment->execute([$payeeOrganizationId, (int)$actor['user_id'], (int)$paymentId]);
    if ($confirmPayment->rowCount() !== 1) {
        throw new DomainException('The payment status changed before confirmation. Refresh and try again.');
    }

    $insertAllocation = $pdo->prepare(
        'INSERT INTO finance_payment_allocations (payment_id, document_id, allocated_amount, created_by_user_id)
         VALUES (?, ?, ?, ?)'
    );
    foreach ($allocationDocuments as $allocation) {
        $insertAllocation->execute([
            (int)$paymentId,
            $allocation['document_id'],
            $allocation['allocated_amount'],
            (int)$actor['user_id']
        ]);
    }

    $method = (string)$payment['payment_method'];
    $assetCode = finance_payment_asset_code($method);
    $payerAdvance = $paymentAmount;
    $payeeAdvance = round($paymentAmount - $payeeAllocated, 2);

    $payerLines = [
        ['code' => $assetCode, 'debit' => 0, 'credit' => $paymentAmount, 'description' => 'Payment sent - #' . (int)$paymentId]
    ];
    if ($payerAdvance > 0) {
        $payerLines[] = ['code' => '1400', 'debit' => $payerAdvance, 'credit' => 0, 'description' => 'Supplier advance - payment #' . (int)$paymentId];
    }

    $payeeLines = [
        ['code' => $assetCode, 'debit' => $paymentAmount, 'credit' => 0, 'description' => 'Payment received - #' . (int)$paymentId]
    ];
    if ($payeeAllocated > 0) {
        $payeeLines[] = ['code' => '1100', 'debit' => 0, 'credit' => $payeeAllocated, 'description' => 'Settle customer invoices - payment #' . (int)$paymentId];
    }
    if ($payeeAdvance > 0) {
        $payeeLines[] = ['code' => '2200', 'debit' => 0, 'credit' => $payeeAdvance, 'description' => 'Customer advance - payment #' . (int)$paymentId];
    }

    $description = 'Confirmed payment #' . (int)$paymentId . ' between organisations ' . $payerOrganizationId . ' and ' . $payeeOrganizationId;
    finance_post_journal(
        $pdo,
        $payerOrganizationId,
        (int)$payment['recorded_by_user_id'],
        'PAY-' . (int)$paymentId . '-OUT',
        'PAYMENT',
        (int)$paymentId,
        (string)$payment['payment_date'],
        $description . ' (outgoing)',
        $payerLines
    );
    finance_post_journal(
        $pdo,
        $payeeOrganizationId,
        (int)$actor['user_id'],
        'PAY-' . (int)$paymentId . '-IN',
        'PAYMENT',
        (int)$paymentId,
        (string)$payment['payment_date'],
        $description . ' (incoming)',
        $payeeLines
    );

    foreach ($allocationDocuments as $allocation) {
        finance_update_document_status($pdo, (int)$allocation['document_id']);
    }

    record_audit(
        $pdo,
        $actor,
        'FINANCE_PAYMENT_CONFIRMED',
        'FINANCE_PAYMENT',
        (int)$paymentId,
        'Confirmed receipt of payment #' . (int)$paymentId . ' for KES ' . number_format($paymentAmount, 2, '.', '') . '. Posted balanced journal entries for both organisations.'
    );
    $pdo->commit();

    respond(200, [
        'success' => true,
        'payment_id' => (int)$paymentId,
        'status' => 'CONFIRMED',
        'amount' => $paymentAmount,
        'message' => 'Payment confirmed. Balanced journal entries were posted for both organisations; any unmatched amount was recorded as an advance.'
    ]);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(400, $e->getMessage());
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(409, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance payment confirm', $e);
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'This payment may already have been confirmed or a journal entry already exists. Refresh before trying again.');
    }
    respond_error(500, 'Unable to confirm the payment.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance payment confirm unexpected', $e);
    respond_error(500, 'Unable to confirm the payment.');
}
