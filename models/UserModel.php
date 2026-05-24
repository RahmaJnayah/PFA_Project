<?php

namespace Models;

use mysqli;

class UserModel
{
    private static function getDbConnection()
    {
        $config = include __DIR__ . '/../config/database.php';
        $conn = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port']
        );

        if ($conn->connect_error) {
            throw new \RuntimeException('Database connection failed.');
        }

        if (!$conn->set_charset('utf8mb4')) {
            $conn->close();
            throw new \RuntimeException('Database charset initialization failed.');
        }

        return $conn;
    }

    public static function findByEmail($email)
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $user;
    }

    public static function findByUsername($username)
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $user;
    }

 
   public static function create($data)
{
    $conn = self::getDbConnection();
    $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $data['username'], $data['email'], $data['password_hash'], $data['role']);
    $success = $stmt->execute();
    $newId = $success ? $conn->insert_id : false;
    $stmt->close();
    $conn->close();
    return $newId; // returns the inserted user ID or false
}

    public static function updatePasswordByEmail($email, $passwordHash)
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        $stmt->bind_param('ss', $passwordHash, $email);
        $success = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $success;
    }
        /**
     * Count all users (for dashboard)
     */
    public static function countAll(): int
    {
        $conn = self::getDbConnection();
        $result = $conn->query("SELECT COUNT(*) FROM users");
        $count = $result->fetch_row()[0] ?? 0;
        $conn->close();
        return (int)$count;
    }

    /**
     * Count active users (for dashboard)
     */
    public static function countActive(): int
    {
        $conn = self::getDbConnection();
        $result = $conn->query("SELECT COUNT(*) FROM users WHERE active = 1");
        $count = $result->fetch_row()[0] ?? 0;
        $conn->close();
        return (int)$count;
    }

    /**
     * Used in users list + recent admin logs
     */
    public static function getAllWithFilter(string $filter = ''): array
    {
        $conn = self::getDbConnection();
        $sql = "SELECT id, username, email, role, active, created_at FROM users WHERE 1=1";

        if ($filter !== '') {
            $sql .= " AND (email LIKE ? OR username LIKE ?)";
            $stmt = $conn->prepare($sql);
            $like = "%$filter%";
            $stmt->bind_param('ss', $like, $like);
        } else {
            $stmt = $conn->prepare($sql);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
        $conn->close();
        return $users;
    }

    /**
     * REQ-38 : Recent admin logs
     */
    public static function getRecentAdminLogs(int $limit = 10): array
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare("
            SELECT al.*, u.username AS admin_name 
            FROM admin_logs al
            JOIN users u ON al.admin_id = u.id
            ORDER BY al.created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        $stmt->close();
        $conn->close();
        return $logs;
    }
}

?>