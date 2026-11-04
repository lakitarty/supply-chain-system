CREATE TABLE supply_order_item_acknowledgements (
    acknowledgement_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    supply_order_item_id INT UNSIGNED NOT NULL,
    quantity_ordered INT UNSIGNED NOT NULL,
    quantity_available INT UNSIGNED NOT NULL DEFAULT 0,
    quantity_short INT UNSIGNED NOT NULL DEFAULT 0,
    quantity_out_of_stock INT UNSIGNED NOT NULL DEFAULT 0,
    expected_delivery_date DATE DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (acknowledgement_item_id),

    UNIQUE KEY uq_acknowledgement_supply_order_item (supply_order_item_id),

    CONSTRAINT fk_acknowledgement_supply_order_item
        FOREIGN KEY (supply_order_item_id)
        REFERENCES supply_order_items (supply_order_item_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT chk_acknowledgement_quantities
        CHECK (
            quantity_available + quantity_short + quantity_out_of_stock
            = quantity_ordered
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE notifications
    MODIFY notification_type ENUM(
        'PRODUCT_CHANGE',
        'SUPPLY_CHANGE',
        'PROMOTION',
        'STOCK_ALERT',
        'GENERAL',
        'LPO_ACKNOWLEDGED',
        'LPO_REJECTED'
    ) NOT NULL;
