<?php

namespace Models;

use PDO;

class AdminModuleGameModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = AdminModuleDatabase::getInstance();
    }

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT game_id, name, category, difficulty, description, is_active
             FROM games
             ORDER BY name ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM games')->fetchColumn();
    }

    public function countActive(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM games WHERE is_active = 1')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT game_id, name, category, difficulty, description, is_active
             FROM games WHERE game_id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE games
             SET name = :name,
                 category = :category,
                 difficulty = :difficulty,
                 description = :description
             WHERE game_id = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':category' => $data['category'],
            ':difficulty' => $data['difficulty'],
            ':description' => $data['description'],
        ]);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE games
             SET is_active = IF(is_active = 1, 0, 1)
             WHERE game_id = :id'
        );
        return $stmt->execute([':id' => $id]);
    }
}
