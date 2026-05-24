<?php

namespace Models;

use mysqli;

class NewsSourceModel
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
     * Dashboard: age of news cache in hours
     */
    public static function getCacheAge(): int
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare("
            SELECT TIMESTAMPDIFF(HOUR, last_updated, NOW()) AS age_hours
            FROM news_cache 
            ORDER BY last_updated DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $row ? (int)$row['age_hours'] : 0;
    }
}