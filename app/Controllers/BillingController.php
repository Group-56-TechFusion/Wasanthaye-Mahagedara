<?php
class BillingController
{
    private const STATUSES = ['all' => 'All', 'paid' => 'Paid', 'pending' => 'Pending', 'cancelled' => 'Cancelled', 'postponed' => 'Postponed'];
    private const METHODS  = ['cash' => 'Cash', 'online' => 'Online'];
    private const MAX_PARTY = 20;

    private Bill $bills;

    public function __construct()
    {
        $this->bills = new Bill();
        if (Auth::check() && !Auth::isAdmin() && Auth::branchId() === null) {
            http_response_code(403);
            exit('Your account is not linked to a showroom.');
        }
    }

    // ------------------------------------------------------------------ list

    public function index(): void
    {
        $scope = $this->scope();
        $filters = [
            'q'         => trim((string) ($_GET['q'] ?? '')),
            'from'      => (string) ($this->parseDate((string) ($_GET['from'] ?? '')) ?: ''),
            'to'        => (string) ($this->parseDate((string) ($_GET['to'] ?? '')) ?: ''),
            'date_by'   => ($_GET['date_by'] ?? '') === 'wedding_date' ? 'wedding_date' : 'booking_date',
            'status'    => isset(self::STATUSES[(string) ($_GET['status'] ?? '')]) ? $_GET['status'] : 'all',
            'branch_id' => (int) ($_GET['branch_id'] ?? 0),
        ];

        Page::render('billing/index', [
            'stats'         => $this->bills->stats($scope),
            'bills'         => $this->bills->search($filters, $scope),
            'recycle'       => $this->bills->recycleBin($scope),
            'filters'       => $filters,
            'branches'      => $this->bills->branches(),
            'statusOptions' => self::STATUSES,
            'canAdd'        => Auth::role() === 'branch_manager',
            'isAdmin'       => Auth::isAdmin(),
        ], 'Billing & Invoices');
    }

    // ------------------------------------------------------------- add / view

    public function create(): void
    {
        $this->requireManager();
        $this->form($this->blank(), [], null);
    }

    public function store(): void
    {
        Page::checkCsrf();
        $this->requireManager();

        [$d, $errors] = $this->collect(null);
        $branchId = (int) Auth::branchId();

        if (!$errors) {
            $errors = $this->checkItems($d, $branchId, 0);
        }
        if (!$errors) {
            try {
                $id = $this->bills->create($d, $branchId, (int) (Auth::user()['id'] ?? 0) ?: null);
                $_SESSION['success'] = 'Bill created.';
                Page::redirect('/billing/view?id=' . $id);
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
        $this->form($d, $errors, null);
    }

    public function show(): void
    {
        $bill = $this->loadAccessible((int) ($_GET['id'] ?? 0));
        Page::render('billing/show', [
            'bill'     => $bill,
            'payments' => $this->bills->payments((int) $bill['id']),
            'members'  => $this->bills->members((int) $bill['id']),
            'canEdit'  => $bill['deleted_at'] === null,
            'isAdmin'  => Auth::isAdmin(),
        ], 'Bill Details');
    }

    // ------------------------------------------------------------------- edit

    public function edit(): void
    {
        $bill = $this->loadAccessible((int) ($_GET['id'] ?? 0));
        if ($bill['deleted_at'] !== null) {
            $_SESSION['error'] = 'Restore this bill from the recycle bin before editing it.';
            Page::redirect('/billing');
        }
        $this->form($this->fromBill($bill), [], $bill);
    }

    public function update(): void
    {
        Page::checkCsrf();
        $id   = (int) ($_POST['id'] ?? 0);
        $bill = $this->loadAccessible($id);
        if ($bill['deleted_at'] !== null) {
            Page::redirect('/billing');
        }

        [$d, $errors] = $this->collect($bill);
        $branchId = (int) $bill['branch_id'];

        if (!$errors) {
            $errors = $this->checkItems($d, $branchId, $id);
        }
        if (!$errors) {
            try {
                $payBranch = Auth::branchId() ?? $branchId; // admin has no branch: use the bill's showroom
                $this->bills->update($id, $d, $bill, $this->bills->payments($id), $payBranch, (int) (Auth::user()['id'] ?? 0) ?: null);
                $_SESSION['success'] = 'Bill updated.';
                Page::redirect('/billing/view?id=' . $id);
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
        $this->form($d, $errors, $bill);
    }

    // --------------------------------------------------- postpone / delete / restore

    public function postpone(): void
    {
        Page::checkCsrf();
        $bill = $this->loadAccessible((int) ($_POST['id'] ?? 0));
        if ($this->bills->postpone((int) $bill['id'])) {
            $_SESSION['success'] = 'Bill ' . $bill['bill_number'] . ' marked as postponed. Its wedding date was cleared and the items are free again.';
        } else {
            $_SESSION['error'] = 'This bill cannot be postponed.';
        }
        Page::redirect('/billing');
    }

    public function destroy(): void
    {
        Page::checkCsrf();
        $bill = $this->loadAccessible((int) ($_POST['id'] ?? 0));
        if ($this->bills->softDelete((int) $bill['id'], (int) (Auth::user()['id'] ?? 0) ?: null)) {
            $_SESSION['success'] = 'Bill ' . $bill['bill_number'] . ' moved to the recycle bin. The advance paid stays in the sales figures.';
        }
        Page::redirect('/billing');
    }

    public function restore(): void
    {
        Page::checkCsrf();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Only an administrator can restore a bill.');
        }
        $bill = $this->loadAccessible((int) ($_POST['id'] ?? 0));
        if ($this->bills->restore((int) $bill['id'])) {
            $_SESSION['success'] = 'Bill ' . $bill['bill_number'] . ' restored.';
        }
        Page::redirect('/billing');
    }

    // ------------------------------------------------------------ item search

    public function items(): void
    {
        $category = ($_GET['category'] ?? '') === 'bestman' ? 'bestman' : 'groom';
        $exclude  = (int) ($_GET['exclude'] ?? 0);
        $bill     = $exclude ? $this->bills->find($exclude) : null;
        // Editing an existing bill: use that bill's showroom. New bill: the manager's own showroom.
        $branchId = $bill ? (int) $bill['branch_id'] : (int) Auth::branchId();
        if (!$bill) {
            $exclude = 0;
        }

        $rows = $this->bills->availableJackets([
            'category'    => $category,
            'branch_name' => $this->bills->branchName((int) $branchId),
            'need'        => $category === 'bestman' ? max(1, (int) ($_GET['need'] ?? 1)) : 1,
            'date'        => $this->parseDate((string) ($_GET['date'] ?? '')) ?: null,
            'exclude'     => $exclude,
            'q'           => trim((string) ($_GET['q'] ?? '')),
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows);
        exit;
    }

    public function customers(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $rows = mb_strlen($q) >= 2 ? $this->bills->findCustomers($q, $this->ownScope()) : [];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows);
        exit;
    }

    // ---------------------------------------------------------------- helpers

    /** Lists, totals and the recycle bin cover every showroom. */
    private function scope(): ?int
    {
        return null;
    }

    /** Customer look-up while adding a bill stays limited to the manager's own showroom. */
    private function ownScope(): ?int
    {
        return Auth::isAdmin() ? null : Auth::branchId();
    }

    private function requireManager(): void
    {
        if (Auth::role() !== 'branch_manager') {
            http_response_code(403);
            exit('Only showroom managers can add bills.');
        }
    }

    private function loadAccessible(int $id): array
    {
        $bill = $id ? $this->bills->find($id) : null;
        if (!$bill) {
            Page::notFound();
            exit;
        }
        return $bill;
    }

    /** @return string|null|false  Y-m-d, null when empty, false when invalid */
    private function parseDate(string $raw)
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $d = DateTime::createFromFormat('Y-m-d', $raw);
        return ($d && $d->format('Y-m-d') === $raw) ? $raw : false;
    }

    private function form(array $d, array $errors, ?array $bill): void
    {
        Page::render('billing/form', [
            'd'       => $d,
            'errors'  => $errors,
            'bill'    => $bill,
            'isAdmin' => Auth::isAdmin(),
            'methods' => self::METHODS,
        ], $bill ? 'Edit Bill' : 'Add New Bill');
    }

    private function blank(): array
    {
        $d = array_fill_keys(array_merge(
            ['bill_number', 'customer_name', 'customer_email', 'address', 'phone1', 'phone2', 'note',
             'dressing_location', 'wedding_hotel', 'groom_kawani', 'bestman_kawani', 'groom_label', 'bestman_label'],
        ), '');
        return $d + [
            'booking_date' => date('Y-m-d'), 'wedding_date' => null,
            'groom_jacket_id' => null, 'bestman_jacket_id' => null,
            'bestmen_count' => 0, 'pageboys_count' => 0,
            'fiber_sword' => 0, 'white_kawani' => 0, 'going_away_kit' => 0, 'homecoming_kit' => 0, 'is_mobile_shop' => 0,
            'package_price' => 0, 'transport' => 0, 'discount' => 0, 'total' => 0,
            'advance_amount' => 0, 'advance_method' => 'cash',
            'installments' => [], 'members' => [], 'members_form' => [],
        ];
    }

    private function fromBill(array $b): array
    {
        $pay = $this->bills->payments((int) $b['id']);
        $d = $this->blank();
        foreach (['bill_number', 'booking_date', 'customer_name', 'customer_email', 'address', 'phone1', 'phone2', 'note',
                  'wedding_date', 'dressing_location', 'wedding_hotel', 'groom_jacket_id', 'bestman_jacket_id',
                  'groom_kawani', 'bestman_kawani', 'bestmen_count', 'pageboys_count', 'fiber_sword', 'white_kawani',
                  'going_away_kit', 'homecoming_kit', 'is_mobile_shop', 'package_price', 'transport', 'discount', 'total'] as $k) {
            $d[$k] = $b[$k];
        }
        $d['groom_label']   = $b['groom_code'] ? $b['groom_code'] . ' – ' . $b['groom_name'] : '';
        $d['bestman_label'] = $b['bestman_code'] ? $b['bestman_code'] . ' – ' . $b['bestman_name'] : '';
        $d['advance_amount'] = (float) ($pay[1]['amount'] ?? 0);
        $d['advance_method'] = $pay[1]['method'] ?? 'cash';
        foreach ([2, 3, 4] as $n) {
            $d['installments'][$n] = [
                'amount' => (float) ($pay[$n]['amount'] ?? 0),
                'date'   => $pay[$n]['payment_date'] ?? '',
                'method' => $pay[$n]['method'] ?? 'cash',
            ];
        }
        foreach ($this->bills->members((int) $b['id']) as $m) {
            $d['members_form'][$m['member_type'] . '-' . $m['position']] = [
                'name' => $m['name'], 'cap' => $m['cap_size'], 'jacket' => $m['jacket_size'],
                'trouser' => $m['trouser_size'], 'shoe' => $m['shoe_size'],
            ];
        }
        return $d;
    }

    /** Re-check on save that the chosen jackets are still free on that wedding date. */
    private function checkItems(array $d, int $branchId, int $excludeBillId): array
    {
        $errors = [];
        $branchName = $this->bills->branchName($branchId);
        $picks = [
            'groom'   => [$d['groom_jacket_id'], 1],
            'bestman' => [$d['bestman_jacket_id'], max(1, (int) $d['bestmen_count'])],
        ];
        foreach ($picks as $category => [$id, $need]) {
            if (!$id) {
                continue;
            }
            $ok = $this->bills->availableJackets([
                'category' => $category, 'id' => $id, 'date' => $d['wedding_date'],
                'branch_name' => $branchName, 'need' => $need, 'exclude' => $excludeBillId, 'q' => '',
            ]);
            if (!$ok) {
                $errors[] = "The selected $category jacket is not available" . ($d['wedding_date'] ? ' on ' . $d['wedding_date'] : '') . '. Pick another one.';
            }
        }
        return $errors;
    }

    /** Read and validate the posted form. @return array{0: array, 1: string[]} */
    private function collect(?array $bill): array
    {
        $errors = [];
        $text   = fn(string $k, int $max = 255): string => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
        $money  = fn($v): float => max(0.0, round((float) $v, 2));
        $count  = fn(string $k): int => max(0, min(self::MAX_PARTY, (int) ($_POST[$k] ?? 0)));
        $locked = $bill !== null && !Auth::isAdmin(); // managers cannot change bill number / booking date

        // Header
        $number = $locked ? $bill['bill_number'] : $text('bill_number', 30);
        if ($bill && $number === '') {
            $number = $bill['bill_number'];
        }
        if ($number !== '' && !preg_match('/^[A-Za-z0-9-]+$/', $number)) {
            $errors[] = 'The bill number may only contain letters, numbers and dashes.';
        }
        $booking = $locked ? $bill['booking_date'] : $this->parseDate((string) ($_POST['booking_date'] ?? ''));
        if ($booking === null) {                        // left empty: today for a new bill, unchanged for an edit
            $booking = $bill['booking_date'] ?? date('Y-m-d');
        }
        if (!$booking) {
            $errors[] = 'Enter a valid booking date.';
            $booking = $bill['booking_date'] ?? date('Y-m-d');
        }

        // Customer
        $name   = $text('customer_name', 150);
        $phone1 = $text('phone1', 25);
        $phone2 = $text('phone2', 25);
        $email  = $text('customer_email', 150);
        if ($name === '') {
            $errors[] = 'Enter the customer name.';
        }
        if (!preg_match('/^[0-9+\-\s]{7,25}$/', $phone1)) {
            $errors[] = 'Enter a valid phone number 1.';
        }
        if ($phone2 !== '' && !preg_match('/^[0-9+\-\s]{7,25}$/', $phone2)) {
            $errors[] = 'Phone number 2 is not valid.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address or leave it empty.';
        }

        // Wedding (date optional)
        $wedding = $this->parseDate((string) ($_POST['wedding_date'] ?? ''));
        if ($wedding === false) {
            $errors[] = 'The wedding date is not valid.';
            $wedding = null;
        }

        // Items
        $groomId   = (int) ($_POST['groom_jacket_id'] ?? 0) ?: null;
        $bestmanId = (int) ($_POST['bestman_jacket_id'] ?? 0) ?: null;
        $bestmen   = $count('bestmen_count');
        $pageboys  = $count('pageboys_count');

        // Money
        $package   = $money($_POST['package_price'] ?? 0);
        $transport = $money($_POST['transport'] ?? 0);
        $discount  = $money($_POST['discount'] ?? 0);
        if ($discount > $package + $transport) {
            $errors[] = 'The discount cannot be more than the package price plus transport.';
        }
        $total = max(0.0, round($package + $transport - $discount, 2));

        $advance = $money($_POST['advance_amount'] ?? 0);
        $method  = isset(self::METHODS[(string) ($_POST['advance_method'] ?? '')]) ? $_POST['advance_method'] : 'cash';

        // Installments 2..4 (edit page only)
        $inst = [];
        $paid = $advance;
        if ($bill) {
            foreach ([2, 3, 4] as $n) {
                $all  = is_array($_POST['inst'] ?? null) ? $_POST['inst'] : [];
                $row  = is_array($all[$n] ?? null) ? $all[$n] : [];
                $amt  = $money($row['amount'] ?? 0);
                $dt   = $this->parseDate((string) ($row['date'] ?? ''));
                $meth = isset(self::METHODS[(string) ($row['method'] ?? '')]) ? $row['method'] : 'cash';
                if ($amt > 0 && !$dt) {
                    $errors[] = "Enter a valid date for payment $n.";
                }
                $inst[$n] = ['amount' => $amt, 'date' => $dt ?: '', 'method' => $meth];
                $paid += $amt;
            }
        }
        if ($paid > $total + 0.004) {
            $errors[] = 'Total payments (Rs. ' . number_format($paid, 2) . ') are more than the bill total (Rs. ' . number_format($total, 2) . ').';
        }

        // Party measurements: only rows that fit the counts above are kept
        $members = [];
        $shown   = [];
        foreach ((array) ($_POST['members'] ?? []) as $key => $row) {
            if (!is_array($row) || !preg_match('/^(groom|bestman|pageboy)-(\d+)$/', (string) $key, $m)) {
                continue;
            }
            [$type, $pos] = [$m[1], (int) $m[2]];
            $limit = ['groom' => 1, 'bestman' => $bestmen, 'pageboy' => $pageboys][$type];
            if ($pos < 1 || $pos > $limit) {
                continue;
            }
            $clean = [];
            foreach (['name', 'cap', 'jacket', 'trouser', 'shoe'] as $f) {
                $clean[$f] = mb_substr(trim((string) ($row[$f] ?? '')), 0, $f === 'name' ? 100 : 30);
            }
            $shown[$key] = $clean;
            $members[]   = $clean + ['type' => $type, 'position' => $pos];
        }

        $d = [
            'bill_number' => $number, 'booking_date' => $booking,
            'customer_name' => $name, 'customer_email' => $email, 'address' => $text('address'),
            'phone1' => $phone1, 'phone2' => $phone2, 'note' => $text('note', 1000),
            'wedding_date' => $wedding,
            'dressing_location' => $text('dressing_location', 150), 'wedding_hotel' => $text('wedding_hotel', 150),
            'groom_jacket_id' => $groomId, 'bestman_jacket_id' => $bestmanId,
            'groom_label' => $text('groom_label', 200), 'bestman_label' => $text('bestman_label', 200),
            'groom_kawani' => $text('groom_kawani', 100), 'bestman_kawani' => $text('bestman_kawani', 100),
            'bestmen_count' => $bestmen, 'pageboys_count' => $pageboys,
            'fiber_sword' => isset($_POST['fiber_sword']) ? 1 : 0,
            'white_kawani' => isset($_POST['white_kawani']) ? 1 : 0,
            'going_away_kit' => isset($_POST['going_away_kit']) ? 1 : 0,
            'homecoming_kit' => isset($_POST['homecoming_kit']) ? 1 : 0,
            'is_mobile_shop' => isset($_POST['is_mobile_shop']) ? 1 : 0,
            'package_price' => $package, 'transport' => $transport, 'discount' => $discount, 'total' => $total,
            'advance_amount' => $advance, 'advance_method' => $method,
            'installments' => $inst,
            'members' => $members,
        ];
        // Keep the typed measurements when the form is shown again after an error.
        $d['members_form'] = $shown;
        return [$d, $errors];
    }
}