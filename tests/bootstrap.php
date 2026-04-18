<?php
/**
 * PHPUnit bootstrap.
 *
 * Loads Composer autoload and then pulls the app's own helpers/classes into
 * scope. Tests should NOT rely on $_SESSION or http globals; instead, units
 * under test (when extracted into services) receive their deps via constructor.
 */

require_once __DIR__ . '/../vendor/autoload.php';

define('PHPUNIT_RUNNING', true);
define('APP_ROOT', realpath(__DIR__ . '/..'));
define('APP_BASE', '');

// Minimal constants some legacy code expects — safe defaults for tests.
if (!defined('CURRENCY'))           define('CURRENCY', 'USD');
if (!defined('CURRENCY_SYMBOL'))    define('CURRENCY_SYMBOL', '$');
if (!defined('DATE_FORMAT'))        define('DATE_FORMAT', 'Y-m-d');
if (!defined('DATETIME_FORMAT'))    define('DATETIME_FORMAT', 'Y-m-d H:i');
if (!defined('TAX_RATE_DEFAULT'))   define('TAX_RATE_DEFAULT', 0);
if (!defined('INVOICE_PREFIX'))     define('INVOICE_PREFIX', 'INV');
if (!defined('ITEMS_PER_PAGE'))     define('ITEMS_PER_PAGE', 20);
