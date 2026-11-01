<?php

class Report
{
    /** Amount deducted per going-away / homecoming kit (Net Total Sale). */
    public const KIT_DEDUCTION = 13500;

    /** number => [label, hint, kind, colour] */
    public const STATS = [
        1  => ['Total Sales',                       'Booked in range',                  'money', '#3b82f6'],
        2  => ['All Total Income',                  'Total cash received',              'money', '#10b981'],
        3  => ['Income (New Bills)',                'Paid on bills booked in range',    'money', '#6366f1'],
        4  => ['Outstanding',                       'Pending from new bills',           'money', '#ef4444'],
        5  => ['Total Bills',                       'Bills created',                    'count', '#1b2438'],
        6  => ['Remaining Wedding Bal.',            'Due for events in range',          'money', '#f59e0b'],
        7  => ['Wedding Bal. Calculated',           'Inst. received for events',        'money', '#8b5cf6'],
        8  => ['Total Weddings',                    'Events in date range',             'count', '#ec4899'],
        9  => ['Net Total Sale',                    'After kit deductions',             'money', '#2563eb'],
        10 => ['All Total Income (Only Showroom)',  'Cash received at this showroom',   'money', '#14b8a6'],
        11 => ['Income (New Bills, Only Showroom)', 'New bills, paid at this showroom', 'money', '#d946ef'],
        12 => ['Income (Old Bills Inst. 2 & 3)',    'Inst. 2 & 3, only this showroom',  'money', '#f97316'],
    ];

    private const PAID_JOIN = "LEFT JOIN (SELECT bill_id, SUM(amount) AS paid FROM bill_payments GROUP BY bill_id) p ON p.bill_id = b.id";
    private const ACTIVE    = "(b.deleted_at IS NULL AND b.is_postponed = 0)";
    private const SALE      = "(CASE WHEN b.deleted_at IS NULL AND b.is_postponed = 0 THEN b.total ELSE COALESCE(p.paid, 0) END)";
    private const BALANCE   = "GREATEST((CASE WHEN b.deleted_at IS NULL AND b.is_postponed = 0 THEN b.total ELSE COALESCE(p.paid, 0) END) - COALESCE(p.paid, 0), 0)";
    private const KITS      = "(COALESCE(b.going_away_kit, 0) + COALESCE(b.homecoming_kit, 0))";

    private static ?PDO $pdo = null;

    private function db(): PDO
    {
        return self::$pdo ??= require BASE_PATH . '/config/database.php';
    }

    //  helpers

    /** Register a value and return a unique placeholder (safe even with native prepares). */
    private function p(array &$args, $value): string
    {
        $key = ':p' . count($args);
        $args[$key] = $value;
        return $key;
    }

    private function between(string $col, array &$args, array $f): string
    {
        return "$col BETWEEN " . $this->p($args, $f['from']) . ' AND ' . $this->p($args, $f['to']);
    }

    private function branchIs(string $col, array &$args, array $f): string
    {
        return $f['branch'] ? " AND $col = " . $this->p($args, $f['branch']) : '';
    }

    private function kitSql(): string
    {
        return "CASE WHEN " . self::ACTIVE . " THEN " . self::KITS . " * " . (int) self::KIT_DEDUCTION . " ELSE 0 END";
    }

    private function all(string $sql, array $args = []): array
    {
        $st = $this->db()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function one(string $sql, array $args = []): array
    {
        $st = $this->db()->prepare($sql);
        $st->execute($args);
        return $st->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function branches(): array
    {
        return $this->all('SELECT id, name FROM branches ORDER BY name');
    }

    

    public function stats(array $f): array
    {
        $out = [];

        
        $args = [];
        $row = $this->one(
            "SELECT COALESCE(SUM(" . self::SALE . "), 0)    AS sales,
                    COALESCE(SUM(" . self::BALANCE . "), 0) AS outstanding,
                    COALESCE(SUM(b.deleted_at IS NULL), 0)  AS bills,
                    COALESCE(SUM(" . $this->kitSql() . "), 0) AS kits
             FROM bills b " . self::PAID_JOIN . "
             WHERE " . $this->between('b.booking_date', $args, $f) . $this->branchIs('b.branch_id', $args, $f),
            $args
        );
        $out[1] = (float) $row['sales'];
        $out[4] = (float) $row['outstanding'];
        $out[5] = (int) $row['bills'];
        $out[9] = (float) $row['sales'] - (float) $row['kits'];

        
        $args = [];
        $row = $this->one(
            "SELECT COUNT(*) AS weddings, COALESCE(SUM(" . self::BALANCE . "), 0) AS balance
             FROM bills b " . self::PAID_JOIN . "
             WHERE " . self::ACTIVE . " AND " . $this->between('b.wedding_date', $args, $f) . $this->branchIs('b.branch_id', $args, $f),
            $args
        );
        $out[6] = (float) $row['balance'];
        $out[8] = (int) $row['weddings'];

        
        foreach ([2 => 'cash', 3 => 'new_bills', 7 => 'wedding_inst', 10 => 'cash_showroom', 11 => 'new_bills_showroom', 12 => 'old_inst23'] as $n => $kind) {
            $out[$n] = $this->paymentTotal($kind, $f);
        }

        ksort($out);
        return $out;
    }

    

    private function paymentWhere(string $kind, array $f, array &$args): string
    {
        $w = $this->between('bp.payment_date', $args, $f);
        switch ($kind) {
            case 'cash':               
                return $w . $this->branchIs('b.branch_id', $args, $f);
            case 'cash_showroom':     
                return $w . $this->branchIs('bp.branch_id', $args, $f);
            case 'new_bills':          
                return $w . ' AND ' . $this->between('b.booking_date', $args, $f) . $this->branchIs('b.branch_id', $args, $f);
            case 'new_bills_showroom': 
                return $w . ' AND ' . $this->between('b.booking_date', $args, $f)
                    . $this->branchIs('b.branch_id', $args, $f) . $this->branchIs('bp.branch_id', $args, $f);
            case 'old_inst23':         
                return $w . ' AND bp.seq IN (2, 3) AND NOT (' . $this->between('b.booking_date', $args, $f) . ')'
                    . $this->branchIs('bp.branch_id', $args, $f);
            case 'wedding_inst':       
                return $w . ' AND bp.seq IN (2, 3, 4) AND ' . self::ACTIVE . ' AND ' . $this->between('b.wedding_date', $args, $f)
                    . $this->branchIs('b.branch_id', $args, $f);
        }
        throw new InvalidArgumentException("Unknown payment view: $kind");
    }

    private function paymentTotal(string $kind, array $f): float
    {
        $args = [];
        $where = $this->paymentWhere($kind, $f, $args);
        $row = $this->one(
            "SELECT COALESCE(SUM(bp.amount), 0) AS t
             FROM bill_payments bp JOIN bills b ON b.id = bp.bill_id
             WHERE $where",
            $args
        );
        return (float) $row['t'];
    }

    private function paymentRows(string $kind, array $f): array
    {
        $args = [];
        $where = $this->paymentWhere($kind, $f, $args);
        $rows = $this->all(
            "SELECT bp.payment_date, bb.name AS branch_name, cb.name AS collected_at, b.bill_number,
                    b.customer_name, bp.seq, bp.method, bp.amount
             FROM bill_payments bp
             JOIN bills b     ON b.id  = bp.bill_id
             JOIN branches bb ON bb.id = b.branch_id
             JOIN branches cb ON cb.id = bp.branch_id
             WHERE $where
             ORDER BY bp.payment_date DESC, bp.id DESC",
            $args
        );
        foreach ($rows as &$r) {
            $r['type']   = (int) $r['seq'] === 1 ? 'Advance' : 'Installment ' . (int) $r['seq'];
            $r['method'] = ucfirst((string) $r['method']);
        }
        unset($r);
        return $rows;
    }

    

    private function billRows(string $where, array $args, string $order): array
    {
        $rows = $this->all(
            "SELECT b.id, b.bill_number, b.booking_date, b.wedding_date, bb.name AS branch_name, b.customer_name,
                    b.total, b.deleted_at, b.is_postponed,
                    COALESCE(p.paid, 0) AS paid,
                    " . self::SALE . "    AS sale,
                    " . self::BALANCE . " AS balance,
                    " . $this->kitSql() . " AS kit_deduction
             FROM bills b
             JOIN branches bb ON bb.id = b.branch_id " . self::PAID_JOIN . "
             WHERE $where ORDER BY $order",
            $args
        );
        foreach ($rows as &$r) {
            $r['status'] = $r['deleted_at'] !== null ? 'Cancelled'
                : ($r['is_postponed'] ? 'Postponed' : ((float) $r['balance'] <= 0 ? 'Paid' : 'Pending'));
            $r['net'] = (float) $r['sale'] - (float) $r['kit_deduction'];
        }
        unset($r);
        return $rows;
    }

    

    
    public function detail(int $view, array $f): array
    {
        $stat = self::STATS[$view] ?? null;
        if (!$stat) {
            $view = 1;
            $stat = self::STATS[1];
        }

        $titles = [
            1  => 'Bills booked in this range (cancelled / postponed bills count only what was paid)',
            2  => 'All cash received in this range (advances and installments)',
            3  => 'Payments received in this range on bills booked in this range',
            4  => 'Unpaid balances on bills booked in this range',
            5  => 'Bills created in this range',
            6  => 'Outstanding balances for weddings in this range',
            7  => 'Installments received in this range for weddings in this range',
            8  => 'Weddings in this range',
            9  => 'Net sale after kit deductions (Rs. ' . number_format(self::KIT_DEDUCTION) . ' per kit)',
            10 => 'Cash actually received at this showroom in this range',
            11 => 'Payments received at this showroom in this range on bills booked in this range',
            12 => 'Installment 2 & 3 payments received in this range on bills booked outside this range',
        ];

        $payKinds = [2 => 'cash', 3 => 'new_bills', 7 => 'wedding_inst', 10 => 'cash_showroom', 11 => 'new_bills_showroom', 12 => 'old_inst23'];

        $d = ['view' => $view, 'name' => $stat[0], 'title' => $titles[$view], 'count_only' => false, 'total_label' => 'Calculated total'];

        if (isset($payKinds[$view])) {
            $d['cols'] = [
                ['payment_date', 'Payment Date', 'date'], ['branch_name', 'Bill Showroom', 'text'],
                ['collected_at', 'Collected At', 'text'], ['bill_number', 'Bill #', 'bold'],
                ['customer_name', 'Customer', 'text'],    ['type', 'Payment Type', 'tag'],
                ['method', 'Method', 'text'],             ['amount', 'Amount', 'money'],
            ];
            $d['rows']   = $this->paymentRows($payKinds[$view], $f);
            $d['amount'] = 'amount';
            return $d;
        }

        $args = [];
        if ($view === 6 || $view === 8) {
            $where = self::ACTIVE . ' AND ' . $this->between('b.wedding_date', $args, $f) . $this->branchIs('b.branch_id', $args, $f);
            if ($view === 6) {
                $where .= ' AND ' . self::BALANCE . ' > 0';
            }
            $d['rows'] = $this->billRows($where, $args, 'b.wedding_date ASC, b.id ASC');
            $d['cols'] = [
                ['wedding_date', 'Wedding Date', 'date'], ['branch_name', 'Showroom', 'text'], ['bill_number', 'Bill #', 'bold'],
                ['customer_name', 'Customer', 'text'], ['total', 'Bill Total', 'money'], ['paid', 'Paid', 'money'],
                ['balance', 'Balance', 'money'],
            ];
            $d['amount']      = $view === 6 ? 'balance' : null;
            $d['count_only']  = $view === 8;
            $d['total_label'] = $view === 8 ? 'Total weddings' : 'Calculated total';
            return $d;
        }

        $where = $this->between('b.booking_date', $args, $f) . $this->branchIs('b.branch_id', $args, $f);
        if ($view === 4) {
            $where .= ' AND ' . self::BALANCE . ' > 0';
        } elseif ($view === 5) {
            $where .= ' AND b.deleted_at IS NULL';
        }
        $d['rows'] = $this->billRows($where, $args, 'b.booking_date DESC, b.id DESC');

        $base = [['booking_date', 'Booking Date', 'date'], ['branch_name', 'Showroom', 'text'], ['bill_number', 'Bill #', 'bold'],
                 ['customer_name', 'Customer', 'text'], ['status', 'Status', 'status']];
        if ($view === 1) {
            $d['cols']   = array_merge($base, [['total', 'Bill Total', 'money'], ['sale', 'Counted Sale', 'money']]);
            $d['amount'] = 'sale';
        } elseif ($view === 4) {
            $d['cols']   = array_merge($base, [['total', 'Bill Total', 'money'], ['paid', 'Paid', 'money'], ['balance', 'Outstanding', 'money']]);
            $d['amount'] = 'balance';
        } elseif ($view === 5) {
            $d['cols']        = array_merge($base, [['total', 'Bill Total', 'money']]);
            $d['amount']      = null;
            $d['count_only']  = true;
            $d['total_label'] = 'Total bills';
        } else { // 9
            $d['cols']   = array_merge($base, [['sale', 'Counted Sale', 'money'], ['kit_deduction', 'Kit Deduction', 'money'], ['net', 'Net Sale', 'money']]);
            $d['amount'] = 'net';
        }
        return $d;
    }

    

    /** One row per showroom (ignores the showroom filter so showrooms can be compared). */
    public function showroomSummary(array $f): array
    {
        $args = [];
        $bills = $this->all(
            "SELECT b.branch_id,
                    SUM(b.deleted_at IS NULL)    AS bills,
                    SUM(" . self::SALE . ")      AS sales,
                    SUM(" . $this->kitSql() . ") AS kits,
                    SUM(COALESCE(p.paid, 0))     AS paid,
                    SUM(" . self::BALANCE . ")   AS outstanding
             FROM bills b " . self::PAID_JOIN . "
             WHERE " . $this->between('b.booking_date', $args, $f) . "
             GROUP BY b.branch_id",
            $args
        );
        $byBranch = [];
        foreach ($bills as $r) {
            $byBranch[(int) $r['branch_id']] = $r;
        }

        $args = [];
        $cash = $this->all(
            "SELECT bp.branch_id, SUM(bp.amount) AS cash
             FROM bill_payments bp
             WHERE " . $this->between('bp.payment_date', $args, $f) . "
             GROUP BY bp.branch_id",
            $args
        );
        $cashBy = [];
        foreach ($cash as $r) {
            $cashBy[(int) $r['branch_id']] = (float) $r['cash'];
        }

        $out = [];
        foreach ($this->branches() as $br) {
            $id    = (int) $br['id'];
            $r     = $byBranch[$id] ?? [];
            $sales = (float) ($r['sales'] ?? 0);
            $out[] = [
                'id'          => $id,
                'name'        => $br['name'],
                'bills'       => (int) ($r['bills'] ?? 0),
                'sales'       => $sales,
                'net'         => $sales - (float) ($r['kits'] ?? 0),
                'paid'        => (float) ($r['paid'] ?? 0),
                'outstanding' => (float) ($r['outstanding'] ?? 0),
                'cash'        => $cashBy[$id] ?? 0.0,
            ];
        }
        return $out;
    }

    
    public function dailyTrend(array $f): array
    {
        $args = [];
        $rows = $this->all(
            "SELECT b.booking_date AS d, b.branch_id, SUM(" . self::SALE . ") AS sales
             FROM bills b " . self::PAID_JOIN . "
             WHERE " . $this->between('b.booking_date', $args, $f) . $this->branchIs('b.branch_id', $args, $f) . "
             GROUP BY b.booking_date, b.branch_id ORDER BY b.booking_date",
            $args
        );

        $grid = [];
        foreach ($rows as $r) {
            $grid[$r['d']][(int) $r['branch_id']] = (float) $r['sales'];
        }

        $from = new DateTimeImmutable($f['from']);
        $to   = new DateTimeImmutable($f['to']);
        $labels = [];
        if ($from->diff($to)->days <= 62) {
            for ($d = $from; $d <= $to; $d = $d->modify('+1 day')) {
                $labels[] = $d->format('Y-m-d');
            }
        } else {
            $labels = array_keys($grid);
            sort($labels);
        }

        $series = [];
        foreach ($this->branches() as $br) {
            if ($f['branch'] && (int) $br['id'] !== $f['branch']) {
                continue;
            }
            $vals = [];
            foreach ($labels as $day) {
                $vals[] = $grid[$day][(int) $br['id']] ?? 0.0;
            }
            $series[$br['name']] = $vals;
        }
        return ['labels' => $labels, 'series' => $series];
    }

    /** Bills created per weekday, Monday first. */
    public function weeklyPattern(array $f): array
    {
        $args = [];
        $rows = $this->all(
            "SELECT DAYOFWEEK(b.booking_date) AS n, COUNT(*) AS bills
             FROM bills b
             WHERE b.deleted_at IS NULL AND " . $this->between('b.booking_date', $args, $f) . $this->branchIs('b.branch_id', $args, $f) . "
             GROUP BY n",
            $args
        );
        $by = [];
        foreach ($rows as $r) {
            $by[(int) $r['n']] = (int) $r['bills'];
        }
        $names = [2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday', 6 => 'Friday', 7 => 'Saturday', 1 => 'Sunday'];
        $out = [];
        foreach ($names as $n => $name) {
            $out[] = ['name' => $name, 'bills' => $by[$n] ?? 0];
        }
        return $out;
    }
}
