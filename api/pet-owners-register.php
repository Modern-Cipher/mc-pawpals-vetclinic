<?php
// api/pet-owners-register.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/Mailer.php';

use App\Services\Mailer;

function base_path(): string {
  return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
}
function json_out(int $code, array $data): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(405, ['ok'=>false, 'error'=>'Method not allowed']);
}

$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name']  ?? '');
$email = trim($_POST['email']      ?? '');
$usern = trim($_POST['username']   ?? '');
$phone = trim($_POST['phone']      ?? '');
$sex   = $_POST['sex']             ?? '';
$pass  = $_POST['password']        ?? '';
$conf  = $_POST['confirm']         ?? '';

if ($first==='' || $last==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || $usern==='' || $phone==='' || !in_array($sex,['male','female'],true)) {
  json_out(422, ['ok'=>false, 'error'=>'Please complete all required fields.']);
}
if ($pass !== $conf) json_out(422, ['ok'=>false, 'error'=>'Passwords do not match.']);
if (strlen($pass) < 8) json_out(422, ['ok'=>false, 'error'=>'Password must be at least 8 characters.']);

try {
  $pdo = db();

  // Duplicates
  $st = $pdo->prepare("SELECT 1 FROM users WHERE email=? LIMIT 1");
  $st->execute([$email]);
  if ($st->fetch()) json_out(409, ['ok'=>false, 'error'=>'Email already in use.']);

  $st = $pdo->prepare("SELECT 1 FROM users WHERE username=? LIMIT 1");
  $st->execute([$usern]);
  if ($st->fetch()) json_out(409, ['ok'=>false, 'error'=>'Username already in use.']);

  // Create user
  $pwdHash = password_hash($pass, PASSWORD_DEFAULT);
  $ins = $pdo->prepare("
    INSERT INTO users (username,email,password,role,first_name,last_name,sex,phone,created_at,updated_at)
    VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())
  ");
  $ins->execute([$usern,$email,$pwdHash,'user',$first,$last,$sex,$phone]);
  $uid = (int)$pdo->lastInsertId();

  // Optional: profile shell
  try {
    $pdo->prepare("INSERT INTO user_profiles (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())")
        ->execute([$uid]);
  } catch (\Throwable $e) {}

  // Seed status row (not verified)
  try {
    $pdo->prepare("INSERT INTO user_email_status (user_id,email,verified_at,created_at,updated_at) VALUES (?, ?, NULL, NOW(), NOW())")
        ->execute([$uid, $email]);
  } catch (\Throwable $e) {}

  // Create verification token
  $token  = bin2hex(random_bytes(32));
  $hash   = hash('sha256', $token);
  $ttlMin = 60;
  $exp    = (new \DateTime("+{$ttlMin} minutes"))->format('Y-m-d H:i:s');

  $pdo->prepare("INSERT INTO email_verifications (user_id,email,token_hash,purpose,expires_at) VALUES (?,?,?,?,?)")
      ->execute([$uid,$email,$hash,'verify',$exp]);

  // Verify URL
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
  $host     = $_SERVER['HTTP_HOST'];
  $BASE     = base_path();
  $verifyUrl= $protocol . $host . $BASE . 'auth/verify-email?token=' . $token;

  (new Mailer())->sendEmailVerification($email, $first ?: $usern, $verifyUrl, $ttlMin);

  $_SESSION['pending_verify_email'] = $email;

  json_out(200, ['ok'=>true, 'email'=>$email, 'redirect'=> $BASE.'auth/verify-email?email='.urlencode($email)]);
} catch (\Throwable $e) {
  if (APP_DEBUG) error_log('[register] '.$e->getMessage());
  json_out(500, ['ok'=>false, 'error'=>'Server error. Please try again.']);
}
