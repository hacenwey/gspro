-- Split & merge bills.
--
-- Payment moves from the order down to the LINE: a table can settle in several
-- invoices, each covering the dishes that bill paid for. An order is only
-- 'paid' once no live line is left without an invoice.
--
-- COLLATE is spelled out on purpose: the tenant DBs are utf8mb4_unicode_ci while
-- MySQL 8 defaults to utf8mb4_0900_ai_ci, and mismatched collations make the
-- foreign keys below impossible (that bit us on the orders migration).

ALTER TABLE order_items
    ADD COLUMN invoice_id CHAR(36) COLLATE utf8mb4_unicode_ci NULL;

ALTER TABLE order_items
    ADD CONSTRAINT fk_order_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL;

CREATE INDEX idx_order_items_invoice ON order_items(invoice_id);

-- Traceability for a merged ticket: it keeps its number and points at its host.
ALTER TABLE orders
    ADD COLUMN merged_into_id CHAR(36) COLLATE utf8mb4_unicode_ci NULL;

ALTER TABLE orders
    ADD CONSTRAINT fk_orders_merged FOREIGN KEY (merged_into_id) REFERENCES orders(id) ON DELETE SET NULL;
