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
