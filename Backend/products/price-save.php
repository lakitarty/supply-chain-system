<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/price-confirmation.php';
require_once __DIR__ . '/pricing-rules.php';
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
        if ($amount === false || !is_finite((float)$amount) || $amount < 0) {
        respond_field_error(400, field_error('price_amount', 'Enter a valid amount.'));
    }
    $includesVat = !empty($input['price_includes_vat']) ? 1 : 0;
    $fromDate = trim((string)($input['effective_from'] ?? ''));
    $toDate = trim((string)($input['effective_to'] ?? '')) ?: null;

    if (!$versionId || $versionId < 1) {
        respond_field_error(400, field_error('product_version_id', 'Select a product version.'));
    }

    if (!in_array($priceType, ['COST', 'SELLING'], true)) {
        respond_field_error(400, field_error('price_type', 'Invalid price type.'));
    }

        if ($amount === null) {
        respond_field_error(400, field_error('price_amount', 'Enter an amount above zero with at most two decimal places.'));
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
       $taxCategory = $statement->fetchColumn();

    if ($taxCategory === false) {
        respond_error(409, 'No tax category has been declared for this product version on the selected date. The manufacturer must declare it before prices can be saved.');
    }

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

    $automaticCascadeEligible =
        $ownsProduct &&
        !$hasScheduledManufacturerSellingPeriod &&
        $priceType === 'SELLING' &&
        $fromDate === $todayNairobi &&
        $toDate === null &&
        (float)$amount > 0;

    $priceDecreaseNotificationOnly = false;

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
        $effectiveTo = $nextFutureDate !== null
            ? price_save_day_before($nextFutureDate)
            : null;
    }

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

        $supplierDecreaseAdjustments = [];

        if ($hasProductSupplierAppointments) {
            foreach ($recipientIds as $recipientId) {
                $recipientId = (int)$recipientId;
                $supplierDecreaseAdjustments[$recipientId] = [
                    'applied' => false,
                    'reason' => 'No automatic adjustment was made. Review your own pricing records and any scheduled commercial periods.'
                ];

                $recipientTypeStatement = $pdo->prepare(
                    "SELECT organization_type
                     FROM organizations
                     WHERE organization_id = ? AND status = 'ACTIVE'
                     LIMIT 1
                     FOR UPDATE"
                );
                $recipientTypeStatement->execute([$recipientId]);
                $recipientType = $recipientTypeStatement->fetchColumn();

                if (!in_array($recipientType, ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
                    $supplierDecreaseAdjustments[$recipientId]['reason'] =
                        'Automatic adjustment was skipped because the direct recipient is not an active supplier organisation.';
                    continue;
                }

                $protectionStatement = $pdo->prepare(
                    'SELECT enabled
                     FROM organization_price_protection
                     WHERE organization_id = ?
                     LIMIT 1
                     FOR UPDATE'
                );
                $protectionStatement->execute([$recipientId]);
                $protectionValue = $protectionStatement->fetchColumn();

                if ($protectionValue === false || (int)$protectionValue !== 1) {
                    $supplierDecreaseAdjustments[$recipientId]['reason'] =
                        'Automatic adjustment was skipped because Price Change Protection is not enabled for your organisation.';
                    continue;
                }

                $promotionStatement = $pdo->prepare(
                    "SELECT pr.promotion_id
                     FROM promotions pr
                     INNER JOIN promotion_products pp
                        ON pp.promotion_id = pr.promotion_id
                     LEFT JOIN promotion_participants participant
                        ON participant.promotion_id = pr.promotion_id
                       AND participant.organization_id = ?
                       AND participant.status IN ('PENDING', 'ACCEPTED', 'ACTIVE')
                     WHERE pp.product_id = ?
                       AND pr.status IN ('SUBMITTED', 'APPROVED', 'SCHEDULED', 'ACTIVE')
                       AND pr.end_date >= ?
                       AND (
                           pr.organization_id = ?
                           OR participant.organization_id IS NOT NULL
                       )
                     LIMIT 1
                     FOR UPDATE"
                );
                $promotionStatement->execute([
                    $recipientId,
                    $productId,
                    $fromDate,
                    $recipientId
                ]);

                if ($promotionStatement->fetchColumn() !== false) {
                    $supplierDecreaseAdjustments[$recipientId]['reason'] =
                        'Automatic adjustment was skipped because a promotion involving this product requires protection or review.';
                    continue;
                }

                $offerStatement = $pdo->prepare(
                    "SELECT supplier_product_offer_id
                     FROM supplier_product_offers
                     WHERE supplier_id = ?
                       AND product_id = ?
                       AND product_version_id = ?
                       AND status = 'ACTIVE'
                       AND (
                           effective_from > ?
                           OR (
                               effective_from <= ?
                               AND (effective_to IS NULL OR effective_to >= ?)
                           )
                       )
                     LIMIT 1
                     FOR UPDATE"
                );
                $offerStatement->execute([
                    $recipientId,
                    $productId,
                    $versionId,
                    $fromDate,
                    $fromDate,
                    $fromDate
                ]);

                if ($offerStatement->fetchColumn() !== false) {
                    $supplierDecreaseAdjustments[$recipientId]['reason'] =
                        'Automatic adjustment was skipped because a supplier offer is current or scheduled for this product.';
                    continue;
                }

                $supplierPriceStatement = $pdo->prepare(
                    "SELECT price_id, organization_id, product_id, product_version_id,
                            price_type, price_amount, price_includes_vat, tax_category,
                            effective_from, effective_to
                     FROM product_prices
                     WHERE organization_id = ?
                       AND product_id = ?
                       AND product_version_id = ?
                       AND price_type IN ('COST', 'SELLING')
                     ORDER BY price_type, effective_from, price_id
                     FOR UPDATE"
                );
                $supplierPriceStatement->execute([$recipientId, $productId, $versionId]);
                $supplierPriceRows = $supplierPriceStatement->fetchAll(PDO::FETCH_ASSOC);

                $hasScheduledSupplierPeriod = false;
                $currentSupplierPrices = ['COST' => [], 'SELLING' => []];

                foreach ($supplierPriceRows as $supplierPriceRow) {
                    $rowStart = (string)$supplierPriceRow['effective_from'];
                    $rowEnd = $supplierPriceRow['effective_to'] === null
                        ? null
                        : (string)$supplierPriceRow['effective_to'];
                    $rowType = (string)$supplierPriceRow['price_type'];

                    if (
                        $rowStart > $fromDate ||
                        ($rowStart <= $fromDate && $rowEnd !== null && $rowEnd >= $fromDate)
                    ) {
                        $hasScheduledSupplierPeriod = true;
                    }

                    if (
                        $rowStart <= $fromDate &&
                        ($rowEnd === null || $rowEnd >= $fromDate) &&
                        isset($currentSupplierPrices[$rowType])
                    ) {
                        $currentSupplierPrices[$rowType][] = $supplierPriceRow;
                    }
                }

                if ($hasScheduledSupplierPeriod) {
                    $supplierDecreaseAdjustments[$recipientId]['reason'] =
                        'Automatic adjustment was skipped because a scheduled or time-limited price period requires review.';
                    continue;
                }

                if (
                    count($currentSupplierPrices['COST']) !== 1 ||
                    count($currentSupplierPrices['SELLING']) !== 1
                ) {
                    $supplierDecreaseAdjustments[$recipientId]['reason'] =
                        'Automatic adjustment was skipped because the current cost and selling-price records need review.';
                    continue;
                }

                $supplierCostRow = $currentSupplierPrices['COST'][0];
                $oldSupplierCost = (float)$supplierCostRow['price_amount'];
                $newSupplierCost = round((float)$amount, 2);

                if ($newSupplierCost >= $oldSupplierCost - 0.005) {
                    $supplierDecreaseAdjustments[$recipientId]['reason'] =
                  'Automatic adjustment was skipped because the new manufacturer price is not lower than the current recorded cost of your organisation. Review the price records before syncing.';continue;
                }

                $writeSupplierCost = function (array $priceRow, float $newPrice) use ($pdo, $fromDate): void {
                    $priceId = (int)$priceRow['price_id'];
                    $rowStart = (string)$priceRow['effective_from'];

                    if ($rowStart === $fromDate) {
                        $update = $pdo->prepare(
                            'UPDATE product_prices SET price_amount = ? WHERE price_id = ?'
                        );
                        $update->execute([$newPrice, $priceId]);
                        return;
                    }

                    $close = $pdo->prepare(
                        'UPDATE product_prices SET effective_to = ? WHERE price_id = ?'
                    );
                    $close->execute([price_save_day_before($fromDate), $priceId]);

                    $insert = $pdo->prepare(
                        'INSERT INTO product_prices
                            (organization_id, product_id, product_version_id, price_type,
                             price_amount, price_includes_vat, tax_category,
                             effective_from, effective_to)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)'
                    );
                    $insert->execute([
                        (int)$priceRow['organization_id'],
                        (int)$priceRow['product_id'],
                        (int)$priceRow['product_version_id'],
                        (string)$priceRow['price_type'],
                        $newPrice,
                        (int)$priceRow['price_includes_vat'],
                        (string)$priceRow['tax_category'],
                        $fromDate
                    ]);
                };  
            
        
                $writeSupplierCost($supplierCostRow, $newSupplierCost);

                $supplierDecreaseAdjustments[$recipientId] = [
                    'applied' => true,
                    'old_cost' => $oldSupplierCost,
                    'new_cost' => $newSupplierCost
                ];

                record_audit(
                    $pdo,
                    $actor,
                    'SUPPLIER_PRICE_DECREASE_APPLIED',
                    'PRODUCT_VERSION',
                    (int)$versionId,
                    "Reduced the direct supplier's COST price for product version {$versionId} after a manufacturer selling-price decrease. The supplier's SELLING price, retailer prices, and branch prices were left unchanged."
                );
            }
        }

        $productName = (string)$productInfo['product_name'];
        $versionNumber = (int)$productInfo['version_number'];
        $oldAmountText = number_format($priceDecreaseOldAmount, 2, '.', '');
        $newAmountText = number_format((float)$amount, 2, '.', '');

        foreach ($recipientIds as $recipientId) {
            $adjustment = $supplierDecreaseAdjustments[(int)$recipientId] ?? null;

            if ($adjustment !== null && !empty($adjustment['applied'])) {
                $ownOldCost = number_format((float)$adjustment['old_cost'], 2, '.', '');
                $ownNewCost = number_format((float)$adjustment['new_cost'], 2, '.', '');

                create_organization_notification(
                    $pdo,
                    (int)$recipientId,
                    'SUPPLY_CHANGE',
                    (int)$versionId,
                    "Supplier cost price reduced: {$productName}",
                    "Your cost price for {$productName}, version {$versionNumber}, was reduced from KSh {$ownOldCost} to KSh {$ownNewCost}, effective {$fromDate}. This adjustment changed your COST price only. Your SELLING price was left unchanged; no retailer or branch prices were changed automatically."
                );
            } else {
                $skipReason = is_array($adjustment) && !empty($adjustment['reason'])
                    ? ' ' . (string)$adjustment['reason']
                    : ' No prices were automatically changed in your organisation. Review your own purchasing and selling prices and adjust them if appropriate.';

                create_organization_notification(
                    $pdo,
                    (int)$recipientId,
                    'SUPPLY_CHANGE',
                    (int)$versionId,
                    "Manufacturer price decreased: {$productName}",
                    "The direct manufacturer supply price for {$productName}, version {$versionNumber}, decreased from KSh {$oldAmountText} to KSh {$newAmountText}, effective {$fromDate}.{$skipReason}"
                );
            }

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
