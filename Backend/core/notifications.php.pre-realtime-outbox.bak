<?php

declare(strict_types=1);

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
}