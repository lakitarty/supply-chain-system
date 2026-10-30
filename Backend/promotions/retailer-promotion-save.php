<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/retailer-promotion-rules.php';

require_post();

try {
    $actor = retailer_promotion_actor($pdo, true);
    $retailerId = (int)$actor['organization_id'];
    $body = read_json_body();

    $promotionId = filter_var($body['promotionId'] ?? 0, FILTER_VALIDATE_INT);
    $name = trim((string)($body['name'] ?? ''));
    $description = trim((string)($body['description'] ?? ''));
    $startDate = trim((string)($body['startDate'] ?? ''));
    $endDate = trim((string)($body['endDate'] ?? ''));
    $rawItems = is_array($body['items'] ?? null) ? $body['items'] : [];

    if ($promotionId === false || $promotionId < 0) {
        respond_error(400, 'Select a valid promotion.');
    }
    if ($name === '' || mb_strlen($name) > 200) {
        respond_field_error(400, field_error('name', 'Enter a promotion name of up to 200 characters.'));
    }
    if (mb_strlen($description) > 2000) {
        respond_field_error(400, field_error('description', 'The description can contain up to 2000 characters.'));
    }
    if (!valid_date($startDate) || !valid_date($endDate) || $endDate < $startDate) {
        respond_field_error(400, field_error('dates', 'Enter valid dates, with the end date on or after the start date.'));
    }
    if (count($rawItems) > 500) {
        respond_error(400, 'A promotion cannot contain more than 500 branch-product entries.');
    }

    $allowedBranches = array_map(
        static fn(array $b): int => (int)$b['branch_id'],
        retailer_promotion_allowed_branches($pdo, $actor)
    );
    $items = [];
    $seen = [];
    foreach ($rawItems as $index => $raw) {
        if (!is_array($raw)) continue;
        $branchId = filter_var($raw['branchId'] ?? null, FILTER_VALIDATE_INT);
        $versionId = filter_var($raw['productVersionId'] ?? null, FILTER_VALIDATE_INT);
        $saleText = retailer_promotion_money(trim((string)($raw['promotionPrice'] ?? '')));
        if (!$branchId || !$versionId || $saleText === null) {
            respond_field_error(400, field_error('items', 'Each item needs a branch, product and valid promotion price.'));
        }
        if (!in_array((int)$branchId, $allowedBranches, true)) {
            respond_error(403, 'You cannot run a promotion at a branch outside your permissions.');
        }
        $key = (int)$branchId . ':' . (int)$versionId;
        if (isset($seen[$key])) {
            respond_field_error(400, field_error('items', 'A product can only appear once per branch in a promotion.'));
        }
        $seen[$key] = true;

        $priceRow = retailer_promotion_price_for_date(
            $pdo, $retailerId, (int)$branchId, (int)$versionId, $startDate
        );
        if (!$priceRow) {
            respond_field_error(400, field_error(
                'items',
                'A selected product has no active selling price for that branch on the promotion start date. Refresh the products and review the dates.'
            ));
        }
        $regularCents = (int)round((float)$priceRow['selling_price'] * 100);
        $promotionCents = (int)round((float)$saleText * 100);
        if ($promotionCents < 1 || $promotionCents >= $regularCents) {
            respond_field_error(400, field_error(
                'items',
                'Every promotion price must be greater than zero and below that branch product’s regular selling price.'
            ));
        }

        $items[] = [
            'branchId' => (int)$branchId,
            'productId' => (int)$priceRow['product_id'],
            'productVersionId' => (int)$versionId,
            'regularPrice' => number_format((float)$priceRow['selling_price'], 2, '.', ''),
            'promotionPrice' => $saleText,
            'priceBasis' => (string)$priceRow['price_basis']
        ];
    }

    $pdo->beginTransaction();
    $isNew = (int)$promotionId < 1;

    if ($isNew) {
        $insert = $pdo->prepare(
            "INSERT INTO retailer_promotions
                (retailer_id, promotion_name, description, start_date, end_date, status, created_by)
             VALUES (?, ?, ?, ?, ?, 'DRAFT', ?)"
        );
        $insert->execute([
            $retailerId, $name, $description === '' ? null : $description,
            $startDate, $endDate, (int)$actor['user_id']
        ]);
        $promotionId = (int)$pdo->lastInsertId();
    } else {
        $lock = $pdo->prepare(
            'SELECT retailer_promotion_id, status, created_by FROM retailer_promotions
             WHERE retailer_promotion_id = ? AND retailer_id = ? FOR UPDATE'
        );
        $lock->execute([(int)$promotionId, $retailerId]);
        $existing = $lock->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $pdo->rollBack();
            respond_error(404, 'The branch promotion was not found.');
        }
        if ((string)$existing['status'] !== 'DRAFT') {
            $pdo->rollBack();
            respond_error(409, 'Only draft branch promotions can be edited.');
        }
        if (!retailer_promotion_scope_allowed($pdo, $actor, (int)$promotionId)) {
            $pdo->rollBack();
            respond_error(403, 'You cannot edit a promotion that includes other branches.');
        }
        $update = $pdo->prepare(
            'UPDATE retailer_promotions SET promotion_name = ?, description = ?, start_date = ?, end_date = ?
             WHERE retailer_promotion_id = ? AND retailer_id = ?'
        );
        $update->execute([
            $name, $description === '' ? null : $description, $startDate, $endDate,
            (int)$promotionId, $retailerId
        ]);
        $pdo->prepare(
            'DELETE FROM retailer_promotion_items WHERE retailer_promotion_id = ? AND retailer_id = ?'
        )->execute([(int)$promotionId, $retailerId]);
    }

    $insertItem = $pdo->prepare(
        'INSERT INTO retailer_promotion_items
            (retailer_promotion_id, retailer_id, branch_id, product_id, product_version_id,
             regular_price_snapshot, promotion_price, price_basis)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $item) {
        $insertItem->execute([
            (int)$promotionId, $retailerId, $item['branchId'], $item['productId'],
            $item['productVersionId'], $item['regularPrice'], $item['promotionPrice'], $item['priceBasis']
        ]);
    }

    record_audit(
        $pdo,
        $actor,
        $isNew ? 'RETAILER_PROMOTION_CREATED' : 'RETAILER_PROMOTION_UPDATED',
        'RETAILER_PROMOTION',
        (int)$promotionId,
        sprintf('Saved draft branch promotion "%s" with %d branch-product entries (%s to %s).', $name, count($items), $startDate, $endDate)
    );
    $pdo->commit();

    respond($isNew ? 201 : 200, [
        'success' => true,
        'promotion_id' => (int)$promotionId,
        'message' => 'Branch promotion draft saved.'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'A branch-product entry conflicts with an existing record. Refresh and try again.');
    }
    log_error('retailer promotion save', $e);
    respond_error(500, 'Unable to save the branch promotion.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('retailer promotion save unexpected', $e);
    respond_error(500, 'Unable to save the branch promotion.');
}
