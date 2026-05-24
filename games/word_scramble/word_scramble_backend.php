<?php
/**
 * games/word_scramble/word_scramble_backend.php
 * YOPY Word Scramble — Backend API
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['chosen_mode'] ?? '') !== 'child') {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Unauthorized','code'=>401]);
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
    default    => respond(404,'error','Unknown action'),
};

function handleStart(PDO $pdo): void {
    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $childId = (int)($_SESSION['child_id'] ?? 0);
    if ($childId === 0) { respond(403,'error','No child profile in session'); return; }
    $allowed    = ['easy','medium','hard'];
    $difficulty = in_array($input['difficulty'] ?? '', $allowed, true) ? $input['difficulty'] : 'easy';
    $gameId = GameService::getIdBySlug($pdo, 'word_scramble');
    if (!$gameId) { respond(500,'error','Game not found in catalogue'); return; }
    $sessionId = GameService::createSession($pdo, $childId, $gameId, $difficulty);
    if (!$sessionId) { respond(500,'error','Could not create game session'); return; }
    $_SESSION['current_game_session']    = $sessionId;
    $_SESSION['current_game_difficulty'] = $difficulty;
    $_SESSION['current_game_state']      = 'DÉMARRÉ';
    if (!isset($_SESSION['behavior'])) $_SESSION['behavior'] = [];
    $_SESSION['behavior']['word_scramble'] = [];
    respond(201,'success','Session created',['session_id'=>$sessionId]);
}

function handleScore(PDO $pdo): void {
    $input     = json_decode(file_get_contents('php://input'), true) ?? [];
    $childId   = (int)($_SESSION['child_id'] ?? 0);
    $sessionId = (int)($input['session_id'] ?? $_SESSION['current_game_session'] ?? 0);
    $points    = (int)($input['points'] ?? 0);
    $duration  = (int)($input['completion_time'] ?? 0);
    $state = $_SESSION['current_game_state'] ?? '';
    if (!in_array($state, ['EN_COURS','DÉMARRÉ'], true)) { respond(422,'error','Invalid state (BR-07)'); return; }
    // Max: ~20 words × 20pts = 400; cap 1000
    if ($points < 0 || $points > 1000) { respond(422,'error','Score out of range (BR-06)'); return; }
    if ($duration < 3) { respond(422,'error','Duration too short (BR-06)'); return; }
    $difficulty = $_SESSION['current_game_difficulty'] ?? 'easy';
    if (!GameService::sessionBelongsToChild($pdo, $sessionId, $childId)) { respond(403,'error','Ownership error (BR-02)'); return; }
    try {
        ScoreService::save($pdo, $sessionId, $points, $duration);
    } catch (PDOException $e) {
        respond($e->getCode() === '23000' ? 409 : 500, 'error', $e->getCode() === '23000' ? 'Duplicate (BR-05)' : 'DB error');
        return;
    }
    $_SESSION['current_game_state'] = 'RÉSULTAT_STOCKÉ';
    unset($_SESSION['behavior']['word_scramble']);
    respond(200,'success','Score saved',['session_id'=>$sessionId,'points'=>$points,'difficulty'=>$difficulty]);
}

function handleBehavior(): void {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['game_id'],$data['signals'])) { respond(422,'error','Invalid payload'); return; }
    $gid = preg_replace('/[^a-z0-9_]/','',strtolower($data['game_id']));
    if (!isset($_SESSION['behavior'])) $_SESSION['behavior'] = [];
    $_SESSION['behavior'][$gid] = array_merge($_SESSION['behavior'][$gid]??[],(array)$data['signals']);
    respond(200,'success','Signals stored',['stored'=>count((array)$data['signals'])]);
}

function respond(int $code, string $status, string $message, array $data=[]): void {
    http_response_code($code);
    echo json_encode(['status'=>$status,'message'=>$message,'data'=>$data,'code'=>$code]);
    exit;
}