<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/validation.php';

function can_discuss_change(PDO $pdo, array $actor, int $changeId): bool
{
    return record_exists(
        $pdo,
        "SELECT 1
         FROM product_changes c
         INNER JOIN products p ON p.product_id = c.product_id
         WHERE c.product_change_id = ?
           AND c.status = 'PUBLISHED'
           AND (p.manufacturer_id = ?
                OR EXISTS (
                    SELECT 1 FROM change_recipients r
                    WHERE r.product_change_id = c.product_change_id AND r.organization_id = ?
                ))
         LIMIT 1",
        [$changeId, $actor['organization_id'], $actor['organization_id']]
    );
}

function find_conversation_id(PDO $pdo, int $changeId): ?int
{
    $statement = $pdo->prepare(
        "SELECT conversation_id FROM conversations WHERE context_type = 'PRODUCT_CHANGE' AND context_id = ? LIMIT 1"
    );

    $statement->execute([$changeId]);
    $id = $statement->fetchColumn();

    return $id === false ? null : (int)$id;
}

function create_conversation(PDO $pdo, array $actor, int $changeId): int
{
    $subject = $pdo->prepare(
        "SELECT CONCAT('Change: ', p.product_name)
         FROM product_changes c
         INNER JOIN products p ON p.product_id = c.product_id
         WHERE c.product_change_id = ?"
    );

    $subject->execute([$changeId]);

    $insert = $pdo->prepare(
        "INSERT INTO conversations (subject, created_by, context_type, context_id) VALUES (?, ?, 'PRODUCT_CHANGE', ?)"
    );

    $insert->execute([$subject->fetchColumn(), $actor['user_id'], $changeId]);

    return (int)$pdo->lastInsertId();
}

function fetch_messages(PDO $pdo, ?int $conversationId, int $afterId): array
{
    if ($conversationId === null) {
        return [];
    }

    $statement = $pdo->prepare(
        "SELECT m.message_id,
                m.message_text,
                m.sent_at,
                CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
                o.organization_name
         FROM messages m
         INNER JOIN users u ON u.user_id = m.sender_user_id
         INNER JOIN organizations o ON o.organization_id = u.organization_id
         WHERE m.conversation_id = ? AND m.message_id > ?
         ORDER BY m.message_id"
    );

    $statement->execute([$conversationId, $afterId]);

    return $statement->fetchAll();
}

function insert_message(PDO $pdo, array $actor, int $conversationId, string $text): int
{
    $statement = $pdo->prepare(
        'INSERT INTO messages (conversation_id, sender_user_id, message_text) VALUES (?, ?, ?)'
    );

    $statement->execute([$conversationId, $actor['user_id'], $text]);

    return (int)$pdo->lastInsertId();
}