<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';

require_post();

try {
    $actor = require_login($pdo);
    if ($actor['organization_type'] !== 'RETAILER') {
        respond_error(403, 'Retailer organisation access is required.');
    }
    if ($actor['role'] === 'BRANCH_ADMIN') {
        $actor = require_branch_admin($pdo);
    } elseif ($actor['role'] !== 'ADMIN') {
        respond_error(403, 'Retailer administrator or branch administrator access is required.');
    }

    $body = read_json_body();
    $priceId = filter_var($body['priceId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $effectiveTo = trim((string)($body['effectiveTo'] ?? ''));

    if ($priceId === false || $priceId === null) {
        respond_field_error(400, field_error('priceId', 'Select a selling-price record to end.'));
    }
    if (!valid_date($effectiveTo)) {
        respond_field_error(400, field_error('effectiveTo', 'Choose a valid price end date.'));
    }

    $retailerId = (int)$actor['organization_id'];
    $pdo->beginTransaction();
    $sql =
        "SELECT retailer_branch_price_id, branch_id, product_id, product_version_id,
                effective_from, effective_to
         FROM retailer_branch_prices
         WHERE retailer_branch_price_id = ? AND retailer_id = ? AND status = 'ACTIVE'";
    $params = [(int)$priceId, $retailerId];
    if ($actor['role'] === 'BRANCH_ADMIN') {
        $sql .= ' AND branch_id = ?';
        $params[] = (int)$actor['branch_id'];
    }
    $sql .= ' LIMIT 1 FOR UPDATE';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $price = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$price) {
        $pdo->rollBack();
        respond_error(404, 'The selling-price record was not found in your permitted branch scope.');
    }
    if ($price['effective_to'] !== null) {
        $pdo->rollBack();
        respond_error(409, 'This price already has an end date. Create a new period rather than rewriting its history.');
    }
    if ($effectiveTo < $price['effective_from']) {
        $pdo->rollBack();
        respond_field_error(400, field_error('effectiveTo', 'The price end date cannot be earlier than its start date.'));
    }

    $overlapStatement = $pdo->prepare(
        "SELECT retailer_branch_price_id
         FROM retailer_branch_prices
         WHERE retailer_id = ? AND branch_id = ? AND product_id = ? AND product_version_id = ?
           AND status = 'ACTIVE' AND retailer_branch_price_id <> ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         LIMIT 1 FOR UPDATE"
    );
    $overlapStatement->execute([
        $retailerId,
        (int)$price['branch_id'],
        (int)$price['product_id'],
        (int)$price['product_version_id'],
        (int)$priceId,
        $effectiveTo,
        $price['effective_from']
    ]);
    if ($overlapStatement->fetchColumn() !== false) {
        $pdo->rollBack();
        respond_error(409, 'That end date overlaps another price period. Choose an earlier date that does not overlap the next price.');
    }

    $update = $pdo->prepare(
        "UPDATE retailer_branch_prices
         SET effective_to = ?
         WHERE retailer_branch_price_id = ? AND retailer_id = ? AND effective_to IS NULL"
    );
    $update->execute([$effectiveTo, (int)$priceId, $retailerId]);

    record_audit(
        $pdo,
        $actor,
        'RETAILER_BRANCH_PRICE_ENDED',
        'RETAILER_BRANCH_PRICE',
        (int)$priceId,
        "Set branch selling price #{$priceId} effective-to date to {$effectiveTo}. The historical price record was retained."
    );
    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Selling-price end date saved. The historical price was retained.'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('retailer branch price end', $e);
    respond_error(500, 'Unable to end the branch selling-price period.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error('retailer branch price end unexpected', $e);
    respond_error(500, 'Unable to end the branch selling-price period.');
}
