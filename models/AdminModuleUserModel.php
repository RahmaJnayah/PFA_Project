<?php

namespace Models;

use PDO;

class AdminModuleUserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = AdminModuleDatabase::getInstance();
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.username AS name, u.email, u.plan, u.status, u.role, u.created_at,
                    COUNT(c.child_id) AS child_count
             FROM users u
             LEFT JOIN parents p ON p.user_id = u.id
             LEFT JOIN children c ON c.parent_id = p.parent_id
             GROUP BY u.id
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
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
        $stmt = $this->db->prepare('SELECT u.id, u.username AS name, u.email, u.plan, u.status, u.role FROM users u WHERE u.id = :id');
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
            'INSERT INTO users (username, email, password_hash, pin_hash, role, status, plan, created_at, updated_at)
             VALUES (:username, :email, :password_hash, :pin_hash, :role, :status, :plan, NOW(), NOW())'
        );
        $stmt->execute([
            ':username' => $data['name'],
            ':email' => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':pin_hash' => !empty($data['pin']) ? password_hash($data['pin'], PASSWORD_BCRYPT) : null,
            ':role' => 'parent',
            ':status' => $data['status'] ?? 'active',
            ':plan' => $data['plan'] ?? 'free',
        ]);

        $userId = (int) $this->db->lastInsertId();
        $this->ensureParentRow($userId);

        return $userId;
    }

    public function update(int $id, array $data): bool
    {
        $fields = ['username = :username', 'email = :email', 'status = :status', 'plan = :plan', 'updated_at = NOW()'];
        $params = [
            ':id' => $id,
            ':username' => $data['name'],
            ':email' => $data['email'],
            ':status' => $data['status'],
            ':plan' => $data['plan'],
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

    private function ensureParentRow(int $userId): void
    {
        $stmt = $this->db->prepare('SELECT parent_id FROM parents WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        if ($stmt->fetchColumn()) {
            return;
        }

        $insert = $this->db->prepare('INSERT INTO parents (user_id) VALUES (:user_id)');
        $insert->execute([':user_id' => $userId]);
    }
}
