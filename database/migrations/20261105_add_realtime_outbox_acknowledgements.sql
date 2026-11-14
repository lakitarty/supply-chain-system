ALTER TABLE realtime_outbox
    MODIFY COLUMN status
        ENUM('PENDING','PROCESSING','AWAITING_ACK','DISPATCHED','FAILED')
        NOT NULL DEFAULT 'PENDING',
    ADD COLUMN dispatch_token CHAR(36) NULL
        AFTER locked_at,
    ADD COLUMN acknowledgement_deadline DATETIME(6) NULL
        AFTER dispatch_token;