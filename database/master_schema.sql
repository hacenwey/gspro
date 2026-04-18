-- ============================================
-- GestionPro - Master Database Schema
-- Stores tenant (client) information
-- ============================================

CREATE DATABASE IF NOT EXISTS gestionpro_master
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gestionpro_master;

-- ===================== TENANTS =====================
CREATE TABLE tenants (
    id CHAR(36) PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    company_name VARCHAR(200) NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    owner_email VARCHAR(100) NOT NULL,
    owner_phone VARCHAR(20) NULL,
    db_name VARCHAR(100) NOT NULL UNIQUE,
    plan ENUM('free','starter','pro','enterprise') NOT NULL DEFAULT 'starter',
    is_active BOOLEAN DEFAULT TRUE,
    max_users INT DEFAULT 5,
    max_products INT DEFAULT 500,
    expires_at DATE NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_tenants_slug ON tenants(slug);
CREATE INDEX idx_tenants_active ON tenants(is_active);

-- ===================== SUPER ADMINS =====================
CREATE TABLE super_admins (
    id CHAR(36) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================== TENANT ACTIVITY LOG =====================
CREATE TABLE tenant_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NULL,
    admin_id CHAR(36) NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES super_admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_tenant_log_date ON tenant_log(created_at);

-- ===================== SUPPORT TICKETS =====================
CREATE TABLE tickets (
    id CHAR(36) PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(100) NULL,
    subject VARCHAR(255) NOT NULL,
    category ENUM('bug','feature','billing','general','urgent') NOT NULL DEFAULT 'general',
    priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    status ENUM('open','in_progress','waiting','resolved','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_tickets_tenant ON tickets(tenant_id);
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_created ON tickets(created_at);

-- ===================== TICKET MESSAGES =====================
CREATE TABLE ticket_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ticket_id CHAR(36) NOT NULL,
    sender_type ENUM('client','admin') NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_ticket_messages_ticket ON ticket_messages(ticket_id);

-- ===================== LOGIN LOGS =====================
CREATE TABLE login_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NULL,
    tenant_slug VARCHAR(50) NULL,
    actor_type ENUM('tenant_user','super_admin') NOT NULL DEFAULT 'tenant_user',
    user_id CHAR(36) NULL,
    username VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_login_logs_tenant ON login_logs(tenant_id);
CREATE INDEX idx_login_logs_created ON login_logs(created_at);
CREATE INDEX idx_login_logs_success ON login_logs(success);
CREATE INDEX idx_login_logs_actor ON login_logs(actor_type);

-- ===================== POLAR CONFIG (singleton, id=1) =====================
CREATE TABLE polar_config (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    mode ENUM('sandbox','live') NOT NULL DEFAULT 'sandbox',
    sandbox_access_token   VARCHAR(255) NOT NULL DEFAULT '',
    sandbox_webhook_secret VARCHAR(255) NOT NULL DEFAULT '',
    sandbox_starter_product_id    VARCHAR(64) NOT NULL DEFAULT '',
    sandbox_pro_product_id        VARCHAR(64) NOT NULL DEFAULT '',
    sandbox_enterprise_product_id VARCHAR(64) NOT NULL DEFAULT '',
    live_access_token   VARCHAR(255) NOT NULL DEFAULT '',
    live_webhook_secret VARCHAR(255) NOT NULL DEFAULT '',
    live_starter_product_id    VARCHAR(64) NOT NULL DEFAULT '',
    live_pro_product_id        VARCHAR(64) NOT NULL DEFAULT '',
    live_enterprise_product_id VARCHAR(64) NOT NULL DEFAULT '',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO polar_config (id) VALUES (1);
