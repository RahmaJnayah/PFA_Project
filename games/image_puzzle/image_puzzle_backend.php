<?php
/**
 * games/image_puzzle/image_puzzle_backend.php
 * YOPY Image Puzzle — Backend API
 * SRS: REQ-13, REQ-14, REQ-17, REQ-18 | BR-05, BR-06, BR-07, BR-08, BR-15, BR-16
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
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

// ── START ─────────────────────────────────────────────────────────────────────
function handleStart(PDO $pdo): void
{
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $childId  = (int) ($_SESSION['child_id'] ?? 0);

    $allowed    = ['easy', 'medium', 'hard'];
    $difficulty = in_array($input['difficulty'] ?? '', $allowed, true)
        ? $input['difficulty'] : 'easy';

    $gameId = GameService::getIdBySlug($pdo, 'image_puzzle');
    if (!$gameId) { respond(500, 'error', 'Game not found in catalogue'); return; }

    $sessionId = GameService::createSession($pdo, $childId, $gameId, $difficulty);
    if (!$sessionId) { respond(500, 'error', 'Could not create session'); return; }

    $_SESSION['current_game_session']    = $sessionId;
    $_SESSION['current_game_difficulty'] = $difficulty; // BR-08: locked
    $_SESSION['current_game_state']      = 'DÉMARRÉ';
    if (!isset($_SESSION['behavior'])) $_SESSION['behavior'] = [];
    $_SESSION['behavior']['image_puzzle'] = [];

    respond(201, 'success', 'Session created', ['session_id' => $sessionId]);
}

// ── SCORE ─────────────────────────────────────────────────────────────────────
function handleScore(PDO $pdo): void
{
    $input     = json_decode(file_get_contents('php://input'), true) ?? [];
    $childId   = (int) ($_SESSION['child_id'] ?? 0);
    $sessionId = (int) ($input['session_id'] ?? $_SESSION['current_game_session'] ?? 0);
    $points    = (int) ($input['points']          ?? 0);
    $duration  = (int) ($input['completion_time'] ?? 0);

    // BR-07: state must have progressed past INIT
    $state = $_SESSION['current_game_state'] ?? '';
    if (!in_array($state, ['EN_COURS', 'DÉMARRÉ'], true)) {
        respond(422, 'error', 'Invalid game state (BR-07)'); return;
    }

    // BR-06: score range [100–1000], time ≥ 3s
    if ($points < 100 || $points > 1000) {
        respond(422, 'error', 'Score out of range [100-1000] (BR-06)'); return;
    }
    if ($duration < 3) {
        respond(422, 'error', 'Completion time too short (BR-06)'); return;
    }

    // BR-02: ownership
    if ($childId > 0 && !GameService::sessionBelongsToChild($pdo, $sessionId, $childId)) {
        respond(403, 'error', 'Session does not belong to this child (BR-02)'); return;
    }

    // BR-05: unique per session
    try {
        ScoreService::save($pdo, $sessionId, $points, $duration);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            respond(409, 'error', 'Score already submitted (BR-05)'); return;
        }
        respond(500, 'error', 'Database error'); return;
    } catch (InvalidArgumentException $e) {
        respond(422, 'error', $e->getMessage()); return;
    }

    $_SESSION['current_game_state'] = 'RÉSULTAT_STOCKÉ';

    // BR-16: AI hook stub (Phase 2)
    // BehaviorAnalyzer::analyze($_SESSION['behavior']['image_puzzle'] ?? []);

    // BR-15: clear raw signals
    unset($_SESSION['behavior']['image_puzzle']);

    respond(200, 'success', 'Score saved', ['session_id' => $sessionId, 'points' => $points]);
}

// ── BEHAVIOR ──────────────────────────────────────────────────────────────────
function handleBehavior(): void
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['game_id'], $data['signals'])) {
        respond(422, 'error', 'Invalid payload'); return;
    }
    $gid = preg_replace('/[^a-z0-9_]/', '', strtolower($data['game_id']));
    if (!isset($_SESSION['behavior'])) $_SESSION['behavior'] = [];
    $_SESSION['behavior'][$gid] = array_merge(
        $_SESSION['behavior'][$gid] ?? [],
        (array) $data['signals']
    );
    respond(200, 'success', 'Signals stored', ['stored' => count((array) $data['signals'])]);
}

// ── Helper ────────────────────────────────────────────────────────────────────
function respond(int $code, string $status, string $message, array $data = []): void
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data, 'code' => $code]);
    exit;
}