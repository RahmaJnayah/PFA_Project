<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApiTestClient.php';
require_once __DIR__ . '/../../config/database.php';

final class ApiDataIntegrationTest extends TestCase
{
    private static PDO $pdo;
    private static ApiTestClient $client;
    private static array $cleanup = [
        'users' => [],
        'parents' => [],
        'children' => [],
        'sessions' => [],
    ];

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connect();
        self::$client = new ApiTestClient(dirname(__DIR__, 2));
    }

    public static function tearDownAfterClass(): void
    {
        $pdo = self::$pdo;

        if (self::$cleanup['sessions']) {
            $placeholders = implode(',', array_fill(0, count(self::$cleanup['sessions']), '?'));
            $pdo->prepare('DELETE FROM game_events WHERE session_id IN (' . $placeholders . ')')
                ->execute(self::$cleanup['sessions']);
            $pdo->prepare('DELETE FROM game_behaviors WHERE session_id IN (' . $placeholders . ')')
                ->execute(self::$cleanup['sessions']);
        }

        if (self::$cleanup['children']) {
            $placeholders = implode(',', array_fill(0, count(self::$cleanup['children']), '?'));
            $pdo->prepare('DELETE FROM child_behavior_analysis WHERE child_id IN (' . $placeholders . ')')
                ->execute(self::$cleanup['children']);
            $pdo->prepare('DELETE FROM children WHERE child_id IN (' . $placeholders . ')')
                ->execute(self::$cleanup['children']);
        }

        if (self::$cleanup['parents']) {
            $placeholders = implode(',', array_fill(0, count(self::$cleanup['parents']), '?'));
            $pdo->prepare('DELETE FROM parents WHERE parent_id IN (' . $placeholders . ')')
                ->execute(self::$cleanup['parents']);
        }

        if (self::$cleanup['users']) {
            $placeholders = implode(',', array_fill(0, count(self::$cleanup['users']), '?'));
            $pdo->prepare('DELETE FROM users WHERE id IN (' . $placeholders . ')')
                ->execute(self::$cleanup['users']);
        }
    }

    public function testUnauthorizedAccess(): void
    {
        $response = self::$client->request([
            'file' => 'parent_profile_api.php',
            'method' => 'GET',
            'session' => [],
        ]);

        $this->assertSame(401, $response['code']);
        $this->assertSame('error', $response['json']['status'] ?? null);
    }

    public function testParentProfileAndChildCrud(): void
    {
        $fixture = $this->createParentFixture();

        $profile = self::$client->request([
            'file' => 'parent_profile_api.php',
            'method' => 'GET',
            'session' => [
                'user_id' => $fixture['user_id'],
                'role' => 'parent',
            ],
        ]);

        $this->assertSame(200, $profile['code']);
        $this->assertSame('success', $profile['json']['status'] ?? null);
        $this->assertSame(0, $profile['json']['child_count'] ?? null);

        $create = self::$client->request([
            'file' => 'parent_children_api.php',
            'method' => 'POST',
            'post' => [
                'action' => 'create',
                'nickname' => 'Test Child',
                'age' => 8,
                'emoji' => 'fox',
                'theme' => 'theme-rose',
                'buddy' => 'joy',
            ],
            'session' => [
                'user_id' => $fixture['user_id'],
                'role' => 'parent',
            ],
        ]);

        $this->assertSame(200, $create['code']);
        $childId = (int) ($create['json']['child_id'] ?? 0);
        $this->assertGreaterThan(0, $childId);
        self::$cleanup['children'][] = $childId;

        $list = self::$client->request([
            'file' => 'parent_children_api.php',
            'method' => 'GET',
            'get' => ['action' => 'list'],
            'session' => [
                'user_id' => $fixture['user_id'],
                'role' => 'parent',
            ],
        ]);

        $this->assertSame(200, $list['code']);
        $children = $list['json']['children'] ?? [];
        $this->assertNotEmpty($children);

        $update = self::$client->request([
            'file' => 'parent_children_api.php',
            'method' => 'POST',
            'post' => [
                'action' => 'update',
                'child_id' => $childId,
                'nickname' => 'Updated Child',
            ],
            'session' => [
                'user_id' => $fixture['user_id'],
                'role' => 'parent',
            ],
        ]);

        $this->assertSame(200, $update['code']);
        $this->assertSame('success', $update['json']['status'] ?? null);

        $delete = self::$client->request([
            'file' => 'parent_children_api.php',
            'method' => 'POST',
            'post' => [
                'action' => 'delete',
                'child_id' => $childId,
            ],
            'session' => [
                'user_id' => $fixture['user_id'],
                'role' => 'parent',
            ],
        ]);

        $this->assertSame(200, $delete['code']);
        $this->assertSame('success', $delete['json']['status'] ?? null);
    }

    public function testBehaviorIngestionAnalysisAndReports(): void
    {
        $fixture = $this->createParentFixture();
        $childId = $this->createChild($fixture['parent_id'], 'Report Child', 7);

        $gameId = $this->getGameId('memory_game');
        $this->assertGreaterThan(0, $gameId);

        $sessionOne = $this->createSession($childId, $gameId, '-2 days');
        $sessionTwo = $this->createSession($childId, $gameId, '-1 day');

        $this->sendBehaviorPayload($fixture['user_id'], $childId, $sessionOne);
        $this->sendBehaviorPayload($fixture['user_id'], $childId, $sessionTwo);

        $analysis = self::$client->request([
            'file' => 'parent_analysis_api.php',
            'method' => 'GET',
            'get' => [
                'start' => date('Y-m-d', strtotime('-3 days')),
                'end' => date('Y-m-d'),
            ],
            'session' => [
                'user_id' => $fixture['user_id'],
                'role' => 'parent',
            ],
        ]);

        $this->assertSame(200, $analysis['code']);
        $this->assertSame('success', $analysis['json']['status'] ?? null);
        $this->assertCount(1, $analysis['json']['children'] ?? []);
        $this->assertCount(2, $analysis['json']['sessions'] ?? []);

        $reports = self::$client->request([
            'file' => 'parent_reports_api.php',
            'method' => 'GET',
            'get' => [
                'start' => date('Y-m-d', strtotime('-3 days')),
                'end' => date('Y-m-d'),
            ],
            'session' => [
                'user_id' => $fixture['user_id'],
                'role' => 'parent',
            ],
        ]);

        $this->assertSame(200, $reports['code']);
        $this->assertSame('success', $reports['json']['status'] ?? null);
        $this->assertGreaterThanOrEqual(1, $reports['json']['summary']['report_count'] ?? 0);
    }

    private function createParentFixture(): array
    {
        $email = 'parent_' . uniqid('', true) . '@example.test';
        $username = 'parent_' . uniqid();
        $hash = password_hash('secret', PASSWORD_BCRYPT);

        $stmt = self::$pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role, plan, status)
             VALUES (:username, :email, :hash, :role, :plan, :status)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':hash' => $hash,
            ':role' => 'parent',
            ':plan' => 'free',
            ':status' => 'active',
        ]);

        $userId = (int) self::$pdo->lastInsertId();
        self::$cleanup['users'][] = $userId;

        $parentStmt = self::$pdo->prepare('INSERT INTO parents (user_id) VALUES (:user_id)');
        $parentStmt->execute([':user_id' => $userId]);

        $parentId = (int) self::$pdo->lastInsertId();
        self::$cleanup['parents'][] = $parentId;

        return [
            'user_id' => $userId,
            'parent_id' => $parentId,
        ];
    }

    private function createChild(int $parentId, string $nickname, int $age): int
    {
        $stmt = self::$pdo->prepare(
            'INSERT INTO children (parent_id, nickname, age, emoji, theme, buddy, avatar)
             VALUES (:parent_id, :nickname, :age, :emoji, :theme, :buddy, :avatar)'
        );
        $stmt->execute([
            ':parent_id' => $parentId,
            ':nickname' => $nickname,
            ':age' => $age,
            ':emoji' => 'fox',
            ':theme' => 'theme-rose',
            ':buddy' => 'joy',
            ':avatar' => 'fox',
        ]);

        $childId = (int) self::$pdo->lastInsertId();
        self::$cleanup['children'][] = $childId;

        return $childId;
    }

    private function getGameId(string $slug): int
    {
        $stmt = self::$pdo->prepare('SELECT game_id FROM games WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $gameId = (int) ($stmt->fetchColumn() ?: 0);

        if ($gameId > 0) {
            return $gameId;
        }

        $fallback = self::$pdo->query('SELECT game_id FROM games ORDER BY game_id ASC LIMIT 1');
        return (int) ($fallback ? $fallback->fetchColumn() : 0);
    }

    private function createSession(int $childId, int $gameId, string $offset): int
    {
        $start = (new DateTimeImmutable('now'))->modify($offset);
        $end = $start->modify('+10 minutes');

        $stmt = self::$pdo->prepare(
            'INSERT INTO game_behaviors
                (child_id, game_id, start_time, end_time, difficulty, points, completion_time, signals, raw_signals)
             VALUES
                (:child_id, :game_id, :start_time, :end_time, :difficulty, :points, :completion_time, :signals, :raw_signals)'
        );
        $stmt->execute([
            ':child_id' => $childId,
            ':game_id' => $gameId,
            ':start_time' => $start->format('Y-m-d H:i:s'),
            ':end_time' => $end->format('Y-m-d H:i:s'),
            ':difficulty' => 'easy',
            ':points' => 0,
            ':completion_time' => 0,
            ':signals' => json_encode([]),
            ':raw_signals' => json_encode([]),
        ]);

        $sessionId = (int) self::$pdo->lastInsertId();
        self::$cleanup['sessions'][] = $sessionId;

        return $sessionId;
    }

    private function sendBehaviorPayload(int $userId, int $childId, int $sessionId): void
    {
        $payload = [
            'session_id' => $sessionId,
            'game_id' => 'memory_game',
            'signals' => [
                'success' => 1,
                'error' => 1,
            ],
            'events' => [
                [
                    'signal' => 'success',
                    'value' => 1,
                    'ts' => (int) (microtime(true) * 1000),
                ],
                [
                    'signal' => 'error',
                    'value' => 1,
                    'ts' => (int) (microtime(true) * 1000) + 50,
                ],
            ],
        ];

        $response = self::$client->request([
            'file' => 'behavior_api.php',
            'method' => 'POST',
            'json' => $payload,
            'session' => [
                'user_id' => $userId,
                'chosen_mode' => 'child',
                'child_id' => $childId,
                'current_game_session' => $sessionId,
            ],
        ]);

        $this->assertSame(200, $response['code']);
        $this->assertSame('success', $response['json']['status'] ?? null);
    }
}
