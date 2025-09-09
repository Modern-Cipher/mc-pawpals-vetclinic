<?php
// api/staffs/update.php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';

header('Content-Type: application/json');
function jerr($m){ echo json_encode(['ok'=>false,'error'=>$m]); exit; }
function ok($d=[]){ echo json_encode(['ok'=>true]+$d); exit; }

try{
  $pdo = db();
  $pdo->beginTransaction();

  $uid   = (int)($_POST['user_id'] ?? 0);
  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $usern = trim($_POST['username'] ?? '');
  $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
  $desig = trim($_POST['designation'] ?? '');
  $perms = $_POST['permissions'] ?? [];

  if ($uid<=0) jerr('Invalid user.');
  if ($first===''||$last===''||$email===''||$usern==='') jerr('Missing required fields.');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jerr('Invalid email address.');
  if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9]{4,20}$/', $usern)) jerr('Invalid username.');

  // ensure staff
  $cur = $pdo->prepare("SELECT u.id,u.role,up.avatar_path FROM users u LEFT JOIN user_profiles up ON up.user_id=u.id WHERE u.id=:id LIMIT 1");
  $cur->execute([':id'=>$uid]);
  $curr = $cur->fetch(); if (!$curr || $curr['role']!=='staff') jerr('User not found.');

  // uniqueness (ignore self)
  $s = $pdo->prepare("SELECT 1 FROM users WHERE email=? AND id<>? LIMIT 1"); $s->execute([$email,$uid]); if ($s->fetch()) jerr('Email already in use.');
  $s = $pdo->prepare("SELECT 1 FROM users WHERE username=? AND id<>? LIMIT 1"); $s->execute([$usern,$uid]); if ($s->fetch()) jerr('Username already in use.');

  // update users
  $pdo->prepare("UPDATE users SET first_name=:f,last_name=:l,email=:e,username=:u,phone=:ph,updated_at=NOW() WHERE id=:id")
      ->execute([':f'=>$first,':l'=>$last,':e'=>$email,':u'=>$usern,':ph'=>$phone ?: null, ':id'=>$uid]);

  // profile
  $pdo->prepare("INSERT INTO user_profiles (user_id,designation,created_at,updated_at)
                 VALUES (:id,:d,NOW(),NOW())
                 ON DUPLICATE KEY UPDATE designation=VALUES(designation), updated_at=NOW()")
      ->execute([':id'=>$uid, ':d'=>$desig ?: null]);

  // optional avatar replacement
  if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error']===UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'], true)) {
      $safeF = preg_replace('/[^A-Za-z0-9]+/','', $first);
      $safeL = preg_replace('/[^A-Za-z0-9]+/','', $last);
      $rand  = substr(bin2hex(random_bytes(3)),0,6);
      $name  = "{$safeF}{$safeL}_{$rand}.{$ext}";
      $dir   = __DIR__ . '/../../views/profile-images';
      if (!is_dir($dir)) @mkdir($dir,0775,true);
      $dest  = $dir . '/' . $name;
      if (@move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
        $rel = 'views/profile-images/' . $name;
        // delete old if was in profile-images folder
        $old = $curr['avatar_path'] ?? '';
        if ($old && str_starts_with($old, 'views/profile-images/')) @unlink(__DIR__ . '/../../' . $old);
        $pdo->prepare("UPDATE user_profiles SET avatar_path=:p, updated_at=NOW() WHERE user_id=:id")
            ->execute([':p'=>$rel, ':id'=>$uid]);
      }
    }
  }

  // permissions
  $allowed = array_values(array_unique(array_filter(array_map('strval',$perms))));
  $pdo->prepare("INSERT INTO staff_permissions (user_id, allowed_json, updated_at) VALUES (:id,:j,NOW())
                 ON DUPLICATE KEY UPDATE allowed_json=VALUES(allowed_json), updated_at=NOW()")
      ->execute([':id'=>$uid, ':j'=>json_encode($allowed, JSON_UNESCAPED_SLASHES)]);

  // new documents (optional append)
  $kinds = $_POST['doc_kind'] ?? [];
  if (!empty($_FILES['docs']['name']) && is_array($_FILES['docs']['name'])){
    $baseDir = __DIR__ . '/../../uploads/staffs/' . $uid;
    if (!is_dir($baseDir)) @mkdir($baseDir,0775,true);
    $fN = $_FILES['docs']['name']; $fT = $_FILES['docs']['tmp_name']; $fE = $_FILES['docs']['error'];
    $fS = $_FILES['docs']['size']; $fM = $_FILES['docs']['type'];
    $safeF = preg_replace('/[^A-Za-z0-9]+/','', $first); $safeL = preg_replace('/[^A-Za-z0-9]+/','', $last);
    $insD = $pdo->prepare("INSERT INTO staff_documents (user_id, kind, orig_name, file_path, mime_type, size_bytes, uploaded_at)
                           VALUES (:uid,:k,:o,:p,:m,:s,NOW())");
    for ($i=0; $i<count($fN); $i++){
      if ($fE[$i]!==UPLOAD_ERR_OK || !$fT[$i]) continue;
      $orig = $fN[$i];
      $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
      if (!in_array($ext,['pdf','jpg','jpeg','png','webp'],true)) continue;
      $rand = substr(bin2hex(random_bytes(3)),0,6);
      $new  = "{$safeF}{$safeL}_{$rand}.{$ext}";
      $dest = $baseDir . '/' . $new;
      if (@move_uploaded_file($fT[$i], $dest)) {
        $rel  = 'uploads/staffs/'.$uid.'/'.$new;
        $kind = $kinds[$i] ?? 'other';
        $insD->execute([':uid'=>$uid, ':k'=>$kind, ':o'=>$orig, ':p'=>$rel, ':m'=>$fM[$i] ?: 'application/octet-stream', ':s'=>(int)$fS[$i]]);
      }
    }
  }

  $pdo->commit();
  ok();
}catch(Throwable $e){
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  jerr('Server error');
}
