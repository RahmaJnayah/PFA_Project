<?php

namespace Models;

use mysqli;

class PasswordResetModel
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

    public static function create($email, $selector, $tokenHash, $expiresAt)
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare(
            'INSERT INTO password_resets (email, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $email, $selector, $tokenHash, $expiresAt);
        $success = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $success;
    }

    public static function findValidByEmailAndSelector($email, $selector)
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare(
            'SELECT * FROM password_resets WHERE email = ? AND selector = ? AND expires_at > NOW() LIMIT 1'
        );
        $stmt->bind_param('ss', $email, $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $reset;
    }

    public static function deleteByEmail($email)
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
        $stmt->bind_param('s', $email);
        $success = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $success;
    }

    public static function deleteById($resetId)
    {
        $conn = self::getDbConnection();
        $stmt = $conn->prepare('DELETE FROM password_resets WHERE reset_id = ?');
        $stmt->bind_param('i', $resetId);
        $success = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $success;
    }
}

?>