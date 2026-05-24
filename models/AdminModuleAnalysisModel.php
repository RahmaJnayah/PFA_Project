<?php

namespace Models;

use PDO;

class AdminModuleAnalysisModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = AdminModuleDatabase::getInstance();
    }

    public function findRecent(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.analysis_id, a.child_id, c.nickname AS child_name,
                    a.period_start, a.period_end, a.session_count,
                    a.dominant_state, a.confidence, a.created_at
             FROM child_behavior_analysis a
             JOIN children c ON c.child_id = a.child_id
             ORDER BY a.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findChronological(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.analysis_id, a.child_id, c.nickname AS child_name,
                    a.period_start, a.period_end, a.session_count,
                    a.dominant_state, a.confidence, a.created_at
             FROM child_behavior_analysis a
             JOIN children c ON c.child_id = a.child_id
             ORDER BY a.created_at ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
