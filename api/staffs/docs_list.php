<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';

header('Content-Type: application/json');

function site_base(): string {
  // http(s)://host/<app>/
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  // SCRIPT_NAME: /mc-pawpals-veterinary-clinic/api/staffs/docs_list.php
  // Go up 3 levels => /mc-pawpals-veterinary-clinic
  $root   = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/\\');
  return "{$scheme}://{$host}{$root}/";
}

try {
  $uid = (int)($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
  if ($uid <= 0) throw new Exception('bad uid');

  $pdo = db();

  // Pick a sensible filename field; fall back to last path segment
  $cols = [];
  foreach ($pdo->query("SHOW COLUMNS FROM staff_documents") as $c) {
    $cols[$c['Field']] = true;
  }
  $nameExpr = "COALESCE(" .
              ($cols['original_name'] ? "original_name," : "") .
              ($cols['orig_name']     ? "orig_name,"     : "") .
              "SUBSTRING_INDEX(REPLACE(file_path,'\\\\','/'), '/', -1)) AS name";

  $sql = "
    SELECT id, user_id, kind, doc_type, file_path, mime_type, size_bytes, uploaded_at, $nameExpr
    FROM staff_documents
    WHERE user_id = ?
    ORDER BY uploaded_at DESC, id DESC
  ";
  $st = $pdo->prepare($sql);
  $st->execute([$uid]);

  $base = site_base(); // ends with /
  $groups = [];
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $kind = $r['kind'] ?: 'other';

    // Normalize stored path: backslashes → slashes, strip any accidental leading "api/"
    $rel = str_replace('\\', '/', (string)$r['file_path']);
    $rel = preg_replace('#^/?api/#i', '', $rel);
    $rel = ltrim($rel, '/');

    $url  = $base . $rel;

    $groups[$kind][] = [
      'id'   => (int)$r['id'],
      'name' => $r['name'],
      'url'  => $url,
      'type' => $r['mime_type'] ?? ''
    ];
  }

  echo json_encode(['ok'=>true, 'groups'=>$groups]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}
