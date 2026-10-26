<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/price-confirmation.php';

function price_save_day_before(string $date): string
{
    return (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
}

function price_save_day_after(string $date): string
{
    return (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
}

require_post();

try {
    $actor = require_admin($pdo);
    $input = read_json_body();

    $versionId = filter_var(
        $input['product_version_id'] ?? null,
        FILTER_VALIDATE_INT
    );
    $priceType = trim((string)($input['price_type'] ?? ''));
    $amount = filter_var(
        $input['price_amount'] ?? null,
        FILTER_VALIDATE_FLOAT
    );
    $includesVat = !empty($input['price_includes_vat']) ? 1 : 0;
    $fromDate = trim((string)($input['effective_from'] ?? ''));
    $toDate = trim((string)($input['effective_to'] ?? '')) ?: null;

    if (!$versionId || $versionId < 1) {
        respond_field_error(400, field_error('product_version_id', 'Select a product version.'));
    }

    if (!in_array($priceType, ['COST', 'SELLING'], true)) {
        respond_field_error(400, field_error('price_type', 'Invalid price type.'));
    }

    if ($amount === false || !is_finite((float)$amount) || $amount < 0) {
        respond_field_error(400, field_error('price_amount', 'Enter a valid amount.'));
    }

    if (!valid_date($fromDate)) {
        respond_field_error(400, field_error('effective_from', 'Valid start date required.'));
    }

    if ($toDate !== null && (!valid_date($toDate) || $toDate < $fromDate)) {
        respond_field_error(400, field_error('effective_to', 'End date must be on or after the start date.'));
    }

    $statement = $pdo->prepare(
        "SELECT
            p.product_id,
            p.manufacturer_id,
            p.status AS product_status,
            manufacturer.organization_type AS manufacturer_type,
            manufacturer.status AS manufacturer_status
         FROM product_versions pv
         INNER JOIN products p ON p.product_id = pv.product_id
         INNER JOIN organizations manufacturer
            ON manufacturer.organization_id = p.manufacturer_id
         WHERE pv.product_version_id = ?
         LIMIT 1"
    );
    $statement->execute([$versionId]);
    $product = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        respond_error(404, 'The selected product version was not found.');
    }

    if (
        $product['product_status'] !== 'ACTIVE' ||
        $product['manufacturer_status'] !== 'ACTIVE'
    ) {
        respond_error(409, 'The product and its manufacturer must be active.');
    }

    $organizationId = (int)$actor['organization_id'];
    $manufacturerId = (int)$product['manufacturer_id'];
    $productId = (int)$product['product_id'];
    $actorType = (string)$actor['organization_type'];
    $manufacturerType = (string)$product['manufacturer_type'];
    $ownsProduct = $organizationId === $manufacturerId;

    $accessEndDate = $toDate ?? '9999-12-31';

    if ($ownsProduct) {
        if (
            !in_array($actorType, ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true) ||
            $actorType !== $manufacturerType
        ) {
            respond_error(403, 'Only the product-owning manufacturer can edit its production cost and selling price.');
        }

        require_price_confirmation_password(
            $pdo,
            $actor,
            (string)($input['price_confirmation_password'] ?? '')
        );
    } elseif (in_array($actorType, ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        $statement = $pdo->prepare(
            "SELECT 1
             FROM product_suppliers ps
             INNER JOIN organizations supplier
                ON supplier.organization_id = ps.supplier_id
             INNER JOIN organization_relationships r
                ON r.source_organization_id = ?
               AND r.target_organization_id = ps.supplier_id
               AND r.relationship_type = 'SUPPLIES'
             WHERE ps.product_id = ?
               AND ps.supplier_id = ?
               AND ps.status = 'ACTIVE'
               AND ps.start_date <= ?
               AND (ps.end_date IS NULL OR ps.end_date >= ?)
               AND supplier.status = 'ACTIVE'
               AND r.status = 'ACTIVE'
               AND r.start_date <= ?
               AND (r.end_date IS NULL OR r.end_date >= ?)
             LIMIT 1"
        );
        $statement->execute([
            $manufacturerId,
            $productId,
            $organizationId,
            $fromDate,
            $accessEndDate,
            $fromDate,
            $accessEndDate
        ]);

        if (!$statement->fetchColumn()) {
            respond_error(403, 'Your organisation does not have an active product-supply appointment for this product and effective period.');
        }
    } elseif ($actorType === 'RETAILER') {
        $statement = $pdo->prepare(
            "WITH RECURSIVE upstream (organization_id) AS (
                SELECT r.source_organization_id
                FROM organization_relationships r
                WHERE r.target_organization_id = ?
                  AND r.relationship_type = 'SUPPLIES'
                  AND r.status = 'ACTIVE'
                  AND r.start_date <= ?
                  AND (r.end_date IS NULL OR r.end_date >= ?)

                UNION

                SELECT r.source_organization_id
                FROM organization_relationships r
                INNER JOIN upstream u ON u.organization_id = r.target_organization_id
                WHERE r.relationship_type = 'SUPPLIES'
                  AND r.status = 'ACTIVE'
                  AND r.start_date <= ?
                  AND (r.end_date IS NULL OR r.end_date >= ?)
            )
            SELECT 1
            FROM product_suppliers ps
            INNER JOIN organizations supplier
                ON supplier.organization_id = ps.supplier_id
               AND supplier.status = 'ACTIVE'
            INNER JOIN organization_relationships manufacturer_link
                ON manufacturer_link.source_organization_id = ?
               AND manufacturer_link.target_organization_id = ps.supplier_id
               AND manufacturer_link.relationship_type = 'SUPPLIES'
            WHERE ps.product_id = ?
              AND ps.status = 'ACTIVE'
              AND ps.start_date <= ?
              AND (ps.end_date IS NULL OR ps.end_date >= ?)
              AND ps.supplier_id IN (SELECT organization_id FROM upstream)
              AND manufacturer_link.status = 'ACTIVE'
              AND manufacturer_link.start_date <= ?
              AND (manufacturer_link.end_date IS NULL OR manufacturer_link.end_date >= ?)
            LIMIT 1"
        );
        $statement->execute([
            $organizationId,
            $fromDate,
            $accessEndDate,
            $fromDate,
            $accessEndDate,
            $manufacturerId,
            $productId,
            $fromDate,
            $accessEndDate,
            $fromDate,
            $accessEndDate
        ]);

        if (!$statement->fetchColumn()) {
            respond_error(403, 'Your organisation has no active supply route for this product and effective period.');
        }
    } else {
        respond_error(403, 'Your organisation is not authorised to edit prices for this product.');
    }

    $statement = $pdo->prepare(
        "SELECT tax_category
         FROM product_tax_classifications
         WHERE product_version_id = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 1"
    );
    $statement->execute([$versionId, $fromDate, $fromDate]);
    $taxCategory = $statement->fetchColumn() ?: 'STANDARD_RATED';

    // Automatically cascade only a genuine current-date manufacturer selling-price change.
    // Scheduled and temporary prices continue through the regular date-history path.
    $todayNairobi = (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))
        ->format('Y-m-d');

    $manufacturerScheduleStatement = $pdo->prepare(
        "SELECT price_id
         FROM product_prices
         WHERE organization_id = ?
           AND product_id = ?
           AND product_version_id = ?
           AND price_type = 'SELLING'
           AND (
               effective_from > ?
               OR (
                   effective_from <= ?
                   AND effective_to >= ?
               )
           )
         LIMIT 1"
    );
    $manufacturerScheduleStatement->execute([
        $organizationId,
        $productId,
        $versionId,
        $todayNairobi,
        $todayNairobi,
        $todayNairobi
    ]);
    $hasScheduledManufacturerSellingPeriod =
        $manufacturerScheduleStatement->fetchColumn() !== false;

    if (
        $ownsProduct &&
        $priceType === 'SELLING' &&
        $fromDate === $todayNairobi &&
        $toDate === null &&
        $hasScheduledManufacturerSellingPeriod
    ) {
        respond_error(
            409,
            'A scheduled or time-limited manufacturer selling-price period exists. Review the price schedule before saving a current-date price change.'
        );
    }

    $priceDecreaseNotificationOnly = false;
        $ownsProduct &&
        !$hasScheduledManufacturerSellingPeriod &&
        $priceType === 'SELLING' &&
        $fromDate === $todayNairobi &&
        $toDate === null &&
        (float)$amount > 0;

    if ($automaticCascadeEligible) {
        $currentPriceStatement = $pdo->prepare(
            "SELECT price_id, price_amount, effective_from, effective_to
             FROM product_prices
             WHERE organization_id = ?
               AND product_id = ?
               AND product_version_id = ?
               AND price_type = 'SELLING'
               AND effective_from <= ?
               AND (effective_to IS NULL OR effective_to >= ?)
             ORDER BY effective_from DESC, price_id DESC"
        );

        $currentPriceStatement->execute([
            $organizationId,
            $productId,
            $versionId,
            $todayNairobi,
            $todayNairobi
        ]);

        $currentPriceRows = $currentPriceStatement->fetchAll(PDO::FETCH_ASSOC);

        if (count($currentPriceRows) > 1) {
            respond_error(
                409,
                'Multiple overlapping current manufacturer selling prices exist. Resolve the price history before changing the price.'
            );
        }

        if (
            count($currentPriceRows) === 1 &&
            abs((float)$currentPriceRows[0]['price_amount'] - (float)$amount) >= 0.005
        ) {
            if ((float)$amount < (float)$currentPriceRows[0]['price_amount'] - 0.005) {
                // Price decreases are notification-only. The ordinary price-save
                // transaction below will save the manufacturer's lower price.
                $priceDecreaseNotificationOnly = true;
            } else {
            require_once __DIR__ . '/price-cascade-rules.php';

            $pdo->beginTransaction();

            $plan = price_cascade_build_plan(
                $pdo,
                $actor,
                (int)$versionId,
                (float)$amount,
                true
            );

            price_cascade_apply_plan($pdo, $actor, $plan);

            record_audit(
                $pdo,
                $actor,
                'PRICE_SAVED',
                'PRODUCT',
                (int)$versionId,
                'Saved manufacturer SELLING price ' .
                number_format((float)$amount, 2, '.', '') .
                " effective {$todayNairobi}; protection-enabled organisations were processed automatically."
            );

            $cascadeEffectiveTo = $currentPriceRows[0]['effective_to'] === null
                ? null
                : (string)$currentPriceRows[0]['effective_to'];

            $pdo->commit();

            respond(200, [
                'success' => true,
                'message' => 'Manufacturer selling price saved. Organisations with Price Change Protection enabled were processed automatically and receive private notices about their own price adjustments.',
                'effective_from' => $todayNairobi,
                'effective_to' => $cascadeEffectiveTo
            ]);
            exit;
            }
        }
    }

    $pdo->beginTransaction();

    // Lock the price history for this organisation, version and price type.
    $statement = $pdo->prepare(
        "SELECT price_id, price_amount, price_includes_vat, tax_category,
                effective_from, effective_to
         FROM product_prices
         WHERE organization_id = ?
           AND product_version_id = ?
           AND price_type = ?
         ORDER BY effective_from, price_id
         FOR UPDATE"
    );
    $statement->execute([$organizationId, $versionId, $priceType]);
    $history = $statement->fetchAll(PDO::FETCH_ASSOC);

    $sameStartRows = [];
    $crossingRows = [];
    $futureRows = [];

    foreach ($history as $row) {
        $rowStart = (string)$row['effective_from'];
        $rowEnd = $row['effective_to'] === null
            ? null
            : (string)$row['effective_to'];

        if ($rowStart === $fromDate) {
            $sameStartRows[] = $row;
        } elseif (
            $rowStart < $fromDate &&
            ($rowEnd === null || $rowEnd >= $fromDate)
        ) {
            $crossingRows[] = $row;
        } elseif ($rowStart > $fromDate) {
            $futureRows[] = $row;
        }
    }

    if (count($sameStartRows) > 1 || count($crossingRows) > 1) {
        $pdo->rollBack();
        respond_error(
            409,
            'The existing price history has multiple overlapping records around the requested start date. Resolve that history before saving another price.'
        );
    }

    $priceDecreaseOldAmount = null;

    if ($priceDecreaseNotificationOnly && $priceType === 'SELLING') {
        $oldEffectivePriceRow = $sameStartRows[0] ?? $crossingRows[0] ?? null;

        if (
            $oldEffectivePriceRow !== null &&
            (float)$oldEffectivePriceRow['price_amount'] > (float)$amount + 0.005
        ) {
            $priceDecreaseOldAmount = (float)$oldEffectivePriceRow['price_amount'];
        }
    }

    usort(
        $futureRows,
        fn(array $a, array $b): int =>
            strcmp((string)$a['effective_from'], (string)$b['effective_from'])
    );

    $nextFutureDate = isset($futureRows[0])
        ? (string)$futureRows[0]['effective_from']
        : null;

    // Explicit periods cannot overwrite a scheduled future price.
    if ($toDate !== null) {
        foreach ($futureRows as $futureRow) {
            if ((string)$futureRow['effective_from'] <= $toDate) {
                $pdo->rollBack();
                respond_error(
                    409,
                    'The requested end date overlaps a scheduled future price. Shorten the period or review the future price first.'
                );
            }
        }
        $effectiveTo = $toDate;
    } else {
        // An open-ended price stops immediately before the next scheduled price.
        $effectiveTo = $nextFutureDate !== null
            ? price_save_day_before($nextFutureDate)
            : null;
    }

    // If a temporary period is requested, preserve the old price afterwards.
    $tailSource = null;
    $tailStart = null;
    $tailEnd = null;

    if ($toDate !== null) {
        if (count($sameStartRows) === 1) {
            $tailSource = $sameStartRows[0];
        } elseif (count($crossingRows) === 1) {
            $tailSource = $crossingRows[0];
        }

        if ($tailSource !== null) {
            $tailStart = price_save_day_after($toDate);
            $tailEnd = $tailSource['effective_to'] === null
                ? null
                : (string)$tailSource['effective_to'];

            if (
                $nextFutureDate !== null &&
                ($tailEnd === null || $tailEnd >= $nextFutureDate)
            ) {
                $tailEnd = price_save_day_before($nextFutureDate);
            }

            if ($tailEnd !== null && $tailStart > $tailEnd) {
                $tailSource = null;
                $tailStart = null;
                $tailEnd = null;
            }
        }
    }

    // Close any older price that currently spans the new start date.
    if ($crossingRows !== []) {
        $closeDate = price_save_day_before($fromDate);
        $close = $pdo->prepare(
            "UPDATE product_prices
             SET effective_to = ?
             WHERE price_id = ?"
        );

        foreach ($crossingRows as $row) {
            $close->execute([$closeDate, (int)$row['price_id']]);
        }
    }

    if ($sameStartRows !== []) {
        // A repeated save for the same start date edits the existing entry.
        $row = $sameStartRows[0];

        $update = $pdo->prepare(
            "UPDATE product_prices
             SET price_amount = ?,
                 price_includes_vat = ?,
                 tax_category = ?,
                 effective_to = ?
             WHERE price_id = ?"
        );
        $update->execute([
            (float)$amount,
            $includesVat,
            $taxCategory,
            $effectiveTo,
            (int)$row['price_id']
        ]);
    } else {
        $insert = $pdo->prepare(
            "INSERT INTO product_prices
                (organization_id, product_id, product_version_id, price_type,
                 price_amount, price_includes_vat, tax_category,
                 effective_from, effective_to)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insert->execute([
            $organizationId,
            $productId,
            $versionId,
            $priceType,
            (float)$amount,
            $includesVat,
            $taxCategory,
            $fromDate,
            $effectiveTo
        ]);
    }

    // Restore the previous price after a temporary period, where applicable.
    if ($tailSource !== null && $tailStart !== null) {
        $tail = $pdo->prepare(
            "INSERT INTO product_prices
                (organization_id, product_id, product_version_id, price_type,
                 price_amount, price_includes_vat, tax_category,
                 effective_from, effective_to)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $tail->execute([
            $organizationId,
            $productId,
            $versionId,
            $priceType,
            (float)$tailSource['price_amount'],
            (int)$tailSource['price_includes_vat'],
            (string)$tailSource['tax_category'],
            $tailStart,
            $tailEnd
        ]);
    }

    $priceDecreaseNotifiedCount = 0;

    if (
        $priceDecreaseNotificationOnly &&
        $priceType === 'SELLING' &&
        $priceDecreaseOldAmount !== null
    ) {
        require_once __DIR__ . '/../core/notifications.php';

        $productStatement = $pdo->prepare(
            "SELECT p.product_name, pv.version_number
             FROM product_versions pv
             INNER JOIN products p ON p.product_id = pv.product_id
             WHERE pv.product_version_id = ?
             LIMIT 1"
        );
        $productStatement->execute([$versionId]);
        $productInfo = $productStatement->fetch(PDO::FETCH_ASSOC);

        if (!$productInfo) {
            throw new RuntimeException('Unable to identify the product for the price-decrease notice.');
        }

        // Match the cascade's routing rule: if active product-supplier
        // appointments exist, notify those direct purchasers only.
        $appointmentCount = $pdo->prepare(
            "SELECT COUNT(*)
             FROM product_suppliers ps
             INNER JOIN organizations supplier
                ON supplier.organization_id = ps.supplier_id
               AND supplier.status = 'ACTIVE'
             WHERE ps.product_id = ?
               AND ps.status = 'ACTIVE'
               AND ps.start_date <= ?
               AND (ps.end_date IS NULL OR ps.end_date >= ?)"
        );
        $appointmentCount->execute([$productId, $fromDate, $fromDate]);
        $hasProductSupplierAppointments = (int)$appointmentCount->fetchColumn() > 0;

        if ($hasProductSupplierAppointments) {
            $recipientsStatement = $pdo->prepare(
                "SELECT DISTINCT ps.supplier_id AS organization_id
                 FROM product_suppliers ps
                 INNER JOIN organizations supplier
                    ON supplier.organization_id = ps.supplier_id
                   AND supplier.status = 'ACTIVE'
                 INNER JOIN organization_relationships r
                    ON r.source_organization_id = ?
                   AND r.target_organization_id = ps.supplier_id
                   AND r.relationship_type = 'SUPPLIES'
                   AND r.status = 'ACTIVE'
                   AND r.start_date <= ?
                   AND (r.end_date IS NULL OR r.end_date >= ?)
                 WHERE ps.product_id = ?
                   AND ps.status = 'ACTIVE'
                   AND ps.start_date <= ?
                   AND (ps.end_date IS NULL OR ps.end_date >= ?)
                 ORDER BY ps.supplier_id"
            );
            $recipientsStatement->execute([
                $manufacturerId,
                $fromDate,
                $fromDate,
                $productId,
                $fromDate,
                $fromDate
            ]);
        } else {
            // If there are no effective product-supplier appointments,
            // only a directly supplied retailer can receive the notice.
            $recipientsStatement = $pdo->prepare(
                "SELECT DISTINCT target.organization_id
                 FROM organization_relationships r
                 INNER JOIN organizations target
                    ON target.organization_id = r.target_organization_id
                   AND target.organization_type = 'RETAILER'
                   AND target.status = 'ACTIVE'
                 WHERE r.source_organization_id = ?
                   AND r.relationship_type = 'SUPPLIES'
                   AND r.status = 'ACTIVE'
                   AND r.start_date <= ?
                   AND (r.end_date IS NULL OR r.end_date >= ?)
                 ORDER BY target.organization_id"
            );
            $recipientsStatement->execute([
                $manufacturerId,
                $fromDate,
                $fromDate
            ]);
        }

        $recipientIds = array_values(array_unique(array_map(
            'intval',
            $recipientsStatement->fetchAll(PDO::FETCH_COLUMN)
        )));

        $productName = (string)$productInfo['product_name'];
        $versionNumber = (int)$productInfo['version_number'];
        $oldAmountText = number_format($priceDecreaseOldAmount, 2, '.', '');
        $newAmountText = number_format((float)$amount, 2, '.', '');

        foreach ($recipientIds as $recipientId) {
            create_organization_notification(
                $pdo,
                $recipientId,
                'SUPPLY_CHANGE',
                (int)$versionId,
                "Manufacturer price decreased: {$productName}",
                "The direct manufacturer supply price for {$productName}, version {$versionNumber}, decreased from KSh {$oldAmountText} to KSh {$newAmountText}, effective {$fromDate}. No prices were automatically changed in your organisation. Review your own purchasing and selling prices and adjust them if appropriate."
            );

            $priceDecreaseNotifiedCount++;
        }
    }

    $periodDescription = $effectiveTo === null
        ? "from {$fromDate} with no scheduled end date"
        : "from {$fromDate} through {$effectiveTo}";

    record_audit(
        $pdo,
        $actor,
        'PRICE_SAVED',
        'PRODUCT',
        $versionId,
        "Saved {$priceType} price " .
        number_format((float)$amount, 2, '.', '') .
        " for {$periodDescription}"
    );

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => ($priceDecreaseNotificationOnly && $priceDecreaseOldAmount !== null)
            ? 'Manufacturer selling price decreased successfully. Direct purchasing organisations were notified where an active route exists; downstream prices were not changed automatically.'
            : 'Price saved successfully with non-overlapping effective dates.',
        'effective_from' => $fromDate,
        'effective_to' => $effectiveTo
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('price save', $e);
    respond_error(500, 'Unable to save price.');
}