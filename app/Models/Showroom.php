<?php
class Showroom
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, type, address, contact_number, status
             FROM branches
             ORDER BY type DESC, name'
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, type, address, contact_number, status
             FROM branches
             WHERE id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM branches WHERE name = ?';
        $params = [$name];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO branches (name, type, address, contact_number, status)
             VALUES (:name, :type, :address, :contact_number, :status)'
        );
        $stmt->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE branches
             SET name = :name, type = :type, address = :address,
                 contact_number = :contact_number, status = :status
             WHERE id = :id'
        );
        $stmt->execute($data + ['id' => $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE branches SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }
}
