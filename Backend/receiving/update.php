<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/receiving-rules.php';

function grn_update_decimal($value, string $field, float $maximum = 999999999999.999): float
{
    if (!is_numeric($value)) {
        respond_error(422, "Invalid value for {$field}.");
    }

    $number = (float)$value;

    if (!is_finite($number) || $number < 0 || $number > $maximum) {
        respond_error(422, "Invalid value for {$field}.");
    }

    return $number;
}

function grn_update_date($value, string $field): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    if (!is_string($value)) {
        respond_error(422, "Invalid date for {$field}.");
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    if (!$date || $date->format('Y-m-d') !== $value) {
        respond_error(422, "Invalid date for {$field}.");
    }

    return $value;
}

function grn_update_text($value, string $field, int $maximumLength): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    if (!is_string($value) || mb_strlen($value) > $maximumLength) {
        respond_error(422, "Invalid value for {$field}.");
    }

    return trim($value);
}

function grn_update_fail(PDO $pdo, int $status, string $message): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond_error($status, $message);
}

function grn_update_quantities(PDO $pdo, array $submitted): array
{
    $quantity = grn_update_decimal(
        $submitted['invoice_quantity'] ?? null,
        'quantity received',
        999999999.999
    );

    $unit = $submitted['invoice_unit'] ?? null;

    if (!in_array($unit, ['EACH', 'CTN'], true)) {
        grn_update_fail($pdo, 422, 'The unit must be EACH or CTN.');
    }

    $unitsPerPack = filter_var(
        $submitted['units_per_invoice_unit'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 4294967295]]
    );

    if ($unitsPerPack === false || ($unit === 'EACH' && (int)$unitsPerPack !== 1)) {
        grn_update_fail($pdo, 422, 'Invalid unit conversion.');
    }

    $exact = $quantity * (int)$unitsPerPack;
    $each = (int)round($exact);

    if (abs($exact - $each) > 0.000001 || $each > 4294967295) {
        grn_update_fail($pdo, 422, 'The quantity received must convert to a whole number of individual units.');
    }

    $basis = $submitted['price_basis'] ?? null;

    if (!in_array($basis, ['VAT_INCLUSIVE', 'VAT_EXCLUSIVE'], true)) {
        grn_update_fail($pdo, 422, 'Invalid price basis.');
    }

    return [
        'quantity' => $quantity,
        'unit' => $unit,
        'units_per_pack' => (int)$unitsPerPack,
        'each' => $each,
        'price' => grn_update_decimal($submitted['invoice_unit_price'] ?? null, 'unit price'),
        'basis' => $basis
    ];
}

try {
    require_post();

    $actor = require_login($pdo);

    if (!in_array($actor['role'] ?? '', ['ADMIN', 'BRANCH_ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Your account does not have access to receiving.');
    }

    $body = read_json_body();

    $grnId = filter_var(
        $body['grn_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (
        $grnId === false
        || !isset($body['items'])
        || !is_array($body['items'])
        || $body['items'] === []
    ) {
        respond_error(400, 'A valid GRN and at least one product line are required.');
    }

    $pdo->beginTransaction();

    $headerStatement = $pdo->prepare(
        "SELECT
            grn_id,
            branch_id,
            supplier_id,
            supply_order_id,
            received_date,
            status
         FROM goods_received_notes
         WHERE grn_id = ?
           AND retailer_id = ?
         LIMIT 1
         FOR UPDATE"
    );

    $headerStatement->execute([(int)$grnId, (int)$actor['organization_id']]);
    $grn = $headerStatement->fetch(PDO::FETCH_ASSOC);

    if (!$grn) {
        grn_update_fail($pdo, 404, 'The requested GRN was not found.');
    }

    if ($grn['status'] !== 'DRAFT') {
        grn_update_fail($pdo, 409, 'Only draft GRNs can be edited.');
    }

    receiving_resolve_branch($pdo, $actor, (int)$grn['branch_id']);

    $invoiceNumber = grn_update_text(
        $body['supplier_invoice_number'] ?? null,
        'supplier invoice number',
        100
    );

    $invoiceDate = grn_update_date(
        $body['supplier_invoice_date'] ?? null,
        'supplier invoice date'
    );

    $receivedDate = grn_update_date(
        $body['received_date'] ?? $grn['received_date'],
        'received date'
    );

    if ($receivedDate === null) {
        grn_update_fail($pdo, 422, 'A received date is required.');
    }

    $remarks = grn_update_text($body['remarks'] ?? null, 'remarks', 2000);
    $taxDate = $invoiceDate ?? $receivedDate;

    $existingStatement = $pdo->prepare(
        "SELECT grn_item_id, product_id, product_version_id
         FROM goods_received_note_items
         WHERE grn_id = ?
         FOR UPDATE"
    );

    $existingStatement->execute([(int)$grnId]);

    $existingById = [];

    foreach ($existingStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existingById[(int)$row['grn_item_id']] = $row;
    }

    $manufacturerStatement = $pdo->prepare(
        "SELECT manufacturer_id FROM products WHERE product_id = ? LIMIT 1"
    );

    $newProductStatement = $pdo->prepare(
        "SELECT
            p.manufacturer_id,
            p.product_name,
            pv.product_version_id,
            pv.barcode,
            pv.pack_size
         FROM products p
         INNER JOIN product_versions pv
            ON pv.product_id = p.product_id
           AND pv.is_current = 1
         WHERE p.product_id = ?
           AND p.status = 'ACTIVE'
           AND EXISTS (
                SELECT 1
                FROM supplier_product_offers o
                WHERE o.supplier_id = ?
                  AND o.product_id = p.product_id
                  AND o.status = 'ACTIVE'
           )
         LIMIT 1"
    );

    $lpoItemStatement = $pdo->prepare(
        "SELECT
            supply_order_item_id,
            product_version_id,
            quantity_ordered,
            unit_price,
            supplier_order_unit,
            supplier_units_per_carton
         FROM supply_order_items
         WHERE supply_order_id = ?
           AND product_id = ?
         LIMIT 1"
    );

    $lines = [];
    $seenItemIds = [];
    $seenProductIds = [];

    foreach ($body['items'] as $submitted) {
        if (!is_array($submitted)) {
            grn_update_fail($pdo, 422, 'Invalid GRN product line.');
        }

        $quantities = grn_update_quantities($pdo, $submitted);
        $itemId = $submitted['grn_item_id'] ?? null;
        $snapshot = null;

        if ($itemId !== null) {
            $itemId = filter_var(
                $itemId,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($itemId === false || !isset($existingById[$itemId]) || isset($seenItemIds[$itemId])) {
                grn_update_fail($pdo, 422, 'A GRN product line is invalid or duplicated.');
            }

            $seenItemIds[$itemId] = true;
            $productId = (int)$existingById[$itemId]['product_id'];
            $versionId = $existingById[$itemId]['product_version_id'] !== null
                ? (int)$existingById[$itemId]['product_version_id']
                : null;

            $manufacturerStatement->execute([$productId]);
            $manufacturerId = (int)$manufacturerStatement->fetchColumn();
        } else {
            $productId = filter_var(
                $submitted['product_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($productId === false) {
                grn_update_fail($pdo, 422, 'A product is required for each new line.');
            }

            $newProductStatement->execute([(int)$productId, (int)$grn['supplier_id']]);
            $product = $newProductStatement->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                grn_update_fail($pdo, 422, 'A selected product is not supplied by this supplier.');
            }

            $lpoItemStatement->execute([(int)$grn['supply_order_id'], (int)$productId]);
            $lpoItem = $lpoItemStatement->fetch(PDO::FETCH_ASSOC);

            $lpoFactor = $lpoItem && $lpoItem['supplier_order_unit'] === 'CTN'
                ? max(1, (int)$lpoItem['supplier_units_per_carton'])
                : 1;

            $versionId = (int)($lpoItem['product_version_id'] ?? $product['product_version_id']);
            $manufacturerId = (int)$product['manufacturer_id'];

            $snapshot = [
                'supply_order_item_id' => $lpoItem ? (int)$lpoItem['supply_order_item_id'] : null,
                'product_name' => $product['product_name'],
                'barcode' => $product['barcode'],
                'pack_size' => $product['pack_size'],
                'lpo_quantity_each' => $lpoItem ? (int)$lpoItem['quantity_ordered'] * $lpoFactor : 0,
                'lpo_unit_price' => $lpoItem ? $lpoItem['unit_price'] : null
            ];
        }

        if (isset($seenProductIds[$productId])) {
            grn_update_fail($pdo, 422, 'A product appears more than once on this GRN.');
        }

        $seenProductIds[$productId] = true;

        $tax = receiving_line_tax($pdo, $versionId, $manufacturerId, $taxDate);

        $lines[] = [
            'item_id' => $itemId,
            'product_id' => (int)$productId,
            'version_id' => $versionId,
            'quantities' => $quantities,
            'tax' => $tax,
            'amounts' => receiving_line_amounts(
                $quantities['quantity'],
                $quantities['price'],
                $quantities['basis'],
                $tax
            ),
            'snapshot' => $snapshot
        ];
    }

    $removedIds = array_values(array_diff(array_keys($existingById), array_keys($seenItemIds)));

    if ($removedIds !== []) {
        $deleteStatement = $pdo->prepare(
            "DELETE FROM goods_received_note_items
             WHERE grn_id = ?
               AND grn_item_id IN (" . implode(', ', array_fill(0, count($removedIds), '?')) . ")"
        );

        $deleteStatement->execute([(int)$grnId, ...$removedIds]);
    }

    $updateItemStatement = $pdo->prepare(
        "UPDATE goods_received_note_items
         SET
            invoice_quantity = ?,
            invoice_unit = ?,
            units_per_invoice_unit = ?,
            invoice_unit_price = ?,
            price_basis = ?,
            tax_category = ?,
            tax_rate_percent = ?,
            taxable_supply = ?,
            tax_calculation_status = ?,
            invoice_goods_value = ?,
            invoice_vat_amount = ?,
            invoice_line_total = ?,
            quantity_delivered_each = ?,
            quantity_accepted_each = ?,
            quantity_rejected_each = 0
         WHERE grn_item_id = ?
           AND grn_id = ?"
    );

    $insertItemStatement = $pdo->prepare(
        "INSERT INTO goods_received_note_items (
            grn_id,
            supply_order_item_id,
            product_id,
            product_version_id,
            product_name_snapshot,
            barcode_snapshot,
            pack_size_snapshot,
            lpo_quantity_each,
            lpo_unit_price_snapshot,
            invoice_quantity,
            invoice_unit,
            units_per_invoice_unit,
            invoice_unit_price,
            price_basis,
            tax_category,
            tax_rate_percent,
            taxable_supply,
            tax_calculation_status,
            invoice_goods_value,
            invoice_vat_amount,
            invoice_line_total,
            quantity_delivered_each,
            quantity_accepted_each,
            quantity_rejected_each
         ) VALUES (" . implode(', ', array_fill(0, 23, '?')) . ", 0)"
    );

    $goodsValue = 0.0;
    $vatAmount = 0.0;
    $grandTotal = 0.0;
    $allTaxCalculated = true;

    foreach ($lines as $line) {
        $quantities = $line['quantities'];
        $amounts = $line['amounts'];

        $values = [
            number_format($quantities['quantity'], 3, '.', ''),
            $quantities['unit'],
            $quantities['units_per_pack'],
            number_format($quantities['price'], 4, '.', ''),
            $quantities['basis'],
            $line['tax']['tax_category'],
            $line['tax']['tax_rate_percent'],
            $line['tax']['taxable_supply'],
            $line['tax']['tax_calculation_status'],
            $amounts['net'] !== null ? number_format($amounts['net'], 2, '.', '') : null,
            $amounts['vat'] !== null ? number_format($amounts['vat'], 2, '.', '') : null,
            number_format($amounts['total'], 2, '.', ''),
            $quantities['each'],
            $quantities['each']
        ];

        if ($line['item_id'] !== null) {
            $updateItemStatement->execute([...$values, (int)$line['item_id'], (int)$grnId]);
        } else {
            $snapshot = $line['snapshot'];

            $insertItemStatement->execute([
                (int)$grnId,
                $snapshot['supply_order_item_id'],
                $line['product_id'],
                $line['version_id'],
                $snapshot['product_name'],
                $snapshot['barcode'],
                $snapshot['pack_size'],
                $snapshot['lpo_quantity_each'],
                $snapshot['lpo_unit_price'],
                ...$values
            ]);
        }

        if ($amounts['net'] === null) {
            $allTaxCalculated = false;
        } else {
            $goodsValue += $amounts['net'];
            $vatAmount += $amounts['vat'];
        }

        $grandTotal += $amounts['total'];
    }

    $calculatedGoodsValue = $allTaxCalculated ? number_format($goodsValue, 2, '.', '') : null;
    $calculatedVatAmount = $allTaxCalculated ? number_format($vatAmount, 2, '.', '') : null;
    $calculatedTotal = number_format($grandTotal, 2, '.', '');

    $updateHeaderStatement = $pdo->prepare(
        "UPDATE goods_received_notes
         SET
            supplier_invoice_number = ?,
            supplier_invoice_date = ?,
            received_date = ?,
            remarks = ?,
            calculated_goods_value = ?,
            calculated_vat_amount = ?,
            calculated_total = ?
         WHERE grn_id = ?
           AND retailer_id = ?
           AND status = 'DRAFT'"
    );

    $updateHeaderStatement->execute([
        $invoiceNumber,
        $invoiceDate,
        $receivedDate,
        $remarks,
        $calculatedGoodsValue,
        $calculatedVatAmount,
        $calculatedTotal,
        (int)$grnId,
        (int)$actor['organization_id']
    ]);

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'GRN draft saved successfully.',
        'grn_id' => (int)$grnId,
        'item_count' => count($lines),
        'calculated_goods_value' => $calculatedGoodsValue,
        'calculated_vat_amount' => $calculatedVatAmount,
        'calculated_total' => $calculatedTotal
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('GRN draft update', $e);
    respond_error(500, 'Unable to save the GRN draft.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('GRN draft update unexpected', $e);
    respond_error(500, 'Unable to save the GRN draft.');
}