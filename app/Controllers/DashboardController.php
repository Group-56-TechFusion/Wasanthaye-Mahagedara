<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $branchId = null;
        $branchName = null;
        if (!Auth::isAdmin()) {
            $branchId = Auth::branchId();
            if ($branchId === null) {
                http_response_code(403);
                exit('Your account is not linked to a showroom.');
            }
            $branchName = Auth::user()['branch_name'] ?? null;
        }

        $calendarMonth = $this->parseCalendarMonth($_GET['calendar_month'] ?? null)
            ?? new DateTimeImmutable('first day of this month');
        $firstOfMonth = $calendarMonth->modify('first day of this month');
        $calendarGridStart = $firstOfMonth->modify('-' . $firstOfMonth->format('w') . ' days');
        $calendarGridEnd = $calendarGridStart->modify('+41 days');

        $overview = (new Dashboard())->overview($branchId, $branchName);
        $overview['calendarMonth'] = $firstOfMonth;
        $overview['calendarGridStart'] = $calendarGridStart;
        $overview['calendarWeddings'] = (new Bill())->weddingsBetween($calendarGridStart, $calendarGridEnd, $branchId);
        $overview['customerCount'] = count((new Customer())->all($branchId));
        $overview['username'] = Auth::user()['username'];
        $overview['branchName'] = $branchName;

        $this->render(
            'dashboard/' . Auth::role(),
            $overview,
            Auth::isAdmin() ? 'Admin Dashboard' : 'Showroom Dashboard'
        );
    }

    private function parseCalendarMonth($value): ?DateTimeImmutable
    {
        if (!is_string($value) || !preg_match('/^(?:1\d{3}|[2-9]\d{3})-(0[1-9]|1[0-2])$/D', $value)) {
            return null;
        }

        $month = DateTimeImmutable::createFromFormat('!Y-m', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($month === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $month->format('Y-m') === $value ? $month : null;
    }
}