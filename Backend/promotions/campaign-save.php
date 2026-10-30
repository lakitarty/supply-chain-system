<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/promotion-rules.php';

require_post();

try {
    $actor = promotion_require_manufacturer($pdo);
    $organizationId = (int)$actor['organization_id'];

    $input = promotion_validate_input(promotion_clean_input(read_json_body()));
    promotion_assert_choices($pdo, $organizationId, $input);
    $input = promotion_add_rrp_snapshots($pdo, $organizationId, $input);

    $isNew = $input['promotionId'] < 1;
    $description = $input['description'] === '' ? null : $input['description'];

    $pdo->beginTransaction();

    if ($isNew) {
        $pdo->prepare(
            "INSERT INTO promotions
                (organization_id, promotion_name, description, start_date, end_date, status, created_by)
             VALUES (?, ?, ?, ?, ?, 'DRAFT', ?)"
        )->execute([
            $organizationId,
            $input['name'],
            $description,
            $input['startDate'],
            $input['endDate'],
            (int)$actor['user_id']
        ]);
        $promotionId = (int)$pdo->lastInsertId();
    } else {
        $promotionId = $input['promotionId'];
        $lock = $pdo->prepare(
            'SELECT status FROM promotions WHERE promotion_id = ? AND organization_id = ? LIMIT 1 FOR UPDATE'
        );
        $lock->execute([$promotionId, $organizationId]);
        $status = $lock->fetchColumn();
        if ($status === false) {
            $pdo->rollBack();
            respond_error(404, 'The campaign was not found in your organisation.');
        }
        if ((string)$status !== 'DRAFT') {
            $pdo->rollBack();
            respond_error(409, 'Only a draft campaign can be edited. Ask the reviewer to return it to draft first.');
        }

        $pdo->prepare(
            'UPDATE promotions SET promotion_name = ?, description = ?, start_date = ?, end_date = ?
             WHERE promotion_id = ?'
        )->execute([$input['name'], $description, $input['startDate'], $input['endDate'], $promotionId]);
        $pdo->prepare('DELETE FROM promotion_products WHERE promotion_id = ?')->execute([$promotionId]);
        $pdo->prepare('DELETE FROM promotion_participants WHERE promotion_id = ?')->execute([$promotionId]);
    }

    $productInsert = $pdo->prepare(
        'INSERT INTO promotion_products
            (promotion_id, product_id, product_version_id, normal_price, rrp_snapshot,
             rrp_recommendation_id, promotion_price, compensation_per_unit)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($input['products'] as $product) {
        $productInsert->execute([
            $promotionId,
            $product['productId'],
            $product['productVersionId'],
            $product['rrpAmount'],
            $product['rrpAmount'],
            $product['rrpRecommendationId'],
            $product['promotionPrice'],
            $product['compensation']
        ]);
    }

    $retailerInsert = $pdo->prepare(
        "INSERT INTO promotion_participants (promotion_id, organization_id, status) VALUES (?, ?, 'PENDING')"
    );
    foreach ($input['retailers'] as $retailerId) {
        $retailerInsert->execute([$promotionId, $retailerId]);
    }

    record_audit(
        $pdo,
        $actor,
        $isNew ? 'PROMOTION_CREATED' : 'PROMOTION_UPDATED',
        'PROMOTION',
        $promotionId,
        sprintf(
            'Saved draft campaign "%s" (%s to %s) with %d products and %d retailers.',
            $input['name'],
            $input['startDate'],
            $input['endDate'],
            count($input['products']),
            count($input['retailers'])
        )
    );
    $pdo->commit();

    respond($isNew ? 201 : 200, [
        'success' => true,
        'promotion_id' => $promotionId,
        'message' => 'Draft campaign "' . $input['name'] . '" was saved.'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('promotion save', $e);
    respond_error(500, 'Unable to save the campaign.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('promotion save unexpected', $e);
    respond_error(500, 'Unable to save the campaign.');
}