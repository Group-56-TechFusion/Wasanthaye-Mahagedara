<?php
class ModuleController extends Controller
{
    public function users()     { $this->page('User Management'); }
    public function branches()  { $this->page('Branches'); }
    public function customers() { $this->page('Customer Management'); }

    public function calendar(): void
    {
        $db = require BASE_PATH . '/config/database.php';
        $today = new DateTimeImmutable('today');
        $selectedDate = $this->parseDate($_GET['date'] ?? null, 'Y-m-d') ?? $today;
        $month = $this->parseDate($_GET['month'] ?? null, 'Y-m') ?? $selectedDate;
        $month = $month->modify('first day of this month');

        $firstOfMonth = $month->modify('first day of this month');
        $gridStart = $firstOfMonth->modify('-' . $firstOfMonth->format('w') . ' days');
        $gridEnd = $gridStart->modify('+41 days');
        $calendarWeddings = Wedding::between($db, $gridStart, $gridEnd, Auth::branchId());
        $selectedWeddings = Wedding::between($db, $selectedDate, $selectedDate, Auth::branchId());
        $upcomingStart = $selectedDate->modify('+1 day');
        $upcomingEnd = $selectedDate->modify('+7 days');
        $upcomingWeddings = Wedding::between($db, $upcomingStart, $upcomingEnd, Auth::branchId());

        $branches = Auth::isAdmin() ? Wedding::branches($db) : [];

        $this->render('calendar/index', [
            'month' => $month,
            'gridStart' => $gridStart,
            'calendarWeddings' => $calendarWeddings,
            'selectedDate' => $selectedDate,
            'selectedWeddings' => $selectedWeddings,
            'upcomingStart' => $upcomingStart,
            'upcomingEnd' => $upcomingEnd,
            'upcomingWeddings' => $upcomingWeddings,
            'branches' => $branches,
            'success' => $_SESSION['calendar_success'] ?? null,
            'error' => $_SESSION['calendar_error'] ?? null,
        ], 'Wedding Calendar');

        unset($_SESSION['calendar_success'], $_SESSION['calendar_error']);
    }

    public function createWedding(): void
    {
        Auth::verifyCsrf();

        $groom = trim((string) ($_POST['groom_name'] ?? ''));
        $bride = trim((string) ($_POST['bride_name'] ?? ''));
        $dateValue = trim((string) ($_POST['wedding_date'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $hotel = trim((string) ($_POST['hotel'] ?? ''));
        $estimateValue = trim((string) ($_POST['estimated_amount'] ?? ''));
        $maidBoysValue = trim((string) ($_POST['maid_boys'] ?? '0'));
        $date = $this->parseDate($dateValue, 'Y-m-d');

        $errors = [];
        if ($groom === '' || mb_strlen($groom) > 100) {
            $errors[] = 'Groom name is required and must be at most 100 characters.';
        }
        if ($bride === '' || mb_strlen($bride) > 100) {
            $errors[] = 'Bride name is required and must be at most 100 characters.';
        }
        if (!$date) {
            $errors[] = 'Enter a valid wedding date.';
        }
        if (mb_strlen($location) > 150 || mb_strlen($hotel) > 150) {
            $errors[] = 'Location and hotel must be at most 150 characters.';
        }
        if ($estimateValue !== '' && (!is_numeric($estimateValue) || (float) $estimateValue < 0)) {
            $errors[] = 'Estimated amount must be a non-negative number.';
        }
        if (!ctype_digit($maidBoysValue) || (int) $maidBoysValue > 1000) {
            $errors[] = 'Maid boys must be a whole number between 0 and 1000.';
        }

        $db = require BASE_PATH . '/config/database.php';
        $branchId = Auth::branchId();
        if (Auth::isAdmin()) {
            $postedBranchId = filter_var($_POST['branch_id'] ?? null, FILTER_VALIDATE_INT);
            $branches = Wedding::branches($db);
            $validBranchIds = array_map(static fn(array $branch): int => (int) $branch['id'], $branches);
            if ($postedBranchId === false || !in_array($postedBranchId, $validBranchIds, true)) {
                $errors[] = 'Select a valid showroom.';
            } else {
                $branchId = $postedBranchId;
            }
        } elseif ($branchId === null) {
            $errors[] = 'Your account must be assigned to a showroom before adding a wedding.';
        }

        if ($errors) {
            $_SESSION['calendar_error'] = implode(' ', $errors);
            $this->redirect('/calendar?add=1' . ($date ? '&date=' . $date->format('Y-m-d') : ''));
        }

        Wedding::create($db, [
            'groom_name' => $groom,
            'bride_name' => $bride,
            'wedding_date' => $date->format('Y-m-d'),
            'branch_id' => $branchId,
            'location' => $location,
            'hotel' => $hotel,
            'estimated_amount' => $estimateValue === '' ? null : $estimateValue,
            'maid_boys' => (int) $maidBoysValue,
        ]);

        $_SESSION['calendar_success'] = 'Wedding added successfully.';
        $this->redirect('/calendar?date=' . $date->format('Y-m-d'));
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