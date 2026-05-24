<?php
// games/tower_blocks/tower_blocks_backend.php
// Handles: session/start | session/score
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../services/GameService.php';
require_once '../../services/ScoreService.php';

$action = $_GET['action'] ?? '';
$pdo    = Database::connect();

match($action) {
    'start' => handleStart($pdo),
    'score' => handleScore($pdo),
    default => http_response_code(404)
};

// ── Helper ─────────────────────────────────────────────────────────────────
// FIX 4: respond() was called by handleScore() but never defined.
function respond(int $httpCode, string $status, string $message, array $data = []): void
{
    http_response_code($httpCode);
    echo json_encode([
        'status'  => $status,
        'message' => $message,
        'data'    => $data,
    ]);
}

// ── START : creates a game_session in the DB ───────────────────────────────
function handleStart(PDO $pdo): void
{
    $childId = (int) ($_SESSION['child_id'] ?? 0);

    // FIX 1: '{game_id}' was a literal placeholder — replaced with the real slug.
    $gameId = GameService::getIdBySlug($pdo, 'tower_blocks');

    // FIX 2: Frontend sends JSON, not a form post, so $_POST is always empty.
    //        Read difficulty from the decoded JSON body instead.
    $input      = json_decode(file_get_contents('php://input'), true) ?? [];
    $difficulty = $input['difficulty'] ?? 'easy';

    $sessionId = GameService::createSession($pdo, $childId, $gameId, $difficulty);
    $_SESSION['current_game_session'] = $sessionId;

    // FIX 3: JS reads data.data.session_id — the old response had no 'data' wrapper,
    //        so serverSessionId stayed null and every score submission failed.
    respond(200, 'success', 'Session created', ['session_id' => $sessionId]);
}

// ── SCORE : validates and persists the score ───────────────────────────────
function handleScore(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sessionId      = (int) ($input['session_id'] ?? $_SESSION['current_game_session'] ?? 0);
    $points         = (int) ($input['points']          ?? 0);
    $completionTime = (int) ($input['completion_time'] ?? 0);

    if ($sessionId === 0) {
        respond(422, 'error', 'Missing session_id');
        return;
    }

    $stmt = $pdo->prepare('
        UPDATE game_behaviors
        SET points          = ?,
            completion_time = ?,
            end_time        = NOW()
        WHERE session_id = ?
    ');
    $stmt->execute([$points, $completionTime, $sessionId]);

    $_SESSION['current_game_state'] = 'RÉSULTAT_STOCKÉ';
    unset($_SESSION['behavior']['tower_blocks']);

    respond(200, 'success', 'Score saved', [
        'session_id'      => $sessionId,
        'points'          => $points,
        'completion_time' => $completionTime,
    ]);
}