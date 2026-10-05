<?php
class User
{
    public static function findByUsername(PDO $db, string $username): ?array
    {
        $stmt = $db->prepare(
            'SELECT u.*, b.name AS branch_name
             FROM users u
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.username = ?
             LIMIT 1'
        );
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }
}