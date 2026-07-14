-- Offline POS support: idempotency key for sales created offline and synced later.
-- The client generates a UUID per sale; the unique index makes re-syncing a no-op.
ALTER TABLE invoices ADD COLUMN client_uid VARCHAR(64) NULL UNIQUE;
