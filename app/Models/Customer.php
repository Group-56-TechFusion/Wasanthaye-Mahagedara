<?php
class Customer
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function all(?int $scope): array
    {
        $contactsSql = 'SELECT id, name, email, address, phone, source, created_at
                        FROM customers';
        $contactsArgs = [];
        if ($scope !== null) {
            $contactsSql .= ' WHERE branch_id = ?';
            $contactsArgs[] = $scope;
        }
        $contactsSql .= ' ORDER BY created_at DESC';
        $contactsStmt = $this->db->prepare($contactsSql);
        $contactsStmt->execute($contactsArgs);

        $rows = [];
        $phoneKeys = [];
        foreach ($contactsStmt->fetchAll() as $contact) {
            $key = trim($contact['phone']);
            $rows[$key] = [
                'name' => $contact['name'],
                'email' => $contact['email'],
                'address' => $contact['address'],
                'phone' => $contact['phone'],
                'source' => $contact['source'],
                'contact_id' => (int) $contact['id'],
                'bill_id' => 0,
                'visits' => 0,
                'bookings' => 0,
                'first_visit' => '',
                'last_visit' => '',
                'created_date' => substr($contact['created_at'], 0, 10),
            ];
            $phoneKeys[$key] = $key;
        }

        $billsSql = "
            SELECT grouped.phone, grouped.phone2, grouped.last_id, grouped.visits, grouped.bookings,
                   grouped.first_visit, grouped.last_visit, b.customer_name, b.customer_email,
                   b.address, b.is_mobile_shop
            FROM (
                SELECT TRIM(phone1) AS phone, MAX(TRIM(phone2)) AS phone2, MAX(id) AS last_id,
                       COUNT(*) AS visits,
                       SUM(wedding_date IS NOT NULL AND is_postponed = 0) AS bookings,
                       MIN(booking_date) AS first_visit, MAX(booking_date) AS last_visit
                FROM bills
                WHERE deleted_at IS NULL";
        $billArgs = [];
        if ($scope !== null) {
            $billsSql .= ' AND branch_id = ?';
            $billArgs[] = $scope;
        }
        $billsSql .= "
                GROUP BY TRIM(phone1)
            ) grouped
            JOIN bills b ON b.id = grouped.last_id
            ORDER BY grouped.last_visit DESC, grouped.last_id DESC";
        $billsStmt = $this->db->prepare($billsSql);
        $billsStmt->execute($billArgs);

        foreach ($billsStmt->fetchAll() as $bill) {
            $phone = trim($bill['phone']);
            $secondaryPhone = trim((string) $bill['phone2']);
            $key = $phoneKeys[$phone] ?? $phoneKeys[$secondaryPhone] ?? $phone;
            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'name' => $bill['customer_name'],
                    'email' => $bill['customer_email'],
                    'address' => $bill['address'],
                    'phone' => $phone,
                    'source' => (int) $bill['is_mobile_shop'] === 1 ? 'mobile_shop' : 'showroom',
                    'contact_id' => 0,
                    'bill_id' => (int) $bill['last_id'],
                    'visits' => 0,
                    'bookings' => 0,
                    'first_visit' => '',
                    'last_visit' => '',
                    'created_date' => '',
                ];
                $key = $phone;
                $phoneKeys[$phone] = $key;
                if ($secondaryPhone !== '') {
                    $phoneKeys[$secondaryPhone] = $key;
                }
            }

            $rows[$key]['visits'] += (int) $bill['visits'];
            $rows[$key]['bookings'] += (int) $bill['bookings'];
            $rows[$key]['first_visit'] = $rows[$key]['first_visit'] === ''
                ? $bill['first_visit']
                : min($rows[$key]['first_visit'], $bill['first_visit']);
            $rows[$key]['last_visit'] = max($rows[$key]['last_visit'], $bill['last_visit']);
            if ($rows[$key]['contact_id'] === 0) {
                $rows[$key]['name'] = $bill['customer_name'];
                $rows[$key]['email'] = $bill['customer_email'];
                $rows[$key]['address'] = $bill['address'];
            }
            if ($rows[$key]['bill_id'] === 0) {
                $rows[$key]['bill_id'] = (int) $bill['last_id'];
            }
        }

        $result = array_values($rows);
        foreach ($result as &$row) {
            $codeDate = $row['first_visit'] ?: $row['created_date'] ?: date('Y-m-d');
            $codeId = $row['contact_id'] ? 'C' . $row['contact_id'] : 'B' . $row['bill_id'];
            $row['code'] = 'CUS-' . str_replace('-', '', $codeDate) . '-' . str_pad($codeId, 5, '0', STR_PAD_LEFT);
        }
        unset($row);

        usort($result, static function (array $a, array $b): int {
            return strcmp($b['last_visit'] ?: $b['created_date'], $a['last_visit'] ?: $a['created_date']);
        });
        return $result;
    }

    public function findByPhone(string $phone, ?int $scope): ?array
    {
        foreach ($this->all($scope) as $customer) {
            if ($customer['phone'] === $phone) {
                return $customer;
            }
        }
        return null;
    }

    public function history(string $phone, ?int $scope): array
    {
        $sql = 'SELECT b.id, b.bill_number, b.booking_date, b.wedding_date, b.total,
                       b.deleted_at, br.name AS branch_name
                FROM bills b
                JOIN branches br ON br.id = b.branch_id
                WHERE (b.phone1 = ? OR b.phone2 = ?)';
        $args = [$phone, $phone];
        if ($scope !== null) {
            $sql .= ' AND b.branch_id = ?';
            $args[] = $scope;
        }
        $sql .= ' ORDER BY b.booking_date DESC, b.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }

    public function phoneExists(string $phone): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM customers WHERE phone = ?');
        $stmt->execute([$phone]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data, ?int $branchId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO customers (name, email, address, phone, source, branch_id)
             VALUES (:name, :email, :address, :phone, :source, :branch_id)'
        );
        $stmt->execute($data + ['branch_id' => $branchId]);
    }
}
