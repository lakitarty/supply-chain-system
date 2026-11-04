CREATE TABLE goods_received_notes (
    grn_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    retailer_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    supply_order_id INT UNSIGNED NOT NULL,

    grn_number VARCHAR(32) NOT NULL,
    lpo_number_snapshot VARCHAR(32) NOT NULL,

    supplier_invoice_number VARCHAR(100) DEFAULT NULL,
    supplier_invoice_date DATE DEFAULT NULL,
    received_date DATE NOT NULL,

    delivery_note_number VARCHAR(100) DEFAULT NULL,
    dispatch_advice_number VARCHAR(100) DEFAULT NULL,
    vehicle_registration VARCHAR(30) DEFAULT NULL,
    driver_name VARCHAR(150) DEFAULT NULL,

    invoice_goods_value DECIMAL(14,2) DEFAULT NULL,
    invoice_vat_amount DECIMAL(14,2) DEFAULT NULL,
    invoice_total DECIMAL(14,2) DEFAULT NULL,

    calculated_goods_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    calculated_vat_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    calculated_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    reconciliation_difference DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    reconciliation_status ENUM(
        'PENDING',
        'MATCHED',
        'VARIANCE_ACCEPTED'
    ) NOT NULL DEFAULT 'PENDING',
    variance_reason VARCHAR(500) DEFAULT NULL,

    remarks TEXT DEFAULT NULL,

    status ENUM('DRAFT', 'POSTED') NOT NULL DEFAULT 'DRAFT',

    created_by INT UNSIGNED NOT NULL,
    posted_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    posted_at DATETIME DEFAULT NULL,

    PRIMARY KEY (grn_id),

    KEY idx_grn_lpo (supply_order_id),
    UNIQUE KEY uq_grn_number (retailer_id, grn_number),
    KEY idx_grn_supplier_invoice (supplier_id, supplier_invoice_number),

    KEY idx_grn_register (retailer_id, received_date, status),
    KEY idx_grn_branch (retailer_id, branch_id, received_date),
    KEY idx_grn_supplier (supplier_id, received_date),

    CONSTRAINT fk_grn_retailer
        FOREIGN KEY (retailer_id)
        REFERENCES organizations (organization_id),

    CONSTRAINT fk_grn_branch
        FOREIGN KEY (retailer_id, branch_id)
        REFERENCES branches (organization_id, branch_id),

    CONSTRAINT fk_grn_supplier
        FOREIGN KEY (supplier_id)
        REFERENCES organizations (organization_id),

    CONSTRAINT fk_grn_lpo
        FOREIGN KEY (supply_order_id)
        REFERENCES supply_orders (supply_order_id),

    CONSTRAINT fk_grn_created_by
        FOREIGN KEY (created_by)
        REFERENCES users (user_id),

    CONSTRAINT fk_grn_posted_by
        FOREIGN KEY (posted_by)
        REFERENCES users (user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE goods_received_note_items (
    grn_item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    grn_id BIGINT UNSIGNED NOT NULL,

    supply_order_item_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_version_id INT UNSIGNED NOT NULL,

    product_name_snapshot VARCHAR(150) NOT NULL,
    barcode_snapshot VARCHAR(100) DEFAULT NULL,
    pack_size_snapshot VARCHAR(100) DEFAULT NULL,

    lpo_quantity_each INT UNSIGNED NOT NULL,
    lpo_unit_price_snapshot DECIMAL(14,4) DEFAULT NULL,
    previous_cost_snapshot DECIMAL(14,4) DEFAULT NULL,

    invoice_quantity DECIMAL(12,3) UNSIGNED NOT NULL DEFAULT 0.000,
    invoice_unit ENUM('EACH', 'CTN') NOT NULL DEFAULT 'EACH',
    units_per_invoice_unit INT UNSIGNED NOT NULL,
    invoice_unit_price DECIMAL(14,4) UNSIGNED NOT NULL DEFAULT 0.0000,

    price_basis ENUM('VAT_INCLUSIVE', 'VAT_EXCLUSIVE') NOT NULL,
    tax_category ENUM('STANDARD_RATED', 'ZERO_RATED', 'EXEMPT') NOT NULL,
    tax_rate_percent DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0.00,

    invoice_goods_value DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0.00,
    invoice_vat_amount DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0.00,
    invoice_line_total DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0.00,

    quantity_delivered_each INT UNSIGNED NOT NULL DEFAULT 0,
    quantity_accepted_each INT UNSIGNED NOT NULL DEFAULT 0,
    quantity_rejected_each INT UNSIGNED NOT NULL DEFAULT 0,

    unit_cost_each DECIMAL(14,4) UNSIGNED DEFAULT NULL,
    discrepancy_reason VARCHAR(500) DEFAULT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (grn_item_id),

    UNIQUE KEY uq_grn_lpo_item (grn_id, supply_order_item_id),
    KEY idx_grn_item_product (product_id, product_version_id),

    CONSTRAINT fk_grn_item_header
        FOREIGN KEY (grn_id)
        REFERENCES goods_received_notes (grn_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_grn_item_lpo_line
        FOREIGN KEY (supply_order_item_id)
        REFERENCES supply_order_items (supply_order_item_id),

    CONSTRAINT fk_grn_item_product
        FOREIGN KEY (product_id)
        REFERENCES products (product_id),

    CONSTRAINT fk_grn_item_version
        FOREIGN KEY (product_id, product_version_id)
        REFERENCES product_versions (product_id, product_version_id),

    CONSTRAINT chk_grn_item_invoice_conversion
        CHECK (
            (invoice_unit = 'EACH' AND units_per_invoice_unit = 1)
            OR
            (invoice_unit = 'CTN' AND units_per_invoice_unit >= 1)
        ),

    CONSTRAINT chk_grn_item_received_quantities
        CHECK (
            quantity_accepted_each + quantity_rejected_each
            = quantity_delivered_each
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE supplier_product_offers
    ADD COLUMN price_unit ENUM('EACH', 'CTN') NOT NULL DEFAULT 'EACH'
        AFTER unit_price,
    ADD COLUMN units_per_carton INT UNSIGNED DEFAULT NULL
        AFTER price_unit,
    ADD CONSTRAINT chk_supplier_offer_price_unit
        CHECK (
            (price_unit = 'CTN' AND units_per_carton IS NOT NULL AND units_per_carton >= 1)
            OR
            (price_unit = 'EACH' AND
                (units_per_carton IS NULL OR units_per_carton >= 1))
        );


ALTER TABLE supply_order_items
    MODIFY COLUMN unit_price DECIMAL(14,4) DEFAULT NULL,
    ADD COLUMN offer_price_unit ENUM('EACH', 'CTN') NOT NULL DEFAULT 'EACH'
        AFTER unit_price,
    ADD COLUMN supplier_order_unit ENUM('EACH', 'CTN') NOT NULL DEFAULT 'EACH'
        AFTER offer_price_unit,
    ADD COLUMN supplier_offer_unit_price DECIMAL(12,2) DEFAULT NULL
        AFTER supplier_order_unit,
    ADD COLUMN supplier_units_per_carton INT UNSIGNED DEFAULT NULL
        AFTER supplier_offer_unit_price,
    ADD CONSTRAINT chk_lpo_item_offer_unit
        CHECK (
            (
                (offer_price_unit = 'CTN' OR supplier_order_unit = 'CTN')
                AND supplier_units_per_carton IS NOT NULL
                AND supplier_units_per_carton >= 1
            )
            OR
            (
                offer_price_unit = 'EACH'
                AND supplier_order_unit = 'EACH'
                AND (supplier_units_per_carton IS NULL OR supplier_units_per_carton >= 1)
            )
        );

UPDATE supply_order_items
SET supplier_offer_unit_price = ROUND(unit_price, 2)
WHERE supplier_offer_unit_price IS NULL
  AND unit_price IS NOT NULL;


ALTER TABLE supplier_product_supply
    ADD COLUMN order_unit ENUM('EACH', 'CTN') NOT NULL DEFAULT 'EACH'
        AFTER order_multiple;


ALTER TABLE product_prices
    MODIFY COLUMN price_amount DECIMAL(14,4) NOT NULL,
    ADD COLUMN source_grn_id BIGINT UNSIGNED DEFAULT NULL
        AFTER effective_to,
    ADD KEY idx_product_prices_source_grn (source_grn_id),
    ADD CONSTRAINT fk_product_prices_source_grn
        FOREIGN KEY (source_grn_id)
        REFERENCES goods_received_notes (grn_id)
        ON DELETE SET NULL;