<?php
// api/pet-owners/list.php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';
header('Content-Type: application/json');

function abs_url(string $rel): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $appBase = preg_replace('~/api/.*$~', '', $_SERVER['SCRIPT_NAME']);
    $rel   = ltrim($rel, '/');
    return rtrim("$scheme://$host$appBase", '/') . "/$rel";
}

try {
    $pdo = db();
    // Dinagdagan ng LEFT JOIN sa user_email_status table
    $sql = "
      SELECT 
        u.id, u.username, u.email, u.phone, u.first_name, u.last_name, u.is_active,
        u.created_at, u.last_login_at,
        p.avatar_path,
        em.verified_at
      FROM users u
      LEFT JOIN user_profiles p ON u.id = p.user_id
      LEFT JOIN user_email_status em ON u.id = em.user_id
      WHERE u.role='user'
      ORDER BY u.created_at DESC
    ";
    $rows = $pdo->query($sql)->fetchAll() ?: [];
    $items = [];
    $defaultAvatar = 'assets/images/person1.jpg';

    foreach ($rows as $r) {
        $avatarPath = $r['avatar_path'] ?: $defaultAvatar;
        $items[] = [
            'id' => (int)$r['id'],
            'username' => $r['username'],
            'email' => $r['email'],
            'phone' => $r['phone'],
            'first_name' => $r['first_name'],
            'last_name' => $r['last_name'],
            'is_active' => (int)$r['is_active'],
            'avatar_url' => abs_url($avatarPath),
            'last_login_at' => $r['last_login_at'],
            'created_at' => $r['created_at'],
            'is_verified' => !empty($r['verified_at']), // Gagawing true/false
        ];
    }

    echo json_encode(['ok'=>true, 'items'=>$items]);
} catch (Throwable $e) {
    error_log("Pet Owner List API Error: " . $e->getMessage());
    echo json_encode(['ok'=>false, 'error'=>'Server error']);
}