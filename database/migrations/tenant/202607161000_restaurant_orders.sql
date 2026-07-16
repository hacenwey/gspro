-- Restaurant module: an order that lives over time, unlike the retail POS sale
-- (cart -> paid invoice, instantly). Gated per tenant by settings.business_type.

CREATE TABLE IF NOT EXISTS service_tables (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    zone VARCHAR(50) NULL,
    seats INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tables_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id CHAR(36) PRIMARY KEY,
    number VARCHAR(30) NOT NULL UNIQUE,
    type ENUM('dine_in','takeaway','delivery') NOT NULL DEFAULT 'dine_in',
    -- open: still being built by the waiter. sent: queued in the kitchen.
    -- preparing/ready: kitchen progress. served: handed over. paid: invoiced.
    status ENUM('open','sent','preparing','ready','served','paid','cancelled') NOT NULL DEFAULT 'open',
    table_id CHAR(36) NULL,
    customer_id CHAR(36) NULL,
    user_id CHAR(36) NOT NULL,
    cash_session_id CHAR(36) NULL,
    invoice_id CHAR(36) NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    ready_at DATETIME NULL,
    closed_at DATETIME NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orders_status (status),
    INDEX idx_orders_table (table_id),
    INDEX idx_orders_created (created_at),
    FOREIGN KEY (table_id) REFERENCES service_tables(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-line status so the kitchen can tick dish by dish, not just whole orders.
CREATE TABLE IF NOT EXISTS order_items (
    id CHAR(36) PRIMARY KEY,
    order_id CHAR(36) NOT NULL,
    product_id CHAR(36) NULL,
    description VARCHAR(200) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','preparing','ready','served','cancelled') NOT NULL DEFAULT 'pending',
    notes VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_items_order (order_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kitchen staff: sees the kitchen screen and nothing else.
ALTER TABLE users MODIFY role ENUM('admin','manager','cashier','accountant','kitchen') NOT NULL DEFAULT 'cashier';
