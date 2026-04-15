<?php
session_start();

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/Lang.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Router.php';

Lang::init();

// ===================== ROUTES =====================

// Auth
Router::get('/login', 'AuthController', 'loginForm');
Router::post('/login', 'AuthController', 'login');
Router::get('/logout', 'AuthController', 'logout');

// Dashboard
Router::get('/dashboard', 'DashboardController', 'index');
Router::get('/api/dashboard/chart', 'DashboardController', 'chartData');

// Products
Router::get('/products', 'ProductController', 'index');
Router::get('/products/create', 'ProductController', 'create');
Router::post('/products/store', 'ProductController', 'store');
Router::get('/products/edit/{id}', 'ProductController', 'edit');
Router::post('/products/update/{id}', 'ProductController', 'update');
Router::post('/products/delete/{id}', 'ProductController', 'delete');
Router::get('/products/view/{id}', 'ProductController', 'view');
Router::post('/products/adjust-stock/{id}', 'ProductController', 'adjustStock');

// Categories
Router::get('/categories', 'CategoryController', 'index');
Router::post('/categories/store', 'CategoryController', 'store');
Router::post('/categories/update/{id}', 'CategoryController', 'update');
Router::post('/categories/delete/{id}', 'CategoryController', 'delete');

// Clients
Router::get('/clients', 'ClientController', 'index');
Router::get('/clients/create', 'ClientController', 'create');
Router::post('/clients/store', 'ClientController', 'store');
Router::get('/clients/edit/{id}', 'ClientController', 'edit');
Router::post('/clients/update/{id}', 'ClientController', 'update');
Router::post('/clients/delete/{id}', 'ClientController', 'delete');
Router::get('/clients/view/{id}', 'ClientController', 'view');

// Suppliers
Router::get('/suppliers', 'SupplierController', 'index');
Router::get('/suppliers/create', 'SupplierController', 'create');
Router::post('/suppliers/store', 'SupplierController', 'store');
Router::get('/suppliers/edit/{id}', 'SupplierController', 'edit');
Router::post('/suppliers/update/{id}', 'SupplierController', 'update');
Router::post('/suppliers/delete/{id}', 'SupplierController', 'delete');
Router::get('/suppliers/view/{id}', 'SupplierController', 'view');

// Cash Register (POS)
Router::get('/caisse', 'CaisseController', 'index');
Router::post('/caisse/open', 'CaisseController', 'open');
Router::post('/caisse/close', 'CaisseController', 'close');
Router::post('/caisse/sell', 'CaisseController', 'sell');
Router::get('/caisse/history', 'CaisseController', 'history');
Router::get('/api/products/search', 'CaisseController', 'searchProducts');

// Invoices & Quotes
Router::get('/invoices', 'InvoiceController', 'index');
Router::get('/invoices/create', 'InvoiceController', 'create');
Router::post('/invoices/store', 'InvoiceController', 'store');
Router::get('/invoices/view/{id}', 'InvoiceController', 'view');
Router::get('/invoices/edit/{id}', 'InvoiceController', 'edit');
Router::post('/invoices/update/{id}', 'InvoiceController', 'update');
Router::post('/invoices/convert/{id}', 'InvoiceController', 'convertToInvoice');
Router::post('/invoices/delete/{id}', 'InvoiceController', 'delete');
Router::get('/invoices/pdf/{id}', 'InvoiceController', 'pdf');

// Debts & Credits
Router::get('/debts', 'DebtController', 'index');
Router::post('/debts/payment/{id}', 'DebtController', 'recordPayment');
Router::get('/debts/view/{id}', 'DebtController', 'view');

// Payments
Router::get('/payments', 'PaymentController', 'index');

// Settings
Router::get('/settings', 'SettingsController', 'index');
Router::post('/settings/update', 'SettingsController', 'update');
Router::post('/settings/users/store', 'SettingsController', 'storeUser');

// Onboarding
Router::get('/onboarding', 'OnboardingController', 'index');
Router::post('/onboarding/company', 'OnboardingController', 'saveCompany');
Router::get('/onboarding/skip', 'OnboardingController', 'skip');

// API endpoints
Router::get('/api/clients/search', 'ClientController', 'search');
Router::get('/api/products/list', 'ProductController', 'apiList');

// Dispatch
Router::dispatch();
