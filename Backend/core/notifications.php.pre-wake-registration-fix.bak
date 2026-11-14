<?php
declare(strict_types=1);

function schedule_realtime_dispatch_wake(PDO $pdo): void
{
    static $registered = false;

    if ($registered) {
        return;
    }

    $registered = true;

    register_shutdown_function(static function () use ($pdo): void {
        if ($pdo->inTransaction()) {
            return;
        }

        $secretPath = 'C:\\Users\\hp\\ChainBridge-Realtime\\php-wake-secret.txt';
        $secret = @file_get_contents($secretPath);

        if ($secret === false) {
            error_log('ChainBridge real-time wake secret could not be read.');
            return;
        }

        $secret = trim($secret);

        if (strlen($secret) < 32) {
            error_log('ChainBridge real-time wake secret is invalid.');
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "x-chainbridge-internal-secret: {$secret}\r\n",
                'content' => '',
                'timeout' => 1,
                'ignore_errors' => true
            ]
        ]);

        @file_get_contents(
            'http://localhost:3001/internal/wake',
            false,
            $context
        );
    });
}

function create_organization_notification(
    PDO $pdo,
    int $organizationId,
    string $notificationType,
    ?int $referenceId,
    string $title,
    string $message
): void {
    if ($organizationId < 1) {
        throw new InvalidArgumentException('A valid notification recipient organization is required.');
    }

    $ownsTransaction = !$pdo->inTransaction();

    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $statement = $pdo->prepare(
            "INSERT INTO notifications
                (organization_id, user_id, notification_type, reference_id, title, message)
             VALUES (?, NULL, ?, ?, ?, ?)"
        );

        $statement->execute([
            $organizationId,
            $notificationType,
            $referenceId,
            mb_substr($title, 0, 200),
            $message
        ]);

        $notificationId = (int)$pdo->lastInsertId();

        $payload = json_encode(
            ['notification_id' => $notificationId],
            JSON_THROW_ON_ERROR
        );

        $outboxStatement = $pdo->prepare(
            "INSERT INTO realtime_outbox
                (event_type, recipient_organization_id, payload)
             VALUES ('notification.created', ?, ?)"
        );

        $outboxStatement->execute([
            $organizationId,
            $payload
        ]);

        if ($ownsTransaction) {
            $pdo->commit();
        }

        schedule_realtime_dispatch_wake($pdo);
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}