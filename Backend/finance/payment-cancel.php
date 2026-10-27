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
    $paymentId = filter_var($body['payment_id'] ?? null, FILTER_VALIDATE_INT);
    if ($paymentId === false || $paymentId === null || $paymentId < 1) {
        respond_error(400, 'Select a valid pending payment.');
    }
    $reason = trim((string)($body['reason'] ?? ''));
    if (mb_strlen($reason) < 3 || mb_strlen($reason) > 300) {
        respond_error(400, 'Enter a reason of 3 to 300 characters.');
    }

    $pdo->beginTransaction();
    $paymentStatement = $pdo->prepare(
        'SELECT payment_id, payer_organization_id, payee_organization_id, amount, status
         FROM finance_payments WHERE payment_id = ? LIMIT 1 FOR UPDATE'
    );
    $paymentStatement->execute([(int)$paymentId]);
    $payment = $paymentStatement->fetch(PDO::FETCH_ASSOC);
    if (!$payment) {
        $pdo->rollBack();
        respond_error(404, 'The payment was not found.');
    }

    $isPayer = (int)$payment['payer_organization_id'] === $organizationId;
    $isPayee = (int)$payment['payee_organization_id'] === $organizationId;
    if (!$isPayer && !$isPayee) {
        $pdo->rollBack();
        respond_error(403, 'This payment is not part of your organisation accounts.');
    }
    if ((string)$payment['status'] !== 'PENDING') {
        $pdo->rollBack();
        respond_error(409, 'Only pending payments can be cancelled or rejected.');
    }

    $newStatus = $isPayer ? 'CANCELLED' : 'FAILED';
    $note = ($isPayer ? 'Cancelled by the payer: ' : 'Rejected by the receiver: ') . $reason;
    $updateStatement = $pdo->prepare(
        "UPDATE finance_payments
         SET status = ?, notes = CONCAT_WS(CHAR(10), notes, ?)
         WHERE payment_id = ? AND status = 'PENDING'"
    );
    $updateStatement->execute([$newStatus, $note, (int)$paymentId]);
    if ($updateStatement->rowCount() !== 1) {
        throw new DomainException('The payment status changed before it could be updated. Refresh and try again.');
    }

    record_audit(
        $pdo,
        $actor,
        $isPayer ? 'FINANCE_PAYMENT_CANCELLED' : 'FINANCE_PAYMENT_REJECTED',
        'FINANCE_PAYMENT',
        (int)$paymentId,
        $note . ' Payment #' . (int)$paymentId . ' for KES ' . number_format((float)$payment['amount'], 2, '.', '') . '.'
    );
    $pdo->commit();

    respond(200, [
        'success' => true,
        'payment_id' => (int)$paymentId,
        'status' => $newStatus,
        'message' => $isPayer ? 'The pending payment was cancelled.' : 'The pending payment was rejected.'
    ]);
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(409, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance payment cancel', $e);
    respond_error(500, 'Unable to update the payment.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance payment cancel unexpected', $e);
    respond_error(500, 'Unable to update the payment.');
}