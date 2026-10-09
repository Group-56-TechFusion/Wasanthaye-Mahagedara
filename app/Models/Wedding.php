<?php
class Wedding
{
    public static function between(
        PDO $db,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        ?int $branchId
    ): array {
        $sql = 'SELECT w.*, b.name AS branch_name
                FROM weddings w
                JOIN branches b ON b.id = w.branch_id
                WHERE w.wedding_date BETWEEN ? AND ?';
        $params = [$start->format('Y-m-d'), $end->format('Y-m-d')];

        if ($branchId !== null) {
            $sql .= ' AND w.branch_id = ?';
            $params[] = $branchId;
        }

        $sql .= ' ORDER BY w.wedding_date, w.groom_name, w.bride_name';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function branches(PDO $db): array
    {
        return $db->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
    }

    public static function create(PDO $db, array $wedding): void
    {
        $stmt = $db->prepare(
            'INSERT INTO weddings
                (groom_name, bride_name, wedding_date, branch_id, location, hotel, estimated_amount, maid_boys)
             VALUES
                (:groom_name, :bride_name, :wedding_date, :branch_id, :location, :hotel, :estimated_amount, :maid_boys)'
        );
        $stmt->execute($wedding);
    }
}
