<?php

namespace Models;

use mysqli;

class GameModel
{
    private static function getDbConnection()
    {
        $config = include __DIR__ . '/../config/database.php';
        return new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port']
        );
    }

    /**
     * Dashboard stat: total game sessions in last 30 days
     */
    public static function countSessionsLast30Days(): int
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM game_sessions 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_row()[0] ?? 0;
        $stmt->close();
        $conn->close();
        return (int)$count;
    }
}