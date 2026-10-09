<?php
abstract class Controller
{
    protected function render(string $view, array $data = [], string $title = ''): void
    {
        extract($data);
        $viewFile = BASE_PATH . "/app/Views/$view.php";
        require BASE_PATH . '/app/Views/layouts/main.php';
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}