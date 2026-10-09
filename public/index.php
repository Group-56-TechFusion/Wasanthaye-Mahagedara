<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require BASE_PATH . '/app/Core/helpers.php';

spl_autoload_register(function (string $class): void {
    foreach (['Core', 'Controllers', 'Models', 'Middleware'] as $dir) {
        $file = BASE_PATH . "/app/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

// Session timeout after 30 minutes of inactivity
if (!empty($_SESSION['user'])) {
    if (time() - ($_SESSION['last_activity'] ?? time()) > 1800) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error'] = 'Your session expired. Please log in again.';
    } else {
        $_SESSION['last_activity'] = time();
    }
}

require BASE_PATH . '/routes/web.php';

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

foreach ($routes as [$routeMethod, $uri, $action, $module]) {
    if ($routeMethod === $method && $uri === $path) {
        if ($module !== null) {
            AccessMiddleware::handle($module);
        }
        [$class, $fn] = explode('@', $action);
        (new $class())->$fn();
        exit;
    }
}

http_response_code(404);
echo '404 - Page not found';