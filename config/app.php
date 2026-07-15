<?php
defined('APP_NAME')           || define('APP_NAME', 'GestionPro');
defined('APP_VERSION')        || define('APP_VERSION', '2.0.0');
defined('APP_BASE')           || define('APP_BASE', '');
defined('APP_ROOT')           || define('APP_ROOT', dirname(__DIR__));
defined('CURRENCY')           || define('CURRENCY', 'MRU');
defined('CURRENCY_SYMBOL')    || define('CURRENCY_SYMBOL', 'UM');
defined('TAX_RATE_DEFAULT')   || define('TAX_RATE_DEFAULT', 0.00);
defined('DATE_FORMAT')        || define('DATE_FORMAT', 'd/m/Y');
defined('DATETIME_FORMAT')    || define('DATETIME_FORMAT', 'd/m/Y H:i');
defined('ITEMS_PER_PAGE')     || define('ITEMS_PER_PAGE', 20);
defined('UPLOAD_DIR')         || define('UPLOAD_DIR', APP_ROOT . '/public/uploads/');
defined('MAX_UPLOAD_SIZE')    || define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Roles
defined('ROLE_ADMIN')         || define('ROLE_ADMIN', 'admin');
defined('ROLE_MANAGER')       || define('ROLE_MANAGER', 'manager');
defined('ROLE_CASHIER')       || define('ROLE_CASHIER', 'cashier');
defined('ROLE_ACCOUNTANT')    || define('ROLE_ACCOUNTANT', 'accountant');

// Back-office roles = everyone except the cashier, who is restricted to the POS.
// Guard non-POS controllers with this group.
defined('ROLES_STAFF')        || define('ROLES_STAFF', [ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT]);

// Invoice prefixes
defined('INVOICE_PREFIX')     || define('INVOICE_PREFIX', 'FAC');
defined('QUOTE_PREFIX')       || define('QUOTE_PREFIX', 'DEV');
defined('CREDIT_NOTE_PREFIX') || define('CREDIT_NOTE_PREFIX', 'AVO');
defined('PO_PREFIX')          || define('PO_PREFIX', 'BC');

// Supported languages
defined('SUPPORTED_LANGS')    || define('SUPPORTED_LANGS', ['en', 'fr', 'ar']);
defined('DEFAULT_LANG')       || define('DEFAULT_LANG', 'fr');

// WhatsApp ordering (Mauritania). International format, digits only, no '+'.
// Used by the landing page "Order on WhatsApp" buttons.
defined('WHATSAPP_ORDER')     || define('WHATSAPP_ORDER', '22232666333');

// Google Search Console ownership token, rendered as a meta tag on the landing.
// Must stay in place: Google re-checks periodically and drops the property if it
// disappears. Set to '' to remove the tag.
defined('GOOGLE_SITE_VERIFICATION') || define('GOOGLE_SITE_VERIFICATION', 'YXDYAYN8Xb7mfIf3rUf713srmXPV_6vjNcCLyrB7rKI');

// Multi-tenant mode
defined('MULTI_TENANT')       || define('MULTI_TENANT', true);
