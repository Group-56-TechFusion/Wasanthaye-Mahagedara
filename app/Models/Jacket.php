<?php
class Jacket
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public static function showroomLabel(string $value): string
    {
        return $value === 'all' ? 'All Showrooms' : str_replace(',', ', ', $value);
    }

    public function byCategory(string $category): array
    {
        $stmt = $this->db->prepare('SELECT * FROM jackets WHERE category = ? ORDER BY name');
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM jackets WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function codeExists(string $code, ?int $ignoreId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM jackets WHERE code = ? AND id <> ?');
        $stmt->execute([$code, $ignoreId ?? 0]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $d): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO jackets (name, code, category, showrooms, size, status, quantity, pageboy_quantity, note, image_1, image_2)
             VALUES (:name, :code, :category, :showrooms, :size, :status, :quantity, :pageboy_quantity, :note, :image_1, :image_2)'
        );
        $stmt->execute($this->params($d));
    }

    public function update(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE jackets SET name = :name, code = :code, category = :category, showrooms = :showrooms, size = :size,
             status = :status, quantity = :quantity, pageboy_quantity = :pageboy_quantity, note = :note,
             image_1 = :image_1, image_2 = :image_2 WHERE id = :id'
        );
        $stmt->execute($this->params($d) + ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM jackets WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function params(array $d): array
    {
        return [
            'name'             => $d['name'],
            'code'             => $d['code'],
            'category'         => $d['category'],
            'showrooms'        => $d['showrooms'],
            'size'             => $d['size'],
            'status'           => $d['status'],
            'quantity'         => (int) $d['quantity'],
            'pageboy_quantity' => (int) $d['pageboy_quantity'],
            'note'             => ($d['note'] ?? '') === '' ? null : $d['note'],
            'image_1'          => $d['image_1'] ?? null,
            'image_2'          => $d['image_2'] ?? null,
        ];
    }
}
