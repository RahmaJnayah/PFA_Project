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

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$body = readJsonBody();
$params = array_merge($_POST, $body);
$action = $params['action'] ?? ($method === 'GET' ? 'list' : '');

if ($action === 'list') {
    $stmt = $pdo->prepare(
        'SELECT child_id, nickname, age, emoji, theme, buddy, avatar, created_at
         FROM children
         WHERE parent_id = :parent_id
         ORDER BY created_at ASC'
    );
    $stmt->execute([':parent_id' => $parentId]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'children' => $children]);
    exit;
}

if ($action === 'create') {
    $nickname = trim((string) ($params['nickname'] ?? ''));
    $emoji = trim((string) ($params['emoji'] ?? ''));
    $theme = trim((string) ($params['theme'] ?? 'theme-rose'));
    $buddy = trim((string) ($params['buddy'] ?? ''));
    $ageRaw = $params['age'] ?? null;

    if ($nickname === '') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Child name is required']);
        exit;
    }

    $age = null;
    if ($ageRaw !== null && $ageRaw !== '') {
        $age = (int) $ageRaw;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO children (parent_id, nickname, age, emoji, theme, buddy, avatar)
         VALUES (:parent_id, :nickname, :age, :emoji, :theme, :buddy, :avatar)'
    );
    $stmt->execute([
        ':parent_id' => $parentId,
        ':nickname' => $nickname,
        ':age' => $age,
        ':emoji' => $emoji !== '' ? $emoji : '🦊',
        ':theme' => $theme !== '' ? $theme : 'theme-rose',
        ':buddy' => $buddy !== '' ? $buddy : null,
        ':avatar' => $emoji !== '' ? $emoji : null,
    ]);

    echo json_encode(['status' => 'success', 'child_id' => (int) $pdo->lastInsertId()]);
    exit;
}

if ($action === 'update') {
    $childId = (int) ($params['child_id'] ?? 0);
    if ($childId <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Child id is required']);
        exit;
    }

    $fields = [];
    $bind = [':child_id' => $childId, ':parent_id' => $parentId];

    if (isset($params['nickname'])) {
        $fields[] = 'nickname = :nickname';
        $bind[':nickname'] = trim((string) $params['nickname']);
    }
    if (array_key_exists('age', $params)) {
        $ageRaw = $params['age'];
        $bind[':age'] = ($ageRaw === '' || $ageRaw === null) ? null : (int) $ageRaw;
        $fields[] = 'age = :age';
    }
    if (isset($params['emoji'])) {
        $fields[] = 'emoji = :emoji';
        $bind[':emoji'] = trim((string) $params['emoji']);
        $fields[] = 'avatar = :avatar';
        $bind[':avatar'] = trim((string) $params['emoji']);
    }
    if (isset($params['theme'])) {
        $fields[] = 'theme = :theme';
        $bind[':theme'] = trim((string) $params['theme']);
    }
    if (array_key_exists('buddy', $params)) {
        $fields[] = 'buddy = :buddy';
        $buddy = trim((string) $params['buddy']);
        $bind[':buddy'] = $buddy !== '' ? $buddy : null;
    }

    if (!$fields) {
        echo json_encode(['status' => 'success', 'message' => 'No changes']);
        exit;
    }

    $sql = 'UPDATE children SET ' . implode(', ', $fields) . '
            WHERE child_id = :child_id AND parent_id = :parent_id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind);

    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'delete') {
    $childId = (int) ($params['child_id'] ?? 0);
    if ($childId <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Child id is required']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM children WHERE child_id = :child_id AND parent_id = :parent_id');
    $stmt->execute([':child_id' => $childId, ':parent_id' => $parentId]);

    echo json_encode(['status' => 'success']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unsupported action']);
