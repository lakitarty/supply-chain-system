ALTER TABLE inventory_stock
    MODIFY COLUMN quantity_on_hand BIGINT(20) NOT NULL DEFAULT 0;