<?php
class AccessMiddleware
{
    /** $module: a module name from permissions.php, or '*' for any logged-in user */
    public static function handle(string $module): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }

        // stop the browser back button showing protected pages after logout
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if ($module !== '*' && !Auth::can($module)) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            exit;
        }
    }
}