<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/change-rules.php';

require_post();

try {
    $actor = require_login($pdo);

    if (!can_manufacture($actor)) {
        respond_error(403, 'Only manufacturers can create product changes.');
    }

    $input = clean_change_input(read_json_body());
    $product = find_own_product($pdo, $actor, $input['productId']);
    $error = first_change_error($pdo, $input, $product);

    if ($error !== null) {
        respond_field_error(400, $error);
    }

    $pdo->beginTransaction();

    $changeId = insert_change($pdo, $actor, $product, $input);

    record_audit(
        $pdo,
        $actor,
        'CHANGE_CREATED',
        'PRODUCT_CHANGE',
        $changeId,
        "Prepared a change for {$product['product_name']}: barcode {$product['barcode']} to {$input['barcode']}"
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Change submitted for approval.',
        'change_id' => $changeId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('changes create', $e);

    respond_error(500, 'Unable to create the change.');
}