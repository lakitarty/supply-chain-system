<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/pricing-rules.php';
require_once __DIR__ . '/../network/supply-arrangement-rules.php';

require_post();

try {
    $actor = require_admin($pdo);

    if (!in_array($actor['organization_type'], ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'], true)) {
        respond_error(403, 'Manufacturer organisation administrator access is required.');
    }

    $body = read_json_body();
    $versionId = filter_var(
        $body['productVersionId'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $category = trim((string)($body['taxCategory'] ?? ''));
    $effectiveFrom = trim((string)($body['effectiveFrom'] ?? ''));
    $effectiveToText = trim((string)($body['effectiveTo'] ?? ''));
    $effectiveTo = $effectiveToText === '' ? null : $effectiveToText;
    $note = trim((string)($body['declarationNote'] ?? ''));
    $closePrevious = in_array($body['closePrevious'] ?? false, [true, 1, '1', 'true'], true);
    $allowedCategories = ['STANDARD_RATED', 'ZERO_RATED', 'EXEMPT'];

    if ($versionId === false || $versionId === null) {
        respond_field_error(400, field_error('productVersionId', 'Select a product package version.'));
    }
    if (!in_array($category, $allowedCategories, true)) {
        respond_field_error(400, field_error('taxCategory', 'Select a valid tax category.'));
    }
    if (!valid_date($effectiveFrom)) {
        respond_field_error(400, field_error('effectiveFrom', 'Enter a valid effective-from date.'));
    }
    if ($effectiveTo !== null && !valid_date($effectiveTo)) {
        respond_field_error(400, field_error('effectiveTo', 'Enter a valid effective-to date or leave it blank.'));
    }
    if ($effectiveTo !== null && $effectiveTo < $effectiveFrom) {
        respond_field_error(400, field_error('effectiveTo', 'The effective-to date cannot be earlier than the effective-from date.'));
    }
    if (mb_strlen($note) > 500) {
        respond_field_error(400, field_error('declarationNote', 'The declaration note must be 500 characters or fewer.'));
    }

    $organizationId = (int)$actor['organization_id'];
    $pdo->beginTransaction();

    $versionStatement = $pdo->prepare(
        "SELECT pv.product_version_id, pv.product_id, pv.version_number,
                pv.barcode, pv.pack_size, p.product_name
         FROM product_versions pv
         INNER JOIN products p ON p.product_id = pv.product_id
         WHERE pv.product_version_id = ?
           AND p.manufacturer_id = ?
           AND p.status = 'ACTIVE'
         LIMIT 1 FOR UPDATE"
    );
    $versionStatement->execute([(int)$versionId, $organizationId]);
    $version = $versionStatement->fetch(PDO::FETCH_ASSOC);

    if (!$version) {
        $pdo->rollBack();
        respond_field_error(400, field_error('productVersionId', 'The selected package version does not belong to your active product catalogue.'));
    }

    $rateStatement = $pdo->prepare(
        "SELECT tax_rate_id, rate_percent, taxable_supply
         FROM tax_rate_schedule
         WHERE tax_category = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         ORDER BY effective_from DESC
         LIMIT 1"
    );
    $rateStatement->execute([$category, $effectiveFrom, $effectiveFrom]);
    $rate = $rateStatement->fetch(PDO::FETCH_ASSOC);

    if (!$rate) {
        $pdo->rollBack();
        respond_field_error(409, field_error('effectiveFrom', 'No tax-rate schedule is configured for this category on the selected effective date. Update the tax-rate schedule before declaring this classification.'));
    }

    if ($closePrevious) {
        $openStatement = $pdo->prepare(
            "SELECT product_tax_classification_id, effective_from
             FROM product_tax_classifications
             WHERE product_version_id = ?
               AND manufacturer_id = ?
               AND effective_to IS NULL
               AND effective_from < ?
             ORDER BY effective_from DESC
             FOR UPDATE"
        );
        $openStatement->execute([(int)$versionId, $organizationId, $effectiveFrom]);
        $openRows = $openStatement->fetchAll(PDO::FETCH_ASSOC);

        if (count($openRows) > 1) {
            $pdo->rollBack();
            respond_error(409, 'More than one open-ended classification exists for this version. Review the existing records before adding another.');
        }

        if (count($openRows) === 1) {
            $previous = $openRows[0];
            $previousEnd = (new DateTimeImmutable($effectiveFrom))->modify('-1 day')->format('Y-m-d');

            if ($previousEnd < $previous['effective_from']) {
                $pdo->rollBack();
                respond_error(409, 'The previous classification cannot be closed before its start date.');
            }

            $closeStatement = $pdo->prepare(
                'UPDATE product_tax_classifications
                 SET effective_to = ?
                 WHERE product_tax_classification_id = ?
                   AND manufacturer_id = ?
                   AND effective_to IS NULL'
            );
            $closeStatement->execute([$previousEnd, (int)$previous['product_tax_classification_id'], $organizationId]);
        }
    }

    $candidateEnd = $effectiveTo ?? '9999-12-31';
    $overlapStatement = $pdo->prepare(
        "SELECT product_tax_classification_id, effective_from, effective_to
         FROM product_tax_classifications
         WHERE product_version_id = ?
           AND manufacturer_id = ?
           AND effective_from <= ?
           AND (effective_to IS NULL OR effective_to >= ?)
         LIMIT 1 FOR UPDATE"
    );
    $overlapStatement->execute([(int)$versionId, $organizationId, $candidateEnd, $effectiveFrom]);

    if ($overlapStatement->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        respond_error(409, 'This product version already has a tax classification that overlaps the selected dates. End the previous period first or choose non-overlapping dates.');
    }

    $today = nairobi_today();
    $newPercent = (int)$rate['taxable_supply'] === 1 ? (float)$rate['rate_percent'] : 0.0;
    $oldPercent = $effectiveFrom >= $today
        ? version_vat_percent($pdo, (int)$versionId, (new DateTimeImmutable($effectiveFrom))->modify('-1 day')->format('Y-m-d'))
        : null;
    $rateChanged = $oldPercent !== null && abs($oldPercent - $newPercent) > 0.0001;

    $insert = $pdo->prepare(
        "INSERT INTO product_tax_classifications
            (product_version_id, manufacturer_id, tax_category,
             effective_from, effective_to, declared_by, declaration_note)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $insert->execute([
        (int)$versionId,
        $organizationId,
        $category,
        $effectiveFrom,
        $effectiveTo,
        (int)$actor['user_id'],
        $note !== '' ? $note : null
    ]);
    $classificationId = (int)$pdo->lastInsertId();
    $repriced = $rateChanged
        ? reprice_for_tax_change($pdo, $organizationId, (int)$versionId, $category, $effectiveFrom, $oldPercent, $newPercent, (int)$actor['user_id'])
        : [];
    $productLabel = (string)$version['product_name'] . ' version ' . (int)$version['version_number'];
    if (!empty($version['pack_size'])) {
        $productLabel .= ' (' . $version['pack_size'] . ')';
    }

    record_audit(
        $pdo,
        $actor,
        'PRODUCT_TAX_CLASSIFICATION_DECLARED',
        'PRODUCT_TAX_CLASSIFICATION',
        $classificationId,
        "Declared {$category} tax classification for {$productLabel} from {$effectiveFrom}" . ($effectiveTo !== null ? " through {$effectiveTo}" : ' with no planned end date') . ". Rate at start: {$rate['rate_percent']}%." . ($closePrevious ? ' Previous open-ended classification closure was requested.' : '')
    );

    foreach ($repriced as $change) {
        record_audit($pdo, $actor, 'PRODUCT_TAX_REPRICED', 'PRODUCT_VERSION', (int)$versionId, "Tax change repricing for {$productLabel}, effective {$effectiveFrom}: {$change}.");
    }

    if ($rateChanged) {
        notify_organizations(
            $pdo,
            tax_change_recipients($pdo, $organizationId, (int)$version['product_id'], $today),
            (int)$versionId,
            "Tax treatment changed: {$version['product_name']}",
            "The VAT rate applying to {$productLabel} changes from " . number_format($oldPercent, 2, '.', '') . '% to ' . number_format($newPercent, 2, '.', '') . "% from {$effectiveFrom}. The manufacturer adjusted its own prices to keep its net revenue. Review your own prices for this product."
        );
    }

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Product tax classification saved. The category is a manufacturer declaration; confirm its legal eligibility before relying on it for tax reporting.',
        'classification_id' => $classificationId,
        'tax_category' => $category,
        'rate_percent_at_start' => (string)$rate['rate_percent'],
        'taxable_supply_at_start' => (int)$rate['taxable_supply'],
        'repriced_rows' => count($repriced)
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('product tax classification save', $e);
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'A classification record already exists for this version and effective-from date. Refresh and review the existing classification history.');
    }
    respond_error(500, 'Unable to save the product tax classification.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('product tax classification save unexpected', $e);
    respond_error(500, 'Unable to save the product tax classification.');
}
