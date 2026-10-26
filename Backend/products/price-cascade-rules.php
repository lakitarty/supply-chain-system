<?php
declare(strict_types=1);

function price_cascade_fail(int $status, string $message): never
{
    throw new DomainException($message, $status);
}

function price_cascade_current_price(
    PDO $pdo,
    int $organizationId,
    int $productId,
    int $versionId,
    string $priceType,
    string $effectiveDate,
    bool $lockRows = false
): array {
    $sql = "
        SELECT *
        FROM product_prices
        WHERE organization_id = ?
          AND product_id = ?
          AND product_version_id = ?
          AND price_type = ?
          AND effective_from <= ?
          AND (effective_to IS NULL OR effective_to >= ?)
        ORDER BY effective_from DESC, price_id DESC
        LIMIT 2
    ";

    if ($lockRows) {
        $sql .= " FOR UPDATE";
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([
        $organizationId,
        $productId,
        $versionId,
        $priceType,
        $effectiveDate,
        $effectiveDate
    ]);

    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {
        price_cascade_fail(
            409,
            "Missing effective {$priceType} price for organisation {$organizationId}. Complete its product pricing before applying this cascade."
        );
    }

    if (count($rows) > 1) {
        price_cascade_fail(
            409,
            "Overlapping active {$priceType} prices exist for organisation {$organizationId}. Resolve the price history before continuing."
        );
    }

    return $rows[0];
}

function price_cascade_margin(array $costRow, array $sellingRow, string $label): float
{
    if (
        (int)$costRow['price_includes_vat'] !== 1 ||
        (int)$sellingRow['price_includes_vat'] !== 1
    ) {
        price_cascade_fail(
            409,
            "{$label} must have VAT-inclusive cost and selling prices before this cascade can calculate its margin."
        );
    }

    if ($costRow['tax_category'] !== $sellingRow['tax_category']) {
        price_cascade_fail(
            409,
            "{$label} cost and selling prices use different tax categories. Review the prices before cascading."
        );
    }

    $cost = (float)$costRow['price_amount'];
    $selling = (float)$sellingRow['price_amount'];

    if ($selling <= 0 || $cost < 0 || $cost >= $selling) {
        price_cascade_fail(
            409,
            "{$label} does not have a valid positive gross margin to preserve."
        );
    }

    return ($selling - $cost) / $selling;
}

function price_cascade_margin_for_values(float $cost, float $selling): float
{
    if ($selling <= 0 || $cost < 0 || $cost >= $selling) {
        price_cascade_fail(409, 'A proposed price would not preserve a valid positive gross margin.');
    }

    return ($selling - $cost) / $selling;
}

function price_cascade_snapshot_price(array &$snapshot, array $row): void
{
    $snapshot['prices'][] = [
        'price_id' => (int)$row['price_id'],
        'organization_id' => (int)$row['organization_id'],
        'product_id' => (int)$row['product_id'],
        'product_version_id' => (int)$row['product_version_id'],
        'price_type' => (string)$row['price_type'],
        'price_amount' => (string)$row['price_amount'],
        'price_includes_vat' => (int)$row['price_includes_vat'],
        'tax_category' => (string)$row['tax_category'],
        'effective_from' => (string)$row['effective_from'],
        'effective_to' => $row['effective_to'] === null ? null : (string)$row['effective_to']
    ];
}

function price_cascade_snapshot_write(
    array &$writes,
    array $row,
    float $newAmount
): void {
    $newAmount = round($newAmount, 2);

    if (abs((float)$row['price_amount'] - $newAmount) < 0.005) {
        return;
    }

    $writes[] = [
        'row' => $row,
        'new_amount' => $newAmount
    ];
}

function price_cascade_connection_rows(
    PDO $pdo,
    int $sourceOrganizationId,
    int $targetOrganizationId,
    string $effectiveDate,
    bool $lockRows = false
): array {
    $sql = "
        SELECT r.relationship_id
        FROM organization_relationships r
        INNER JOIN organizations target
            ON target.organization_id = r.target_organization_id
        WHERE r.source_organization_id = ?
          AND r.target_organization_id = ?
          AND r.relationship_type = 'SUPPLIES'
          AND r.status = 'ACTIVE'
          AND r.start_date <= ?
          AND (r.end_date IS NULL OR r.end_date >= ?)
          AND target.status = 'ACTIVE'
        ORDER BY r.relationship_id
    ";

    if ($lockRows) {
        $sql .= " FOR UPDATE";
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([
        $sourceOrganizationId,
        $targetOrganizationId,
        $effectiveDate,
        $effectiveDate
    ]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function price_cascade_downstream_retailers(
    PDO $pdo,
    int $sourceOrganizationId,
    string $effectiveDate,
    bool $lockRows = false
): array {
    $sql = "
        SELECT r.relationship_id,
               target.organization_id,
               target.organization_name,
               target.organization_type
        FROM organization_relationships r
        INNER JOIN organizations target
            ON target.organization_id = r.target_organization_id
        WHERE r.source_organization_id = ?
          AND r.relationship_type = 'SUPPLIES'
          AND r.status = 'ACTIVE'
          AND r.start_date <= ?
          AND (r.end_date IS NULL OR r.end_date >= ?)
          AND target.organization_type = 'RETAILER'
          AND target.status = 'ACTIVE'
        ORDER BY target.organization_id, r.relationship_id
    ";

    if ($lockRows) {
        $sql .= " FOR UPDATE";
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([
        $sourceOrganizationId,
        $effectiveDate,
        $effectiveDate
    ]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function price_cascade_round_retail_price(float $cost, float $margin): array
{
    if ($margin <= 0 || $margin >= 1) {
        price_cascade_fail(409, 'The retailer margin is not valid for price rounding.');
    }

    $rawPrice = $cost / (1 - $margin);

    if (!is_finite($rawPrice) || $rawPrice <= 0) {
        price_cascade_fail(409, 'The proposed retail selling price is invalid.');
    }

    // Round upward so rounding does not reduce the target gross margin.
    $multipleOfFive = ceil(($rawPrice - 0.0000001) / 5) * 5;

    while ($multipleOfFive + 0.0000001 < $rawPrice) {
        $multipleOfFive += 5;
    }

    $endsIn99 = ceil(($rawPrice + 0.01 - 0.0000001) / 10) * 10 - 0.01;

    while ($endsIn99 + 0.0000001 < $rawPrice) {
        $endsIn99 += 10;
    }

    $multipleOfFive = round($multipleOfFive, 2);
    $endsIn99 = round($endsIn99, 2);

    $extraFive = $multipleOfFive - $rawPrice;
    $extra99 = $endsIn99 - $rawPrice;

    if ($extraFive <= $extra99) {
        $price = $multipleOfFive;
        $rule = 'MULTIPLE_OF_5';
    } else {
        $price = $endsIn99;
        $rule = 'ENDS_IN_99';
    }

    return [
        'price' => round($price, 2),
        'rounding_rule' => $rule,
        'raw_price' => round($rawPrice, 4),
        'margin_after' => ($price - $cost) / $price
    ];
}

function price_cascade_vat_factor(
    PDO $pdo,
    int $manufacturerId,
    int $versionId,
    string $effectiveDate,
    bool $lockRows = false
): float {
    $classificationSql = "
        SELECT tax_category
        FROM product_tax_classifications
        WHERE product_version_id = ?
          AND manufacturer_id = ?
          AND effective_from <= ?
          AND (effective_to IS NULL OR effective_to >= ?)
        ORDER BY effective_from DESC, product_tax_classification_id DESC
        LIMIT 2
    ";

    if ($lockRows) {
        $classificationSql .= " FOR UPDATE";
    }

    $classification = $pdo->prepare($classificationSql);
    $classification->execute([
        $versionId,
        $manufacturerId,
        $effectiveDate,
        $effectiveDate
    ]);

    $classifications = $classification->fetchAll(PDO::FETCH_ASSOC);

    if (count($classifications) !== 1) {
        price_cascade_fail(
            409,
            'A unique effective tax classification is required to update VAT-exclusive branch prices.'
        );
    }

    $taxCategory = (string)$classifications[0]['tax_category'];

    $rateSql = "
        SELECT rate_percent, taxable_supply
        FROM tax_rate_schedule
        WHERE tax_category = ?
          AND effective_from <= ?
          AND (effective_to IS NULL OR effective_to >= ?)
        ORDER BY effective_from DESC
        LIMIT 2
    ";

    if ($lockRows) {
        $rateSql .= " FOR UPDATE";
    }

    $rateStatement = $pdo->prepare($rateSql);
    $rateStatement->execute([
        $taxCategory,
        $effectiveDate,
        $effectiveDate
    ]);

    $rates = $rateStatement->fetchAll(PDO::FETCH_ASSOC);

    if (count($rates) !== 1) {
        price_cascade_fail(
            409,
            'A unique effective tax rate is required to update VAT-exclusive branch prices.'
        );
    }

    $rate = (float)$rates[0]['rate_percent'];
    $taxable = (int)$rates[0]['taxable_supply'] === 1;

    $factor = $taxable ? 1 + ($rate / 100) : 1.0;

    if ($factor <= 0) {
        price_cascade_fail(409, 'The effective VAT conversion factor is invalid.');
    }

    return $factor;
}

function price_cascade_protection_enabled(
    PDO $pdo,
    int $organizationId,
    bool $lockRow = false
): bool {
    $sql = "
        SELECT enabled
        FROM organization_price_protection
        WHERE organization_id = ?
        LIMIT 1
    ";

    if ($lockRow) {
        $sql .= " FOR UPDATE";
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([$organizationId]);
    $enabled = $statement->fetchColumn();

    return $enabled !== false && (int)$enabled === 1;
}
function price_cascade_has_scheduled_org_price_period(
    PDO $pdo,
    int $organizationId,
    int $productId,
    int $versionId,
    string $effectiveDate,
    bool $lockRows = false
): bool {
    $sql = "
        SELECT price_id
        FROM product_prices
        WHERE organization_id = ?
          AND product_id = ?
          AND product_version_id = ?
          AND price_type IN ('COST', 'SELLING')
          AND (
              effective_from > ?
              OR (
                  effective_from <= ?
                  AND effective_to >= ?
              )
          )
        LIMIT 1
    ";

    if ($lockRows) {
        $sql .= " FOR UPDATE";
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([
        $organizationId,
        $productId,
        $versionId,
        $effectiveDate,
        $effectiveDate,
        $effectiveDate
    ]);

    return $statement->fetchColumn() !== false;
}

function price_cascade_has_scheduled_branch_price_period(
    PDO $pdo,
    int $retailerId,
    int $productId,
    int $versionId,
    string $effectiveDate,
    bool $lockRows = false
): bool {
    $sql = "
        SELECT retailer_branch_price_id
        FROM retailer_branch_prices
        WHERE retailer_id = ?
          AND product_id = ?
          AND product_version_id = ?
          AND status = 'ACTIVE'
          AND (
              effective_from > ?
              OR (
                  effective_from <= ?
                  AND effective_to >= ?
              )
          )
        LIMIT 1
    ";

    if ($lockRows) {
        $sql .= " FOR UPDATE";
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([
        $retailerId,
        $productId,
        $versionId,
        $effectiveDate,
        $effectiveDate,
        $effectiveDate
    ]);

    return $statement->fetchColumn() !== false;
}
function price_cascade_has_scheduled_manufacturer_selling_period(
    PDO $pdo,
    int $manufacturerId,
    int $productId,
    int $versionId,
    string $effectiveDate,
    bool $lockRows = false
): bool {
    $sql = "
        SELECT price_id
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
        LIMIT 1
    ";

    if ($lockRows) {
        $sql .= " FOR UPDATE";
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([
        $manufacturerId,
        $productId,
        $versionId,
        $effectiveDate,
        $effectiveDate,
        $effectiveDate
    ]);

    return $statement->fetchColumn() !== false;
}
function price_cascade_record_review(
    array &$snapshot,
    array &$reviewNotifications,
    int $organizationId,
    string $reasonCode
): void {
    foreach ($reviewNotifications as $existing) {
        if ((int)$existing['organization_id'] === $organizationId) {
            return;
        }
    }

    $entry = [
        'organization_id' => $organizationId,
        'reason_code' => $reasonCode
    ];

    $reviewNotifications[] = $entry;

    if (!isset($snapshot['review_notifications'])) {
        $snapshot['review_notifications'] = [];
    }

    $snapshot['review_notifications'][] = $entry;
}
function price_cascade_build_plan(
    PDO $pdo,
    array $actor,
    int $versionId,
    float $requestedManufacturerPrice,
    bool $lockRows = false
): array {
    if (($actor['role'] ?? '') !== 'ADMIN') {
        price_cascade_fail(403, 'Only an organisation administrator can apply a manufacturer price change.');
    }

    if (!in_array(
        (string)($actor['organization_type'] ?? ''),
        ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'],
        true
    )) {
        price_cascade_fail(403, 'This account is not authorised to change manufacturer prices.');
    }

    if ($versionId < 1 || !is_finite($requestedManufacturerPrice) || $requestedManufacturerPrice <= 0) {
        price_cascade_fail(400, 'Choose a current product version and enter a positive manufacturer price.');
    }

    $newManufacturerPrice = round($requestedManufacturerPrice, 2);
    $effectiveDate = (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');

    $productSql = "
        SELECT p.product_id, p.product_name, p.manufacturer_id, p.status AS product_status,
               pv.product_version_id, pv.version_number, pv.is_current,
               manufacturer.organization_name AS manufacturer_name,
               manufacturer.organization_type AS manufacturer_type,
               manufacturer.status AS manufacturer_status
        FROM product_versions pv
        INNER JOIN products p ON p.product_id = pv.product_id
        INNER JOIN organizations manufacturer
            ON manufacturer.organization_id = p.manufacturer_id
        WHERE pv.product_version_id = ?
        LIMIT 1
    ";

    if ($lockRows) {
        $productSql .= " FOR UPDATE";
    }

    $productStatement = $pdo->prepare($productSql);
    $productStatement->execute([$versionId]);
    $product = $productStatement->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        price_cascade_fail(404, 'The selected product version was not found.');
    }

    if (
        (int)$product['manufacturer_id'] !== (int)$actor['organization_id'] ||
        (string)$product['manufacturer_type'] !== (string)$actor['organization_type']
    ) {
        price_cascade_fail(403, 'You can only change the price of a product owned by your organisation.');
    }

    if ((int)$product['is_current'] !== 1) {
        price_cascade_fail(409, 'Only the current product version can receive a new manufacturer price.');
    }

    if (
        (string)$product['product_status'] !== 'ACTIVE' ||
        (string)$product['manufacturer_status'] !== 'ACTIVE'
    ) {
        price_cascade_fail(409, 'The product and its manufacturer must be active.');
    }

    $productId = (int)$product['product_id'];
    $manufacturerId = (int)$product['manufacturer_id'];

    $snapshot = [
        'actor_organization_id' => (int)$actor['organization_id'],
        'product_id' => $productId,
        'version_id' => $versionId,
        'effective_date' => $effectiveDate,
        'manufacturer_new_price' => number_format($newManufacturerPrice, 2, '.', ''),
        'assignments' => [],
        'relationships' => [],
        'prices' => [],
        'branch_prices' => [],
        'retailer_products' => [],
        'tax_factors' => [],
        'protection_preferences' => []
    ];

    $mfgCost = price_cascade_current_price(
        $pdo, $manufacturerId, $productId, $versionId, 'COST', $effectiveDate, $lockRows
    );

    $mfgSelling = price_cascade_current_price(
        $pdo, $manufacturerId, $productId, $versionId, 'SELLING', $effectiveDate, $lockRows
    );

    if (price_cascade_has_scheduled_manufacturer_selling_period(
        $pdo, $manufacturerId, $productId, $versionId, $effectiveDate, $lockRows
    )) {
        price_cascade_fail(
            409,
            'The manufacturer has a scheduled selling-price period. Review the price history before running a cascade.'
        );
    }

    if ($newManufacturerPrice < (float)$mfgSelling['price_amount'] - 0.005) {
        price_cascade_fail(
            409,
            'Manufacturer price decreases are notification-only. Save the lower selling price from the catalogue; direct purchasing organisations will be notified, and downstream prices will not be changed automatically.'
        );
    }

    price_cascade_snapshot_price($snapshot, $mfgCost);
    price_cascade_snapshot_price($snapshot, $mfgSelling);

    price_cascade_margin($mfgCost, $mfgSelling, (string)$product['manufacturer_name']);

    if (abs((float)$mfgSelling['price_amount'] - $newManufacturerPrice) < 0.005) {
        price_cascade_fail(400, 'The proposed manufacturer price is the same as the current price.');
    }

    $organizationChanges = [];
    $productPriceWrites = [];
    $branchPriceWrites = [];
    $branchChanges = [];
    $warnings = [];

    $mfgOldMargin = price_cascade_margin($mfgCost, $mfgSelling, (string)$product['manufacturer_name']);
    $mfgNewMargin = price_cascade_margin_for_values(
        (float)$mfgCost['price_amount'],
        $newManufacturerPrice
    );

    $organizationChanges[$manufacturerId] = [
        'organization_id' => $manufacturerId,
        'organization_name' => (string)$product['manufacturer_name'],
        'organization_type' => (string)$product['manufacturer_type'],
        'old_cost_price' => (float)$mfgCost['price_amount'],
        'new_cost_price' => (float)$mfgCost['price_amount'],
        'old_selling_price' => (float)$mfgSelling['price_amount'],
        'new_selling_price' => $newManufacturerPrice,
        'gross_margin_before_percent' => round($mfgOldMargin * 100, 2),
        'gross_margin_after_percent' => round($mfgNewMargin * 100, 2),
        'rounding_rule' => null,
        'branch_prices_found' => 0
    ];

    price_cascade_snapshot_write($productPriceWrites, $mfgSelling, $newManufacturerPrice);

    // Only product-specific suppliers with an active manufacturer-to-supplier
    // SUPPLIES relationship are eligible for this product's price cascade.
    $assignmentSql = "
        SELECT ps.product_supplier_id, ps.supplier_id, ps.area_id,
               ps.start_date, ps.end_date,
               o.organization_name, o.organization_type
        FROM product_suppliers ps
        INNER JOIN organizations o ON o.organization_id = ps.supplier_id
        WHERE ps.product_id = ?
          AND ps.status = 'ACTIVE'
          AND ps.start_date <= ?
          AND (ps.end_date IS NULL OR ps.end_date >= ?)
          AND o.status = 'ACTIVE'
        ORDER BY ps.supplier_id, ps.product_supplier_id
    ";

    if ($lockRows) {
        $assignmentSql .= " FOR UPDATE";
    }

    $assignmentStatement = $pdo->prepare($assignmentSql);
    $assignmentStatement->execute([$productId, $effectiveDate, $effectiveDate]);
    $assignmentRows = $assignmentStatement->fetchAll(PDO::FETCH_ASSOC);

    $supplierMap = [];

    foreach ($assignmentRows as $assignment) {
        $assignmentId = (int)$assignment['product_supplier_id'];
        $supplierId = (int)$assignment['supplier_id'];

        $snapshot['assignments'][] = [
            'product_supplier_id' => $assignmentId,
            'supplier_id' => $supplierId,
            'area_id' => $assignment['area_id'] === null ? null : (int)$assignment['area_id'],
            'start_date' => (string)$assignment['start_date'],
            'end_date' => $assignment['end_date'] === null ? null : (string)$assignment['end_date']
        ];

        if (!isset($supplierMap[$supplierId])) {
            $supplierMap[$supplierId] = [
                'organization_id' => $supplierId,
                'organization_name' => (string)$assignment['organization_name'],
                'organization_type' => (string)$assignment['organization_type'],
                'assignment_ids' => [],
                'relationship_ids' => []
            ];
        }

        $supplierMap[$supplierId]['assignment_ids'][] = $assignmentId;
    }

    ksort($supplierMap);

    foreach ($supplierMap as $supplierId => &$supplier) {
        $relationshipRows = price_cascade_connection_rows(
            $pdo, $manufacturerId, (int)$supplierId, $effectiveDate, $lockRows
        );

        if (!$relationshipRows) {
            price_cascade_fail(
                409,
                "The active product-supplier assignment for {$supplier['organization_name']} has no active manufacturer supply relationship. Resolve the relationship before cascading."
            );
        }

        foreach ($relationshipRows as $relationshipRow) {
            $relationshipId = (int)$relationshipRow['relationship_id'];
            $supplier['relationship_ids'][] = $relationshipId;

            $snapshot['relationships'][] = [
                'relationship_id' => $relationshipId,
                'source_organization_id' => $manufacturerId,
                'target_organization_id' => (int)$supplierId
            ];
        }

        sort($supplier['assignment_ids']);
        sort($supplier['relationship_ids']);
    }
    unset($supplier);

    $retailerIncoming = [];
    $blockedRetailers = [];
    $reviewNotifications = [];

    foreach ($supplierMap as $supplierId => $supplier) {
        $supplierProtectionEnabled = price_cascade_protection_enabled(
            $pdo,
            (int)$supplierId,
            $lockRows
        );

        $snapshot['protection_preferences'][] = [
            'organization_id' => (int)$supplierId,
            'enabled' => $supplierProtectionEnabled
        ];

        if (!$supplierProtectionEnabled) {
            $blockedRows = price_cascade_downstream_retailers(
                $pdo, (int)$supplierId, $effectiveDate, $lockRows
            );
            foreach ($blockedRows as $blockedRow) {
                $blockedRetailers[(int)$blockedRow['organization_id']] = true;
            }

            $warnings[] = 'Some downstream organisations have not enabled Price Change Protection; their prices and dependent routes were not changed.';
            continue;
        }

        if (price_cascade_has_scheduled_org_price_period(
            $pdo, (int)$supplierId, $productId, $versionId, $effectiveDate, $lockRows
        )) {
            price_cascade_record_review(
                $snapshot, $reviewNotifications, (int)$supplierId, 'SCHEDULE_REVIEW'
            );

            $blockedRows = price_cascade_downstream_retailers(
                $pdo, (int)$supplierId, $effectiveDate, $lockRows
            );
            foreach ($blockedRows as $blockedRow) {
                $blockedRetailers[(int)$blockedRow['organization_id']] = true;
            }

            $warnings[] = 'Some downstream organisations have scheduled price periods requiring review; those organisations and dependent routes were not changed.';
            continue;
        }

        try {
            $supplierCost = price_cascade_current_price(
                $pdo, (int)$supplierId, $productId, $versionId, 'COST', $effectiveDate, $lockRows
            );

            $supplierSelling = price_cascade_current_price(
                $pdo, (int)$supplierId, $productId, $versionId, 'SELLING', $effectiveDate, $lockRows
            );

            $supplierMargin = price_cascade_margin(
                $supplierCost,
                $supplierSelling,
                (string)$supplier['organization_name']
            );
        } catch (DomainException $e) {
            price_cascade_record_review(
                $snapshot, $reviewNotifications, (int)$supplierId, 'PRICING_REVIEW'
            );

            $blockedRows = price_cascade_downstream_retailers(
                $pdo, (int)$supplierId, $effectiveDate, $lockRows
            );
            foreach ($blockedRows as $blockedRow) {
                $blockedRetailers[(int)$blockedRow['organization_id']] = true;
            }

            $warnings[] = 'Some opted-in downstream organisations have incomplete or inconsistent pricing; those organisations and dependent routes were not changed.';
            continue;
        }

        price_cascade_snapshot_price($snapshot, $supplierCost);
        price_cascade_snapshot_price($snapshot, $supplierSelling);

        $newSupplierCost = $newManufacturerPrice;
        $newSupplierSelling = round($newSupplierCost / (1 - $supplierMargin), 2);

        $organizationChanges[(int)$supplierId] = [
            'organization_id' => (int)$supplierId,
            'organization_name' => (string)$supplier['organization_name'],
            'organization_type' => (string)$supplier['organization_type'],
            'old_cost_price' => (float)$supplierCost['price_amount'],
            'new_cost_price' => $newSupplierCost,
            'old_selling_price' => (float)$supplierSelling['price_amount'],
            'new_selling_price' => $newSupplierSelling,
            'gross_margin_before_percent' => round($supplierMargin * 100, 2),
            'gross_margin_after_percent' => round(
                price_cascade_margin_for_values($newSupplierCost, $newSupplierSelling) * 100,
                2
            ),
            'rounding_rule' => null,
            'branch_prices_found' => 0
        ];

        price_cascade_snapshot_write($productPriceWrites, $supplierCost, $newSupplierCost);
        price_cascade_snapshot_write($productPriceWrites, $supplierSelling, $newSupplierSelling);

        $retailerRows = price_cascade_downstream_retailers(
            $pdo, (int)$supplierId, $effectiveDate, $lockRows
        );

        foreach ($retailerRows as $retailerRow) {
            $retailerId = (int)$retailerRow['organization_id'];
            $relationshipId = (int)$retailerRow['relationship_id'];

            $snapshot['relationships'][] = [
                'relationship_id' => $relationshipId,
                'source_organization_id' => (int)$supplierId,
                'target_organization_id' => $retailerId
            ];

            if (!isset($retailerIncoming[$retailerId])) {
                $retailerIncoming[$retailerId] = [
                    'organization_id' => $retailerId,
                    'organization_name' => (string)$retailerRow['organization_name'],
                    'organization_type' => (string)$retailerRow['organization_type'],
                    'incoming_cost' => $newSupplierSelling,
                    'source_organizations' => [],
                    'relationship_ids' => []
                ];
            }

            $retailerIncoming[$retailerId]['source_organizations'][(int)$supplierId] = true;
            $retailerIncoming[$retailerId]['relationship_ids'][] = $relationshipId;
        }
    }

    // If the product has no product-specific supplier assignment, an active
    // direct manufacturer-to-retailer relationship can be used instead.
    if (count($supplierMap) === 0) {
        $directRetailers = price_cascade_downstream_retailers(
            $pdo, $manufacturerId, $effectiveDate, $lockRows
        );

        foreach ($directRetailers as $retailerRow) {
            $retailerId = (int)$retailerRow['organization_id'];
            $relationshipId = (int)$retailerRow['relationship_id'];

            $snapshot['relationships'][] = [
                'relationship_id' => $relationshipId,
                'source_organization_id' => $manufacturerId,
                'target_organization_id' => $retailerId
            ];

            if (!isset($retailerIncoming[$retailerId])) {
                $retailerIncoming[$retailerId] = [
                    'organization_id' => $retailerId,
                    'organization_name' => (string)$retailerRow['organization_name'],
                    'organization_type' => (string)$retailerRow['organization_type'],
                    'incoming_cost' => $newManufacturerPrice,
                    'source_organizations' => [],
                    'relationship_ids' => []
                ];
            }

            $retailerIncoming[$retailerId]['source_organizations'][$manufacturerId] = true;
            $retailerIncoming[$retailerId]['relationship_ids'][] = $relationshipId;
        }
    }

    if (count($supplierMap) > 0) {
        foreach ($supplierMap as $supplierId => $supplier) {
            $hasRetailer = false;

            foreach ($retailerIncoming as $incoming) {
                if (isset($incoming['source_organizations'][(int)$supplierId])) {
                    $hasRetailer = true;
                    break;
                }
            }

            if (!$hasRetailer) {
                $warnings[] = "No active retailer relationship was found downstream of {$supplier['organization_name']}. Its prices can update, but no retailer price will be changed through that supplier.";
            }
        }
    }

    foreach ($retailerIncoming as $retailerId => $incoming) {
        $retailerProtectionEnabled = price_cascade_protection_enabled(
            $pdo,
            (int)$retailerId,
            $lockRows
        );

        $snapshot['protection_preferences'][] = [
            'organization_id' => (int)$retailerId,
            'enabled' => $retailerProtectionEnabled
        ];

        if (!$retailerProtectionEnabled) {
            $warnings[] = 'Some downstream organisations have not enabled Price Change Protection; their prices and dependent routes were not changed.';
            continue;
        }

        if (isset($blockedRetailers[(int)$retailerId])) {
            price_cascade_record_review(
                $snapshot,
                $reviewNotifications,
                (int)$retailerId,
                'UPSTREAM_ROUTE_REVIEW'
            );
            $warnings[] = 'Some downstream organisations have supply routes that require review; their prices were not changed.';
            continue;
        }

        if (
            price_cascade_has_scheduled_org_price_period(
                $pdo, (int)$retailerId, $productId, $versionId, $effectiveDate, $lockRows
            ) ||
            price_cascade_has_scheduled_branch_price_period(
                $pdo, (int)$retailerId, $productId, $versionId, $effectiveDate, $lockRows
            )
        ) {
            price_cascade_record_review(
                $snapshot,
                $reviewNotifications,
                (int)$retailerId,
                'SCHEDULE_REVIEW'
            );
            $warnings[] = 'Some downstream organisations have scheduled price periods requiring review; those organisations were not changed.';
            continue;
        }
        // Skip downstream retailers that have not onboarded this product version.
        // Existing price or branch records still make a retailer eligible for
        // strict validation, even if its retailer_products mapping is missing.
        $setupStatement = $pdo->prepare(
            "SELECT
                EXISTS(
                    SELECT 1
                    FROM retailer_products rp
                    INNER JOIN product_versions mapped_version
                        ON mapped_version.product_version_id = rp.product_version_id
                       AND mapped_version.product_id = rp.product_id
                    WHERE rp.retailer_id = ?
                      AND rp.product_id = ?
                      AND rp.product_version_id = ?
                      AND rp.status = 'ACTIVE'
                ) AS has_mapping,
                EXISTS(
                    SELECT 1
                    FROM product_prices pp
                    WHERE pp.organization_id = ?
                      AND pp.product_id = ?
                      AND pp.product_version_id = ?
                      AND pp.effective_from <= ?
                      AND (pp.effective_to IS NULL OR pp.effective_to >= ?)
                ) AS has_organization_prices,
                EXISTS(
                    SELECT 1
                    FROM retailer_branch_prices bp
                    WHERE bp.retailer_id = ?
                      AND bp.product_id = ?
                      AND bp.product_version_id = ?
                      AND bp.status = 'ACTIVE'
                      AND bp.effective_from <= ?
                      AND (bp.effective_to IS NULL OR bp.effective_to >= ?)
                ) AS has_branch_price"
        );
        $setupStatement->execute([
            (int)$retailerId,
            $productId,
            $versionId,
            (int)$retailerId,
            $productId,
            $versionId,
            $effectiveDate,
            $effectiveDate,
            (int)$retailerId,
            $productId,
            $versionId,
            $effectiveDate,
            $effectiveDate
        ]);
        $retailerSetup = $setupStatement->fetch(PDO::FETCH_ASSOC);

        if (
            $retailerSetup !== false &&
            empty($retailerSetup['has_mapping']) &&
            empty($retailerSetup['has_organization_prices']) &&
            empty($retailerSetup['has_branch_price'])
        ) {
            price_cascade_record_review(
                $snapshot,
                $reviewNotifications,
                (int)$retailerId,
                'PRODUCT_SETUP_REQUIRED'
            );
            $warnings[] = "{$incoming['organization_name']} has an active downstream supply relationship but has not onboarded {$product['product_name']} Version {$product['version_number']}. It has no active retailer-product mapping, effective organisation prices, or active branch price for this version, so it is skipped until product setup is completed.";
            continue;
        }

        if (count($incoming['source_organizations']) > 1) {
            price_cascade_fail(
                409,
                "{$incoming['organization_name']} is connected to this product through multiple active supplying organisations. The current organisation-level price model cannot represent different acquisition costs for each route; resolve the product's supply route before applying."
            );
        }

        try {
            $retailerCost = price_cascade_current_price(
                $pdo, (int)$retailerId, $productId, $versionId, 'COST', $effectiveDate, $lockRows
            );

            $retailerSelling = price_cascade_current_price(
                $pdo, (int)$retailerId, $productId, $versionId, 'SELLING', $effectiveDate, $lockRows
            );

            $retailerMargin = price_cascade_margin(
                $retailerCost,
                $retailerSelling,
                (string)$incoming['organization_name']
            );
        } catch (DomainException $e) {
            price_cascade_record_review(
                $snapshot,
                $reviewNotifications,
                (int)$retailerId,
                'PRICING_REVIEW'
            );
            $warnings[] = 'Some opted-in downstream organisations have incomplete or inconsistent pricing; those organisations were not changed.';
            continue;
        }

        price_cascade_snapshot_price($snapshot, $retailerCost);
        price_cascade_snapshot_price($snapshot, $retailerSelling);

        $newRetailerCost = (float)$incoming['incoming_cost'];

        $rounded = price_cascade_round_retail_price($newRetailerCost, $retailerMargin);
        $newRetailerSelling = (float)$rounded['price'];

        $organizationChanges[(int)$retailerId] = [
            'organization_id' => (int)$retailerId,
            'organization_name' => (string)$incoming['organization_name'],
            'organization_type' => 'RETAILER',
            'old_cost_price' => (float)$retailerCost['price_amount'],
            'new_cost_price' => $newRetailerCost,
            'old_selling_price' => (float)$retailerSelling['price_amount'],
            'new_selling_price' => $newRetailerSelling,
            'gross_margin_before_percent' => round($retailerMargin * 100, 2),
            'gross_margin_after_percent' => round((float)$rounded['margin_after'] * 100, 2),
            'rounding_rule' => (string)$rounded['rounding_rule'],
            'branch_prices_found' => 0
        ];

        price_cascade_snapshot_write($productPriceWrites, $retailerCost, $newRetailerCost);
        price_cascade_snapshot_write($productPriceWrites, $retailerSelling, $newRetailerSelling);

        // Checkout uses branch-level prices, so propagate the proposed retail
        // selling price to existing effective branch prices for this version.
        $branchSql = "
            SELECT bp.*, b.branch_name
            FROM retailer_branch_prices bp
            INNER JOIN branches b ON b.branch_id = bp.branch_id
            WHERE bp.retailer_id = ?
              AND bp.product_id = ?
              AND bp.product_version_id = ?
              AND bp.status = 'ACTIVE'
              AND b.status = 'ACTIVE'
              AND b.organization_id = bp.retailer_id
              AND bp.effective_from <= ?
              AND (bp.effective_to IS NULL OR bp.effective_to >= ?)
            ORDER BY bp.branch_id, bp.effective_from DESC, bp.retailer_branch_price_id DESC
        ";

        if ($lockRows) {
            $branchSql .= " FOR UPDATE";
        }

        $branchStatement = $pdo->prepare($branchSql);
        $branchStatement->execute([
            (int)$retailerId,
            $productId,
            $versionId,
            $effectiveDate,
            $effectiveDate
        ]);

        $branchRows = $branchStatement->fetchAll(PDO::FETCH_ASSOC);
        $branchesSeen = [];

        foreach ($branchRows as $branchRow) {
            $branchId = (int)$branchRow['branch_id'];

            if (isset($branchesSeen[$branchId])) {
                price_cascade_fail(
                    409,
                    "Overlapping active branch prices exist for {$incoming['organization_name']} branch {$branchRow['branch_name']}. Resolve the branch price periods before cascading."
                );
            }

            $branchesSeen[$branchId] = true;

            $snapshot['branch_prices'][] = [
                'retailer_branch_price_id' => (int)$branchRow['retailer_branch_price_id'],
                'retailer_id' => (int)$branchRow['retailer_id'],
                'branch_id' => $branchId,
                'product_id' => (int)$branchRow['product_id'],
                'product_version_id' => (int)$branchRow['product_version_id'],
                'selling_price' => (string)$branchRow['selling_price'],
                'price_basis' => (string)$branchRow['price_basis'],
                'effective_from' => (string)$branchRow['effective_from'],
                'effective_to' => $branchRow['effective_to'] === null ? null : (string)$branchRow['effective_to']
            ];

            $basis = (string)$branchRow['price_basis'];

            if ($basis === 'VAT_INCLUSIVE') {
                $newBranchPrice = $newRetailerSelling;
            } elseif ($basis === 'VAT_EXCLUSIVE') {
                $factor = price_cascade_vat_factor(
                    $pdo, $manufacturerId, $versionId, $effectiveDate, $lockRows
                );

                $snapshot['tax_factors'][] = [
                    'product_version_id' => $versionId,
                    'vat_factor' => round($factor, 8)
                ];

                $newBranchPrice = round($newRetailerSelling / $factor, 2);
            } else {
                price_cascade_fail(409, 'An affected branch uses an unsupported price basis.');
            }

            $branchChanges[] = [
                'organization_id' => (int)$retailerId,
                'organization_name' => (string)$incoming['organization_name'],
                'branch_id' => $branchId,
                'branch_name' => (string)$branchRow['branch_name'],
                'price_basis' => $basis,
                'old_price' => (float)$branchRow['selling_price'],
                'new_price' => round($newBranchPrice, 2)
            ];

            if (abs((float)$branchRow['selling_price'] - $newBranchPrice) >= 0.005) {
                $branchPriceWrites[] = [
                    'row' => $branchRow,
                    'new_amount' => round($newBranchPrice, 2)
                ];
            }
        }

        $organizationChanges[(int)$retailerId]['branch_prices_found'] = count($branchRows);

        if (count($branchRows) === 0) {
            $warnings[] = "{$incoming['organization_name']} has no active branch selling-price record for this product version. Its organisation-level prices can update, but checkout will still require a branch price to be configured.";
        }

        // Detect incorrect active retailer-product mappings instead of silently
        // rewriting an unrelated product version.
        $mappingSql = "
            SELECT rp.retailer_product_id, rp.product_version_id,
                   pv.product_id AS version_product_id, rp.status
            FROM retailer_products rp
            LEFT JOIN product_versions pv ON pv.product_version_id = rp.product_version_id
            WHERE rp.retailer_id = ?
              AND rp.product_id = ?
              AND rp.status = 'ACTIVE'
            ORDER BY rp.retailer_product_id
        ";

        if ($lockRows) {
            $mappingSql .= " FOR UPDATE";
        }

        $mappingStatement = $pdo->prepare($mappingSql);
        $mappingStatement->execute([(int)$retailerId, $productId]);
        $mappingRows = $mappingStatement->fetchAll(PDO::FETCH_ASSOC);

        $hasValidMapping = false;

        foreach ($mappingRows as $mappingRow) {
            $snapshot['retailer_products'][] = [
                'retailer_product_id' => (int)$mappingRow['retailer_product_id'],
                'retailer_id' => (int)$retailerId,
                'product_id' => $productId,
                'product_version_id' => (int)$mappingRow['product_version_id'],
                'version_product_id' => $mappingRow['version_product_id'] === null
                    ? null
                    : (int)$mappingRow['version_product_id'],
                'status' => (string)$mappingRow['status']
            ];

            if (
                (int)$mappingRow['product_version_id'] === $versionId &&
                $mappingRow['version_product_id'] !== null &&
                (int)$mappingRow['version_product_id'] === $productId
            ) {
                $hasValidMapping = true;
            }
        }

        if (!$hasValidMapping) {
            $warnings[] = "{$incoming['organization_name']} has no active retailer-product mapping for the current version of {$product['product_name']}. The cascade will not repair that mapping automatically.";
        }
    }

    if (count($supplierMap) === 0 && count($retailerIncoming) === 0) {
        $warnings[] = 'No active product-specific supplier or direct retailer route was found. Only the manufacturer price can change.';
    }

    $snapshot['relationships'] = array_values($snapshot['relationships']);

    $signatureSource = [
        'snapshot' => $snapshot,
        'manufacturer_new_price' => number_format($newManufacturerPrice, 2, '.', ''),
        'effective_date' => $effectiveDate
    ];

    $signatureJson = json_encode(
        $signatureSource,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
    );

    if ($signatureJson === false) {
        price_cascade_fail(500, 'Unable to create a price preview signature.');
    }

    return [
        'product' => [
            'product_id' => $productId,
            'product_name' => (string)$product['product_name'],
            'product_version_id' => $versionId,
            'version_number' => (int)$product['version_number'],
            'manufacturer_id' => $manufacturerId,
            'manufacturer_name' => (string)$product['manufacturer_name']
        ],
        'effective_date' => $effectiveDate,
        'manufacturer_new_price' => $newManufacturerPrice,
        'organizations' => array_values($organizationChanges),
        'branch_changes' => $branchChanges,
        'warnings' => array_values(array_unique($warnings)),
        'review_notifications' => $reviewNotifications,
        'signature' => hash('sha256', $signatureJson),
        '_writes' => $productPriceWrites,
        '_branch_writes' => $branchPriceWrites
    ];
}

function price_cascade_public_plan(array $plan, array $actor): array
{
    $organizationId = (int)$actor['organization_id'];

    $ownOrganizations = array_values(array_filter(
        $plan['organizations'] ?? [],
        static fn(array $row): bool =>
            (int)($row['organization_id'] ?? 0) === $organizationId
    ));

    $ownBranchChanges = array_values(array_filter(
        $plan['branch_changes'] ?? [],
        static fn(array $row): bool =>
            (int)($row['organization_id'] ?? 0) === $organizationId
    ));

    $publicWarnings = empty($plan['warnings'])
        ? []
        : ['Some price-chain participants require setup review. Other organisations’ prices and records remain private.'];

    return [
        'product' => $plan['product'],
        'effective_date' => $plan['effective_date'],
        'manufacturer_new_price' => $plan['manufacturer_new_price'],
        'organizations' => $ownOrganizations,
        'branch_changes' => $ownBranchChanges,
        'warnings' => $publicWarnings,
        'signature' => $plan['signature']
    ];
}

function price_cascade_apply_plan(PDO $pdo, array $actor, array $plan): array
{
    $effectiveDate = (string)$plan['effective_date'];
    $previousDate = (new DateTimeImmutable($effectiveDate, new DateTimeZone('Africa/Nairobi')))
        ->modify('-1 day')
        ->format('Y-m-d');

    $updatedProductPrices = 0;
    $updatedBranchPrices = 0;
    $changedOrganizations = [];

    foreach ($plan['_writes'] as $write) {
        $row = $write['row'];
        $newAmount = (float)$write['new_amount'];
        $priceId = (int)$row['price_id'];

        if ((string)$row['effective_from'] < $effectiveDate) {
            $close = $pdo->prepare(
                'UPDATE product_prices SET effective_to = ? WHERE price_id = ?'
            );
            $close->execute([$previousDate, $priceId]);

            $insert = $pdo->prepare("
                INSERT INTO product_prices
                    (organization_id, product_id, product_version_id, price_type,
                     price_amount, price_includes_vat, tax_category,
                     effective_from, effective_to)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert->execute([
                (int)$row['organization_id'],
                (int)$row['product_id'],
                (int)$row['product_version_id'],
                (string)$row['price_type'],
                $newAmount,
                (int)$row['price_includes_vat'],
                (string)$row['tax_category'],
                $effectiveDate,
                $row['effective_to'] === null ? null : (string)$row['effective_to']
            ]);
        } else {
            // Price periods are date-based. If a price was already introduced
            // today, update today's record and retain the change in the audit log.
            $update = $pdo->prepare(
                'UPDATE product_prices SET price_amount = ? WHERE price_id = ?'
            );
            $update->execute([$newAmount, $priceId]);
        }

        $updatedProductPrices++;
        $changedOrganizations[(int)$row['organization_id']] = true;
    }

    foreach ($plan['_branch_writes'] as $write) {
        $row = $write['row'];
        $newAmount = (float)$write['new_amount'];
        $branchPriceId = (int)$row['retailer_branch_price_id'];

        if ((string)$row['effective_from'] < $effectiveDate) {
            $close = $pdo->prepare(
                'UPDATE retailer_branch_prices SET effective_to = ? WHERE retailer_branch_price_id = ?'
            );
            $close->execute([$previousDate, $branchPriceId]);

            $insert = $pdo->prepare("
                INSERT INTO retailer_branch_prices
                    (retailer_id, branch_id, product_id, product_version_id,
                     selling_price, price_basis, effective_from, effective_to,
                     status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', ?)
            ");

            $insert->execute([
                (int)$row['retailer_id'],
                (int)$row['branch_id'],
                (int)$row['product_id'],
                (int)$row['product_version_id'],
                $newAmount,
                (string)$row['price_basis'],
                $effectiveDate,
                $row['effective_to'] === null ? null : (string)$row['effective_to'],
                (int)$actor['user_id']
            ]);
        } else {
            $update = $pdo->prepare("
                UPDATE retailer_branch_prices
                SET selling_price = ?, updated_at = CURRENT_TIMESTAMP
                WHERE retailer_branch_price_id = ?
            ");
            $update->execute([$newAmount, $branchPriceId]);
        }

        $updatedBranchPrices++;
        $changedOrganizations[(int)$row['retailer_id']] = true;
    }

    $manufacturerName = (string)$plan['product']['manufacturer_name'];
    $productName = (string)$plan['product']['product_name'];
    $versionNumber = (int)$plan['product']['version_number'];
    $newManufacturerPrice = number_format((float)$plan['manufacturer_new_price'], 2, '.', '');

    require_once __DIR__ . '/../core/notifications.php';
    require_once __DIR__ . '/../core/audit.php';

    foreach ($plan['organizations'] as $organization) {
        $organizationId = (int)$organization['organization_id'];

        if (
            $organizationId === (int)$actor['organization_id'] ||
            !isset($changedOrganizations[$organizationId])
        ) {
            continue;
        }

        $oldCost = number_format((float)$organization['old_cost_price'], 2, '.', '');
        $newCost = number_format((float)$organization['new_cost_price'], 2, '.', '');
        $oldSelling = number_format((float)$organization['old_selling_price'], 2, '.', '');
        $newSelling = number_format((float)$organization['new_selling_price'], 2, '.', '');

        $message = "Price update processed for {$productName}, version {$versionNumber}. "
            . "Your own recorded cost changed from KSh {$oldCost} to KSh {$newCost}, and your own selling price changed from KSh {$oldSelling} to KSh {$newSelling}. "
            . "Effective date: {$effectiveDate}. This notification contains only your organisation's own prices.";

        create_organization_notification(
            $pdo,
            $organizationId,
            'SUPPLY_CHANGE',
            (int)$plan['product']['product_version_id'],
            "Product price updated: {$productName}",
            $message
        );
    }

    foreach (($plan['review_notifications'] ?? []) as $review) {
        $reviewOrganizationId = (int)$review['organization_id'];

        if ($reviewOrganizationId === (int)$actor['organization_id']) {
            continue;
        }

        $reviewText = match ((string)$review['reason_code']) {
            'SCHEDULE_REVIEW' =>
                "An existing price schedule for {$productName}, version {$versionNumber}, requires review before automatic repricing can proceed. No automatic price adjustment was made to your organisation.",
            'PRICING_REVIEW' =>
                "Your current cost or selling-price records for {$productName}, version {$versionNumber}, need review before automatic repricing can proceed. No automatic price adjustment was made to your organisation.",
            'PRODUCT_SETUP_REQUIRED' =>
                "Product setup for {$productName}, version {$versionNumber}, is incomplete for your organisation. Complete your own product mapping and pricing records before using automatic repricing. No automatic price adjustment was made to your organisation.",
            default =>
                "The supply route for {$productName}, version {$versionNumber}, could not be used for automatic repricing. Review your own purchase and selling prices before making a manual adjustment. No automatic price adjustment was made to your organisation."
        };

        create_organization_notification(
            $pdo,
            $reviewOrganizationId,
            'SUPPLY_CHANGE',
            (int)$plan['product']['product_version_id'],
            "Price update needs review: {$productName}",
            $reviewText . " Effective date: {$effectiveDate}."
        );
    }
    record_audit(
        $pdo,
        $actor,
        'PRODUCT_PRICE_CASCADE_APPLIED',
        'PRODUCT_VERSION',
        (int)$plan['product']['product_version_id'],
        "Applied manufacturer price change for {$productName} version {$versionNumber}: KSh {$newManufacturerPrice}; {$updatedProductPrices} organisation price rows and {$updatedBranchPrices} branch price rows updated; effective {$effectiveDate}."
    );

    return [
        'updated_product_price_rows' => $updatedProductPrices,
        'updated_branch_price_rows' => $updatedBranchPrices,
        'changed_organizations' => count($changedOrganizations)
    ];
}