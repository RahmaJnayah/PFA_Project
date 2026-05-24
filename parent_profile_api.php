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

$stmt = $pdo->prepare(
    'SELECT u.username, u.email, u.plan, u.status, u.created_at, p.parent_id
     FROM users u
     JOIN parents p ON p.user_id = u.id
     WHERE u.id = :user_id'
);
$stmt->execute([':user_id' => $userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Profile not found']);
    exit;
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM children WHERE parent_id = :parent_id');
$countStmt->execute([':parent_id' => (int) $profile['parent_id']]);
$childCount = (int) $countStmt->fetchColumn();

$createdAt = $profile['created_at'] ?? null;
$joinedYear = $createdAt ? (new DateTimeImmutable($createdAt))->format('Y') : null;

$response = [
    'status' => 'success',
    'profile' => [
        'name' => $profile['username'],
        'email' => $profile['email'],
        'plan' => $profile['plan'],
        'status' => $profile['status'],
        'joined_year' => $joinedYear,
    ],
    'child_count' => $childCount,
];

echo json_encode($response);
