<?php
class ModuleController extends Controller
{
    public function users(): void
    {
        $db = require BASE_PATH . '/config/database.php';
        $this->render('users/index', [
            'users' => User::all($db),
            'branches' => Wedding::branches($db),
            'success' => $_SESSION['users_success'] ?? null,
            'error' => $_SESSION['users_error'] ?? null,
        ], 'User Management');

        unset($_SESSION['users_success'], $_SESSION['users_error']);
    }

    public function createUser(): void
    {
        Auth::verifyCsrf();

        $usernameValue = $_POST['username'] ?? '';
        $username = is_string($usernameValue) ? trim($usernameValue) : '';
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';
        $roleValue = $_POST['role'] ?? '';
        $role = is_string($roleValue) ? $roleValue : '';
        $branchValue = $_POST['branch_id'] ?? '';
        $errors = [];

        if ($username === '' || mb_strlen($username) > 50) {
            $errors[] = 'Username is required and must be at most 50 characters.';
        }
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Password must be between 8 and 72 characters.';
        }
        if (!is_string($passwordConfirmation) || $password !== $passwordConfirmation) {
            $errors[] = 'Password confirmation does not match.';
        }
        if (!in_array($role, ['admin', 'branch_manager'], true)) {
            $errors[] = 'Select a valid account role.';
        }

        $db = require BASE_PATH . '/config/database.php';
        $branchId = null;
        if ($role === 'branch_manager') {
            $validatedBranchId = filter_var($branchValue, FILTER_VALIDATE_INT);
            $branches = Wedding::branches($db);
            $validBranchIds = array_map(static fn(array $branch): int => (int) $branch['id'], $branches);
            if ($validatedBranchId === false || !in_array($validatedBranchId, $validBranchIds, true)) {
                $errors[] = 'Select a valid showroom for the branch manager.';
            } else {
                $branchId = $validatedBranchId;
            }
        }

        if (!$errors && User::findByUsername($db, $username)) {
            $errors[] = 'That username is already in use.';
        }

        if ($errors) {
            $_SESSION['users_error'] = implode(' ', $errors);
            $this->redirect('/users?add=1');
        }

        try {
            User::create($db, $username, $password, $role, $branchId);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000' || (int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }
            $_SESSION['users_error'] = 'That username is already in use.';
            $this->redirect('/users?add=1');
        }

        $_SESSION['users_success'] = 'User account created successfully.';
        $this->redirect('/users');
    }

    public function branches()  { $this->page('Branches'); }
    public function customers() { $this->page('Customer Management'); }

    
    public function calendar(): void
    {
        $today = new DateTimeImmutable('today');
        $selectedDate = $this->parseDate($_GET['date'] ?? null, 'Y-m-d') ?? $today;
        $month = $this->parseDate($_GET['month'] ?? null, 'Y-m') ?? $selectedDate;
        $month = $month->modify('first day of this month');

        $firstOfMonth = $month->modify('first day of this month');
        $gridStart = $firstOfMonth->modify('-' . $firstOfMonth->format('w') . ' days');
        $gridEnd = $gridStart->modify('+41 days');

        
        
        $bills = new Bill();
        $calendarWeddings = $bills->weddingsBetween($gridStart, $gridEnd);
        $selectedWeddings = $bills->weddingsBetween($selectedDate, $selectedDate);
        $upcomingStart = $selectedDate->modify('+1 day');
        $upcomingEnd = $selectedDate->modify('+7 days');
        $upcomingWeddings = $bills->weddingsBetween($upcomingStart, $upcomingEnd);

        $this->render('calendar/index', [
            'month' => $month,
            'gridStart' => $gridStart,
            'calendarWeddings' => $calendarWeddings,
            'selectedDate' => $selectedDate,
            'selectedWeddings' => $selectedWeddings,
            'upcomingStart' => $upcomingStart,
            'upcomingEnd' => $upcomingEnd,
            'upcomingWeddings' => $upcomingWeddings,
        ], 'Wedding Calendar');
    }

    public function billing()   { $this->page('Billing and Invoices'); }
    public function inventory() { $this->page('Inventory'); }
    public function reports()   { $this->page('Reports'); }

    private function parseDate($value, string $format): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format($format) !== $value) {
            return null;
        }

        return $date;
    }

    private function page(string $heading): void
    {
        $this->render('module/placeholder', ['heading' => $heading], $heading);
    }
}