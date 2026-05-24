<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'parent') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../ai/analysis/BehaviorAnalyzer.php';

$pdo = Database::connect();
$userId = (int) $_SESSION['user_id'];

$parentStmt = $pdo->prepare('SELECT parent_id FROM parents WHERE user_id = :user_id');
$parentStmt->execute([':user_id' => $userId]);
$parentId = $parentStmt->fetchColumn();

if (!$parentId) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Parent profile not found']);
    exit;
}

function parseDate(string $value, DateTimeImmutable $fallback): DateTimeImmutable
{
    try {
        return new DateTimeImmutable($value);
    } catch (Exception $e) {
        return $fallback;
    }
}

$now = new DateTimeImmutable('now');
$defaultStart = $now->sub(new DateInterval('P14D'));
$startParam = trim((string) ($_GET['start'] ?? ''));
$endParam = trim((string) ($_GET['end'] ?? ''));

$startDate = $startParam !== '' ? parseDate($startParam, $defaultStart) : $defaultStart;
$endDate = $endParam !== '' ? parseDate($endParam, $now) : $now;
$endDate = $endDate->setTime(23, 59, 59);

$childStmt = $pdo->prepare(
    'SELECT child_id, nickname, age, emoji, theme, buddy
     FROM children
     WHERE parent_id = :parent_id
     ORDER BY created_at ASC'
);
$childStmt->execute([':parent_id' => $parentId]);
$children = $childStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($children)) {
    echo json_encode([
        'status' => 'success',
        'range' => [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
        ],
        'children' => [],
        'sessions' => [],
    ]);
    exit;
}

$childIds = array_map(static fn($c) => (int) $c['child_id'], $children);
$placeholders = implode(',', array_fill(0, count($childIds), '?'));

$sql =
    'SELECT b.session_id, b.child_id, b.game_id, b.start_time, b.end_time,
            b.signals, b.raw_signals, g.slug, g.name
     FROM game_behaviors b
     JOIN games g ON g.game_id = b.game_id
     WHERE b.child_id IN (' . $placeholders . ')
       AND b.start_time >= ?
       AND b.start_time <= ?
     ORDER BY b.start_time ASC';

$params = array_merge($childIds, [
    $startDate->format('Y-m-d H:i:s'),
    $endDate->format('Y-m-d H:i:s'),
]);

$sessionStmt = $pdo->prepare($sql);
$sessionStmt->execute($params);
$sessions = $sessionStmt->fetchAll(PDO::FETCH_ASSOC);

$childAgeMap = [];
$childPayload = [];
foreach ($children as $child) {
    $childId = (int) $child['child_id'];
    $childAgeMap[$childId] = $child['age'] !== null ? (int) $child['age'] : null;
    $childPayload[] = [
        'id' => $childId,
        'name' => $child['nickname'],
        'age' => $child['age'] !== null ? (int) $child['age'] : null,
        'emoji' => $child['emoji'] ?? '',
        'theme' => $child['theme'] ?? 'theme-rose',
        'buddy' => $child['buddy'] ?? null,
    ];
}

$analyzer = new BehaviorAnalyzer($pdo);

$sessionPayload = [];
foreach ($sessions as $session) {
    $childId = (int) $session['child_id'];
    $analysis = $analyzer->analyzeSessionRecord($session, $childAgeMap[$childId] ?? null);
    $sessionPayload[] = [
        'session_id' => (int) $session['session_id'],
        'child_id' => $childId,
        'game_slug' => $session['slug'] ?? null,
        'start_time' => $session['start_time'],
        'end_time' => $session['end_time'],
        'scores' => $analysis['scores'],
        'state' => $analysis['state'],
        'confidence' => $analysis['confidence'],
        'data_quality' => $analysis['data_quality'],
    ];
}

echo json_encode([
    'status' => 'success',
    'range' => [
        'start' => $startDate->format('Y-m-d'),
        'end' => $endDate->format('Y-m-d'),
    ],
    'children' => $childPayload,
    'sessions' => $sessionPayload,
]);
