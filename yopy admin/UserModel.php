<?php
/**
 * YOPY — User Model
 * Handles all database operations for parent (user) accounts.
 *
 * Expected table: users
 *   id, name, email, password_hash, pin_hash, status (active|suspended),
 *   plan (free|premium), created_at, updated_at
 */

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, COUNT(c.id) AS child_count
             FROM users u
             LEFT JOIN children c ON c.user_id = u.id
             GROUP BY u.id
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function countActive(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    }

    public function countByPlan(string $plan): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE plan = :plan');
        $stmt->execute([':plan' => $plan]);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, pin_hash, status, plan, created_at, updated_at)
             VALUES (:name, :email, :password_hash, :pin_hash, :status, :plan, NOW(), NOW())'
        );
        $stmt->execute([
            ':name'          => $data['name'],
            ':email'         => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':pin_hash'      => password_hash($data['pin'],      PASSWORD_BCRYPT),
            ':status'        => $data['status'] ?? 'active',
            ':plan'          => $data['plan']   ?? 'free',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = ['name = :name', 'email = :email', 'status = :status', 'plan = :plan', 'updated_at = NOW()'];
        $params = [
            ':id'     => $id,
            ':name'   => $data['name'],
            ':email'  => $data['email'],
            ':status' => $data['status'],
            ':plan'   => $data['plan'],
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (!empty($data['pin'])) {
            $fields[] = 'pin_hash = :pin_hash';
            $params[':pin_hash'] = password_hash($data['pin'], PASSWORD_BCRYPT);
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        return $this->db->prepare($sql)->execute($params);
    }

    public function toggleStatus(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET status = IF(status = 'active', 'suspended', 'active'), updated_at = NOW()
             WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // ── Admin Auth ────────────────────────────────────────────────────────────

    public function findAdmin(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email AND role = 'admin'");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }
}
