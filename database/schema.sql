-- ============================================
-- Gestion Commerciale - Database Schema
-- MySQL 8.0+ / MariaDB 10.5+
-- ============================================

CREATE DATABASE IF NOT EXISTS gestion_commerciale
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gestion_commerciale;

-- ===================== USERS =====================
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','manager','cashier','accountant') NOT NULL DEFAULT 'cashier',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================== CATEGORIES =====================
CREATE TABLE categories (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(100) NOT NULL,
    parent_id CHAR(36) NULL,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_categories_parent ON categories(parent_id);

-- ===================== PRODUCTS =====================
CREATE TABLE products (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    reference VARCHAR(50) NOT NULL UNIQUE,
    barcode VARCHAR(50) NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    category_id CHAR(36) NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'piece',
    purchase_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) DEFAULT 16.00,
    min_stock INT DEFAULT 0,
    current_stock INT DEFAULT 0,
    image VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_barcode ON products(barcode);
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_products_active_stock ON products(is_active, current_stock);

-- ===================== PRODUCT VARIANTS =====================
CREATE TABLE product_variants (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    product_id CHAR(36) NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    attributes JSON NOT NULL,
    price_override DECIMAL(12,2) NULL,
    stock INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== STOCK MOVEMENTS =====================
CREATE TABLE stock_movements (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    product_id CHAR(36) NOT NULL,
    variant_id CHAR(36) NULL,
    type ENUM('in','out','adjustment','transfer') NOT NULL,
    reason ENUM('purchase','sale','return_client','return_supplier','loss','inventory','internal') NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(12,2) NULL,
    reference_type VARCHAR(50) NULL,
    reference_id CHAR(36) NULL,
    notes TEXT NULL,
    user_id CHAR(36) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE INDEX idx_stock_movements_product ON stock_movements(product_id, created_at);
CREATE INDEX idx_stock_movements_type ON stock_movements(type);

-- ===================== CUSTOMERS =====================
CREATE TABLE customers (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    type ENUM('individual','company') NOT NULL DEFAULT 'individual',
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    address TEXT NULL,
    tax_id VARCHAR(50) NULL,
    category ENUM('vip','regular','occasional') DEFAULT 'regular',
    loyalty_points INT DEFAULT 0,
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    balance DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_customers_phone ON customers(phone);
CREATE INDEX idx_customers_name ON customers(last_name);
CREATE INDEX idx_customers_category ON customers(category);

-- ===================== SUPPLIERS =====================
CREATE TABLE suppliers (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    company_name VARCHAR(200) NOT NULL,
    contact_name VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    address TEXT NULL,
    tax_id VARCHAR(50) NULL,
    payment_terms INT DEFAULT 30,
    rating DECIMAL(3,2) DEFAULT 0.00,
    balance DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================== CASH SESSIONS =====================
CREATE TABLE cash_sessions (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    closing_balance DECIMAL(12,2) NULL,
    expected_balance DECIMAL(12,2) NULL,
    difference DECIMAL(12,2) NULL,
    status ENUM('open','closed') DEFAULT 'open',
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE INDEX idx_cash_sessions_user ON cash_sessions(user_id, status);

-- ===================== INVOICES (factures + devis + avoirs) =====================
CREATE TABLE invoices (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    number VARCHAR(30) NOT NULL UNIQUE,
    type ENUM('invoice','quote','credit_note') NOT NULL,
    status ENUM('draft','sent','accepted','refused','expired','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
    customer_id CHAR(36) NULL,
    user_id CHAR(36) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NULL,
    validity_date DATE NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2) DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT NULL,
    quote_id CHAR(36) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (quote_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_invoices_customer ON invoices(customer_id, issue_date);
CREATE INDEX idx_invoices_number ON invoices(number);
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_invoices_type ON invoices(type);

-- ===================== INVOICE ITEMS =====================
CREATE TABLE invoice_items (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    invoice_id CHAR(36) NOT NULL,
    product_id CHAR(36) NULL,
    variant_id CHAR(36) NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    discount_pct DECIMAL(5,2) DEFAULT 0.00,
    line_total DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================== PAYMENTS =====================
CREATE TABLE payments (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    type ENUM('incoming','outgoing') NOT NULL,
    invoice_id CHAR(36) NULL,
    customer_id CHAR(36) NULL,
    supplier_id CHAR(36) NULL,
    cash_session_id CHAR(36) NULL,
    amount DECIMAL(12,2) NOT NULL,
    method ENUM('cash','card','check','transfer','mobile','bankily','masrivi','sedad') NOT NULL DEFAULT 'cash',
    reference VARCHAR(100) NULL,
    payment_date DATE NOT NULL,
    notes TEXT NULL,
    user_id CHAR(36) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE INDEX idx_payments_invoice ON payments(invoice_id);
CREATE INDEX idx_payments_customer ON payments(customer_id);
CREATE INDEX idx_payments_date ON payments(payment_date);

-- ===================== DEBTS =====================
CREATE TABLE debts (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    type ENUM('receivable','payable') NOT NULL,
    customer_id CHAR(36) NULL,
    supplier_id CHAR(36) NULL,
    invoice_id CHAR(36) NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) DEFAULT 0.00,
    remaining DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,
    due_date DATE NOT NULL,
    status ENUM('pending','partial','paid','overdue') DEFAULT 'pending',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_debts_customer ON debts(customer_id, status);
CREATE INDEX idx_debts_supplier ON debts(supplier_id, status);
CREATE INDEX idx_debts_due_date ON debts(due_date);

-- ===================== PURCHASE ORDERS =====================
CREATE TABLE purchase_orders (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    number VARCHAR(30) NOT NULL UNIQUE,
    supplier_id CHAR(36) NOT NULL,
    status ENUM('draft','sent','partial','received','cancelled') NOT NULL DEFAULT 'draft',
    order_date DATE NOT NULL,
    expected_date DATE NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    user_id CHAR(36) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ===================== PURCHASE ORDER ITEMS =====================
CREATE TABLE purchase_order_items (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    purchase_order_id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    received_qty INT DEFAULT 0,
    line_total DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- ===================== SETTINGS =====================
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================== AUDIT LOG =====================
CREATE TABLE audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id CHAR(36) NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_audit_log_entity ON audit_log(entity_type, entity_id);
CREATE INDEX idx_audit_log_date ON audit_log(created_at);

-- ===================== SEED DATA =====================

-- Default admin user (password: admin123)
INSERT INTO users (id, username, email, password_hash, full_name, role) VALUES
(UUID(), 'admin', 'admin@gestionpro.com', '$2y$12$LJ3m4ys3Grd0SBxYmJqSKe8x5z5z5z5z5z5z5z5z5z5z5z5z5z', 'Administrateur', 'admin');

-- Default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', 'Mon Entreprise'),
('company_address', 'Nouakchott, Mauritanie'),
('company_phone', ''),
('company_email', ''),
('company_tax_id', ''),
('company_logo', ''),
('invoice_prefix', 'FAC'),
('quote_prefix', 'DEV'),
('credit_note_prefix', 'AVO'),
('po_prefix', 'BC'),
('default_tax_rate', '16'),
('currency', 'MRU'),
('currency_symbol', 'UM'),
('default_payment_terms', '30'),
('default_quote_validity', '30'),
('low_stock_alert', '1'),
('onboarding_done', '0');

-- Sample categories
INSERT INTO categories (id, name, description) VALUES
(UUID(), 'Alimentation', 'Produits alimentaires'),
(UUID(), 'Boissons', 'Boissons et rafraichissements'),
(UUID(), 'Electronique', 'Appareils electroniques'),
(UUID(), 'Fournitures', 'Fournitures de bureau'),
(UUID(), 'Hygiene', 'Produits d\'hygiene et de soin');
