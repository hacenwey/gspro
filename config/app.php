<?php
if (defined('APP_NAME')) { return; }
define('APP_NAME', 'GestionPro');
define('APP_VERSION', '2.0.0');
define('APP_BASE', '/gestion_commerciale');
define('APP_ROOT', dirname(__DIR__));
define('CURRENCY', 'MRU');
define('CURRENCY_SYMBOL', 'UM');
define('TAX_RATE_DEFAULT', 16.00);
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('ITEMS_PER_PAGE', 20);
define('UPLOAD_DIR', APP_ROOT . '/public/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_CASHIER', 'cashier');
define('ROLE_ACCOUNTANT', 'accountant');

// Invoice prefixes
define('INVOICE_PREFIX', 'FAC');
define('QUOTE_PREFIX', 'DEV');
define('CREDIT_NOTE_PREFIX', 'AVO');
define('PO_PREFIX', 'BC');

// Supported languages
define('SUPPORTED_LANGS', ['fr', 'ar']);
define('DEFAULT_LANG', 'fr');

// Multi-tenant mode
define('MULTI_TENANT', true);
