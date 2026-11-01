<?php
class ReportController
{
    /** Excel sheet names are limited to 31 characters. */
    private const SHEET_NAMES = [
        1 => 'Total Sales', 2 => 'All Income', 3 => 'Income New Bills', 4 => 'Outstanding', 5 => 'Total Bills',
        6 => 'Wedding Balance', 7 => 'Wedding Inst', 8 => 'Weddings', 9 => 'Net Sale',
        10 => 'Income Only Showroom', 11 => 'New Bills Only Showroom', 12 => 'Old Bills Inst 2-3',
    ];

    private Report $reports;

    public function __construct()
    {
        // Reports are admin-only (also enforced by the 'reports' permission in config/permissions.php).
        if (Auth::check() && !Auth::isAdmin()) {
            http_response_code(403);
            exit('Reports are available to administrators only.');
        }
        $this->reports = new Report();
    }

    // ------------------------------------------------------------------ pages

    public function index(): void
    {
        $f        = $this->filters();
        $branches = $this->reports->branches();
        $view     = $this->view();

        Page::render('reports/index', [
            'f'        => $f,
            'activeView' => $view,
            'branches' => $branches,
            'stats'    => $this->reports->stats($f),
            'detail'   => $this->reports->detail($view, $f),
            'summary'  => $this->reports->showroomSummary($f),
            'trend'    => $this->reports->dailyTrend($f),
            'weekly'   => $this->reports->weeklyPattern($f),
            'branchName' => $this->branchName($branches, $f['branch']),
        ], 'Reports');
    }

    /** GET /reports/export?format=xlsx|csv  (same filters as the page) */
    public function export(): void
    {
        $f        = $this->filters();
        $branches = $this->reports->branches();
        $book     = $this->buildWorkbook($f, $this->branchName($branches, $f['branch']));

        $slug = 'report_' . $f['from'] . '_to_' . $f['to'];
        if ($f['branch']) {
            $slug .= '_' . preg_replace('/[^A-Za-z0-9]+/', '-', $this->branchName($branches, $f['branch']));
        }

        $wantCsv = ($_GET['format'] ?? 'xlsx') === 'csv' || !XlsxWriter::available();
        if ($wantCsv) {
            $body = $book->csv();
            $type = 'text/csv; charset=UTF-8';
            $file = $slug . '.csv';
        } else {
            $body = $book->xlsx();
            $type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $file = $slug . '.xlsx';
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $type);
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . strlen($body));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $body;
        exit;
    }

    // ------------------------------------------------------------------ export content

    private function buildWorkbook(array $f, string $branchName): XlsxWriter
    {
        $book  = new XlsxWriter();
        $stats = $this->reports->stats($f);

        // 1) Summary: filters + all 12 numbers
        $rows = [
            [['Showroom Analytics Report', 'title']],
            ['Period', $f['from'] . '  to  ' . $f['to']],
            ['Showroom', $branchName],
            ['Generated', date('Y-m-d H:i')],
            [],
            [['#', 'head'], ['Metric', 'head'], ['Value', 'head'], ['Notes', 'head']],
        ];
        foreach (Report::STATS as $n => [$label, $hint, $kind]) {
            $rows[] = [$n, $label, [$kind === 'count' ? (int) $stats[$n] : (float) $stats[$n], $kind === 'count' ? 'int' : 'money'], $hint];
        }
        $book->sheet('Summary', $rows);

        // 2) Showroom comparison
        $rows = [[['Showroom', 'head'], ['Bills', 'head'], ['Total Sales', 'head'], ['Net Sales', 'head'],
                  ['Paid To Date', 'head'], ['Outstanding', 'head'], ['Cash Received In Period', 'head']]];
        $tot = ['bills' => 0, 'sales' => 0.0, 'net' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0, 'cash' => 0.0];
        foreach ($this->reports->showroomSummary($f) as $s) {
            $rows[] = [$s['name'], [$s['bills'], 'int'], [$s['sales'], 'money'], [$s['net'], 'money'],
                       [$s['paid'], 'money'], [$s['outstanding'], 'money'], [$s['cash'], 'money']];
            foreach ($tot as $k => $_) {
                $tot[$k] += $s[$k];
            }
        }
        $rows[] = [['TOTAL', 'bold'], [(int) $tot['bills'], 'boldint'], [$tot['sales'], 'boldmoney'], [$tot['net'], 'boldmoney'],
                   [$tot['paid'], 'boldmoney'], [$tot['outstanding'], 'boldmoney'], [$tot['cash'], 'boldmoney']];
        $book->sheet('Showrooms', $rows, 1);

        // 3) Daily sales per showroom
        $trend = $this->reports->dailyTrend($f);
        $head  = [['Date', 'head']];
        foreach (array_keys($trend['series']) as $name) {
            $head[] = [$name, 'head'];
        }
        $head[] = ['Total', 'head'];
        $rows = [$head];
        foreach ($trend['labels'] as $i => $day) {
            $row = [$day];
            $sum = 0.0;
            foreach ($trend['series'] as $vals) {
                $row[] = [(float) $vals[$i], 'money'];
                $sum  += $vals[$i];
            }
            $row[] = [$sum, 'boldmoney'];
            $rows[] = $row;
        }
        $book->sheet('Daily Sales', $rows, 1);

        // 4) Weekday pattern
        $rows = [[['Weekday', 'head'], ['Bills Created', 'head']]];
        foreach ($this->reports->weeklyPattern($f) as $w) {
            $rows[] = [$w['name'], [$w['bills'], 'int']];
        }
        $book->sheet('Weekdays', $rows, 1);

        // 5) One sheet per drill-down table (5 is a count of the same bills as 1, so it is skipped)
        foreach (array_keys(Report::STATS) as $n) {
            if ($n === 5) {
                continue;
            }
            $d    = $this->reports->detail($n, $f);
            $head = [];
            foreach ($d['cols'] as [, $label]) {
                $head[] = [$label, 'head'];
            }
            $rows = [[[$n . '. ' . $d['name'], 'title']], [$d['title']], [], $head];
            $sum  = 0.0;
            foreach ($d['rows'] as $r) {
                $row = [];
                foreach ($d['cols'] as [$key, , $type]) {
                    $row[] = $type === 'money' ? [(float) $r[$key], 'money'] : (string) $r[$key];
                }
                $rows[] = $row;
                if ($d['amount']) {
                    $sum += (float) $r[$d['amount']];
                }
            }
            if ($d['count_only']) {
                $rows[] = [[$d['total_label'], 'bold'], [count($d['rows']), 'boldint']];
            } else {
                $pad = array_fill(0, count($d['cols']) - 2, '');
                $rows[] = array_merge([[strtoupper($d['total_label']), 'bold']], $pad, [[$sum, 'boldmoney']]);
            }
            $book->sheet($n . ' ' . self::SHEET_NAMES[$n], $rows, 4);
        }

        return $book;
    }

    // ------------------------------------------------------------------ input

    private function filters(): array
    {
        $from = $this->date($_GET['from'] ?? '') ?? date('Y-m-01');
        $to   = $this->date($_GET['to'] ?? '') ?? date('Y-m-t');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        return ['from' => $from, 'to' => $to, 'branch' => max(0, (int) ($_GET['branch_id'] ?? 0))];
    }

    private function view(): int
    {
        $v = (int) ($_GET['view'] ?? 1);
        return isset(Report::STATS[$v]) ? $v : 1;
    }

    private function date($raw): ?string
    {
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        return $d && $d->format('Y-m-d') === $raw ? $raw : null;
    }

    private function branchName(array $branches, int $id): string
    {
        foreach ($branches as $b) {
            if ((int) $b['id'] === $id) {
                return (string) $b['name'];
            }
        }
        return 'All Showrooms';
    }
}
