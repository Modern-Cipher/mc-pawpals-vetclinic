<?php
// api/staffs/vets/list.php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../config/connection.php';
header('Content-Type: application/json');

try {
  $pdo = db();
  // consider "Veterinarian" designation in user_profiles
  $sql = "
    SELECT u.id, CONCAT(u.first_name,' ',u.last_name) AS name,
           (SELECT designation FROM user_profiles WHERE user_id=u.id LIMIT 1) AS designation
    FROM users u
    WHERE u.role='staff' AND u.is_active=1
      AND (
        (SELECT designation FROM user_profiles WHERE user_id=u.id LIMIT 1) LIKE 'Vet%'
        OR (SELECT designation FROM user_profiles WHERE user_id=u.id LIMIT 1) LIKE 'Veterinarian%'
        OR (SELECT designation FROM user_profiles WHERE user_id=u.id LIMIT 1) = 'Veterinarian'
      )
    ORDER BY name ASC
  ";
  $rows = $pdo->query($sql)->fetchAll() ?: [];
  $items = array_map(fn($r)=>[
    'id'=>(int)$r['id'],
    'name'=>$r['name'],
    'designation'=>$r['designation'] ?: null
  ], $rows);

  echo json_encode(['ok'=>true,'items'=>$items]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}
