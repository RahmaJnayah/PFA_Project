<?php
/**
 * YOPY — Database Configuration
 * PDO singleton. Adjust credentials via environment variables or edit defaults below.
 */

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host   = getenv('DB_HOST')   ?: 'localhost';
            $port   = getenv('DB_PORT')   ?: '3306';
            $dbname = getenv('DB_NAME')   ?: 'yopy_db';
            $user   = getenv('DB_USER')   ?: 'root';
            $pass   = getenv('DB_PASS')   ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // In production, log the error — never expose it.
                error_log('Database connection failed: ' . $e->getMessage());
                die(json_encode(['error' => 'Database connection failed.']));
            }
        }

        return self::$instance;
    }
}
