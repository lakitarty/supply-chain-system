<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_login($pdo);
    $organizationId = (int)$actor['organization_id'];
    $organizationType = (string)$actor['organization_type'];
    $role = (string)$actor['role'];
    $orderId = filter_var($_GET['orderId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($orderId === false || $orderId === null) respond_error(400, 'A valid LPO number is required.');

    $sql = "SELECT so.supply_order_id, so.lpo_number, so.lpo_valid_until, so.retailer_id, so.supplier_id, so.order_direction,
                   so.branch_id, so.order_date, so.expected_delivery_date, so.status,
                   so.reviewed_at, so.rejection_reason, b.branch_name,
                   buyer.organization_name AS retailer_name, seller.organization_name AS supplier_name, seller.address AS supplier_address, seller.phone AS supplier_phone, seller.address AS supplier_address, seller.phone AS supplier_phone,
                   CONCAT_WS(' ', NULLIF(order_creator.first_name, ''), NULLIF(order_creator.last_name, '')) AS prepared_by,
                   CONCAT_WS(' ', NULLIF(lpo_checker.first_name, ''), NULLIF(lpo_checker.last_name, '')) AS checked_by,
                   CONCAT_WS(' ', NULLIF(lpo_checker.first_name, ''), NULLIF(lpo_checker.last_name, '')) AS checked_by,
                   (SELECT COALESCE(SUM(CASE
                     WHEN i.price_basis = 'VAT_EXCLUSIVE' THEN i.quantity_ordered * ROUND(i.unit_price * (1 + CASE
                       WHEN COALESCE(i.tax_category, 'STANDARD_RATED') = 'STANDARD_RATED' THEN 0.16 ELSE 0 END), 2)
                     ELSE i.quantity_ordered * i.unit_price END), 0)
                    FROM supply_order_items i WHERE i.supply_order_id = so.supply_order_id) AS order_value
            FROM supply_orders so
            INNER JOIN organizations buyer ON buyer.organization_id = so.retailer_id
            INNER JOIN organizations seller ON seller.organization_id = so.supplier_id
            LEFT JOIN branches b ON b.branch_id = so.branch_id
            LEFT JOIN users order_creator ON order_creator.user_id = so.created_by
            LEFT JOIN users lpo_checker ON lpo_checker.user_id = so.reviewed_by
            WHERE so.supply_order_id = ? ";
    $params = [(int)$orderId];

    if ($organizationType === 'RETAILER') {
        if (!in_array($role, ['ADMIN', 'BRANCH_ADMIN'], true)) respond_error(403, 'You do not have permission to view LPO details.');
        $sql .= " AND so.retailer_id = ? AND so.order_direction = 'RETAILER_TO_SUPPLIER'";
        $params[] = $organizationId;
        if ($role === 'BRANCH_ADMIN') {
            $assignedBranchId = filter_var($actor['branch_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($assignedBranchId === false || $assignedBranchId === null) respond_error(403, 'Your assigned branch is not available.');
            $sql .= ' AND so.branch_id = ?';
            $params[] = (int)$assignedBranchId;
        }
    } elseif (in_array($organizationType, ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true)) {
        if ($role !== 'ADMIN') respond_error(403, 'Supplier administrator access is required to view LPO details.');
        $sql .= " AND ((so.supplier_id = ? AND so.order_direction IN ('RETAILER_TO_SUPPLIER','SUPPLIER_TO_MANUFACTURER') AND so.status <> 'DRAFT')
                       OR (so.retailer_id = ? AND so.order_direction = 'SUPPLIER_TO_MANUFACTURER'))";
        $params[] = $organizationId;
        $params[] = $organizationId;
    } elseif ($organizationType === 'MANUFACTURER') {
        if ($role !== 'ADMIN') respond_error(403, 'Manufacturer administrator access is required to view incoming LPO details.');
        $sql .= " AND so.supplier_id = ? AND so.order_direction = 'SUPPLIER_TO_MANUFACTURER' AND so.status <> 'DRAFT'";
        $params[] = $organizationId;
    } else {
        respond_error(403, 'Purchasing access is not available for this account.');
    }

    $orderStatement = $pdo->prepare($sql . ' LIMIT 1');
    $orderStatement->execute($params);
    $order = $orderStatement->fetch(PDO::FETCH_ASSOC);
    if (!$order) respond_error(404, 'The requested LPO was not found or is not accessible to your organisation.');

    // Use the organisation issuing this LPO, based on its direction.
    $isSupplierDirection =
        strtoupper(trim((string)($order['order_direction'] ?? '')))
        === 'SUPPLIER_TO_RETAILER';

    $headerOrgId = $isSupplierDirection
        ? (int)($order['supplier_id'] ?? 0)
        : (int)($order['retailer_id'] ?? 0);

    $headerOrgStatement = $pdo->prepare(
        "SELECT * FROM organizations WHERE organization_id = ?"
    );
    $headerOrgStatement->execute([$headerOrgId]);
    $headerOrgRecord = $headerOrgStatement->fetch(PDO::FETCH_ASSOC) ?: [];
    $headerOrg = array_change_key_case($headerOrgRecord, CASE_LOWER);

    $order['organization_name'] = trim(
        (string)($headerOrg['organization_name'] ?? '')
    );

    if ($order['organization_name'] === '') {
        $order['organization_name'] = trim((string)(
            $isSupplierDirection
                ? ($order['supplier_name'] ?? '')
                : ($order['retailer_name'] ?? '')
        ));
    }

    if ($order['organization_name'] === '') {
        $order['organization_name'] = trim((string)(
            $isSupplierDirection
                ? ($order['retailer_name'] ?? '')
                : ($order['supplier_name'] ?? '')
        ));
    }

    $order['organization_location'] = '';

    // organizations.address is the confirmed address field in this system.
    foreach ([
        'address',
        'postal_address',
        'physical_address',
        'registered_address',
        'head_office_address',
        'organization_address',
        'business_address',
        'location',
        'town',
        'city',
        'county',
        'region',
        'country'
    ] as $field) {
        if (
            isset($headerOrg[$field]) &&
            trim((string)$headerOrg[$field]) !== ''
        ) {
            $order['organization_location'] = trim((string)$headerOrg[$field]);
            break;
        }
    }

    $order['organization_phone'] = '';

    foreach ([
        'telephone',
        'phone',
        'phone_number',
        'office_phone',
        'contact_phone',
        'mobile_number',
        'contact_number'
    ] as $field) {
        if (
            isset($headerOrg[$field]) &&
            trim((string)$headerOrg[$field]) !== ''
        ) {
            $order['organization_phone'] = trim((string)$headerOrg[$field]);
            break;
        }
    }

$itemsStatement = $pdo->prepare(
        "SELECT i.supply_order_item_id, i.product_id, i.product_version_id, i.price_basis,
                COALESCE(i.tax_category,
                  (SELECT tc.tax_category FROM product_tax_classifications tc
                   WHERE tc.product_version_id = i.product_version_id AND tc.manufacturer_id = p.manufacturer_id
                     AND tc.effective_from <= so.order_date
                     AND (tc.effective_to IS NULL OR tc.effective_to >= so.order_date)
                   ORDER BY tc.effective_from DESC, tc.product_tax_classification_id DESC LIMIT 1)) AS tax_category,
                i.supplier_product_offer_id, i.source_product_price_id,
                p.product_name, pv.barcode AS lpo_code, pv.barcode, pv.pack_size, pv.packaging_description, NULL AS unit_of_measure, i.quantity_ordered, i.quantity_received, i.unit_price
         FROM supply_order_items i
         INNER JOIN products p ON p.product_id = i.product_id
         LEFT JOIN product_versions pv ON pv.product_version_id = i.product_version_id
         INNER JOIN supply_orders so ON so.supply_order_id = i.supply_order_id
         WHERE i.supply_order_id = ?
         ORDER BY p.product_name, i.supply_order_item_id"
    );
    $itemsStatement->execute([(int)$orderId]);
    respond(200, ['success' => true, 'order' => $order, 'items' => $itemsStatement->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    log_error('purchasing order detail', $e);
    respond_error(500, 'Unable to load the LPO details.');
} catch (Throwable $e) {
    log_error('purchasing order detail unexpected', $e);
    respond_error(500, 'Unable to load the LPO details.');
}