<?php
/**
 * games/whack_a_mole/whack_a_mole_backend.php
 * YOPY Whack-a-Mole — Backend API
 * SRS: REQ-13,14,15,18 | BR-05,06,07,08,15,16
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

    $gameId = GameService::getIdBySlug($pdo, 'whack_a_mole');
    if (!$gameId) { respond(500,'error','Game not found in catalogue'); return; }

    $sessionId = GameService::createSession($pdo, $childId, $gameId, $difficulty);
    if (!$sessionId) { respond(500,'error','Could not create game session'); return; }

    $_SESSION['current_game_session']    = $sessionId;
    $_SESSION['current_game_difficulty'] = $difficulty;
    $_SESSION['current_game_state']      = 'DÉMARRÉ';
    if (!isset($_SESSION['behavior'])) $_SESSION['behavior'] = [];
    $_SESSION['behavior']['whack_a_mole'] = [];

    respond(201,'success','Session created',['session_id'=>$sessionId]);
}
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

    $stmt = $pdo->prepare('
        UPDATE game_behaviors
        SET points          = ?,
            completion_time = ?,
            end_time        = NOW()
        WHERE session_id = ?
    ');
    $stmt->execute([$points, $completionTime, $sessionId]);

    $_SESSION['current_game_state'] = 'RÉSULTAT_STOCKÉ';
    unset($_SESSION['behavior']['whack_a_mole']);

    respond(200, 'success', 'Score saved', [
        'session_id'      => $sessionId,
        'points'          => $points,
        'completion_time' => $completionTime
    ]);
}
function handleBehavior(): void {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['game_id'],$data['signals'])) { respond(422,'error','Invalid payload'); return; }
    $gid = preg_replace('/[^a-z0-9_]/','',strtolower($data['game_id']));
    if (!isset($_SESSION['behavior'])) $_SESSION['behavior'] = [];
    $_SESSION['behavior'][$gid] = array_merge($_SESSION['behavior'][$gid] ?? [], (array)$data['signals']);
    respond(200,'success','Signals stored',['stored'=>count((array)$data['signals'])]);
}

function respond(int $code, string $status, string $message, array $data=[]): void {
    http_response_code($code);
    echo json_encode(['status'=>$status,'message'=>$message,'data'=>$data,'code'=>$code]);
    exit;
}