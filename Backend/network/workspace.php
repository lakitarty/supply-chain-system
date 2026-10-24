<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';

try {
    $actor = require_login($pdo);

    if ($actor['role'] === 'BRANCH_ADMIN') {
        $actor = require_branch_admin($pdo);
    } elseif ($actor['role'] !== 'ADMIN') {
        respond_error(403, 'Relationship workspace access is limited to organisation and branch administrators.');
    }

    $relationshipId = filter_var(
        $_GET['relationshipId'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($relationshipId === false || $relationshipId === null || $relationshipId < 1) {
        respond_error(400, 'A valid relationship ID is required.');
    }

    $organizationId = (int)$actor['organization_id'];

    $statement = $pdo->prepare(
        "SELECT r.relationship_id,
                r.status,
                r.relationship_type,
                r.start_date,
                r.end_date,
                r.source_organization_id,
                r.target_organization_id,
                source_org.organization_name AS source_organization_name,
                source_org.organization_type AS source_organization_type,
                target_org.organization_name AS target_organization_name,
                target_org.organization_type AS target_organization_type,
                partner.organization_id AS partner_id,
                partner.organization_name AS partner_name,
                partner.organization_type AS partner_type,
                partner.email AS partner_email,
                partner.phone AS partner_phone,
                partner.address AS partner_address,
                partner.kra_pin AS partner_kra_pin,
                r.source_organization_id = ? AS i_supply
         FROM organization_relationships r
         INNER JOIN organizations source_org
            ON source_org.organization_id = r.source_organization_id
         INNER JOIN organizations target_org
            ON target_org.organization_id = r.target_organization_id
         INNER JOIN organizations partner
            ON partner.organization_id = IF(
                r.source_organization_id = ?,
                r.target_organization_id,
                r.source_organization_id
            )
         WHERE r.relationship_id = ?
           AND (r.source_organization_id = ? OR r.target_organization_id = ?)
           AND r.status = 'ACTIVE'
         LIMIT 1"
    );

    $statement->execute([
        $organizationId,
        $organizationId,
        (int)$relationshipId,
        $organizationId,
        $organizationId
    ]);

    $relationship = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$relationship) {
        respond_error(404, 'An active relationship belonging to your organisation was not found.');
    }

    $branchId = filter_var($_GET['branchId'] ?? null, FILTER_VALIDATE_INT);

    if ($branchId === false || $branchId === null || $branchId < 1) {
        respond_error(400, 'A valid branch ID is required to filter regional products.');
    }

    $branchStatement = $pdo->prepare(
        "SELECT b.branch_id,
                b.branch_name,
                b.area_id,
                b.status,
                area.area_name
         FROM branches b
         LEFT JOIN distribution_areas area ON area.area_id = b.area_id
         WHERE b.branch_id = ?
           AND b.organization_id = ?
           AND b.status = 'ACTIVE'
         LIMIT 1"
    );
    $branchStatement->execute([(int)$branchId, $organizationId]);
    $branch = $branchStatement->fetch(PDO::FETCH_ASSOC);

    if (!$branch) {
        respond_error(404, 'An active branch belonging to your organisation was not found.');
    }

    if ($actor['role'] === 'BRANCH_ADMIN' && (int)$branch['branch_id'] !== (int)$actor['branch_id']) {
        respond_error(403, 'Branch administrators can only view their assigned branch.');
    }

    $branchAreaId = $branch['area_id'] !== null ? (int)$branch['area_id'] : null;
    $partnerId = (int)$relationship['partner_id'];
    $sourceOrganizationId = (int)$relationship['source_organization_id'];
    $targetOrganizationId = (int)$relationship['target_organization_id'];

    $sourceOrganizationType = $relationship['source_organization_type'];
    $targetOrganizationType = $relationship['target_organization_type'];

    $manufacturerToSupplier =
        $sourceOrganizationType === 'MANUFACTURER'
        && in_array($targetOrganizationType, ['SUPPLIER', 'MANUFACTURER_SUPPLIER'], true);

    $supplierToPartner = in_array(
        $sourceOrganizationType,
        ['SUPPLIER', 'MANUFACTURER_SUPPLIER'],
        true
    );

    // A manufacturer-to-supplier relationship uses the receiving supplier's
    // product assignment. Other supplying organisations use their own offers.
    $productSupplierId = $manufacturerToSupplier
        ? $targetOrganizationId
        : $sourceOrganizationId;

    $products = [];

    // A branch without a mapped county must not inherit national coverage.
    if ($branchAreaId !== null) {
        $statement = $pdo->prepare(
            "SELECT DISTINCT
                    p.product_id,
                    p.product_name,
                    p.image_path,
                    p.category,
                    p.description,
                    p.manufacturer_id,
                    manufacturer.organization_name AS manufacturer_name,
                    pv.barcode,
                    pv.pack_size,
                    pv.packaging_description,
                    ps.area_id,
                    area.area_name AS distribution_area_name,
                    COALESCE(sps.availability_status, p.availability_status) AS supply_availability,
                    CASE
                        WHEN p.manufacturer_id = ?
                        THEN 'Manufactured by supplying organisation'
                        ELSE 'Listed as supplier offering'
                    END AS supply_basis,
                    sps.lead_time_days,
                    sps.minimum_order_quantity,
                    sps.order_multiple
             FROM products p
             INNER JOIN organizations manufacturer
                ON manufacturer.organization_id = p.manufacturer_id
             INNER JOIN product_suppliers ps
                ON ps.product_id = p.product_id
               AND ps.supplier_id = ?
               AND ps.area_id = ?
               AND ps.status = 'ACTIVE'
               AND ps.start_date <= CURDATE()
               AND (ps.end_date IS NULL OR ps.end_date >= CURDATE())
             LEFT JOIN product_versions pv
                ON pv.product_id = p.product_id
               AND pv.is_current = 1
             LEFT JOIN supplier_product_supply sps
                ON sps.product_id = p.product_id
               AND sps.supplier_id = ?
               AND sps.availability_status <> 'INACTIVE'
             INNER JOIN distribution_areas area
                ON area.area_id = ps.area_id
             WHERE p.status = 'ACTIVE'
               AND (? = 0 OR p.manufacturer_id = ?)
             ORDER BY p.product_name
             LIMIT 200"
        );

        $statement->execute([
            $sourceOrganizationId,
            $productSupplierId,
            $branchAreaId,
            $productSupplierId,
            $manufacturerToSupplier ? 1 : 0,
            $sourceOrganizationId
        ]);

        $products = $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    $statement = $pdo->prepare(
        "SELECT so.supply_order_id,
                so.order_date,
                so.expected_delivery_date,
                so.status,
                so.branch_id,
                b.branch_name,
                retailer.organization_name AS retailer_name,
                supplier.organization_name AS supplier_name,
                (SELECT COUNT(*)
                   FROM supply_order_items soi
                  WHERE soi.supply_order_id = so.supply_order_id) AS item_count,
                (SELECT COALESCE(SUM(soi.quantity_ordered), 0)
                   FROM supply_order_items soi
                  WHERE soi.supply_order_id = so.supply_order_id) AS quantity_ordered,
                (SELECT COALESCE(SUM(soi.quantity_received), 0)
                   FROM supply_order_items soi
                  WHERE soi.supply_order_id = so.supply_order_id) AS quantity_received,
                (SELECT SUM(soi.quantity_ordered * soi.unit_price)
                   FROM supply_order_items soi
                  WHERE soi.supply_order_id = so.supply_order_id) AS order_value
         FROM supply_orders so
         INNER JOIN organizations retailer
            ON retailer.organization_id = so.retailer_id
         INNER JOIN organizations supplier
            ON supplier.organization_id = so.supplier_id
         LEFT JOIN branches b ON b.branch_id = so.branch_id
         WHERE (
             (so.retailer_id = ? AND so.supplier_id = ?)
             OR
             (so.retailer_id = ? AND so.supplier_id = ?)
         )
         ORDER BY so.order_date DESC, so.supply_order_id DESC
         LIMIT 50"
    );

    $statement->execute([
        $organizationId,
        $partnerId,
        $partnerId,
        $organizationId
    ]);

    $orders = $statement->fetchAll(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare(
        "SELECT DISTINCT
                pr.promotion_id,
                pr.promotion_name,
                pr.description,
                pr.start_date,
                pr.end_date,
                pr.status,
                owner.organization_name AS owner_name,
                pp.status AS participation_status,
                (SELECT COUNT(*)
                   FROM promotion_products px
                  WHERE px.promotion_id = pr.promotion_id) AS product_count
         FROM promotions pr
         INNER JOIN organizations owner
            ON owner.organization_id = pr.organization_id
         INNER JOIN promotion_participants pp
            ON pp.promotion_id = pr.promotion_id
         WHERE (
             (pr.organization_id = ? AND pp.organization_id = ?)
             OR
             (pr.organization_id = ? AND pp.organization_id = ?)
         )
           AND pr.status IN ('APPROVED', 'SCHEDULED', 'ACTIVE')
           AND pr.end_date >= CURDATE()
         ORDER BY pr.start_date DESC
         LIMIT 30"
    );

    $statement->execute([
        $partnerId,
        $organizationId,
        $organizationId,
        $partnerId
    ]);

    $promotions = $statement->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'relationship' => $relationship,
        'branch' => [
            'branch_id' => (int)$branch['branch_id'],
            'branch_name' => $branch['branch_name'],
            'area_id' => $branchAreaId,
            'area_name' => $branch['area_name'] ?? null
        ],
        'products' => $products,
        'orders' => $orders,
        'promotions' => $promotions
    ]);

} catch (PDOException $e) {
    log_error('relationship workspace', $e);
    respond_error(500, 'Unable to load the relationship workspace.');
}