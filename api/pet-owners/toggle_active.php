<?php
// api/pet-owners/toggle_active.php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';

header('Content-Type: application/json');

function fail($m='Server error'){ echo json_encode(['ok'=>false,'error'=>$m]); exit; }
function ok($extra=[]){ echo json_encode(['ok'=>true]+$extra); exit; }

try {
    $userId = (int)($_POST['user_id'] ?? 0);
    $isActive = (isset($_POST['is_active']) && $_POST['is_active'] == '1') ? 1 : 0;
    if ($userId <= 0) fail('Invalid user ID');

    $pdo = db();
    // Ensure it's a pet owner account
    $q = $pdo->prepare("SELECT id, role FROM users WHERE id=? LIMIT 1");
    $q->execute([$userId]);
    $u = $q->fetch();
    if (!$u || $u['role'] !== 'user') fail('Not a pet owner account');

    $up = $pdo->prepare("UPDATE users SET is_active=? WHERE id=?");
    $up->execute([$isActive, $userId]);

    ok();
} catch (Throwable $e) {
    fail();
}