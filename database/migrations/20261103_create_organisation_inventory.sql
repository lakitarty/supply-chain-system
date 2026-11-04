CREATE TABLE inventory_locations (
    inventory_location_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    organization_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    location_code VARCHAR(40) NOT NULL,
    location_name VARCHAR(100) NOT NULL,
    location_type ENUM(
        'CENTRAL_WAREHOUSE',
        'BACK_OFFICE',
        'SHOP_FLOOR',
        'OTHER'
    ) NOT NULL,
    is_default TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (inventory_location_id),
    UNIQUE KEY uq_inventory_location_code (
        organization_id,
        branch_id,
        location_code
    ),
    UNIQUE KEY uq_inventory_location_reference (
        inventory_location_id,
        organization_id,
        branch_id
    ),
    KEY idx_inventory_location_branch (
        organization_id,
        branch_id,
        status
    ),
    CONSTRAINT fk_inventory_location_branch
        FOREIGN KEY (organization_id, branch_id)
        REFERENCES branches (organization_id, branch_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_stock (
    inventory_stock_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    organization_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    inventory_location_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_version_id INT UNSIGNED NOT NULL,
    quantity_on_hand BIGINT UNSIGNED NOT NULL DEFAULT 0,
    reorder_level BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (inventory_stock_id),
    UNIQUE KEY uq_inventory_stock_location_product (
        inventory_location_id,
        product_id,
        product_version_id
    ),
    KEY idx_inventory_stock_organisation (
        organization_id,
        branch_id,
        inventory_location_id
    ),
    CONSTRAINT fk_inventory_stock_location
        FOREIGN KEY (
            inventory_location_id,
            organization_id,
            branch_id
        )
        REFERENCES inventory_locations (
            inventory_location_id,
            organization_id,
            branch_id
        )
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO inventory_locations (
    organization_id,
    branch_id,
    location_code,
    location_name,
    location_type,
    is_default
)
SELECT 5, 7, 'CENTRAL-WAREHOUSE', 'Central Warehouse',
       'CENTRAL_WAREHOUSE', 0
FROM branches b
WHERE b.organization_id = 5
  AND b.branch_id = 7
  AND b.status = 'ACTIVE';

INSERT INTO inventory_locations (
    organization_id,
    branch_id,
    location_code,
    location_name,
    location_type,
    is_default
)
SELECT 5, 7, 'BACK-OFFICE', 'Back Office',
       'BACK_OFFICE', 1
FROM branches b
WHERE b.organization_id = 5
  AND b.branch_id = 7
  AND b.status = 'ACTIVE';

INSERT INTO inventory_locations (
    organization_id,
    branch_id,
    location_code,
    location_name,
    location_type,
    is_default
)
SELECT 5, 7, 'SHOP-FLOOR', 'Shop Floor',
       'SHOP_FLOOR', 0
FROM branches b
WHERE b.organization_id = 5
  AND b.branch_id = 7
  AND b.status = 'ACTIVE';

ALTER TABLE goods_received_notes
    ADD COLUMN inventory_location_id BIGINT UNSIGNED NULL
        AFTER branch_id,
    ADD KEY idx_grn_inventory_location (
        inventory_location_id,
        retailer_id,
        branch_id
    ),
    ADD CONSTRAINT fk_grn_inventory_location
        FOREIGN KEY (
            inventory_location_id,
            retailer_id,
            branch_id
        )
        REFERENCES inventory_locations (
            inventory_location_id,
            organization_id,
            branch_id
        )
        ON UPDATE CASCADE
        ON DELETE RESTRICT;