<?php
class Page
{
    public static function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function image(?string $name): string
    {
        return $name ? '/uploads/inventory/' . rawurlencode($name) : '';
    }

    public static function render(string $view, array $data = [], string $title = 'Inventory'): void
    {
        extract($data);
        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';
        require BASE_PATH . '/app/Views/layouts/main.php';
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    public static function notFound(): void
    {
        http_response_code(404);
        echo '404 - Page not found';
        exit;
    }

    public static function flash(): array
    {
        $flash = [
            'success' => $_SESSION['success'] ?? null,
            'error'   => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['success'], $_SESSION['error']);
        return $flash;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['inventory_token'])) {
            $_SESSION['inventory_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['inventory_token'];
    }

    public static function checkCsrf(): void
    {
        $sent = $_POST['_token'] ?? '';
        if (!is_string($sent) || !hash_equals($_SESSION['inventory_token'] ?? '', $sent)) {
            http_response_code(419);
            echo 'Session token mismatch. Go back, refresh the page and try again.';
            exit;
        }
    }
}