
<?php 
session_start(); 
header('Content-Type: application/json'); 
 
$data = json_decode(file_get_contents('php://input'), true); 
if (!$data || !isset($data['game_id'], $data['signals'])) { 
    http_response_code(422); 
    echo json_encode(['status'=>'error','message'=>'Invalid payload']); 
    exit; 
} 
 
// Merge dans $_SESSION['behavior'] — JAMAIS en base de données 
if (!isset($_SESSION['behavior'])) { 
    $_SESSION['behavior'] = []; 
} 
$gid = htmlspecialchars($data['game_id']); 
$_SESSION['behavior'][$gid] = array_merge( 
    $_SESSION['behavior'][$gid] ?? [], 
    $data['signals'] 
); 
 
echo json_encode(['status'=>'success','stored'=>count($data['signals'])]); 