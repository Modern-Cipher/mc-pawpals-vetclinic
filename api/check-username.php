<?php
// api/check-username.php
require_once __DIR__ . '/../config/connection.php';
header('Content-Type: application/json');

try{
  $pdo = db();
  $u = $_GET['u'] ?? $_GET['username'] ?? '';
  $u = trim($u);

  // same rule as front-end
  if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9_]{4,20}$/', $u)) {
    echo json_encode(['ok'=>true, 'available'=>false, 'reason'=>'invalid_format']); exit;
  }

  $st = $pdo->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
  $st->execute([$u]);
  $exists = (bool)$st->fetchColumn();

  echo json_encode(['ok'=>true, 'available'=>!$exists]);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>'server_error']);
}
