<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';

header('Content-Type: application/json');

function app_root_fs(): string {
  // Absolute filesystem path to app root
  return rtrim(dirname(__DIR__, 2), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

try {
  $docId = (int)($_POST['doc_id'] ?? 0);
  if ($docId <= 0) throw new Exception('bad id');

  $pdo = db();
  $st  = $pdo->prepare("SELECT file_path FROM staff_documents WHERE id=? LIMIT 1");
  $st->execute([$docId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new Exception('nf');

  $rel = ltrim($row['file_path'], '/');
  $abs = app_root_fs() . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
  if (is_file($abs)) @unlink($abs);

  $pdo->prepare("DELETE FROM staff_documents WHERE id=? LIMIT 1")->execute([$docId]);

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}
