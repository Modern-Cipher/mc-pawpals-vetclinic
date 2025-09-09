<?php
// api/petcare.php
require_once __DIR__ . '/../middleware/auth.php';
require_login(['admin']);

require_once __DIR__ . '/../app/models/PetCare.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$user   = $_SESSION['user'];
$user_id = (int)$user['id'];

try {
  if ($action === 'create') {
    $ok = PetCare::create($_POST, $_FILES, $user_id);
    echo json_encode(['success'=>$ok, 'message'=>$ok?'Tip created.':'Failed to create tip.']); exit;
  }
  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) throw new Exception('Missing ID');
    $ok = PetCare::update($id, $_POST, $_FILES, $user_id);
    echo json_encode(['success'=>$ok, 'message'=>$ok?'Tip updated.':'Failed to update tip.']); exit;
  }
  if ($action === 'delete') {
    $raw = json_decode(file_get_contents('php://input'), true) ?? [];
    $id  = (int)($raw['id'] ?? 0);
    if (!$id) throw new Exception('Missing ID');
    $ok = PetCare::delete($id);
    echo json_encode(['success'=>$ok, 'message'=>$ok?'Tip deleted.':'Failed to delete tip.']); exit;
  }
  echo json_encode(['success'=>false, 'message'=>'Unknown action']);
} catch(Throwable $e){
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
