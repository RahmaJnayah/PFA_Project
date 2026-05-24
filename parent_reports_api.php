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
$defaultStart = $now->sub(new DateInterval('P30D'));
$startParam = trim((string) ($_GET['start'] ?? ''));
$endParam = trim((string) ($_GET['end'] ?? ''));

$startDate = $startParam !== '' ? parseDate($startParam, $defaultStart) : $defaultStart;
$endDate = $endParam !== '' ? parseDate($endParam, $now) : $now;
$endDate = $endDate->setTime(23, 59, 59);

$childStmt = $pdo->prepare('SELECT child_id FROM children WHERE parent_id = :parent_id');
$childStmt->execute([':parent_id' => $parentId]);
$childIds = array_map('intval', $childStmt->fetchAll(PDO::FETCH_COLUMN));

if ($childIds) {
    $analyzer = new BehaviorAnalyzer($pdo);
    foreach ($childIds as $childId) {
        $analysis = $analyzer->analyzeChildPeriod($childId, $startDate, $endDate);
        if ($analysis !== null) {
            $analyzer->storeAnalysis($analysis);
        }
    }
}

$sql =
    'SELECT a.analysis_id, a.child_id, a.period_start, a.period_end, a.session_count,
            a.focus_points, a.joy_points, a.frustration_points, a.boredom_points,
            a.dominant_state, a.confidence, a.created_at,
            c.nickname
     FROM child_behavior_analysis a
     JOIN children c ON c.child_id = a.child_id
     WHERE c.parent_id = :parent_id
       AND a.period_end >= :start
       AND a.period_end <= :end
     ORDER BY a.period_end DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':parent_id' => $parentId,
    ':start' => $startDate->format('Y-m-d H:i:s'),
    ':end' => $endDate->format('Y-m-d H:i:s'),
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reports = [];
$balances = [];
$joyScores = [];
$stressScores = [];
$sessionTotal = 0;

foreach ($rows as $row) {
    $focus = (float) $row['focus_points'];
    $joy = (float) $row['joy_points'];
    $frustration = (float) $row['frustration_points'];
    $boredom = (float) $row['boredom_points'];
    $total = $focus + $joy + $frustration + $boredom;

    $balance = $total > 0 ? (($focus + $joy) / $total) * 100 : 0;
    $joyPct = $total > 0 ? ($joy / $total) * 100 : 0;
    $stressPct = $total > 0 ? ($frustration / $total) * 100 : 0;

    $balances[] = $balance;
    $joyScores[] = $joyPct;
    $stressScores[] = $stressPct;
    $sessionTotal += (int) $row['session_count'];

    $reports[] = [
        'id' => (int) $row['analysis_id'],
        'child_id' => (int) $row['child_id'],
        'child_name' => $row['nickname'],
        'period_start' => $row['period_start'],
        'period_end' => $row['period_end'],
        'session_count' => (int) $row['session_count'],
        'balance' => round($balance, 1),
        'joy' => round($joyPct, 1),
        'stress' => round($stressPct, 1),
        'dominant_state' => $row['dominant_state'],
        'confidence' => (float) $row['confidence'],
    ];
}

function avg(array $values): float
{
    if (!$values) {
        return 0.0;
    }
    return array_sum($values) / count($values);
}

$summary = [
    'range' => [
        'start' => $startDate->format('Y-m-d'),
        'end' => $endDate->format('Y-m-d'),
    ],
    'balance' => round(avg($balances), 1),
    'joy' => round(avg($joyScores), 1),
    'stress' => round(avg($stressScores), 1),
    'sessions' => $sessionTotal,
    'report_count' => count($reports),
];

echo json_encode([
    'status' => 'success',
    'summary' => $summary,
    'reports' => $reports,
]);
