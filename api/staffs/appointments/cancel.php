<?php
// api/staffs/appointments/cancel.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json; charset=utf-8');

function jerr(string $m='Server error', int $c=500){ http_response_code($c); echo json_encode(['ok'=>false,'error'=>$m]); exit; }

try{
  $pdo = db();
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true) ?: $_POST;

  $id = (int)($data['appointment_id'] ?? 0);
  if($id<=0) jerr('Missing appointment_id',422);

  $st = $pdo->prepare("UPDATE appointments SET status='Cancelled', updated_at=NOW() WHERE id=? LIMIT 1");
  $st->execute([$id]);

  echo json_encode(['ok'=>true]);
}catch(Throwable $e){ error_log('appointments/cancel staff: '.$e->getMessage()); jerr(); }
