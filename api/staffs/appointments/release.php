<?php
// api/staffs/appointments/release.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff','admin']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

try {
  $pdo = db();
  $me  = (int)($_SESSION['user']['id'] ?? 0);
  $id  = (int)($_POST['id'] ?? 0);
  if (!$id) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }

  $sql = "UPDATE appointments SET staff_id_assigned = NULL WHERE id=:id AND staff_id_assigned=:me";
  $st  = $pdo->prepare($sql);
  $st->execute([':id'=>$id, ':me'=>$me]);

  if ($st->rowCount() < 1) {
    echo json_encode(['ok'=>false,'error'=>'Not assigned to you']); exit;
  }

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}
