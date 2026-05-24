<?php

namespace Models;

use PDO;

class AdminModuleChildModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = AdminModuleDatabase::getInstance();
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.child_id AS id, c.nickname AS name, c.age, c.emoji, c.theme, c.character_id,
                    u.id AS user_id, u.username AS parent_name, u.email AS parent_email,
                    ch.name AS character_name
             FROM children c
             JOIN parents p ON p.parent_id = c.parent_id
             JOIN users u ON u.id = p.user_id
             LEFT JOIN characters ch ON ch.character_id = c.character_id
             ORDER BY c.child_id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM children')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.child_id AS id, c.nickname AS name, c.age, c.emoji, c.theme, c.character_id,
                    u.id AS user_id, u.username AS parent_name
             FROM children c
             JOIN parents p ON p.parent_id = c.parent_id
             JOIN users u ON u.id = p.user_id
             WHERE c.child_id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findAllSimple(): array
    {
        $stmt = $this->db->query(
            'SELECT child_id AS id, nickname AS name, age
             FROM children
             ORDER BY nickname ASC'
        );
        return $stmt->fetchAll();
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(array $data): int
    {
        $parentId = $this->getParentId($data['user_id']);

        $stmt = $this->db->prepare(
            'INSERT INTO children (parent_id, nickname, age, emoji, theme, character_id, created_at, updated_at)
             VALUES (:parent_id, :nickname, :age, :emoji, :theme, :character_id, NOW(), NOW())'
        );
        $stmt->execute([
            ':parent_id' => $parentId,
            ':nickname' => $data['name'],
            ':age' => $data['age'] ?: null,
            ':emoji' => $data['emoji'] ?? '🦊',
            ':theme' => $data['theme'] ?? 'theme-rose',
            ':character_id' => $data['character_id'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $parentId = $this->getParentId($data['user_id']);

        $stmt = $this->db->prepare(
            'UPDATE children
             SET parent_id = :parent_id, nickname = :nickname, age = :age,
                 emoji = :emoji, theme = :theme, character_id = :character_id, updated_at = NOW()
             WHERE child_id = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':parent_id' => $parentId,
            ':nickname' => $data['name'],
            ':age' => $data['age'] ?: null,
            ':emoji' => $data['emoji'],
            ':theme' => $data['theme'],
            ':character_id' => $data['character_id'] ?? null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM children WHERE child_id = :id');
        return $stmt->execute([':id' => $id]);
    }

    private function getParentId(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT parent_id FROM parents WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        $parentId = $stmt->fetchColumn();
        if ($parentId) {
            return (int) $parentId;
        }

        $insert = $this->db->prepare('INSERT INTO parents (user_id) VALUES (:user_id)');
        $insert->execute([':user_id' => $userId]);
        return (int) $this->db->lastInsertId();
    }
}
