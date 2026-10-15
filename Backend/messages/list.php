<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/message-rules.php';

try {
    $actor = require_login($pdo);

    $changeId = filter_var(
        $_GET['changeId'] ?? null,
        FILTER_VALIDATE_INT
    );

    $afterId = filter_var(
        $_GET['after'] ?? '0',
        FILTER_VALIDATE_INT
    );

    if ($changeId === false || $changeId === null || $changeId < 1) {
        respond_error(400, 'A valid change ID is required.');
    }

    if ($afterId === false || $afterId === null || $afterId < 0) {
        respond_error(400, 'A valid message cursor is required.');
    }

    if (!can_discuss_change($pdo, $actor, $changeId)) {
        respond_error(404, 'Change not found.');
    }

    $conversationId = find_conversation_id($pdo, $changeId);

    respond(200, [
        'success' => true,
        'messages' => fetch_messages($pdo, $conversationId, $afterId)
    ]);

} catch (PDOException $e) {
    log_error('messages list', $e);

    respond_error(500, 'Unable to load the messages.');
}