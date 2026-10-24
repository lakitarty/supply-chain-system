<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';

require_post();

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Supplier organisation administrator access is required.');
    }

    $body = read_json_body();
    $offerId = filter_var($body['offerId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $effectiveTo = trim((string)($body['effectiveTo'] ?? ''));

    if ($offerId === false || $offerId === null) {
        respond_field_error(400, field_error('offerId', 'Select an offer to end.'));
    }
    if (!valid_date($effectiveTo)) {
        respond_field_error(400, field_error('effectiveTo', 'Choose a valid offer end date.'));
    }

    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        "SELECT supplier_product_offer_id, product_id, product_version_id, area_id,
                unit_price, effective_from, effective_to
         FROM supplier_product_offers
         WHERE supplier_product_offer_id = ? AND supplier_id = ? AND status = 'ACTIVE'
         LIMIT 1 FOR UPDATE"
    );
    $statement->execute([(int)$offerId, (int)$actor['organization_id']]);
    $offer = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        $pdo->rollBack();
        respond_error(404, 'The offer was not found in your organisation.');
    }
    if ($offer['effective_to'] !== null) {
        $pdo->rollBack();
        respond_error(409, 'This offer already has an end date. Create a new period rather than rewriting its history.');
    }
    if ($effectiveTo < $offer['effective_from']) {
        $pdo->rollBack();
        respond_field_error(400, field_error('effectiveTo', 'The offer end date cannot be earlier than its start date.'));
    }

    $overlapStatement = $pdo->prepare(
        "SELECT supplier_product_offer_id
         FROM supplier_product_offers
         WHERE supplier_id = ? AND product_id = ? AND product_version_id = ? AND area_id = ?
           AND status = 'ACTIVE' AND supplier_product_offer_id <> ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         LIMIT 1 FOR UPDATE"
    );
    $overlapStatement->execute([
        (int)$actor['organization_id'],
        (int)$offer['product_id'],
        (int)$offer['product_version_id'],
        (int)$offer['area_id'],
        (int)$offerId,
        $effectiveTo,
        $offer['effective_from']
    ]);

    if ($overlapStatement->fetchColumn() !== false) {
        $pdo->rollBack();
        respond_error(409, 'That end date overlaps another offer period. Choose an earlier date that does not overlap the next offer.');
    }

    $update = $pdo->prepare(
        "UPDATE supplier_product_offers
         SET effective_to = ?
         WHERE supplier_product_offer_id = ? AND supplier_id = ? AND effective_to IS NULL"
    );
    $update->execute([$effectiveTo, (int)$offerId, (int)$actor['organization_id']]);

    record_audit(
        $pdo,
        $actor,
        'SUPPLIER_PRODUCT_OFFER_ENDED',
        'SUPPLIER_PRODUCT_OFFER',
        (int)$offerId,
        "Set supplier offer #{$offerId} effective-to date to {$effectiveTo}. The historical offer record was retained."
    );
    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Offer end date saved. The historical offer was retained.'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('supplier product offer end', $e);
    respond_error(500, 'Unable to end the supplier offer.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('supplier product offer end unexpected', $e);
    respond_error(500, 'Unable to end the supplier offer.');
}
