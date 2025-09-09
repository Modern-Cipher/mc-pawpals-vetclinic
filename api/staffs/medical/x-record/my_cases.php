<?php
// api/staffs/medical/my_cases.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../middleware/auth.php';

header('Content-Type: application/json');

if (empty($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'staff') {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Unauthorized']);
  exit;
}

$staff_id = (int)$_SESSION['user']['id'];
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

try {
  $pdo = db();
  $sql = "
    SELECT
      p.id,
      p.name            AS pet_name,
      p.species, p.breed,
      p.photo_path,
      CONCAT_WS(' ', u.first_name, u.last_name) AS owner_name,
      MAX(m.record_date)                         AS last_visit,
      COUNT(m.id)                                AS visit_count
    FROM medical_records m
    JOIN pets  p ON p.id   = m.pet_id
    JOIN users u ON u.id   = p.user_id
    WHERE m.staff_id = :sid
    GROUP BY p.id
    ORDER BY last_visit DESC
    LIMIT 500
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':sid'=>$staff_id]);
  $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

  foreach ($rows as &$r) {
    $r['photo_url'] = $r['photo_path'] ? ($BASE . ltrim($r['photo_path'], '/')) : null;
    $r['time_text'] = $r['last_visit'] ? date('M j, Y g:i A', strtotime($r['last_visit'])) : null;
  }

  echo json_encode(['ok'=>true,'cases'=>$rows]);
} catch (\Throwable $e) {
  error_log('[staff.my_cases] ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}
