<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/message-rules.php';

require_post();

try {
    $actor = require_login($pdo);
    $data = read_json_body();
    $changeId = (int)($data['changeId'] ?? 0);
    $text = trim((string)($data['text'] ?? ''));

    if (!can_discuss_change($pdo, $actor, $changeId)) {
        respond_error(404, 'Change not found.');
    }

    if ($text === '') {
        respond_field_error(400, field_error('text', 'Write a message first.'));
    }

    if (mb_strlen($text) > 2000) {
        respond_field_error(400, field_error('text', 'Messages are limited to 2000 characters.'));
    }

    $pdo->beginTransaction();

    $conversationId = find_conversation_id($pdo, $changeId) ?? create_conversation($pdo, $actor, $changeId);
    $messageId = insert_message($pdo, $actor, $conversationId, $text);

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Sent.',
        'message_id' => $messageId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('messages send', $e);

    respond_error(500, 'Unable to send the message.');
}