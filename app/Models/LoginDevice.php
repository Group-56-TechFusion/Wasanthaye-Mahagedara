<?php
class LoginDevice
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function recordLoginAttempt(int $userId, string $tokenHash, string $userAgent): string
    {
        $stmt = $this->db->prepare(
            "INSERT INTO login_devices (user_id, token_hash, user_agent, status)
             VALUES (:user_id, :token_hash, :user_agent, 'pending')
             ON DUPLICATE KEY UPDATE
               user_agent = VALUES(user_agent),
               last_seen_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':user_agent' => mb_substr($userAgent, 0, 500, 'UTF-8'),
        ]);

        $stmt = $this->db->prepare(
            'SELECT status FROM login_devices WHERE user_id = :user_id AND token_hash = :token_hash'
        );
        $stmt->execute([':user_id' => $userId, ':token_hash' => $tokenHash]);
        $status = $stmt->fetchColumn();
        if (!is_string($status)) {
            throw new RuntimeException('Unable to read the device approval status.');
        }

        return $status;
    }

    public function all(): array
    {
        return $this->db->query(
            "SELECT d.id, d.user_agent, d.status, d.first_seen_at, d.last_seen_at,
                    d.reviewed_at, u.username, u.role, b.name AS branch_name,
                    reviewer.username AS reviewed_by
             FROM login_devices d
             JOIN users u ON u.id = d.user_id
             LEFT JOIN branches b ON b.id = u.branch_id
             LEFT JOIN users reviewer ON reviewer.id = d.reviewed_by
             ORDER BY CASE d.status
                        WHEN 'pending' THEN 0
                        WHEN 'approved' THEN 1
                        WHEN 'rejected' THEN 2
                        ELSE 3
                      END,
                      d.last_seen_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status, int $adminId): bool
    {
        $allowedTransitions = [
            'approved' => "status IN ('pending', 'rejected', 'revoked')",
            'rejected' => "status = 'pending'",
            'revoked' => "status = 'approved'",
        ];
        if (!isset($allowedTransitions[$status])) {
            throw new InvalidArgumentException('Invalid device status.');
        }

        $stmt = $this->db->prepare(
            "UPDATE login_devices
             SET status = :status, reviewed_by = :admin_id, reviewed_at = CURRENT_TIMESTAMP
             WHERE id = :id AND {$allowedTransitions[$status]}"
        );
        $stmt->execute([':status' => $status, ':admin_id' => $adminId, ':id' => $id]);
        return $stmt->rowCount() === 1;
    }
}
