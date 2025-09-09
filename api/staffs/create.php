<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/services/Mailer.php';
use App\Services\Mailer;

header('Content-Type: application/json');

function site_base(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $root   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\'); // /<app>
  return "{$scheme}://{$host}{$root}/";
}
function app_root_path(): string {
  // filesystem path to /<app>
  return dirname(dirname(__DIR__));
}

function jerr($msg){ echo json_encode(['ok'=>false,'error'=>$msg]); exit; }
function ok($data=[]){ echo json_encode(['ok'=>true]+$data); exit; }

try{
  $pdo = db();
  $pdo->beginTransaction();

  // --- inputs
  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $usern = trim($_POST['username'] ?? '');
  $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
  $desig = trim($_POST['designation'] ?? '');
  $perms = $_POST['permissions'] ?? [];

  if ($first === '' || $last === '' || $email === '' || $usern === '') jerr('Missing required fields.');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jerr('Invalid email address.');
  if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9]{4,20}$/', $usern)) {
    jerr('Username must be 4–20 characters, letters and numbers only, and include at least one letter and one number.');
  }

  // uniqueness
  $s = $pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1"); $s->execute([$email]); if ($s->fetch()) jerr('Email already in use.');
  $s = $pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1"); $s->execute([$usern]); if ($s->fetch()) jerr('Username already in use.');

  // temp password
  $tmp  = bin2hex(random_bytes(4)) . '@' . random_int(10,99);
  $hash = password_hash($tmp, PASSWORD_BCRYPT);

  // create user
  $insU = $pdo->prepare("
    INSERT INTO users
      (username,email,password,role,first_name,last_name,phone,is_active,must_change_password,created_at,updated_at)
    VALUES
      (:u,:e,:p,'staff',:f,:l,:ph,1,1,NOW(),NOW())
  ");
  $insU->execute([
    ':u'=>$usern, ':e'=>$email, ':p'=>$hash, ':f'=>$first, ':l'=>$last, ':ph'=>$phone ?: null
  ]);
  $uid = (int)$pdo->lastInsertId();

  // profile + avatar
  $avatarRel = 'assets/images/person1.jpg';
  if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $ext  = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
      $labelFirst = preg_replace('/[^A-Za-z0-9]+/','', $first);
      $labelLast  = preg_replace('/[^A-Za-z0-9]+/','', $last);
      $rand = substr(bin2hex(random_bytes(3)), 0, 6);
      $new  = "{$labelFirst}{$labelLast}_{$rand}.{$ext}";
      $destDir = app_root_path() . '/views/profile-images';
      @mkdir($destDir, 0775, true);
      $dest = $destDir . '/' . $new;
      if (@move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
        $avatarRel = 'views/profile-images/' . $new;
      }
    }
  }
  $insP = $pdo->prepare("
    INSERT INTO user_profiles (user_id, designation, avatar_path, created_at, updated_at)
    VALUES (:id, :d, :a, NOW(), NOW())
  ");
  $insP->execute([':id'=>$uid, ':d'=>$desig ?: null, ':a'=>$avatarRel]);

  // permissions
  $allowed = array_values(array_unique(array_filter(array_map('strval',$perms))));
  $insPerm = $pdo->prepare("
    INSERT INTO staff_permissions (user_id, allowed_json, updated_at)
    VALUES (:id, :j, NOW())
    ON DUPLICATE KEY UPDATE allowed_json = VALUES(allowed_json), updated_at = NOW()
  ");
  $insPerm->execute([':id'=>$uid, ':j'=>json_encode($allowed, JSON_UNESCAPED_SLASHES)]);

  // documents (multiple categories)
  if (!empty($_FILES['docs']) && is_array($_FILES['docs']['name'])) {
    $kinds = $_POST['doc_kind'] ?? [];
    $fNames = $_FILES['docs']['name'];
    $fTmp   = $_FILES['docs']['tmp_name'];
    $fErr   = $_FILES['docs']['error'];
    $fSize  = $_FILES['docs']['size'];
    $fType  = $_FILES['docs']['type'];

    $labelFirst = preg_replace('/[^A-Za-z0-9]+/','', $first);
    $labelLast  = preg_replace('/[^A-Za-z0-9]+/','', $last);
    $allowedExt = ['pdf','jpg','jpeg','png','webp'];

    $baseDir = app_root_path() . '/uploads/staffs/' . $uid;
    if (!is_dir($baseDir)) { @mkdir($baseDir, 0775, true); }

    $insD = $pdo->prepare("
      INSERT INTO staff_documents (user_id, kind, orig_name, file_path, mime_type, size_bytes, uploaded_at)
      VALUES (:uid,:k,:o,:p,:m,:s,NOW())
    ");

    for ($i=0; $i<count($fNames); $i++){
      if ($fErr[$i] !== UPLOAD_ERR_OK || !$fTmp[$i]) continue;
      $orig = $fNames[$i];
      $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
      if (!in_array($ext, $allowedExt, true)) continue;

      $rand = substr(bin2hex(random_bytes(3)), 0, 6);
      $new  = "{$labelFirst}{$labelLast}_{$rand}.{$ext}";
      $dest = $baseDir . '/' . $new;

      if (@move_uploaded_file($fTmp[$i], $dest)) {
        $rel = 'uploads/staffs/' . $uid . '/' . $new;
        $kind = isset($kinds[$i]) ? (string)$kinds[$i] : 'other';
        $insD->execute([
          ':uid'=>$uid, ':k'=>$kind, ':o'=>$orig,
          ':p'=>$rel, ':m'=>$fType[$i] ?: 'application/octet-stream',
          ':s'=>(int)$fSize[$i]
        ]);
      }
    }
  }

  // email temp password
  $loginUrl = site_base() . 'auth/login';
  $mailer = new Mailer();
  $mailer->sendTemplate('staff_temp_password', $email, "$first $last", [
    'first_name'    => $first,
    'last_name'     => $last,
    'username'      => $usern,
    'temp_password' => $tmp,
    'login_url'     => $loginUrl,
  ]);

  $pdo->commit();
  ok(['user_id'=>$uid, 'temp_password'=>$tmp]);

}catch(Throwable $e){
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  jerr($e->getMessage());
}
