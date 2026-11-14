<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';

require_post();

try {
    $actor = require_login($pdo);

    if ($actor['role'] !== 'ADMIN') {
        respond_error(403, 'Organisation administrator access is required to verify an LPO.');
    }

    $type = (string)$actor['organization_type'];

    if (!in_array($type, ['SUPPLIER', 'MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'This organisation cannot verify incoming LPOs.');
    }

    $body = read_json_body();

    $orderId = filter_var(
        $body['orderId'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $decision = strtoupper(trim((string)($body['decision'] ?? '')));
    $reason = trim((string)($body['reason'] ?? ''));
    $acknowledgementItems = $body['items'] ?? null;

    if ($orderId === false || $orderId === null) {
        respond_error(400, 'A valid LPO number is required.');
    }

    if (!in_array($decision, ['CONFIRMED', 'REJECTED'], true)) {
        respond_error(400, 'Choose Confirm or Reject.');
    }

    if (strlen($reason) > 500) {
        respond_error(400, 'The rejection note must be 500 characters or fewer.');
    }

    if ($decision === 'CONFIRMED' && !is_array($acknowledgementItems)) {
        respond_error(422, 'Product-level acknowledgement details are required.');
    }

    $readUnsignedInteger = static function ($value): ?int {
        if (is_int($value)) {
            $number = $value;
        } elseif (
            is_string($value)
            && preg_match('/^(0|[1-9][0-9]*)$/D', $value) === 1
        ) {
            $number = filter_var(
                $value,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            );

            if ($number === false) {
                return null;
            }
        } else {
            return null;
        }

        if ($number < 0 || $number > 4294967295) {
            return null;
        }

        return $number;
    };

    $organizationId = (int)$actor['organization_id'];

    $pdo->beginTransaction();

    $orderStatement = $pdo->prepare(
        "SELECT supply_order_id, lpo_number, order_direction,
                supplier_id, retailer_id, status, lpo_valid_until
         FROM supply_orders
         WHERE supply_order_id = ?
         LIMIT 1
         FOR UPDATE"
    );

    $orderStatement->execute([(int)$orderId]);
    $order = $orderStatement->fetch(PDO::FETCH_ASSOC);

    if (
        !$order
        || (int)$order['supplier_id'] !== $organizationId
        || $order['status'] !== 'SUBMITTED'
    ) {
        $pdo->rollBack();

        respond_error(
            404,
            'The submitted LPO was not found or is not awaiting verification by your organisation.'
        );
    }

    $direction = (string)$order['order_direction'];

    $allowed = (
        $direction === 'RETAILER_TO_SUPPLIER'
        && in_array($type, ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)
    ) || (
        $direction === 'SUPPLIER_TO_MANUFACTURER'
        && in_array($type, ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true)
    );

    if (!$allowed) {
        $pdo->rollBack();
        respond_error(403, 'Your organisation type cannot verify this LPO direction.');
    }

    if (
        !empty($order['lpo_valid_until'])
        && $order['lpo_valid_until'] < date('Y-m-d')
    ) {
        $pdo->rollBack();

        respond_error(
            409,
            'This LPO expired on ' . $order['lpo_valid_until']
            . ' and can no longer be approved. Ask the issuing organisation for a new LPO.'
        );
    }

    $acknowledgedLineCount = 0;
    $totalAvailable = 0;
    $totalShort = 0;
    $totalOutOfStock = 0;

    if ($decision === 'CONFIRMED') {
        $lineStatement = $pdo->prepare(
            "SELECT supply_order_item_id, quantity_ordered
             FROM supply_order_items
             WHERE supply_order_id = ?
             ORDER BY supply_order_item_id
             FOR UPDATE"
        );

        $lineStatement->execute([(int)$orderId]);
        $lpoLines = $lineStatement->fetchAll(PDO::FETCH_ASSOC);

        if (!$lpoLines) {
            $pdo->rollBack();
            respond_error(409, 'An LPO without product lines cannot be acknowledged.');
        }

        if (count($acknowledgementItems) !== count($lpoLines)) {
            $pdo->rollBack();

            respond_error(
                422,
                'Provide an acknowledgement for every LPO product line.'
            );
        }

        $submittedItems = [];

        foreach ($acknowledgementItems as $acknowledgement) {
            if (!is_array($acknowledgement)) {
                $pdo->rollBack();
                respond_error(422, 'One or more acknowledgement lines are invalid.');
            }

            $lineId = $readUnsignedInteger(
                $acknowledgement['supplyOrderItemId'] ?? null
            );

            $available = $readUnsignedInteger(
                $acknowledgement['quantityAvailable'] ?? null
            );

            $short = $readUnsignedInteger(
                $acknowledgement['quantityShort'] ?? null
            );

            $outOfStock = $readUnsignedInteger(
                $acknowledgement['quantityOutOfStock'] ?? null
            );

            if (
                $lineId === null || $lineId < 1
                || $available === null
                || $short === null
                || $outOfStock === null
            ) {
                $pdo->rollBack();
                respond_error(422, 'Acknowledgement quantities must be valid non-negative whole numbers.');
            }

            if (isset($submittedItems[$lineId])) {
                $pdo->rollBack();
                respond_error(422, 'An LPO product line cannot be acknowledged more than once.');
            }

            $expectedDate = $acknowledgement['expectedDeliveryDate'] ?? null;

            if ($expectedDate === '') {
                $expectedDate = null;
            }

            if ($expectedDate !== null) {
                if (!is_string($expectedDate)) {
                    $pdo->rollBack();
                    respond_error(422, 'An expected delivery date is invalid.');
                }

                $dateObject = DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $expectedDate
                );

                $dateErrors = DateTimeImmutable::getLastErrors();

                if (
                    !$dateObject
                    || (
                        $dateErrors !== false
                        && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)
                    )
                    || $dateObject->format('Y-m-d') !== $expectedDate
                    || $expectedDate < date('Y-m-d')
                ) {
                    $pdo->rollBack();
                    respond_error(422, 'Expected delivery dates must be valid dates that are not in the past.');
                }
            }

            if (
                $available > 4294967295 - $short
                || $available + $short > 4294967295 - $outOfStock
            ) {
                $pdo->rollBack();
                respond_error(422, 'Acknowledgement quantities exceed the supported limit.');
            }

            if (
                $expectedDate !== null
                && $short === 0
                && $outOfStock === 0
            ) {
                $pdo->rollBack();
                respond_error(422, 'An expected delivery date is only applicable to unavailable quantities.');
            }

            $submittedItems[$lineId] = [
                'quantity_available' => $available,
                'quantity_short' => $short,
                'quantity_out_of_stock' => $outOfStock,
                'expected_delivery_date' => $expectedDate
            ];
        }

        $insertAcknowledgement = $pdo->prepare(
            "INSERT INTO supply_order_item_acknowledgements
                (supply_order_item_id, quantity_ordered,
                 quantity_available, quantity_short,
                 quantity_out_of_stock, expected_delivery_date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        foreach ($lpoLines as $line) {
            $lineId = (int)$line['supply_order_item_id'];
            $ordered = (int)$line['quantity_ordered'];

            if (!isset($submittedItems[$lineId])) {
                $pdo->rollBack();

                respond_error(
                    422,
                    'Every original LPO product line must be acknowledged.'
                );
            }

            $item = $submittedItems[$lineId];

            $quantityTotal = $item['quantity_available']
                + $item['quantity_short']
                + $item['quantity_out_of_stock'];

            if ($quantityTotal !== $ordered) {
                $pdo->rollBack();

                respond_error(
                    422,
                    'Available, short and out-of-stock quantities must add up to the ordered quantity for every product.'
                );
            }

            $insertAcknowledgement->execute([
                $lineId,
                $ordered,
                $item['quantity_available'],
                $item['quantity_short'],
                $item['quantity_out_of_stock'],
                $item['expected_delivery_date']
            ]);

            $totalAvailable += $item['quantity_available'];
            $totalShort += $item['quantity_short'];
            $totalOutOfStock += $item['quantity_out_of_stock'];
            $acknowledgedLineCount++;
        }
    }

    $update = $pdo->prepare(
        "UPDATE supply_orders
         SET status = ?,
             reviewed_by = ?,
             reviewed_at = CURRENT_TIMESTAMP,
             rejection_reason = ?
         WHERE supply_order_id = ?
           AND supplier_id = ?
           AND status = 'SUBMITTED'"
    );

    $update->execute([
        $decision,
        (int)$actor['user_id'],
        $decision === 'REJECTED' && $reason !== '' ? $reason : null,
        (int)$orderId,
        $organizationId
    ]);

    if ($update->rowCount() !== 1) {
        $pdo->rollBack();
        respond_error(409, 'The LPO was updated by another user. Refresh and try again.');
    }

    $reviewingParty = $direction === 'RETAILER_TO_SUPPLIER'
        ? 'Supplier'
        : 'Manufacturer';

    if ($decision === 'CONFIRMED') {
        $auditDescription = $reviewingParty
            . ' acknowledged LPO ' . $order['lpo_number']
            . ' across ' . $acknowledgedLineCount . ' product line(s).'
            . ' Available: ' . $totalAvailable
            . ', short: ' . $totalShort
            . ', out of stock: ' . $totalOutOfStock . '.';

        record_audit(
            $pdo,
            $actor,
            'SUPPLY_ORDER_CONFIRMED',
            'SUPPLY_ORDER',
            (int)$orderId,
            $auditDescription
        );

        create_organization_notification(
            $pdo,
            (int)$order['retailer_id'],
            'LPO_ACKNOWLEDGED',
            (int)$orderId,
            'LPO acknowledgement received',
            'The receiving organisation has acknowledged LPO '
                . $order['lpo_number']
                . '. Product availability, shortages and expected delivery details are now available in the LPO.'
        );
    } else {
        $auditDescription = $reviewingParty
            . ' rejected LPO ' . $order['lpo_number']
            . ($reason !== '' ? '. Reason: ' . $reason : '.');

        record_audit(
            $pdo,
            $actor,
            'SUPPLY_ORDER_REJECTED',
            'SUPPLY_ORDER',
            (int)$orderId,
            $auditDescription
        );

        create_organization_notification(
            $pdo,
            (int)$order['retailer_id'],
            'LPO_REJECTED',
            (int)$orderId,
            'LPO rejected',
            'The receiving organisation rejected LPO '
                . $order['lpo_number']
                . ($reason !== '' ? '. Reason: ' . $reason : '.')
        );
    }

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => $decision === 'CONFIRMED'
            ? 'LPO acknowledged and confirmed.'
            : 'LPO rejected.',
        'supply_order_id' => (int)$orderId,
        'status' => $decision,
        'acknowledged_lines' => $acknowledgedLineCount
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('purchasing LPO review', $e);
    respond_error(500, 'Unable to verify the LPO.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('purchasing LPO review unexpected', $e);
    respond_error(500, 'Unable to verify the LPO.');
}