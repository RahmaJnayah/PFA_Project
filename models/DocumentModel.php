<?php

namespace Models;

use mysqli;

class DocumentModel
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
     * Dashboard stat: total IR (search) queries in last 30 days
     */
    public static function countIRQueriesLast30Days(): int
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM search_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_row()[0] ?? 0;
        $stmt->close();
        $conn->close();
        return (int)$count;
    }

    /**
     * Used later in Documents admin page
     */
    public static function getAllDocuments(): array
    {
        $conn = self::getDbConnection();
        $result = $conn->query("SELECT * FROM documents ORDER BY created_at DESC");
        $docs = [];
        while ($row = $result->fetch_assoc()) {
            $docs[] = $row;
        }
        $conn->close();
        return $docs;
    }
}