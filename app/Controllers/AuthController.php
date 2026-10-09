<?php
class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        require BASE_PATH . '/app/Views/auth/login.php';
    }

    public function login(): void
    {
        Auth::verifyCsrf();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $_SESSION['error'] = 'Username and password are required.';
            $this->redirect('/login');
        }

        $db   = require BASE_PATH . '/config/database.php';
        $user = User::findByUsername($db, $username);

        // one generic message for every failure, so nothing is revealed
        if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = 'Invalid username or password.';
            $this->redirect('/login');
        }

        Auth::login($user);
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::verifyCsrf();
        Auth::logout();
        header('Location: /login');
        exit;
    }
}