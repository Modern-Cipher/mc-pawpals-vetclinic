<?php
// api/staffs/toggle_active.php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';

header('Content-Type: application/json');

function fail($m='Server error'){ echo json_encode(['ok'=>false,'error'=>$m]); exit; }
function ok($extra=[]){ echo json_encode(['ok'=>true]+$extra); exit; }

try {
  $userId = (int)($_POST['user_id'] ?? 0);
  $isActive = (isset($_POST['is_active']) && $_POST['is_active'] == '1') ? 1 : 0;
  if ($userId <= 0) fail('Invalid id');

  $pdo = db();
  // ensure it's a staff account
  $q = $pdo->prepare("SELECT id, role FROM users WHERE id=? LIMIT 1");
  $q->execute([$userId]);
  $u = $q->fetch();
  if (!$u || $u['role'] !== 'staff') fail('Not a staff account');

  $up = $pdo->prepare("UPDATE users SET is_active=?, updated_at=NOW() WHERE id=? LIMIT 1");
  $up->execute([$isActive, $userId]);

  ok();
} catch (Throwable $e) {
  fail();
}
