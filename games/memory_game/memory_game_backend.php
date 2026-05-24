<?php
// games/memory_game/memory_game_backend.php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../services/GameService.php'; // si vous voulez garder certaines helpers

$action = $_GET['action'] ?? '';

if ($action === 'start') {
    $input = json_decode(file_get_contents('php://input'), true);
    $difficulty = $input['difficulty'] ?? 'easy';
    $childId    = (int) ($input['child_id'] ?? $_SESSION['child_id'] ?? 0);

    if (!$childId) {
        echo json_encode(['status' => 'error', 'message' => 'Missing child_id']);
        exit;
    }

    // Récupérer l'ID du jeu "memory_game"
    $pdo = Database::connect();
    $stmt = $pdo->prepare('SELECT game_id FROM games WHERE name = ?');
    $stmt->execute(['memory_game']);
    $gameId = $stmt->fetchColumn();
    if (!$gameId) {
        echo json_encode(['status' => 'error', 'message' => 'Game not found']);
        exit;
    }

    // Créer une nouvelle session dans game_behaviors
    $signals = '{}';
    $raw     = '[]';
    $stmt = $pdo->prepare('
        INSERT INTO game_behaviors (child_id, game_id, start_time, difficulty, signals, raw_signals)
        VALUES (?, ?, NOW(), ?, ?, ?)
    ');
    $stmt->execute([$childId, $gameId, $difficulty, $signals, $raw]);
    $sessionId = $pdo->lastInsertId();

    $_SESSION['current_game_session'] = $sessionId;

    echo json_encode(['status' => 'success', 'data' => ['session_id' => $sessionId]]);
}
elseif ($action === 'score') {
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = (int) ($input['session_id'] ?? 0);
    $points    = (int) ($input['points'] ?? 0);
    $completionTime = (int) ($input['completion_time'] ?? 0);

    if (!$sessionId) {
        echo json_encode(['status' => 'error', 'message' => 'Missing session_id']);
        exit;
    }

    $pdo = Database::connect();
    $stmt = $pdo->prepare('
        UPDATE game_behaviors
        SET points = ?, completion_time = ?, end_time = NOW()
        WHERE session_id = ?
    ');
    $stmt->execute([$points, $completionTime, $sessionId]);

    echo json_encode(['status' => 'success', 'data' => ['points' => $points]]);
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}