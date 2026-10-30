<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../network/network-rules.php';

function direct_conversation_access(PDO $pdo, int $conversationId, int $organizationId): bool
{
    $statement = $pdo->prepare(
        "SELECT theirs.organization_id
         FROM conversations c
         INNER JOIN conversation_participants mine
             ON mine.conversation_id = c.conversation_id
            AND mine.organization_id = ?
         INNER JOIN conversation_participants theirs
             ON theirs.conversation_id = c.conversation_id
            AND theirs.organization_id <> mine.organization_id
         WHERE c.conversation_id = ?
           AND c.context_type = 'DIRECT'
         LIMIT 1"
    );

    $statement->execute([$organizationId, $conversationId]);
    $otherOrganizationId = $statement->fetchColumn();

    if ($otherOrganizationId === false) {
        return false;
    }

    return in_array(
        (int)$otherOrganizationId,
        connected_organization_ids($pdo, $organizationId),
        true
    );
}

function direct_conversation_messages(PDO $pdo, int $conversationId, int $afterId): array
{
    $statement = $pdo->prepare(
        "SELECT m.message_id,
                m.message_text,
                m.sent_at,
                m.sender_user_id,
                CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
                u.organization_id,
                o.organization_name
         FROM messages m
         INNER JOIN users u ON u.user_id = m.sender_user_id
         INNER JOIN organizations o ON o.organization_id = u.organization_id
         WHERE m.conversation_id = ?
           AND m.message_id > ?
         ORDER BY m.message_id ASC
         LIMIT 200"
    );
    $statement->execute([$conversationId, $afterId]);

    return $statement->fetchAll();
}

try {
    $actor = require_login($pdo);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $action = (string)($_GET['action'] ?? 'list');

        if ($action === 'list') {
            $statement = $pdo->prepare(
                "SELECT c.conversation_id,
                        c.subject,
                        c.created_at,
                        other.organization_id AS other_organization_id,
                        other.organization_name AS other_organization_name,
                        other.organization_type AS other_organization_type,
                        other.logo_path AS other_logo_path,
                        latest.message_text AS last_message,
                        latest.sent_at AS last_message_at,
                        latest.sender_user_id AS last_sender_user_id
                 FROM conversations c
                 INNER JOIN conversation_participants mine
                     ON mine.conversation_id = c.conversation_id
                    AND mine.organization_id = ?
                 INNER JOIN conversation_participants theirs
                     ON theirs.conversation_id = c.conversation_id
                    AND theirs.organization_id <> mine.organization_id
                 INNER JOIN organizations other
                     ON other.organization_id = theirs.organization_id
                 LEFT JOIN messages latest
                     ON latest.message_id = (
                         SELECT MAX(m2.message_id)
                         FROM messages m2
                         WHERE m2.conversation_id = c.conversation_id
                     )
                 WHERE c.context_type = 'DIRECT'
                 ORDER BY COALESCE(latest.sent_at, c.created_at) DESC"
            );
            $statement->execute([$actor['organization_id']]);

            $connectedIds = connected_organization_ids(
                $pdo,
                (int)$actor['organization_id']
            );

            $conversationRows = array_values(array_filter(
                $statement->fetchAll(),
                static fn(array $conversation): bool =>
                    in_array(
                        (int)$conversation['other_organization_id'],
                        $connectedIds,
                        true
                    )
            ));

            respond(200, [
                'success' => true,
                'conversations' => $conversationRows
            ]);
        }

        if ($action === 'messages') {
            $conversationId = filter_var($_GET['conversationId'] ?? null, FILTER_VALIDATE_INT);
            $afterId = filter_var($_GET['after'] ?? '0', FILTER_VALIDATE_INT);

            if (!$conversationId || $conversationId < 1 || $afterId === false || $afterId < 0) {
                respond_error(400, 'A valid conversation and message cursor are required.');
            }

            if (!direct_conversation_access($pdo, $conversationId, $actor['organization_id'])) {
                respond_error(404, 'Conversation not found.');
            }

            $messages = direct_conversation_messages($pdo, $conversationId, $afterId);

            if (($_GET['markViewed'] ?? '') === '1') {
                $markViewed = $pdo->prepare(
                    "UPDATE notifications
                     SET status = 'READ',
                         read_at = COALESCE(read_at, NOW())
                     WHERE organization_id = ?
                       AND (user_id IS NULL OR user_id = ?)
                       AND notification_type = 'GENERAL'
                       AND reference_id = ?
                       AND title = 'New direct message'
                       AND status = 'UNREAD'"
                );

                $markViewed->execute([
                    (int)$actor['organization_id'],
                    (int)$actor['user_id'],
                    $conversationId
                ]);
            }

            respond(200, [
                'success' => true,
                'messages' => $messages
            ]);
        }

        respond_error(400, 'Unknown conversation action.');
    }

    require_post();
    $data = read_json_body();
    $action = (string)($data['action'] ?? '');

    if ($action === 'open') {
        $otherOrganizationId = filter_var(
            $data['organization_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$otherOrganizationId || $otherOrganizationId < 1 ||
            $otherOrganizationId === $actor['organization_id']) {
            respond_field_error(400, field_error('organization_id', 'Select another organisation.'));
        }

        $organizationCheck = $pdo->prepare(
            "SELECT organization_id, organization_name, organization_type, logo_path
             FROM organizations
             WHERE organization_id = ? AND status = 'ACTIVE'
             LIMIT 1"
        );
        $organizationCheck->execute([$otherOrganizationId]);
        $otherOrganization = $organizationCheck->fetch();

        if (!$otherOrganization) {
            respond_error(404, 'Organisation not found or inactive.');
        }

        $connectedIds = connected_organization_ids(
            $pdo,
            (int)$actor['organization_id']
        );

        if (!in_array((int)$otherOrganizationId, $connectedIds, true)) {
            respond_error(403, 'You can only message organisations connected through active supply-chain relationships.');
        }

        $pdo->beginTransaction();

        $find = $pdo->prepare(
            "SELECT c.conversation_id
             FROM conversations c
             INNER JOIN conversation_participants a
                 ON a.conversation_id = c.conversation_id
                AND a.organization_id = ?
             INNER JOIN conversation_participants b
                 ON b.conversation_id = c.conversation_id
                AND b.organization_id = ?
             WHERE c.context_type = 'DIRECT'
               AND (
                   SELECT COUNT(*)
                   FROM conversation_participants allp
                   WHERE allp.conversation_id = c.conversation_id
               ) = 2
             ORDER BY c.conversation_id ASC
             LIMIT 1
             FOR UPDATE"
        );
        $find->execute([$actor['organization_id'], $otherOrganizationId]);
        $conversationId = $find->fetchColumn();

        if ($conversationId === false) {
            $subject = 'Conversation with ' . $otherOrganization['organization_name'];
            $create = $pdo->prepare(
                "INSERT INTO conversations (subject, created_by, context_type, context_id)
                 VALUES (?, ?, 'DIRECT', NULL)"
            );
            $create->execute([$subject, $actor['user_id']]);
            $conversationId = (int)$pdo->lastInsertId();

            $participants = $pdo->prepare(
                "INSERT INTO conversation_participants (conversation_id, organization_id)
                 VALUES (?, ?), (?, ?)"
            );
            $participants->execute([
                $conversationId,
                $actor['organization_id'],
                $conversationId,
                $otherOrganizationId
            ]);
        } else {
            $conversationId = (int)$conversationId;
        }

        $pdo->commit();

        respond(200, [
            'success' => true,
            'conversation_id' => $conversationId,
            'organization' => $otherOrganization
        ]);
    }

    if ($action === 'send') {
        $conversationId = filter_var(
            $data['conversation_id'] ?? null,
            FILTER_VALIDATE_INT
        );
        $messageText = trim((string)($data['text'] ?? ''));

        if (!$conversationId || $conversationId < 1) {
            respond_field_error(400, field_error('conversation_id', 'Select a conversation.'));
        }

        if ($messageText === '') {
            respond_field_error(400, field_error('text', 'Write a message first.'));
        }

        if (mb_strlen($messageText) > 2000) {
            respond_field_error(400, field_error('text', 'Messages are limited to 2000 characters.'));
        }

        if (!direct_conversation_access($pdo, $conversationId, $actor['organization_id'])) {
            respond_error(404, 'Conversation not found.');
        }

        $insert = $pdo->prepare(
            "INSERT INTO messages (conversation_id, sender_user_id, message_text)
             VALUES (?, ?, ?)"
        );
        $insert->execute([$conversationId, $actor['user_id'], $messageText]);

        $recipientStatement = $pdo->prepare(
            "SELECT cp.organization_id
             FROM conversation_participants cp
             WHERE cp.conversation_id = ?
               AND cp.organization_id <> ?
             LIMIT 1"
        );
        $recipientStatement->execute([
            $conversationId,
            (int)$actor['organization_id']
        ]);
        $recipientOrganizationId = (int)$recipientStatement->fetchColumn();

        if ($recipientOrganizationId > 0) {
            $senderStatement = $pdo->prepare(
                "SELECT organization_name
                 FROM organizations
                 WHERE organization_id = ?
                 LIMIT 1"
            );
            $senderStatement->execute([(int)$actor['organization_id']]);
            $senderOrganizationName = (string)$senderStatement->fetchColumn();

            create_organization_notification(
                $pdo,
                $recipientOrganizationId,
                'GENERAL',
                $conversationId,
                'New direct message',
                'A new message has arrived from ' .
                    ($senderOrganizationName !== '' ? $senderOrganizationName : 'another organisation') .
                    '.'
            );
        }

        respond(201, [
            'success' => true,
            'message' => 'Sent.',
            'message_id' => (int)$pdo->lastInsertId()
        ]);
    }

    respond_error(400, 'Unknown conversation action.');
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('direct conversations', $e);
    respond_error(500, 'Unable to process the conversation request.');
}