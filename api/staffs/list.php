<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';
header('Content-Type: application/json');

function abs_url(string $rel): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  // strip everything after /api/… so we land on app root
  $appBase = preg_replace('~/api/.*$~', '', $_SERVER['SCRIPT_NAME']);
  $rel  = ltrim($rel, '/');
  return rtrim("$scheme://$host$appBase", '/') . "/$rel";
}

try {
  $pdo = db();
  $sql = "
    SELECT u.id,u.username,u.email,u.phone,u.first_name,u.last_name,u.is_active,u.must_change_password,
           u.created_at,u.updated_at,u.last_login_at,
           (SELECT designation FROM user_profiles WHERE user_id=u.id LIMIT 1) AS designation,
           (SELECT avatar_path FROM user_profiles WHERE user_id=u.id LIMIT 1) AS avatar_path,
           (SELECT allowed_json FROM staff_permissions WHERE user_id=u.id LIMIT 1) AS allowed_json
    FROM users u
    WHERE u.role='staff'
    ORDER BY u.created_at DESC
  ";
  $rows = $pdo->query($sql)->fetchAll() ?: [];
  $items = [];
  $defaultAvatar = 'assets/images/person1.jpg';

  foreach ($rows as $r) {
    $allowed = [];
    if (!empty($r['allowed_json'])) {
      $tmp = json_decode($r['allowed_json'], true);
      if (is_array($tmp)) $allowed = $tmp;
    }
    $avatarPath = $r['avatar_path'] ?: $defaultAvatar;
    $items[] = [
      'id' => (int)$r['id'],
      'username' => $r['username'],
      'email' => $r['email'],
      'phone' => $r['phone'],
      'first_name' => $r['first_name'],
      'last_name' => $r['last_name'],
      'is_active' => (int)$r['is_active'],
      'designation' => $r['designation'] ?: null,
      'avatar_url' => abs_url($avatarPath),
      'permissions' => $allowed,
    ];
  }

  echo json_encode(['ok'=>true,'items'=>$items]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}
