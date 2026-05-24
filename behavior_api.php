<?php
/**
 * /api/behavior_api.php
 * Version unifiée – utilise game_behaviors comme table unique
 */

declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

// Auth guard
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['chosen_mode'] ?? '') !== 'child' ||
    empty($_SESSION['child_id'])
) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized', 'code' => 401]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON body', 'code' => 422]);
    exit;
}

$childId   = (int) $_SESSION['child_id'];
$sessionId = (int) ($_SESSION['current_game_session'] ?? $input['session_id'] ?? 0);
if ($sessionId === 0) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No active game session', 'code' => 403]);
    exit;
}

$pdo = Database::connect();

// Vérifier que la session existe et appartient bien à l'enfant
$stmt = $pdo->prepare('SELECT session_id FROM game_behaviors WHERE session_id = ? AND child_id = ?');
$stmt->execute([$sessionId, $childId]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Session does not belong to current child', 'code' => 403]);
    exit;
}

const VALID_SIGNALS = [
    'reaction', 'error', 'success', 'abandon',
    'pace_change', 'retry', 'hint', 'timeout'
];

$aggregated = is_array($input['signals'] ?? null) ? $input['signals'] : [];
$events     = is_array($input['events'] ?? null) ? $input['events'] : [];

// UPSERT dans game_behaviors (mise à jour des signaux agrégés et bruts)
try {
    $stmt = $pdo->prepare('
        INSERT INTO game_behaviors (session_id, child_id, game_id, start_time, signals, raw_signals)
        VALUES (:sid, :cid, :gid, NOW(), :sig, :raw)
        ON DUPLICATE KEY UPDATE
            signals     = JSON_MERGE_PATCH(signals,     VALUES(signals)),
            raw_signals = JSON_MERGE_PATCH(raw_signals, VALUES(raw_signals))
    ');
    // Récupération du game_id à partir de la session (déjà stocké)
    $stmtGame = $pdo->prepare('SELECT game_id FROM game_behaviors WHERE session_id = ?');
    $stmtGame->execute([$sessionId]);
    $gameId = $stmtGame->fetchColumn();
    if (!$gameId) {
        throw new Exception('Game ID not found for session');
    }
    $stmt->execute([
        ':sid' => $sessionId,
        ':cid' => $childId,
        ':gid' => $gameId,
        ':sig' => json_encode($aggregated),
        ':raw' => json_encode($events)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to upsert behavior: ' . $e->getMessage(), 'code' => 500]);
    exit;
}

// Insertion des événements individuels dans game_events (inchangé)
$rows    = [];
$params  = [];
$saved   = 0;
$skipped = 0;

foreach ($events as $i => $ev) {
    $signal = trim((string) ($ev['signal'] ?? ''));
    if (!in_array($signal, VALID_SIGNALS, true)) {
        $skipped++;
        continue;
    }
    $rows[]              = "(:sid{$i}, :sig{$i}, :val{$i}, :ts{$i})";
    $params[":sid{$i}"]  = $sessionId;
    $params[":sig{$i}"]  = $signal;
    $params[":val{$i}"]  = isset($ev['value']) ? (float) $ev['value'] : null;
    $params[":ts{$i}"]   = isset($ev['ts'])    ? (int)   $ev['ts']    : (int) (microtime(true) * 1000);
    $saved++;
}

if (!empty($rows)) {
    try {
        $sql = 'INSERT INTO game_events (session_id, `signal`, value, ts) VALUES '
             . implode(', ', $rows);
        $pdo->prepare($sql)->execute($params);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to insert events: ' . $e->getMessage(), 'code' => 500]);
        exit;
    }
}

// Nettoyage session (optionnel)
$gameIdSlug = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($input['game_id'] ?? '')));
if ($gameIdSlug && isset($_SESSION['behavior'][$gameIdSlug])) {
    unset($_SESSION['behavior'][$gameIdSlug]);
}

http_response_code(200);
echo json_encode([
    'status'         => 'success',
    'code'           => 200,
    'session_id'     => $sessionId,
    'events_saved'   => $saved,
    'events_skipped' => $skipped,
]);