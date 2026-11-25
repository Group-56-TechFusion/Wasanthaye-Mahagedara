<?php
class Dashboard
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function overview(?int $branchId, ?string $branchName): array
    {
        $scopeSql = $branchId === null ? '' : ' AND b.branch_id = :branch_id';
        $params = $branchId === null ? [] : [':branch_id' => $branchId];
        $monthly = $this->one(
            "SELECT COUNT(*) AS bills,
                    COALESCE(SUM(b.total), 0) AS sales,
                    COALESCE(SUM(GREATEST(
                        CASE WHEN b.is_postponed = 0 THEN b.total ELSE COALESCE(p.paid, 0) END
                        - COALESCE(p.paid, 0), 0
                    )), 0) AS outstanding
             FROM bills b
             LEFT JOIN (
                 SELECT bill_id, SUM(amount) AS paid
                 FROM bill_payments
                 GROUP BY bill_id
             ) p ON p.bill_id = b.id
             WHERE b.deleted_at IS NULL
               AND b.booking_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')$scopeSql",
            $params
        );

        $upcomingParams = $branchId === null ? [] : [':branch_id' => $branchId];
        $upcomingScope = $branchId === null ? '' : ' AND b.branch_id = :branch_id';
        $upcomingCount = $this->one(
            "SELECT COUNT(*) AS total
             FROM bills b
             WHERE b.deleted_at IS NULL AND b.is_postponed = 0
               AND b.wedding_date >= CURDATE()
               AND b.wedding_date < DATE_ADD(CURDATE(), INTERVAL 31 DAY)$upcomingScope",
            $upcomingParams
        );
        $upcomingWeddings = $this->all(
            "SELECT b.id, b.bill_number, b.customer_name, b.wedding_date, br.name AS branch_name
             FROM bills b
             JOIN branches br ON br.id = b.branch_id
             WHERE b.deleted_at IS NULL AND b.is_postponed = 0
               AND b.wedding_date >= CURDATE()
               AND b.wedding_date < DATE_ADD(CURDATE(), INTERVAL 31 DAY)$upcomingScope
             ORDER BY b.wedding_date, b.id
             LIMIT 6",
            $upcomingParams
        );

        $recentParams = $branchId === null ? [] : [':branch_id' => $branchId];
        $recentScope = $branchId === null ? '' : ' AND b.branch_id = :branch_id';
        $recentBills = $this->all(
            "SELECT b.id, b.bill_number, b.customer_name, b.booking_date, b.total,
                    br.name AS branch_name
             FROM bills b
             JOIN branches br ON br.id = b.branch_id
             WHERE b.deleted_at IS NULL$recentScope
             ORDER BY b.booking_date DESC, b.id DESC
             LIMIT 6",
            $recentParams
        );

        $lowStock = $this->lowStock($branchName);
        return [
            'monthly' => [
                'bills' => (int) $monthly['bills'],
                'sales' => (float) $monthly['sales'],
                'outstanding' => (float) $monthly['outstanding'],
            ],
            'upcomingCount' => (int) $upcomingCount['total'],
            'upcomingWeddings' => $upcomingWeddings,
            'recentBills' => $recentBills,
            'lowStock' => $lowStock,
            'branches' => $branchId === null ? $this->branchPerformance() : [],
        ];
    }

    private function branchPerformance(): array
    {
        return $this->all(
            "SELECT br.id, br.name, br.status,
                    COUNT(b.id) AS bills,
                    COALESCE(SUM(b.total), 0) AS sales,
                    COUNT(DISTINCT b.phone1) AS customers
             FROM branches br
             LEFT JOIN bills b
               ON b.branch_id = br.id AND b.deleted_at IS NULL
                  AND b.booking_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
             GROUP BY br.id, br.name, br.status
             ORDER BY sales DESC, br.name"
        );
    }

    private function lowStock(?string $branchName): array
    {
        $sql = "SELECT id, code, name, quantity, showrooms
                FROM jackets
                WHERE status = 'available' AND quantity <= 2";
        $params = [];
        if ($branchName !== null && $branchName !== '') {
            $sql .= " AND (showrooms = 'all' OR FIND_IN_SET(:branch_name, showrooms))";
            $params[':branch_name'] = $branchName;
        }
        $sql .= ' ORDER BY quantity, name LIMIT 5';
        return $this->all($sql, $params);
    }

    private function one(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function all(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
