<?php
class Router {
    private static array $routes = [];

    public static function get(string $path, string $controller, string $method): void {
        self::$routes['GET'][$path] = ['controller' => $controller, 'method' => $method];
    }

    public static function post(string $path, string $controller, string $method): void {
        self::$routes['POST'][$path] = ['controller' => $controller, 'method' => $method];
    }

    public static function dispatch(): void {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = APP_URL;
        $path = str_replace($base, '', $uri);
        $path = '/' . trim($path, '/');
        if ($path === '/') $path = '/dashboard';

        $method = $_SERVER['REQUEST_METHOD'];

        // Check for exact match
        if (isset(self::$routes[$method][$path])) {
            $route = self::$routes[$method][$path];
            self::execute($route);
            return;
        }

        // Check for parameterized routes
        foreach (self::$routes[$method] ?? [] as $routePath => $route) {
            $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                self::execute($route, $matches);
                return;
            }
        }

        http_response_code(404);
        require APP_ROOT . '/views/layout/404.php';
    }

    private static function execute(array $route, array $params = []): void {
        $controllerFile = APP_ROOT . '/controllers/' . $route['controller'] . '.php';
        if (!file_exists($controllerFile)) {
            http_response_code(500);
            die("Controller not found: " . $route['controller']);
        }
        require_once $controllerFile;
        $controller = new $route['controller']();
        call_user_func_array([$controller, $route['method']], $params);
    }
}
