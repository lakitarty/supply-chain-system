<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_login($pdo);
    $organizationId = (int)$actor['organization_id'];
    $role = (string)$actor['role'];
    $type = (string)$actor['organization_type'];
    $isRetailer = $type === 'RETAILER';
    $isSupplierBuyer = in_array($type, ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true);
    $isManufacturer = $type === 'MANUFACTURER';

    if ($role !== 'ADMIN' && !($type === 'RETAILER' && $role === 'BRANCH_ADMIN')) {
        respond_error(403, 'Purchasing administrator access is required.');
    }
    if (!$isRetailer && !$isSupplierBuyer && !$isManufacturer) {
        respond_error(403, 'Purchasing access is not available for this account.');
    }

    $branches = [];
    $suppliers = [];
    $assignedBranchId = null;

    if ($isRetailer && $role === 'ADMIN') {
        $branchStatement = $pdo->prepare(
            "SELECT b.branch_id, b.branch_name, b.area_id, area.area_name
             FROM branches b
             LEFT JOIN distribution_areas area ON area.area_id = b.area_id
             WHERE b.organization_id = ? AND b.status = 'ACTIVE'
             ORDER BY b.is_main_branch DESC, b.branch_name"
        );
        $branchStatement->execute([$organizationId]);
        $branches = $branchStatement->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($isRetailer && $role === 'BRANCH_ADMIN') {
        $branchStatement = $pdo->prepare(
            "SELECT b.branch_id, b.branch_name, b.area_id, area.area_name
             FROM branches b
             LEFT JOIN distribution_areas area ON area.area_id = b.area_id
             WHERE b.branch_id = ? AND b.organization_id = ? AND b.status = 'ACTIVE'
             LIMIT 1"
        );
        $branchStatement->execute([(int)$actor['branch_id'], $organizationId]);
        $branches = $branchStatement->fetchAll(PDO::FETCH_ASSOC);
        if (!$branches) {
            respond_error(403, 'Your assigned branch is not active.');
        }
        $assignedBranchId = (int)$actor['branch_id'];
    }

    if ($isRetailer) {
        $supplierStatement = $pdo->prepare(
            "SELECT DISTINCT o.organization_id AS supplier_id,
                    o.organization_name AS supplier_name, o.organization_type
             FROM organizations o
             INNER JOIN organization_relationships r
                ON r.status = 'ACTIVE'
               AND r.start_date <= CURDATE()
               AND (r.end_date IS NULL OR r.end_date >= CURDATE())
               AND r.relationship_type = 'SUPPLIES'
               AND ((r.source_organization_id = ? AND r.target_organization_id = o.organization_id)
                    OR (r.target_organization_id = ? AND r.source_organization_id = o.organization_id))
             WHERE o.status = 'ACTIVE'
               AND o.organization_type IN ('SUPPLIER', 'MANUFACTURER_SUPPLIER')
             ORDER BY o.organization_name"
        );
        $supplierStatement->execute([$organizationId, $organizationId]);
        $suppliers = $supplierStatement->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($isSupplierBuyer) {
        $supplierStatement = $pdo->prepare(
            "SELECT DISTINCT o.organization_id AS supplier_id,
                    o.organization_name AS supplier_name, o.organization_type
             FROM organizations o
             INNER JOIN organization_relationships r
                ON r.source_organization_id = o.organization_id
               AND r.target_organization_id = ?
               AND r.relationship_type = 'SUPPLIES'
               AND r.status = 'ACTIVE'
               AND r.start_date <= CURDATE()
               AND (r.end_date IS NULL OR r.end_date >= CURDATE())
             WHERE o.status = 'ACTIVE'
               AND o.organization_type IN ('MANUFACTURER', 'MANUFACTURER_SUPPLIER')
             ORDER BY o.organization_name"
        );
        $supplierStatement->execute([$organizationId]);
        $suppliers = $supplierStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    $branchId = filter_var($_GET['branchId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($isRetailer) {
        if ($assignedBranchId !== null) {
            $branchId = $assignedBranchId;
        } elseif ($branchId === false || $branchId === null) {
            $branchId = $branches ? (int)$branches[0]['branch_id'] : null;
        }
        if ($branchId !== null) {
            $branchExists = false;
            foreach ($branches as $branch) {
                if ((int)$branch['branch_id'] === (int)$branchId) { $branchExists = true; break; }
            }
            if (!$branchExists) {
                respond_error(403, 'Choose an active branch belonging to your organisation.');
            }
        }
    } else {
        $branchId = null;
    }

    $productSupplierId = null;
    if (isset($_GET['supplierId']) && $_GET['supplierId'] !== '') {
        $validatedSupplierId = filter_var($_GET['supplierId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($validatedSupplierId === false) { respond_error(400, 'Invalid account selection.'); }
        $productSupplierId = (int)$validatedSupplierId;
        $allowed = false;
        foreach ($suppliers as $candidate) {
            if ((int)$candidate['supplier_id'] === $productSupplierId) { $allowed = true; break; }
        }
        if (!$allowed) { respond_error(403, 'This organisation is not an approved supply partner.'); }
    }

    $products = [];
    if ($isRetailer && $branchId !== null) {
        $productStatement = $pdo->prepare(
            "SELECT DISTINCT
                    p.product_id, p.product_name, p.manufacturer_id,
                    p.availability_status AS product_availability_status,
                    p.status AS product_status,
                    manufacturer.organization_name AS manufacturer_name,
                    pv.product_version_id, pv.version_number, pv.barcode,
                    pv.effective_from AS version_effective_from,
                    pv.effective_to AS version_effective_to,
                    pv.pack_size, pv.packaging_description,
                    o.organization_id AS supplier_id, o.organization_name AS supplier_name,
                    offer.unit_price, offer.price_basis, offer.currency_code,
                    offer.supplier_product_offer_id, NULL AS source_product_price_id,
                    COALESCE(supply.minimum_order_quantity, 1) AS minimum_order_quantity,
                    COALESCE(supply.order_multiple, 1) AS order_multiple,
                    COALESCE(supply.lead_time_days, 0) AS lead_time_days,
                    CASE
                      WHEN pv.effective_from > CURDATE()
                        OR (pv.effective_to IS NOT NULL AND pv.effective_to < CURDATE())
                        OR p.availability_status <> 'AVAILABLE' OR p.status <> 'ACTIVE'
                        OR offer.supplier_product_offer_id IS NULL
                      THEN 'INACTIVE'
                      ELSE COALESCE(supply.availability_status, 'AVAILABLE')
                    END AS availability_status,
                    COALESCE(
                      (SELECT tc.tax_category FROM product_tax_classifications tc
                       WHERE tc.product_version_id = pv.product_version_id
                         AND tc.manufacturer_id = p.manufacturer_id
                         AND tc.effective_from <= GREATEST(CURDATE(), pv.effective_from)
                         AND (tc.effective_to IS NULL OR tc.effective_to >= GREATEST(CURDATE(), pv.effective_from))
                       ORDER BY tc.effective_from DESC, tc.product_tax_classification_id DESC LIMIT 1),
                      (SELECT cp.tax_category FROM product_prices cp
                       WHERE cp.organization_id = p.manufacturer_id AND cp.product_id = p.product_id
                         AND cp.product_version_id = pv.product_version_id AND cp.price_type = 'COST'
                         AND cp.effective_from <= GREATEST(CURDATE(), pv.effective_from)
                         AND (cp.effective_to IS NULL OR cp.effective_to >= GREATEST(CURDATE(), pv.effective_from))
                       ORDER BY cp.effective_from DESC, cp.price_id DESC LIMIT 1)
                    ) AS tax_category
             FROM branches b
             INNER JOIN product_suppliers ps
                ON ps.area_id = b.area_id AND ps.status = 'ACTIVE'
               AND ps.start_date <= CURDATE() AND (ps.end_date IS NULL OR ps.end_date >= CURDATE())
             INNER JOIN products p ON p.product_id = ps.product_id
             INNER JOIN organizations manufacturer ON manufacturer.organization_id = p.manufacturer_id AND manufacturer.status = 'ACTIVE'
             INNER JOIN organizations o ON o.organization_id = ps.supplier_id AND o.status = 'ACTIVE'
               AND o.organization_type IN ('SUPPLIER', 'MANUFACTURER_SUPPLIER')
             INNER JOIN organization_relationships r
                ON r.status = 'ACTIVE' AND r.start_date <= CURDATE()
               AND (r.end_date IS NULL OR r.end_date >= CURDATE()) AND r.relationship_type = 'SUPPLIES'
               AND ((r.source_organization_id = b.organization_id AND r.target_organization_id = o.organization_id)
                    OR (r.target_organization_id = b.organization_id AND r.source_organization_id = o.organization_id))
             INNER JOIN product_versions pv ON pv.product_id = p.product_id AND pv.is_current = 1
             LEFT JOIN supplier_product_offers offer
                ON offer.supplier_id = o.organization_id AND offer.product_id = p.product_id
               AND offer.product_version_id = pv.product_version_id AND offer.area_id = b.area_id
               AND offer.status = 'ACTIVE' AND offer.currency_code = 'KES'
               AND offer.effective_from <= GREATEST(CURDATE(), pv.effective_from)
               AND (offer.effective_to IS NULL OR offer.effective_to >= GREATEST(CURDATE(), pv.effective_from))
             LEFT JOIN supplier_product_supply supply ON supply.supplier_id = o.organization_id AND supply.product_id = p.product_id
             WHERE b.branch_id = ? AND b.organization_id = ? AND b.status = 'ACTIVE' AND b.area_id IS NOT NULL
               AND (? = 0 OR ps.supplier_id = ?)
             ORDER BY p.product_name, o.organization_name"
        );
        $selectedSupplier = $productSupplierId ?? 0;
        $productStatement->execute([(int)$branchId, $organizationId, $selectedSupplier, $selectedSupplier]);
        $products = $productStatement->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($isSupplierBuyer && $productSupplierId !== null) {
        $productStatement = $pdo->prepare(
            "SELECT
                    p.product_id, p.product_name, p.manufacturer_id,
                    p.availability_status AS product_availability_status,
                    p.status AS product_status,
                    manufacturer.organization_name AS manufacturer_name,
                    pv.product_version_id, pv.version_number, pv.barcode,
                    pv.effective_from AS version_effective_from,
                    pv.effective_to AS version_effective_to,
                    pv.pack_size, pv.packaging_description,
                    manufacturer.organization_id AS supplier_id,
                    manufacturer.organization_name AS supplier_name,
                    selling.price_amount AS unit_price,
                    CASE WHEN selling.price_includes_vat = 1 THEN 'VAT_INCLUSIVE' ELSE 'VAT_EXCLUSIVE' END AS price_basis,
                    'KES' AS currency_code,
                    NULL AS supplier_product_offer_id,
                    selling.price_id AS source_product_price_id,
                    1 AS minimum_order_quantity, 1 AS order_multiple, 0 AS lead_time_days,
                    CASE
                      WHEN p.status <> 'ACTIVE' OR p.availability_status <> 'AVAILABLE'
                        OR pv.effective_from > CURDATE()
                        OR (pv.effective_to IS NOT NULL AND pv.effective_to < CURDATE())
                        OR selling.price_id IS NULL
                      THEN 'INACTIVE' ELSE 'AVAILABLE'
                    END AS availability_status,
                    COALESCE(
                      (SELECT tc.tax_category FROM product_tax_classifications tc
                       WHERE tc.product_version_id = pv.product_version_id
                         AND tc.manufacturer_id = p.manufacturer_id
                         AND tc.effective_from <= GREATEST(CURDATE(), pv.effective_from)
                         AND (tc.effective_to IS NULL OR tc.effective_to >= GREATEST(CURDATE(), pv.effective_from))
                       ORDER BY tc.effective_from DESC, tc.product_tax_classification_id DESC LIMIT 1),
                      selling.tax_category
                    ) AS tax_category
             FROM products p
             INNER JOIN organizations manufacturer
                ON manufacturer.organization_id = p.manufacturer_id
               AND manufacturer.organization_id = ? AND manufacturer.status = 'ACTIVE'
             INNER JOIN product_versions pv ON pv.product_id = p.product_id AND pv.is_current = 1
             INNER JOIN organization_relationships r
                ON r.source_organization_id = manufacturer.organization_id
               AND r.target_organization_id = ? AND r.relationship_type = 'SUPPLIES'
               AND r.status = 'ACTIVE' AND r.start_date <= CURDATE()
               AND (r.end_date IS NULL OR r.end_date >= CURDATE())
             LEFT JOIN product_prices selling
                ON selling.price_id = (
                    SELECT pp.price_id FROM product_prices pp
                    WHERE pp.organization_id = manufacturer.organization_id
                      AND pp.product_id = p.product_id
                      AND pp.product_version_id = pv.product_version_id
                      AND pp.price_type = 'SELLING'
                      AND pp.effective_from <= GREATEST(CURDATE(), pv.effective_from)
                      AND (pp.effective_to IS NULL OR pp.effective_to >= GREATEST(CURDATE(), pv.effective_from))
                    ORDER BY pp.effective_from DESC, pp.price_id DESC LIMIT 1
                )
             ORDER BY p.product_name"
        );
        $productStatement->execute([$productSupplierId, $organizationId]);
        $products = $productStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    $orderValue = "(SELECT COALESCE(SUM(CASE
        WHEN i.price_basis = 'VAT_EXCLUSIVE' THEN i.quantity_ordered * ROUND(i.unit_price * (1 + CASE
          WHEN COALESCE(i.tax_category, (SELECT tc.tax_category FROM product_tax_classifications tc
            WHERE tc.product_version_id = i.product_version_id AND tc.effective_from <= so.order_date
              AND (tc.effective_to IS NULL OR tc.effective_to >= so.order_date)
            ORDER BY tc.effective_from DESC, tc.product_tax_classification_id DESC LIMIT 1), 'STANDARD_RATED') = 'STANDARD_RATED'
          THEN 0.16 ELSE 0 END), 2)
        ELSE i.quantity_ordered * i.unit_price END), 0)
       FROM supply_order_items i WHERE i.supply_order_id = so.supply_order_id) AS order_value";

    $baseOrders = "SELECT so.supply_order_id, so.lpo_number, so.retailer_id, so.supplier_id, so.order_direction, so.order_date, so.expected_delivery_date,
                          so.status, so.branch_id, so.reviewed_at, so.rejection_reason,
                          b.branch_name, buyer.organization_name AS retailer_name,
                          seller.organization_name AS supplier_name,
                          (SELECT COUNT(*) FROM supply_order_items i WHERE i.supply_order_id = so.supply_order_id) AS item_count,
                          (SELECT COALESCE(SUM(i.quantity_ordered), 0) FROM supply_order_items i WHERE i.supply_order_id = so.supply_order_id) AS quantity_ordered,
                          $orderValue
                   FROM supply_orders so
                   INNER JOIN organizations buyer ON buyer.organization_id = so.retailer_id
                   INNER JOIN organizations seller ON seller.organization_id = so.supplier_id
                   LEFT JOIN branches b ON b.branch_id = so.branch_id ";

    if ($isRetailer) {
        $sql = $baseOrders . " WHERE so.retailer_id = ? AND so.order_direction = 'RETAILER_TO_SUPPLIER'
                              AND (? IS NULL OR so.branch_id = ?)
                              ORDER BY so.supply_order_id DESC LIMIT 150";
        $orderStatement = $pdo->prepare($sql);
        $orderStatement->execute([$organizationId, $branchId, $branchId]);
    } elseif ($isSupplierBuyer) {
        $sql = $baseOrders . " WHERE ((so.supplier_id = ? AND so.order_direction IN ('RETAILER_TO_SUPPLIER','SUPPLIER_TO_MANUFACTURER') AND so.status <> 'DRAFT')
                                      OR (so.retailer_id = ? AND so.order_direction = 'SUPPLIER_TO_MANUFACTURER'))
                              ORDER BY so.supply_order_id DESC LIMIT 200";
        $orderStatement = $pdo->prepare($sql);
        $orderStatement->execute([$organizationId, $organizationId]);
    } else {
        $sql = $baseOrders . " WHERE so.supplier_id = ? AND so.order_direction = 'SUPPLIER_TO_MANUFACTURER' AND so.status <> 'DRAFT'
                              ORDER BY so.supply_order_id DESC LIMIT 150";
        $orderStatement = $pdo->prepare($sql);
        $orderStatement->execute([$organizationId]);
    }

    respond(200, [
        'success' => true,
        'organization_type' => $type,
        'organization_id' => $organizationId,
        'branches' => $branches,
        'suppliers' => $suppliers,
        'products' => $products,
        'orders' => $orderStatement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    log_error('purchasing orders list', $e);
    respond_error(500, 'Unable to load purchasing information.');
} catch (Throwable $e) {
    log_error('purchasing orders list unexpected', $e);
    respond_error(500, 'Unable to load purchasing information.');
}