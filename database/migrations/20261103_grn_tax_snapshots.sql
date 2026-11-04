ALTER TABLE goods_received_note_items
    MODIFY COLUMN tax_category
        ENUM('STANDARD_RATED','ZERO_RATED','EXEMPT') NULL DEFAULT NULL,
    MODIFY COLUMN tax_rate_percent
        DECIMAL(5,2) UNSIGNED NULL DEFAULT NULL,
    ADD COLUMN taxable_supply TINYINT(1) UNSIGNED NULL
        AFTER tax_rate_percent,
    ADD COLUMN tax_calculation_status
        ENUM(
            'PENDING',
            'CALCULATED',
            'MISSING_CLASSIFICATION',
            'AMBIGUOUS_CLASSIFICATION',
            'MISSING_RATE',
            'AMBIGUOUS_RATE'
        ) NOT NULL DEFAULT 'PENDING'
        AFTER taxable_supply,
    MODIFY COLUMN invoice_goods_value DECIMAL(14,2) UNSIGNED NULL DEFAULT NULL,
    MODIFY COLUMN invoice_vat_amount DECIMAL(14,2) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE goods_received_notes
    MODIFY COLUMN calculated_goods_value DECIMAL(14,2) NULL DEFAULT NULL,
    MODIFY COLUMN calculated_vat_amount DECIMAL(14,2) NULL DEFAULT NULL,
    MODIFY COLUMN reconciliation_difference DECIMAL(14,2) NULL DEFAULT NULL;
