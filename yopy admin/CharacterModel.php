<?php
/**
 * YOPY — Character Model
 * Handles all database operations for onboarding companion characters
 * (Joyla, Sparko, Ticky, Binky, Bluey, Poppi …).
 *
 * Expected table: characters
 *   id, name, image, trait, tagline, color, is_active (tinyint),
 *   created_at, updated_at
 */

class CharacterModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function findAll(): array
    {
        return $this->db
            ->query('SELECT *, (SELECT COUNT(*) FROM children WHERE character_id = characters.id) AS usage_count
                     FROM characters ORDER BY id ASC')
            ->fetchAll();
    }

    public function findActive(): array
    {
        return $this->db
            ->query('SELECT * FROM characters WHERE is_active = 1 ORDER BY id ASC')
            ->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM characters')->fetchColumn();
    }

    public function countActive(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM characters WHERE is_active = 1')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM characters WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO characters (name, image, trait, tagline, color, is_active, created_at, updated_at)
             VALUES (:name, :image, :trait, :tagline, :color, :is_active, NOW(), NOW())'
        );
        $stmt->execute([
            ':name'      => $data['name'],
            ':image'     => $data['image'],
            ':trait'     => $data['trait'],
            ':tagline'   => $data['tagline'],
            ':color'     => $data['color']     ?? '#9B59B6',
            ':is_active' => (int)($data['is_active'] ?? 1),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE characters
             SET name = :name, image = :image, trait = :trait,
                 tagline = :tagline, color = :color, is_active = :is_active, updated_at = NOW()
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id'        => $id,
            ':name'      => $data['name'],
            ':image'     => $data['image'],
            ':trait'     => $data['trait'],
            ':tagline'   => $data['tagline'],
            ':color'     => $data['color'],
            ':is_active' => (int)($data['is_active'] ?? 1),
        ]);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE characters SET is_active = 1 - is_active, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([':id' => $id]);
    }

    public function delete(int $id): bool
    {
        // Unlink children first to avoid FK violations
        $this->db->prepare('UPDATE children SET character_id = NULL WHERE character_id = :id')
                 ->execute([':id' => $id]);

        $stmt = $this->db->prepare('DELETE FROM characters WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
