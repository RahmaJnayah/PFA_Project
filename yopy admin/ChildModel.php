<?php
/**
 * YOPY — Child Model
 * Handles all database operations for child profiles.
 *
 * Expected table: children
 *   id, user_id (FK→users), name, emoji, theme, character_id (FK→characters),
 *   age, created_at, updated_at
 */

class ChildModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.name AS parent_name, u.email AS parent_email,
                    ch.name AS character_name
             FROM children c
             JOIN  users u  ON u.id  = c.user_id
             LEFT JOIN characters ch ON ch.id = c.character_id
             ORDER BY c.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM children')->fetchColumn();
    }

    public function findByParent(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM children WHERE user_id = :uid ORDER BY created_at ASC');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.name AS parent_name FROM children c
             JOIN users u ON u.id = c.user_id
             WHERE c.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO children (user_id, name, emoji, theme, character_id, age, created_at, updated_at)
             VALUES (:user_id, :name, :emoji, :theme, :character_id, :age, NOW(), NOW())'
        );
        $stmt->execute([
            ':user_id'      => $data['user_id'],
            ':name'         => $data['name'],
            ':emoji'        => $data['emoji']        ?? '🦊',
            ':theme'        => $data['theme']        ?? 'theme-rose',
            ':character_id' => $data['character_id'] ?? null,
            ':age'          => $data['age']          ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE children
             SET name = :name, emoji = :emoji, theme = :theme,
                 character_id = :character_id, age = :age, updated_at = NOW()
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id'           => $id,
            ':name'         => $data['name'],
            ':emoji'        => $data['emoji'],
            ':theme'        => $data['theme'],
            ':character_id' => $data['character_id'] ?? null,
            ':age'          => $data['age']          ?? null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM children WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
