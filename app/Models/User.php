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

    public static function all(PDO $db): array
    {
        $stmt = $db->query(
            'SELECT u.id, u.username, u.role, u.is_active, b.name AS branch_name
             FROM users u
             LEFT JOIN branches b ON b.id = u.branch_id
             ORDER BY u.username'
        );
        return $stmt->fetchAll();
    }

    public static function create(PDO $db, string $username, string $password, string $role, ?int $branchId): void
    {
        $stmt = $db->prepare(
            'INSERT INTO users (username, password_hash, role, branch_id)
             VALUES (:username, :password_hash, :role, :branch_id)'
        );
        $stmt->execute([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'branch_id' => $branchId,
        ]);
    }
}