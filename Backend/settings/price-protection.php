<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../core/audit.php';

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    if ($method === 'GET') {
        $actor = require_login($pdo);

        $statement = $pdo->prepare(
            'SELECT enabled
             FROM organization_price_protection
             WHERE organization_id = ?
             LIMIT 1'
        );
        $statement->execute([(int)$actor['organization_id']]);
        $value = $statement->fetchColumn();

        respond(200, [
            'success' => true,
            'enabled' => $value !== false && (int)$value === 1,
            'can_manage' => $actor['role'] === 'ADMIN'
        ]);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');
        respond_error(405, 'Use GET to read or POST to change this setting.');
    }

    require_post();
    $actor = require_admin($pdo);
    $input = read_json_body();

    if (!array_key_exists('enabled', $input) || !is_bool($input['enabled'])) {
        respond_field_error(
            400,
            field_error('enabled', 'Choose whether Price Change Protection is enabled.')
        );
    }

    $enabled = $input['enabled'] ? 1 : 0;

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        "INSERT INTO organization_price_protection
            (organization_id, enabled, updated_by)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            enabled = VALUES(enabled),
            updated_by = VALUES(updated_by),
            updated_at = CURRENT_TIMESTAMP"
    );
    $statement->execute([
        (int)$actor['organization_id'],
        $enabled,
        (int)$actor['user_id']
    ]);

    record_audit(
        $pdo,
        $actor,
        'PRICE_PROTECTION_SETTING_CHANGED',
        'ORGANIZATION',
        (int)$actor['organization_id'],
        'Price Change Protection ' . ($enabled ? 'enabled' : 'disabled') . '.'
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'enabled' => (bool)$enabled,
        'message' => $enabled
            ? 'Price Change Protection is enabled for your organisation.'
            : 'Price Change Protection is disabled for your organisation.'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('price protection setting', $e);
    respond_error(500, 'Unable to load or save Price Change Protection.');
}