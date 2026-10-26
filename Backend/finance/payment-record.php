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
    $payerId = (int)$actor['organization_id'];
    $body = read_json_body();
    $payeeId = filter_var($body['payee_organization_id'] ?? null, FILTER_VALIDATE_INT);
    if ($payeeId === false || $payeeId === null || $payeeId < 1 || (int)$payeeId === $payerId) {
        respond_error(400, 'Choose a valid payee organisation.');
    }
    $payeeId = (int)$payeeId;
    $amount = finance_money($body['amount'] ?? null, 'Payment amount', false);
    $method = strtoupper(trim((string)($body['payment_method'] ?? '')));
    if (!in_array($method, ['CASH', 'BANK_TRANSFER', 'MPESA', 'CHEQUE', 'OTHER'], true)) {
        respond_error(400, 'Choose CASH, BANK_TRANSFER, MPESA, CHEQUE or OTHER.');
    }
    $reference = trim((string)($body['reference_number'] ?? ''));
    if (mb_strlen($reference) > 120) {
        respond_error(400, 'The payment reference cannot exceed 120 characters.');
    }
    $paymentDate = trim((string)($body['payment_date'] ?? finance_today()));
    if (!valid_date($paymentDate)) {
        respond_error(400, 'Enter a valid payment date.');
    }
    $notes = trim((string)($body['notes'] ?? ''));
    if (mb_strlen($notes) > 10000) {
        respond_error(400, 'Payment notes are too long.');
    }

    $payeeStatement = $pdo->prepare(
        "SELECT organization_id FROM organizations WHERE organization_id = ? AND status = 'ACTIVE' LIMIT 1"
    );
    $payeeStatement->execute([$payeeId]);
    if ($payeeStatement->fetchColumn() === false) {
        respond_error(404, 'The selected payee organisation is not active or was not found.');
    }
    if (!finance_relationship_is_active($pdo, $payerId, $payeeId, $paymentDate)) {
        respond_error(409, 'Payments can only be recorded between organisations with an active supply relationship on the payment date.');
    }

    $branchId = null;
    if (isset($body['payer_branch_id']) && $body['payer_branch_id'] !== '' && $body['payer_branch_id'] !== null) {
        $branchId = filter_var($body['payer_branch_id'], FILTER_VALIDATE_INT);
        if ($branchId === false || $branchId < 1) {
            respond_error(400, 'Choose a valid payer branch.');
        }
        $branchStatement = $pdo->prepare(
            "SELECT branch_id FROM branches WHERE branch_id = ? AND organization_id = ? AND status = 'ACTIVE' LIMIT 1"
        );
        $branchStatement->execute([(int)$branchId, $payerId]);
        if ($branchStatement->fetchColumn() === false) {
            respond_error(403, 'The selected branch does not belong to your organisation or is inactive.');
        }
        $branchId = (int)$branchId;
    }

    $pdo->beginTransaction();
    if ($reference !== '') {
        $duplicate = $pdo->prepare(
            "SELECT payment_id FROM finance_payments
             WHERE payer_organization_id = ? AND payee_organization_id = ?
               AND payment_method = ? AND reference_number = ?
               AND status NOT IN ('FAILED', 'CANCELLED')
             LIMIT 1 FOR UPDATE"
        );
        $duplicate->execute([$payerId, $payeeId, $method, $reference]);
        if ($duplicate->fetchColumn() !== false) {
            $pdo->rollBack();
            respond_error(409, 'A payment with this reference already exists between these organisations. Check the existing payment before retrying.');
        }
    }

    $insert = $pdo->prepare(
        "INSERT INTO finance_payments
            (payer_organization_id, payee_organization_id, payer_branch_id,
             amount, currency_code, payment_method, reference_number, payment_date,
             status, recorded_by_organization_id, recorded_by_user_id, notes)
         VALUES (?, ?, ?, ?, 'KES', ?, ?, ?, 'PENDING', ?, ?, ?)"
    );
    $insert->execute([
        $payerId, $payeeId, $branchId, $amount, $method,
        $reference === '' ? null : $reference,
        $paymentDate, $payerId, (int)$actor['user_id'],
        $notes === '' ? null : $notes
    ]);
    $paymentId = (int)$pdo->lastInsertId();

    record_audit(
        $pdo,
        $actor,
        'FINANCE_PAYMENT_RECORDED',
        'FINANCE_PAYMENT',
        $paymentId,
        "Recorded outgoing payment #{$paymentId} for KES " . number_format($amount, 2, '.', '') . " to organisation {$payeeId}; awaiting payee confirmation."
    );
    $pdo->commit();

    respond(201, [
        'success' => true,
        'payment_id' => $paymentId,
        'status' => 'PENDING',
        'amount' => $amount,
        'currency_code' => 'KES',
        'message' => 'Payment recorded as pending. It will not affect the posted ledger until the payee confirms receipt.'
    ]);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(400, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance payment record', $e);
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'The payment conflicts with an existing record. Check the payment reference and try again.');
    }
    respond_error(500, 'Unable to record the payment.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance payment record unexpected', $e);
    respond_error(500, 'Unable to record the payment.');
}
