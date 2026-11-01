<?php
class AccessMiddleware
{
    
    public static function handle(string $module): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }

        
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if ($module !== '*' && !Auth::can($module)) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            exit;
        }
    }
}