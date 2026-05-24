<?php
/**
 * config/database.php
 * Your existing config array + a Database class wrapper so all game
 * backends can call  Database::connect()  without any changes.
 *
 * SRS: NF-S4 (PDO prepared statements), ARCH-05 (Singleton)
 */


$dbConfig = [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => getenv('DB_DATABASE') ?: 'yopy_platform',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
];

// ── PDO singleton wrapper (used by game backends) ─────────────────────────
if (!class_exists('Database', false)) {
    class Database
    {
        private static ?PDO $instance = null;

        private static function getConfig(): array
        {
            return [
                'host'     => getenv('DB_HOST') ?: '127.0.0.1',
                'username' => getenv('DB_USERNAME') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: '',
                'database' => getenv('DB_DATABASE') ?: 'yopy_platform',
                'port'     => (int) (getenv('DB_PORT') ?: 3306),
            ];
        }

        public static function connect(): PDO
        {
            if (self::$instance === null) {
                $cfg = self::getConfig();
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $cfg['host'],
                    $cfg['port'],
                    $cfg['database']
                );

                self::$instance = new PDO(
                    $dsn,
                    $cfg['username'],
                    $cfg['password'],
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            }
            return self::$instance;
        }

        private function __clone() {}
        public function __wakeup() { throw new \Exception('Cannot unserialize singleton'); }
    }
}

// Allows UserModel to do: $config = include 'database.php';
return $dbConfig;