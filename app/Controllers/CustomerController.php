<?php
class CustomerController extends Controller
{
    private const SOURCES = [
        'call_center' => 'Call Center',
        'walk_in' => 'Walk-in',
        'showroom' => 'Showroom',
        'mobile_shop' => 'Mobile Shop',
    ];

    private Customer $customers;

    public function __construct()
    {
        $this->customers = new Customer();
    }

    public function index(): void
    {
        $filters = [
            'q' => trim($this->postOrGetString($_GET['q'] ?? '')),
            'source' => $this->postOrGetString($_GET['source'] ?? ''),
            'from' => $this->dateFilter($_GET['from'] ?? ''),
            'to' => $this->dateFilter($_GET['to'] ?? ''),
        ];
        $errors = [];
        foreach (['from', 'to'] as $dateField) {
            $raw = $this->postOrGetString($_GET[$dateField] ?? '');
            if ($raw !== '' && $filters[$dateField] === '') {
                $errors[] = 'Enter a valid ' . ($dateField === 'from' ? 'start' : 'end') . ' date.';
            }
        }
        if ($filters['source'] !== '' && !isset(self::SOURCES[$filters['source']])) {
            $errors[] = 'Select a valid customer source.';
        }
        if ($filters['from'] !== '' && $filters['to'] !== '' && $filters['from'] > $filters['to']) {
            $errors[] = 'The start date must be on or before the end date.';
        }

        $allCustomers = $this->customers->all($this->scope());
        $rows = array_values(array_filter($allCustomers, function (array $customer) use ($filters): bool {
            if ($filters['q'] !== ''
                && stripos($customer['name'], $filters['q']) === false
                && stripos($customer['phone'], $filters['q']) === false
                && stripos($customer['code'], $filters['q']) === false) {
                return false;
            }
            if ($filters['source'] !== '' && $customer['source'] !== $filters['source']) {
                return false;
            }
            $date = $customer['last_visit'] ?: $customer['created_date'];
            if ($filters['from'] !== '' && $date < $filters['from']) {
                return false;
            }
            if ($filters['to'] !== '' && $date > $filters['to']) {
                return false;
            }
            return true;
        }));

        $month = date('Y-m');
        $stats = [
            'total' => count($allCustomers),
            'new' => 0,
            'repeat' => 0,
            'visits' => 0,
        ];
        foreach ($allCustomers as $customer) {
            $firstVisit = $customer['first_visit'] ?: $customer['created_date'];
            if ($firstVisit !== '' && substr($firstVisit, 0, 7) === $month) {
                $stats['new']++;
            }
            if ($customer['visits'] > 1) {
                $stats['repeat']++;
            }
            $stats['visits'] += $customer['visits'];
        }

        $this->render('customers/index', [
            'customers' => $rows,
            'stats' => $stats,
            'filters' => $filters,
            'sources' => self::SOURCES,
            'errors' => array_merge($errors, $_SESSION['customer_errors'] ?? []),
            'success' => $_SESSION['customer_success'] ?? null,
        ], 'Customer Management');
        unset($_SESSION['customer_errors'], $_SESSION['customer_success']);
    }

    public function show(): void
    {
        $phone = trim($this->postOrGetString($_GET['phone'] ?? ''));
        $customer = $this->customers->findByPhone($phone, $this->scope());
        if (!$customer) {
            http_response_code(404);
            echo '404 - Customer not found';
            return;
        }

        $this->render('customers/show', [
            'customer' => $customer,
            'history' => $this->customers->history($phone, $this->scope()),
            'sources' => self::SOURCES,
        ], 'Customer Details');
    }

    public function store(): void
    {
        Auth::verifyCsrf();

        $data = [
            'name' => trim($this->postOrGetString($_POST['name'] ?? '')),
            'phone' => trim($this->postOrGetString($_POST['phone'] ?? '')),
            'email' => trim($this->postOrGetString($_POST['email'] ?? '')),
            'address' => trim($this->postOrGetString($_POST['address'] ?? '')),
            'source' => $this->postOrGetString($_POST['source'] ?? ''),
        ];
        $errors = [];
        if ($data['name'] === '' || mb_strlen($data['name']) > 150) {
            $errors[] = 'Enter a customer name (up to 150 characters).';
        }
        if ($data['phone'] === '' || mb_strlen($data['phone']) > 25) {
            $errors[] = 'Enter a phone number (up to 25 characters).';
        } elseif ($this->customers->phoneExists($data['phone'])) {
            $errors[] = 'A customer with that phone number already exists.';
        }
        if ($data['email'] !== '' && (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 150)) {
            $errors[] = 'Enter a valid email address (up to 150 characters).';
        }
        if (mb_strlen($data['address']) > 255) {
            $errors[] = 'Address must be at most 255 characters.';
        }
        if (!isset(self::SOURCES[$data['source']]) || in_array($data['source'], ['showroom', 'mobile_shop'], true)) {
            $errors[] = 'Select Call Center or Walk-in as the source.';
        }

        if ($errors) {
            $_SESSION['customer_errors'] = $errors;
            $this->redirect('/customers?add=1');
        }

        try {
            $this->customers->create($data, $this->scope());
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000' || (int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }
            $_SESSION['customer_errors'] = ['A customer with that phone number already exists.'];
            $this->redirect('/customers?add=1');
        }
        $_SESSION['customer_success'] = 'Customer added successfully.';
        $this->redirect('/customers');
    }

    private function scope(): ?int
    {
        if (Auth::isAdmin()) {
            return null;
        }
        $branchId = Auth::branchId();
        if ($branchId === null) {
            http_response_code(403);
            exit('Your account is not linked to a showroom.');
        }
        return $branchId;
    }

    private function dateFilter($value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))
            || $date->format('Y-m-d') !== $value) {
            return '';
        }
        return $value;
    }

    private function postOrGetString($value): string
    {
        return is_string($value) ? $value : '';
    }
}
