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
