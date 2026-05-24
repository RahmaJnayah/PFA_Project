<?php

namespace Models;

use PDO;
use PDOException;

class AdminModuleDatabase
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = include __DIR__ . '/../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log('Admin DB connection failed: ' . $e->getMessage());
                throw new \RuntimeException('Database connection unavailable.');
            }
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
