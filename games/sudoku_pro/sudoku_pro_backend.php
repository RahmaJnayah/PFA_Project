<?php
/**
 * games/sudoku/sudoku_backend.php
 * YOPY Sudoku — Backend API
 *
 * ?action=start    — creates a game_session row, returns session_id
 * ?action=score    — validates & persists score (BR-05, BR-06, BR-08)
 * ?action=behavior — merges JS behavior signals into $_SESSION (REQ-01, BR-15)
 *
 * SRS: REQ-13, REQ-14, REQ-15, REQ-18
 * BR:  BR-05, BR-06, BR-07, BR-08, BR-15, BR-16
 */

session_start();
header('Content-Type: application/json');

// ── Auth guard ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || ($_SESSION['chosen_mode'] ?? '') !== 'child') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized', 'code' => 401]);
    exit;
}

require_once '../../config/database.php';
require_once '../../services/GameService.php';
require_once '../../services/ScoreService.php';

$action = $_GET['action'] ?? '';
$pdo    = Database::connect();

match ($action) {
    'start'    => handleStart($pdo),
    'score'    => handleScore($pdo),
    'behavior' => handleBehavior(),
    default    => respond(404, 'error', 'Unknown action'),
};

// ────────────────────────────────────────────────────────────────────────────
// ACTION: start — State: INITIALISÉ → DÉMARRÉ
// ────────────────────────────────────────────────────────────────────────────
function handleStart(PDO $pdo): void
{
    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $childId = (int) ($_SESSION['child_id'] ?? 0);

    if ($childId === 0) {
        respond(403, 'error', 'No child profile in session');
        return;
    }

    $allowed    = ['easy', 'medium', 'hard'];
    $difficulty = in_array($input['difficulty'] ?? '', $allowed, true)
        ? $input['difficulty'] : 'easy';

   $gameId = GameService::getIdBySlug($pdo, 'sudoku_pro');
    if (!$gameId) {
        respond(500, 'error', 'Game not found in catalogue');
        return;
    }

    $sessionId = GameService::createSession($pdo, $childId, $gameId, $difficulty);
    if (!$sessionId) {
        respond(500, 'error', 'Could not create game session');
        return;
    }

    $_SESSION['current_game_session']    = $sessionId;
    $_SESSION['current_game_difficulty'] = $difficulty; // BR-08: immutable
    $_SESSION['current_game_state']      = 'DÉMARRÉ';

    if (!isset($_SESSION['behavior'])) $_SESSION['behavior'] = [];
    $_SESSION['behavior']['sudoku'] = [];

    respond(201, 'success', 'Session created', ['session_id' => $sessionId]);
}

// ────────────────────────────────────────────────────────────────────────────
// ACTION: score
// ────────────────────────────────────────────────────────────────────────────
// ────────────────────────────────────────────────────────────────────────────
// ACTION: score  ←←← SIMPLIFIED (exactly like memory_game + simon_says)
// ────────────────────────────────────────────────────────────────────────────
function handleScore(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sessionId      = (int) ($input['session_id'] ?? $_SESSION['current_game_session'] ?? 0);
    $points         = (int) ($input['points'] ?? 0);
    $completionTime = (int) ($input['completion_time'] ?? 0);

    if ($sessionId === 0) {
        respond(422, 'error', 'Missing session_id');
        return;
    }

    // ←←← THIS IS THE ONLY UPDATE WE NEED
    $stmt = $pdo->prepare('
        UPDATE game_behaviors
        SET points          = ?,
            completion_time = ?,
            end_time        = NOW()
        WHERE session_id = ?
    ');
    $stmt->execute([$points, $completionTime, $sessionId]);

    $_SESSION['current_game_state'] = 'RÉSULTAT_STOCKÉ';

    // Clear behavior data
    unset($_SESSION['behavior']['sudoku']);

    respond(200, 'success', 'Score saved', [
        'session_id'      => $sessionId,
        'points'          => $points,
        'completion_time' => $completionTime
    ]);
}

// ────────────────────────────────────────────────────────────────────────────
// ACTION: behavior — REQ-01, BR-15: signals NEVER written to database
// ────────────────────────────────────────────────────────────────────────────
function handleBehavior(): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['game_id'], $data['signals'])) {
        respond(422, 'error', 'Invalid payload');
        return;
    }

    $gid = preg_replace('/[^a-z0-9_]/', '', strtolower($data['game_id']));

    if (!isset($_SESSION['behavior'])) $_SESSION['behavior'] = [];

    $_SESSION['behavior'][$gid] = array_merge(
        $_SESSION['behavior'][$gid] ?? [],
        (array) $data['signals']
    );

    respond(200, 'success', 'Signals stored', ['stored' => count((array) $data['signals'])]);
}

// ── Response helper ──────────────────────────────────────────────────────────
function respond(int $code, string $status, string $message, array $data = []): void
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data, 'code' => $code]);
    exit;
}