<?php
class Bill
{
    private static ?PDO $pdo = null;

    private function db(): PDO
    {
        return self::$pdo ??= require BASE_PATH . '/config/database.php';
    }

    private const PAID_JOIN = "LEFT JOIN (SELECT bill_id, SUM(amount) AS paid FROM bill_payments GROUP BY bill_id) p ON p.bill_id = b.id";

    private const COLUMNS = [
        'customer_name', 'customer_email', 'address', 'phone1', 'phone2', 'note',
        'wedding_date', 'dressing_location', 'wedding_hotel',
        'groom_jacket_id', 'bestman_jacket_id', 'groom_kawani', 'bestman_kawani',
        'bestmen_count', 'pageboys_count',
        'fiber_sword', 'white_kawani', 'going_away_kit', 'homecoming_kit',
        'package_price', 'transport', 'discount', 'total', 'is_mobile_shop',
    ];

    // ---------------------------------------------------------------- reading

    public function branches(): array
    {
        return $this->db()->query('SELECT id, name FROM branches ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function branchName(int $id): string
    {
        $st = $this->db()->prepare('SELECT name FROM branches WHERE id = :id');
        $st->execute([':id' => $id]);
        return (string) $st->fetchColumn();
    }

    /** Summary cards. Cancelled bills and postponed bills keep only the money already paid in the sale figure. */
    public function stats(?int $scope): array
    {
        $sql = "SELECT
                  SUM(b.deleted_at IS NULL) AS total_bills,
                  SUM(CASE
                        WHEN b.deleted_at IS NULL AND b.is_postponed = 0 THEN b.total
                        ELSE COALESCE(p.paid, 0)
                      END) AS total_sale,
                  SUM(b.deleted_at IS NULL AND b.is_postponed = 0 AND (b.total - COALESCE(p.paid, 0)) <= 0) AS paid_bills,
                  SUM(b.deleted_at IS NULL AND b.is_postponed = 0 AND (b.total - COALESCE(p.paid, 0)) > 0)  AS pending_bills,
                  SUM(b.deleted_at IS NULL AND b.is_postponed = 1) AS postponed,
                  SUM(b.deleted_at IS NOT NULL) AS cancelled
                FROM bills b " . self::PAID_JOIN . ($scope ? ' WHERE b.branch_id = :scope' : '');
        $st = $this->db()->prepare($sql);
        $st->execute($scope ? [':scope' => $scope] : []);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total_bills'   => (int) ($row['total_bills'] ?? 0),
            'total_sale'    => (float) ($row['total_sale'] ?? 0),
            'paid_bills'    => (int) ($row['paid_bills'] ?? 0),
            'pending_bills' => (int) ($row['pending_bills'] ?? 0),
            'postponed'     => (int) ($row['postponed'] ?? 0),
            'cancelled'     => (int) ($row['cancelled'] ?? 0),
        ];
    }

    private function listSelect(): string
    {
        return "SELECT b.*, br.name AS branch_name, COALESCE(p.paid, 0) AS paid,
                       (b.total - COALESCE(p.paid, 0)) AS balance
                FROM bills b
                JOIN branches br ON br.id = b.branch_id " . self::PAID_JOIN;
    }

    public function search(array $f, ?int $scope): array
    {
        $where = [];
        $args  = [];

        $where[] = $f['status'] === 'cancelled' ? 'b.deleted_at IS NOT NULL' : 'b.deleted_at IS NULL';
        switch ($f['status']) {
            case 'paid':
                $where[] = 'b.is_postponed = 0 AND (b.total - COALESCE(p.paid, 0)) <= 0';
                break;
            case 'pending':
                $where[] = 'b.is_postponed = 0 AND (b.total - COALESCE(p.paid, 0)) > 0';
                break;
            case 'postponed':
                $where[] = 'b.is_postponed = 1';
                break;
        }

        $branch = $scope ?: ((int) $f['branch_id'] ?: null);
        if ($branch) {
            $where[] = 'b.branch_id = :branch';
            $args[':branch'] = $branch;
        }

        if ($f['q'] !== '') {
            $like  = '%' . addcslashes($f['q'], '%_\\') . '%';
            $cols  = ['b.bill_number', 'b.customer_name', 'b.phone1', 'b.phone2', 'b.customer_email', 'b.address', 'b.wedding_hotel'];
            $parts = [];
            foreach ($cols as $i => $c) {
                $parts[] = "$c LIKE :q$i";
                $args[":q$i"] = $like;
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        $col = $f['date_by'] === 'wedding_date' ? 'b.wedding_date' : 'b.booking_date';
        if ($f['from'] !== '') {
            $where[] = "$col >= :from";
            $args[':from'] = $f['from'];
        }
        if ($f['to'] !== '') {
            $where[] = "$col <= :to";
            $args[':to'] = $f['to'];
        }

        $sql = $this->listSelect() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY b.booking_date DESC, b.id DESC LIMIT 500';
        $st = $this->db()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recycleBin(?int $scope): array
    {
        $sql = $this->listSelect() . ' WHERE b.deleted_at IS NOT NULL' . ($scope ? ' AND b.branch_id = :scope' : '') . ' ORDER BY b.deleted_at DESC';
        $st = $this->db()->prepare($sql);
        $st->execute($scope ? [':scope' => $scope] : []);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT b.*, br.name AS branch_name, COALESCE(p.paid, 0) AS paid,
                       (b.total - COALESCE(p.paid, 0)) AS balance,
                       gj.code AS groom_code, gj.name AS groom_name,
                       bj.code AS bestman_code, bj.name AS bestman_name,
                       u.username AS created_by_name
                FROM bills b
                JOIN branches br ON br.id = b.branch_id " . self::PAID_JOIN . "
                LEFT JOIN jackets gj ON gj.id = b.groom_jacket_id
                LEFT JOIN jackets bj ON bj.id = b.bestman_jacket_id
                LEFT JOIN users u ON u.id = b.created_by
                WHERE b.id = :id";
        $st = $this->db()->prepare($sql);
        $st->execute([':id' => $id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Payments keyed by seq (1 = advance, 2..4 = installments). */
    public function payments(int $billId): array
    {
        $st = $this->db()->prepare(
            'SELECT bp.*, br.name AS branch_name FROM bill_payments bp
             JOIN branches br ON br.id = bp.branch_id
             WHERE bp.bill_id = :id ORDER BY bp.seq'
        );
        $st->execute([':id' => $billId]);
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(int) $row['seq']] = $row;
        }
        return $out;
    }

    public function members(int $billId): array
    {
        $st = $this->db()->prepare("SELECT * FROM bill_party_members WHERE bill_id = :id ORDER BY FIELD(member_type, 'groom', 'bestman', 'pageboy'), position");
        $st->execute([':id' => $billId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Earlier customers matching a name or phone (for "Find Existing Customer"). */
    public function findCustomers(string $q, ?int $scope): array
    {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $sql = "SELECT customer_name, customer_email, address, phone1, phone2, MAX(id) AS last_id
                FROM bills
                WHERE (customer_name LIKE :a OR phone1 LIKE :b OR phone2 LIKE :c)"
             . ($scope ? ' AND branch_id = :scope' : '') . "
                GROUP BY customer_name, customer_email, address, phone1, phone2
                ORDER BY last_id DESC LIMIT 10";
        $args = [':a' => $like, ':b' => $like, ':c' => $like];
        if ($scope) {
            $args[':scope'] = $scope;
        }
        $st = $this->db()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------ availability

    /**
     * Jackets that can be picked for a bill.
     * A jacket is locked only on the wedding date itself: stock minus what other
     * live bills (not deleted, not postponed) already use on that same date.
     * Options: category, branch_name, need, date (Y-m-d|null), exclude (bill id), q, id (optional).
     */
    public function availableJackets(array $o): array
    {
        $args = [
            ':cat'    => $o['category'],
            ':branch' => $o['branch_name'],
            ':need'   => max(1, (int) $o['need']),
        ];
        $join = '';
        $used = '0';

        if (!empty($o['date'])) {
            $join = "LEFT JOIN (
                        SELECT jid, SUM(n) AS used FROM (
                            SELECT groom_jacket_id AS jid, 1 AS n FROM bills
                             WHERE wedding_date = :d1 AND deleted_at IS NULL AND is_postponed = 0
                               AND groom_jacket_id IS NOT NULL AND id <> :x1
                            UNION ALL
                            SELECT bestman_jacket_id AS jid, GREATEST(bestmen_count, 1) AS n FROM bills
                             WHERE wedding_date = :d2 AND deleted_at IS NULL AND is_postponed = 0
                               AND bestman_jacket_id IS NOT NULL AND id <> :x2
                        ) t GROUP BY jid
                     ) u ON u.jid = j.id";
            $used = 'COALESCE(u.used, 0)';
            $args[':d1'] = $args[':d2'] = $o['date'];
            $args[':x1'] = $args[':x2'] = (int) ($o['exclude'] ?? 0);
        }

        $where = "j.status = 'available' AND j.category = :cat
                  AND (j.showrooms = 'all' OR FIND_IN_SET(:branch, j.showrooms))";
        if (!empty($o['id'])) {
            $where .= ' AND j.id = :id';
            $args[':id'] = (int) $o['id'];
        }
        if (($o['q'] ?? '') !== '') {
            $like = '%' . addcslashes($o['q'], '%_\\') . '%';
            $where .= ' AND (j.code LIKE :q1 OR j.name LIKE :q2)';
            $args[':q1'] = $args[':q2'] = $like;
        }

        $sql = "SELECT j.id, j.code, j.name, j.size, (j.quantity - $used) AS remaining
                FROM jackets j $join
                WHERE $where
                HAVING remaining >= :need
                ORDER BY j.code LIMIT 30";
        $st = $this->db()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ----------------------------------------------------------------- writing

    public function create(array $d, int $branchId, ?int $userId): int
    {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $number = $d['bill_number'] !== '' ? $d['bill_number'] : $this->nextNumber();

            $cols = array_merge(['bill_number', 'booking_date', 'branch_id', 'created_by'], self::COLUMNS);
            $vals = [$number, $d['booking_date'], $branchId, $userId];
            foreach (self::COLUMNS as $c) {
                $vals[] = $d[$c];
            }
            $sql = 'INSERT INTO bills (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
            $pdo->prepare($sql)->execute($vals);
            $id = (int) $pdo->lastInsertId();

            $this->syncPayments($id, $this->wanted($d, $d['booking_date'], []), [], $branchId, $userId);
            $this->saveMembers($id, $d['members']);

            $pdo->commit();
            return $id;
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() === '23000') {
                throw new RuntimeException('That bill number already exists.');
            }
            throw $e;
        }
    }

    public function update(int $id, array $d, array $existing, array $have, int $paymentBranch, ?int $userId): void
    {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $sets = ['bill_number = ?', 'booking_date = ?'];
            $vals = [$d['bill_number'], $d['booking_date']];
            foreach (self::COLUMNS as $c) {
                $sets[] = "$c = ?";
                $vals[] = $d[$c];
            }
            // Giving a postponed bill a new wedding date reactivates it.
            if ($existing['is_postponed'] && $d['wedding_date']) {
                $sets[] = 'is_postponed = 0';
                $sets[] = 'postponed_from = NULL';
            }
            $vals[] = $id;
            $pdo->prepare('UPDATE bills SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);

            $this->syncPayments($id, $this->wanted($d, $existing['booking_date'], $have), $have, $paymentBranch, $userId);
            $this->saveMembers($id, $d['members']);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() === '23000') {
                throw new RuntimeException('That bill number already exists.');
            }
            throw $e;
        }
    }

    /** Postpone: highlight the bill, clear the wedding date (kept in postponed_from), release the items. */
    public function postpone(int $id): bool
    {
        $st = $this->db()->prepare(
            'UPDATE bills SET is_postponed = 1, postponed_from = wedding_date, wedding_date = NULL
             WHERE id = :id AND deleted_at IS NULL AND is_postponed = 0'
        );
        $st->execute([':id' => $id]);
        return $st->rowCount() > 0;
    }

    /** Move to the recycle bin. Payments stay, so the advance still counts in every total. */
    public function softDelete(int $id, ?int $userId): bool
    {
        $st = $this->db()->prepare('UPDATE bills SET deleted_at = NOW(), deleted_by = :u WHERE id = :id AND deleted_at IS NULL');
        $st->execute([':u' => $userId, ':id' => $id]);
        return $st->rowCount() > 0;
    }

    public function restore(int $id): bool
    {
        $st = $this->db()->prepare('UPDATE bills SET deleted_at = NULL, deleted_by = NULL WHERE id = :id AND deleted_at IS NOT NULL');
        $st->execute([':id' => $id]);
        return $st->rowCount() > 0;
    }

    // ----------------------------------------------------------------- helpers

    private function nextNumber(): string
    {
        $n = $this->db()->query("SELECT COALESCE(MAX(CAST(bill_number AS UNSIGNED)), 1000) + 1 FROM bills")->fetchColumn();
        return (string) $n;
    }

    /** Desired payment rows per seq, null = no row. */
    private function wanted(array $d, string $advanceDate, array $have): array
    {
        $want = [
            1 => $d['advance_amount'] > 0
                ? ['amount' => $d['advance_amount'], 'date' => $have[1]['payment_date'] ?? $advanceDate, 'method' => $d['advance_method']]
                : null,
        ];
        foreach ([2, 3, 4] as $n) {
            $i = $d['installments'][$n] ?? null;
            $want[$n] = ($i && $i['amount'] > 0) ? $i : null;
        }
        return $want;
    }

    /**
     * New or changed rows are stamped with the branch of the logged-in user;
     * untouched rows keep the branch they were originally recorded at.
     */
    private function syncPayments(int $billId, array $want, array $have, int $branchId, ?int $userId): void
    {
        $pdo = $this->db();
        foreach ($want as $seq => $w) {
            $cur = $have[$seq] ?? null;
            if ($w === null) {
                if ($cur) {
                    $pdo->prepare('DELETE FROM bill_payments WHERE id = ?')->execute([$cur['id']]);
                }
                continue;
            }
            if (!$cur) {
                $pdo->prepare('INSERT INTO bill_payments (bill_id, seq, amount, payment_date, method, branch_id, recorded_by) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$billId, $seq, $w['amount'], $w['date'], $w['method'], $branchId, $userId]);
            } elseif (abs((float) $cur['amount'] - $w['amount']) > 0.004
                || $cur['payment_date'] !== $w['date']
                || $cur['method'] !== $w['method']) {
                $pdo->prepare('UPDATE bill_payments SET amount = ?, payment_date = ?, method = ?, branch_id = ?, recorded_by = ? WHERE id = ?')
                    ->execute([$w['amount'], $w['date'], $w['method'], $branchId, $userId, $cur['id']]);
            }
        }
    }

    private function saveMembers(int $billId, array $members): void
    {
        $pdo = $this->db();
        $pdo->prepare('DELETE FROM bill_party_members WHERE bill_id = ?')->execute([$billId]);
        $ins = $pdo->prepare(
            'INSERT INTO bill_party_members (bill_id, member_type, position, name, cap_size, jacket_size, trouser_size, shoe_size)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        foreach ($members as $m) {
            $ins->execute([$billId, $m['type'], $m['position'], $m['name'], $m['cap'], $m['jacket'], $m['trouser'], $m['shoe']]);
        }
    }
}