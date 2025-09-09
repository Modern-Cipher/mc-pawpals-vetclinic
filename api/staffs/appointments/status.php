<?php
// api/staffs/appointments/status.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff','admin']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

try {
  $pdo    = db();
  $me     = (int)($_SESSION['user']['id'] ?? 0);
  $id     = (int)($_POST['id'] ?? 0);
  $status = trim($_POST['status'] ?? '');

  $allowed = ['Confirmed','Completed','Cancelled','No-Show','Pending'];
  if (!$id || !in_array($status, $allowed, true)) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid input']); exit;
  }

  $st = $pdo->prepare("SELECT staff_id_assigned FROM appointments WHERE id=:id");
  $st->execute([':id'=>$id]);
  $row = $st->fetch();
  if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

  $assignee = (int)($row['staff_id_assigned'] ?? 0);
  $role = $_SESSION['user']['role'] ?? 'staff';
  if ($role !== 'admin' && $assignee !== $me) {
    http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Not allowed']); exit;
  }

  $upd = $pdo->prepare("UPDATE appointments SET status=:s WHERE id=:id");
  $upd->execute([':s'=>$status, ':id'=>$id]);

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}
