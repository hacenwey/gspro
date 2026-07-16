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

// Google Search Console ownership, meta-tag method. Ownership is currently proven
// by the /google0c32589854cd8289.html file instead, so this stays empty — set it
// to the token from Search Console's "HTML tag" method to also emit the meta tag.
// Whichever proof is used must stay in place: Google re-checks periodically.
defined('GOOGLE_SITE_VERIFICATION') || define('GOOGLE_SITE_VERIFICATION', '');

// Business vertical, per tenant (settings key 'business_type'). GestionPro is
// horizontal: a clothing shop must not see tables and a kitchen screen, so the
// restaurant module is gated on this. Retail stays the default.
defined('BUSINESS_RETAIL')     || define('BUSINESS_RETAIL', 'retail');
defined('BUSINESS_RESTAURANT') || define('BUSINESS_RESTAURANT', 'restaurant');
defined('BUSINESS_TYPES')      || define('BUSINESS_TYPES', [BUSINESS_RETAIL, BUSINESS_RESTAURANT]);
defined('BUSINESS_TYPE_DEFAULT') || define('BUSINESS_TYPE_DEFAULT', BUSINESS_RETAIL);

// Multi-tenant mode
defined('MULTI_TENANT')       || define('MULTI_TENANT', true);
