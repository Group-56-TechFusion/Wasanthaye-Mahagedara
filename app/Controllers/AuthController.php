<?php
class AuthController extends Controller
{
    private const DEVICE_COOKIE = 'wm_trusted_device';

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

        
        if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = 'Invalid username or password.';
            $this->redirect('/login');
        }

        if ($user['role'] !== 'admin') {
            $deviceToken = $this->deviceToken();
            $device = new LoginDevice($db);
            $status = $device->recordLoginAttempt(
                (int) $user['id'],
                hash('sha256', $deviceToken),
                is_string($_SERVER['HTTP_USER_AGENT'] ?? null) ? $_SERVER['HTTP_USER_AGENT'] : ''
            );

            if ($status !== 'approved') {
                $_SESSION['error'] = $status === 'pending'
                    ? 'This device is waiting for administrator approval. Try signing in again after it has been approved.'
                    : 'This device is not approved. Please contact an administrator.';
                $this->redirect('/login');
            }
        }

        Auth::login($user);
        $this->redirect('/dashboard');
    }

    private function deviceToken(): string
    {
        $token = $_COOKIE[self::DEVICE_COOKIE] ?? null;
        if (is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1) {
            return $token;
        }

        $token = bin2hex(random_bytes(32));
        $secure = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
        $cookieSet = setcookie(self::DEVICE_COOKIE, $token, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (!$cookieSet) {
            throw new RuntimeException('Unable to set the device approval cookie.');
        }

        return $token;
    }

    public function logout(): void
    {
        Auth::verifyCsrf();
        Auth::logout();
        header('Location: /login');
        exit;
    }
}