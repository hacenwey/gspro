<?php
/**
 * Super Admin Panel Router
 * Routes: /gestion_commerciale/admin/...
 */

require_once APP_ROOT . '/admin/AdminController.php';

Lang::init();

$path = Tenant::extractAdminPath();
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'];

$admin = new AdminController();

// Route matching
$routes = [
    'GET' => [
        '/'             => 'dashboard',
        '/login'        => 'loginForm',
        '/logout'       => 'logout',
        '/tenants'      => 'tenants',
        '/tenants/create' => 'createTenant',
        '/tenants/edit/{id}' => 'editTenant',
        '/tickets'      => 'tickets',
        '/tickets/view/{id}' => 'viewTicket',
        '/connections'  => 'connections',
        '/polar'        => 'polar',
        '/migrations'   => 'migrations',
        '/migrations/run' => 'runMigrations',
        '/migrations/baseline' => 'baselineMigrations',
    ],
    'POST' => [
        '/login'          => 'login',
        '/tenants/store'  => 'storeTenant',
        '/tenants/update/{id}' => 'updateTenant',
        '/tenants/toggle/{id}' => 'toggleTenant',
        '/tenants/delete/{id}' => 'deleteTenant',
        '/tenants/reset-password/{id}' => 'resetPassword',
        '/tenants/activate/{id}' => 'activateSubscription',
        '/tenants/extend-trial/{id}' => 'extendTrial',
        '/tickets/reply/{id}' => 'replyTicket',
        '/tickets/status/{id}' => 'updateTicketStatus',
        '/polar/save'            => 'savePolar',
        '/polar/products/create' => 'createPolarProduct',
        '/polar/products/archive/{id}' => 'archivePolarProduct',
        '/polar/products/assign' => 'assignPolarProduct',
    ]
];

// Try exact match
if (isset($routes[$method][$path])) {
    $admin->{$routes[$method][$path]}();
    exit;
}

// Try parameterized match
foreach ($routes[$method] ?? [] as $routePath => $action) {
    $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $routePath);
    $pattern = '#^' . $pattern . '$#';
    if (preg_match($pattern, $path, $matches)) {
        array_shift($matches);
        call_user_func_array([$admin, $action], $matches);
        exit;
    }
}

http_response_code(404);
echo '404 - Page admin introuvable';
