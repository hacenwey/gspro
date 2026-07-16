-- Fix: order_items.status was missing 'sent', so sending an order to the kitchen
-- failed with "Data truncated for column 'status'". orders.status had it; the line
-- enum did not, while the service writes 'sent' on every line it queues.
--
-- The SQLite-backed tests could not catch this: SQLite has no ENUM, it stores the
-- column as TEXT and takes any value. OrderSchemaTest now pins the schema enums
-- against the statuses the service actually writes.

ALTER TABLE order_items
    MODIFY status ENUM('pending','sent','preparing','ready','served','cancelled') NOT NULL DEFAULT 'pending';
