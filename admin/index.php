<?php
/**
 * Admin entry point - redirects to main router.
 * This file exists because the admin/ directory is a real folder
 * and Apache serves it directly instead of routing through the main index.php.
 */
require_once dirname(__DIR__) . '/index.php';
