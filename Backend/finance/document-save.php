<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/helpers.php';

require_post();

try {
    $actor = require_admin($pdo);
    $organizationId = (int)$actor['organization_id'];
    $body = read_json_body();

    $documentType = strtoupper(trim((string)($body['document_type'] ?? '')));
    if (!in_array($documentType, ['INVOICE', 'BILL'], true)) {
        respond_error(400, 'For now, create an INVOICE or BILL. Credit and debit notes are not enabled in this first release.');
    }
    $direction = $documentType === 'INVOICE' ? 'RECEIVABLE' : 'PAYABLE';

    $counterpartyId = filter_var($body['counterparty_organization_id'] ?? null, FILTER_VALIDATE_INT);
    if ($counterpartyId === false || $counterpartyId === null || $counterpartyId < 1 || $counterpartyId === $organizationId) {
        respond_error(400, 'Choose a valid counterparty organisation.');
    }
    $counterpartyId = (int)$counterpartyId;

    $documentNumber = trim((string)($body['document_number'] ?? ''));
    if ($documentNumber === '' || strlen($documentNumber) > 60 || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]{0,59}$/D', $documentNumber)) {
        respond_error(400, 'Enter a document number of up to 60 characters using letters, numbers, dots, underscores, slashes or hyphens.');
    }

    $issueDate = trim((string)($body['issue_date'] ?? finance_today()));
    $dueDate = trim((string)($body['due_date'] ?? ''));
    if (!valid_date($issueDate)) {
        respond_error(400, 'Enter a valid issue date.');
    }
    $dueDate = $dueDate === '' ? null : $dueDate;
    if ($dueDate !== null && (!valid_date($dueDate) || $dueDate < $issueDate)) {
        respond_error(400, 'The due date must be valid and cannot precede the issue date.');
    }

    $counterpartyStatement = $pdo->prepare(
        "SELECT organization_id FROM organizations WHERE organization_id = ? AND status = 'ACTIVE' LIMIT 1"
    );
    $counterpartyStatement->execute([$counterpartyId]);
    if ($counterpartyStatement->fetchColumn() === false) {
        respond_error(404, 'The selected counterparty organisation is not active or was not found.');
    }
    if (!finance_relationship_is_active($pdo, $organizationId, $counterpartyId, $issueDate)) {
        respond_error(409, 'Invoices and bills can only be created for an active supply relationship.');
    }

    $branchId = null;
    if (isset($body['branch_id']) && $body['branch_id'] !== '' && $body['branch_id'] !== null) {
        $branchId = filter_var($body['branch_id'], FILTER_VALIDATE_INT);
        if ($branchId === false || $branchId < 1) {
            respond_error(400, 'Choose a valid branch.');
        }
        $branchStatement = $pdo->prepare(
            "SELECT branch_id FROM branches WHERE branch_id = ? AND organization_id = ? AND status = 'ACTIVE' LIMIT 1"
        );
        $branchStatement->execute([(int)$branchId, $organizationId]);
        if ($branchStatement->fetchColumn() === false) {
            respond_error(403, 'The selected branch does not belong to your organisation or is inactive.');
        }
        $branchId = (int)$branchId;
    }

    $sourceOrderId = null;
    if (isset($body['source_supply_order_id']) && $body['source_supply_order_id'] !== '' && $body['source_supply_order_id'] !== null) {
        $sourceOrderId = filter_var($body['source_supply_order_id'], FILTER_VALIDATE_INT);
        if ($sourceOrderId === false || $sourceOrderId < 1) {
            respond_error(400, 'Choose a valid source supply order.');
        }
        $orderStatement = $pdo->prepare(
            "SELECT supply_order_id FROM supply_orders
             WHERE supply_order_id = ? AND status <> 'CANCELLED'
               AND ((retailer_id = ? AND supplier_id = ?) OR (retailer_id = ? AND supplier_id = ?))
             LIMIT 1"
        );
        $orderStatement->execute([(int)$sourceOrderId, $organizationId, $counterpartyId, $counterpartyId, $organizationId]);
        if ($orderStatement->fetchColumn() === false) {
            respond_error(403, 'The selected supply order is not an active order between these organisations.');
        }
        $sourceOrderId = (int)$sourceOrderId;
    }

    $lines = $body['lines'] ?? null;
    if (!is_array($lines) || count($lines) < 1 || count($lines) > 100) {
        respond_error(400, 'Add between 1 and 100 document lines.');
    }

    $preparedLines = [];
    $subtotal = 0.0;
    $taxTotal = 0.0;
    $lineNumber = 0;
    foreach ($lines as $line) {
        if (!is_array($line)) {
            respond_error(400, 'Every document line must be an object.');
        }
        $lineNumber++;
        $productId = null;
        $productName = '';
        if (isset($line['product_id']) && $line['product_id'] !== '' && $line['product_id'] !== null) {
            $productId = filter_var($line['product_id'], FILTER_VALIDATE_INT);
            if ($productId === false || $productId < 1) {
                respond_error(400, "Line {$lineNumber} has an invalid product.");
            }
            $productStatement = $pdo->prepare('SELECT product_name FROM products WHERE product_id = ? LIMIT 1');
            $productStatement->execute([(int)$productId]);
            $productName = (string)($productStatement->fetchColumn() ?: '');
            if ($productName === '') {
                respond_error(400, "Line {$lineNumber} references a product that was not found.");
            }
            $productId = (int)$productId;
        }

        $description = trim((string)($line['description'] ?? ''));
        if ($description === '') {
            $description = $productName;
        }
        if ($description === '' || mb_strlen($description) > 500) {
            respond_error(400, "Line {$lineNumber} needs a description of up to 500 characters.");
        }

        if (!is_numeric($line['quantity'] ?? null)) {
            respond_error(400, "Line {$lineNumber} needs a numeric quantity.");
        }
        $quantity = (float)$line['quantity'];
        if (!is_finite($quantity) || $quantity <= 0 || $quantity > 999999999.999) {
            respond_error(400, "Line {$lineNumber} quantity must be positive and within the supported range.");
        }
        $quantity = round($quantity, 3);
        $unitPrice = finance_money($line['unit_price'] ?? null, "Line {$lineNumber} unit price");
        $taxRate = is_numeric($line['tax_rate'] ?? 0) ? (float)($line['tax_rate'] ?? 0) : -1;
        if (!is_finite($taxRate) || $taxRate < 0 || $taxRate > 100) {
            respond_error(400, "Line {$lineNumber} tax rate must be between 0 and 100 percent.");
        }

        $lineSubtotal = round($quantity * $unitPrice, 2);
        if (!is_finite($lineSubtotal) || $lineSubtotal > 999999999999.99) {
            respond_error(400, "Line {$lineNumber} subtotal exceeds the supported amount.");
        }
        $lineTax = round($lineSubtotal * $taxRate / 100, 2);
        $lineTotal = round($lineSubtotal + $lineTax, 2);
        $subtotal = round($subtotal + $lineSubtotal, 2);
        $taxTotal = round($taxTotal + $lineTax, 2);
        $preparedLines[] = [
            'line_number' => $lineNumber,
            'product_id' => $productId,
            'description' => $description,
            'quantity' => round($quantity, 3),
            'unit_price' => $unitPrice,
            'tax_rate' => round($taxRate, 3),
            'line_subtotal' => $lineSubtotal,
            'line_tax' => $lineTax,
            'line_total' => $lineTotal
        ];
    }
    $totalAmount = round($subtotal + $taxTotal, 2);
    if (!is_finite($totalAmount) || $totalAmount > 999999999999.99) {
        respond_error(400, 'The document total exceeds the supported amount.');
    }

    $pdo->beginTransaction();
    $insertDocument = $pdo->prepare(
        "INSERT INTO finance_documents
            (organization_id, counterparty_organization_id, branch_id, source_supply_order_id,
             document_number, document_type, direction, issue_date, due_date, currency_code,
             subtotal, tax_total, total_amount, status, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'KES', ?, ?, ?, 'DRAFT', ?, ?)"
    );
    $notes = trim((string)($body['notes'] ?? ''));
    $insertDocument->execute([
        $organizationId, $counterpartyId, $branchId, $sourceOrderId,
        $documentNumber, $documentType, $direction, $issueDate, $dueDate,
        $subtotal, $taxTotal, $totalAmount,
        $notes === '' ? null : mb_substr($notes, 0, 10000),
        (int)$actor['user_id']
    ]);
    $documentId = (int)$pdo->lastInsertId();

    $insertLine = $pdo->prepare(
        "INSERT INTO finance_document_lines
            (document_id, line_number, product_id, description, quantity, unit_price,
             tax_rate, line_subtotal, line_tax, line_total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($preparedLines as $line) {
        $insertLine->execute([
            $documentId, $line['line_number'], $line['product_id'], $line['description'],
            $line['quantity'], $line['unit_price'], $line['tax_rate'],
            $line['line_subtotal'], $line['line_tax'], $line['line_total']
        ]);
    }

    record_audit(
        $pdo,
        $actor,
        'FINANCE_DOCUMENT_DRAFT_CREATED',
        'FINANCE_DOCUMENT',
        $documentId,
        "Created {$documentType} draft {$documentNumber}; total KES " . number_format($totalAmount, 2, '.', '') . '.'
    );
    $pdo->commit();

    respond(201, [
        'success' => true,
        'document_id' => $documentId,
        'document_number' => $documentNumber,
        'document_type' => $documentType,
        'direction' => $direction,
        'subtotal' => $subtotal,
        'tax_total' => $taxTotal,
        'total_amount' => $totalAmount,
        'status' => 'DRAFT',
        'message' => 'Draft saved. Review it and issue it when ready.'
    ]);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(400, $e->getMessage());
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond_error(409, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance document save', $e);
    if ((string)$e->getCode() === '23000') {
        respond_error(409, 'That document number already exists for your organisation or conflicts with existing data.');
    }
    respond_error(500, 'Unable to save the financial document.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('finance document save unexpected', $e);
    respond_error(500, 'Unable to save the financial document.');
}
