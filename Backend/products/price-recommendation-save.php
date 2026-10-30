<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../network/network-rules.php';

function recommendation_at_date(
    PDO $pdo,
    int $versionId,
    string $type,
    string $effectiveDate,
    bool $lock = false
): ?array {
    $sql = 'SELECT recommendation_id, amount, currency, effective_from, created_at
            FROM product_price_recommendations
            WHERE product_version_id = ?
              AND recommendation_type = ?
              AND effective_from <= ?
            ORDER BY effective_from DESC, created_at DESC, recommendation_id DESC
            LIMIT 1';
    if ($lock) {
        $sql .= ' FOR UPDATE';
    }
    $statement = $pdo->prepare($sql);
    $statement->execute([$versionId, $type, $effectiveDate]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function recommendation_fail(int $status, string $message): never
{
    throw new DomainException($message, $status);
}

require_post();

try {
    $actor = require_admin($pdo);
    $input = read_json_body();

    $versionId = filter_var($input['product_version_id'] ?? null, FILTER_VALIDATE_INT);
    $type = strtoupper(trim((string)($input['recommendation_type'] ?? '')));
    $rawAmount = trim((string)($input['amount'] ?? ''));
    $effectiveFrom = trim((string)($input['effective_from'] ?? ''));

    if (!$versionId || $versionId < 1) {
        respond_field_error(400, field_error('product_version_id', 'Select a valid product version.'));
    }
    if (!in_array($type, ['RRP', 'RSP'], true)) {
        respond_field_error(400, field_error('recommendation_type', 'Choose RRP or RSP.'));
    }
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $rawAmount)) {
        respond_field_error(400, field_error('amount', 'Enter a positive KSh amount with no more than two decimal places.'));
    }
    $amount = round((float)$rawAmount, 2);
    if (!is_finite($amount) || $amount <= 0) {
        respond_field_error(400, field_error('amount', 'The recommendation must be greater than zero.'));
    }
    if (!valid_date($effectiveFrom)) {
        respond_field_error(400, field_error('effective_from', 'Enter a valid effective date.'));
    }

    $today = (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');
    if ($effectiveFrom < $today) {
        respond_field_error(400, field_error('effective_from', 'A new recommendation must take effect today or on a future date.'));
    }

    $productStatement = $pdo->prepare(
        "SELECT p.product_id, p.product_name, p.manufacturer_id, p.status AS product_status,
                pv.product_version_id, pv.version_number,
                pv.effective_from AS version_effective_from,
                pv.effective_to AS version_effective_to,
                o.organization_type AS manufacturer_type, o.status AS manufacturer_status,
                o.organization_name AS manufacturer_name
         FROM product_versions pv
         INNER JOIN products p ON p.product_id = pv.product_id
         INNER JOIN organizations o ON o.organization_id = p.manufacturer_id
         WHERE pv.product_version_id = ?
         LIMIT 1"
    );
    $productStatement->execute([(int)$versionId]);
    $product = $productStatement->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        respond_error(404, 'The selected product version was not found.');
    }

    if ((int)$product['manufacturer_id'] !== (int)$actor['organization_id']) {
        respond_error(403, 'Only the product-owning manufacturer can publish recommendations for this product.');
    }
    if (
        !in_array($actor['organization_type'], ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true) ||
        $actor['organization_type'] !== $product['manufacturer_type']
    ) {
        respond_error(403, 'Your organisation is not authorised to publish product recommendations.');
    }
    if ($product['product_status'] !== 'ACTIVE' || $product['manufacturer_status'] !== 'ACTIVE') {
        respond_error(409, 'The product and manufacturer must be active.');
    }
    if (
        $effectiveFrom < (string)$product['version_effective_from'] ||
        ($product['version_effective_to'] !== null && $effectiveFrom > (string)$product['version_effective_to'])
    ) {
        respond_field_error(409, field_error(
            'effective_from',
            'The effective date must fall within this product version’s effective period.'
        ));
    }

    $pdo->beginTransaction();

    $previous = recommendation_at_date($pdo, (int)$versionId, $type, $effectiveFrom, true);
    $rrp = $type === 'RRP'
        ? ['amount' => number_format($amount, 2, '.', '')]
        : recommendation_at_date($pdo, (int)$versionId, 'RRP', $effectiveFrom, true);

    if ($type === 'RSP') {
        if ($rrp === null) {
            recommendation_fail(409, 'Publish an RRP for this product version and effective date before publishing the supplier recommendation.');
        }
        if ($amount > (float)$rrp['amount'] + 0.0001) {
            recommendation_fail(409, 'The supplier recommended selling price cannot exceed the manufacturer RRP. Update the RSP or publish a compatible RRP first.');
        }

        // Ensure a scheduled RRP does not later become lower than this RSP
        // before a subsequent RSP recommendation supersedes it.
        $nextRspStatement = $pdo->prepare(
            "SELECT MIN(effective_from)
             FROM product_price_recommendations
             WHERE product_version_id = ?
               AND recommendation_type = 'RSP'
               AND effective_from > ?"
        );
        $nextRspStatement->execute([(int)$versionId, $effectiveFrom]);
        $nextRspDate = $nextRspStatement->fetchColumn();

        $futureRrpSql = "SELECT DISTINCT effective_from
                         FROM product_price_recommendations
                         WHERE product_version_id = ?
                           AND recommendation_type = 'RRP'
                           AND effective_from >= ?";
        $futureRrpParams = [(int)$versionId, $effectiveFrom];
        if ($nextRspDate !== false && $nextRspDate !== null) {
            $futureRrpSql .= ' AND effective_from < ?';
            $futureRrpParams[] = $nextRspDate;
        }
        $futureRrpSql .= ' ORDER BY effective_from';
        $futureRrpDates = $pdo->prepare($futureRrpSql);
        $futureRrpDates->execute($futureRrpParams);
        foreach ($futureRrpDates->fetchAll(PDO::FETCH_COLUMN) as $rrpDate) {
            $futureRrp = recommendation_at_date($pdo, (int)$versionId, 'RRP', (string)$rrpDate, true);
            if ($futureRrp !== null && $amount > (float)$futureRrp['amount'] + 0.0001) {
                recommendation_fail(
                    409,
                    'The supplier recommendation would exceed a scheduled manufacturer RRP. Adjust the RSP or choose a compatible effective date.'
                );
            }
        }
    } else {
        $rsp = recommendation_at_date($pdo, (int)$versionId, 'RSP', $effectiveFrom, true);
        if ($rsp !== null && (float)$rsp['amount'] > $amount + 0.0001) {
            recommendation_fail(409, 'The new RRP would be lower than the current supplier recommendation. Adjust the RSP first or choose an effective date where the recommendations are compatible.');
        }

        // Ensure this RRP does not invalidate a future RSP before a later RRP supersedes it.
        $nextRrpStatement = $pdo->prepare(
            "SELECT MIN(effective_from)
             FROM product_price_recommendations
             WHERE product_version_id = ?
               AND recommendation_type = 'RRP'
               AND effective_from > ?"
        );
        $nextRrpStatement->execute([(int)$versionId, $effectiveFrom]);
        $nextRrpDate = $nextRrpStatement->fetchColumn();

        $futureRspSql = "SELECT DISTINCT effective_from
                         FROM product_price_recommendations
                         WHERE product_version_id = ?
                           AND recommendation_type = 'RSP'
                           AND effective_from >= ?";
        $futureRspParams = [(int)$versionId, $effectiveFrom];
        if ($nextRrpDate !== false && $nextRrpDate !== null) {
            $futureRspSql .= ' AND effective_from < ?';
            $futureRspParams[] = $nextRrpDate;
        }
        $futureRspSql .= ' ORDER BY effective_from';
        $futureRspDates = $pdo->prepare($futureRspSql);
        $futureRspDates->execute($futureRspParams);
        foreach ($futureRspDates->fetchAll(PDO::FETCH_COLUMN) as $rspDate) {
            $futureRsp = recommendation_at_date($pdo, (int)$versionId, 'RSP', (string)$rspDate, true);
            if ($futureRsp !== null && (float)$futureRsp['amount'] > $amount + 0.0001) {
                recommendation_fail(
                    409,
                    'The new RRP would exceed an effective supplier recommendation. Adjust the affected RSP or choose a compatible effective date.'
                );
            }
        }
    }

    $insert = $pdo->prepare(
        'INSERT INTO product_price_recommendations
            (manufacturer_id, product_id, product_version_id, recommendation_type,
             amount, currency, effective_from, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, \'KES\', ?, ?)'
    );
    $insert->execute([
        (int)$actor['organization_id'],
        (int)$product['product_id'],
        (int)$versionId,
        $type,
        number_format($amount, 2, '.', ''),
        $effectiveFrom,
        (int)$actor['user_id']
    ]);
    $recommendationId = (int)$pdo->lastInsertId();

    $previousAmount = $previous === null ? null : number_format((float)$previous['amount'], 2, '.', '');
    $newAmount = number_format($amount, 2, '.', '');
    $typeLabel = $type === 'RRP' ? 'recommended retail price (RRP)' : 'supplier recommended selling price (RSP)';
    $recipientIds = [];

    if ($type === 'RSP') {
        // Match the product catalogue's organization-level upstream visibility:
        // downstream supplier organizations that can see this manufacturer's products receive RSP updates.
        $reachable = reachable_organization_ids($pdo, (int)$actor['organization_id'], 'downstream');
        if ($reachable !== []) {
            $recipients = $pdo->prepare(
                "SELECT organization_id
                 FROM organizations
                 WHERE organization_type IN ('SUPPLIER','MANUFACTURER_SUPPLIER')
                   AND status = 'ACTIVE'
                   AND organization_id IN (" . sql_placeholders($reachable) . ")
                 ORDER BY organization_id"
            );
            $recipients->execute($reachable);
            $recipientIds = array_map('intval', $recipients->fetchAll(PDO::FETCH_COLUMN));
        }
    } else {
        $reachable = reachable_organization_ids($pdo, (int)$actor['organization_id'], 'downstream');
        if ($reachable !== []) {
            $recipients = $pdo->prepare(
                "SELECT organization_id
                 FROM organizations
                 WHERE organization_type = 'RETAILER'
                   AND status = 'ACTIVE'
                   AND organization_id IN (" . sql_placeholders($reachable) . ")
                 ORDER BY organization_id"
            );
            $recipients->execute($reachable);
            $recipientIds = array_map('intval', $recipients->fetchAll(PDO::FETCH_COLUMN));
        }
    }

    $beforeText = $previousAmount === null
        ? 'A new recommendation of KSh ' . $newAmount . ' was published'
        : 'The recommendation changed from KSh ' . $previousAmount . ' to KSh ' . $newAmount;
    $message = $beforeText . " for {$product['product_name']} (version {$product['version_number']}). "
        . 'The ' . $typeLabel . ' is effective ' . $effectiveFrom . '. '
        . 'This is non-binding pricing guidance; your organisation retains control over its own selling prices.';

    foreach (array_unique($recipientIds) as $recipientId) {
        create_organization_notification(
            $pdo,
            (int)$recipientId,
            'PRODUCT_CHANGE',
            (int)$product['product_id'],
            strtoupper($type) . ' updated: ' . (string)$product['product_name'],
            $message
        );
    }

    record_audit(
        $pdo,
        $actor,
        'PRICE_RECOMMENDATION_PUBLISHED',
        'PRODUCT_VERSION',
        (int)$versionId,
        sprintf(
            'Published %s for %s version %d at KSh %s effective %s. Previous effective recommendation: %s. Notified %d organisations.',
            $type,
            (string)$product['product_name'],
            (int)$product['version_number'],
            $newAmount,
            $effectiveFrom,
            $previousAmount ?? 'none',
            count(array_unique($recipientIds))
        )
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'recommendation_id' => $recommendationId,
        'recommendation_type' => $type,
        'amount' => $newAmount,
        'effective_from' => $effectiveFrom,
        'notifications_created' => count(array_unique($recipientIds)),
        'message' => $type . ' published for ' . (string)$product['product_name'] . '.'
    ]);
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 409, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('product price recommendation save', $e);
    respond_error(500, 'Unable to publish the recommendation.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('product price recommendation save unexpected', $e);
    respond_error(500, 'Unable to publish the recommendation.');
}
