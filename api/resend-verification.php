<?php
// api/resend-verification.php
declare(strict_types=1);

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/Mailer.php';

use App\Services\Mailer;

function base_path(): string { return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/'; }
function json_out(int $code, array $data): void {
  http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['ok'=>false,'error'=>'Method not allowed']);

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(422, ['ok'=>false,'error'=>'Valid email required']);

try {
  $pdo = db();
  $st = $pdo->prepare("SELECT id, first_name, username, email, role FROM users WHERE email=? LIMIT 1");
  $st->execute([$email]);
  $u = $st->fetch();
  if (!$u) json_out(404, ['ok'=>false,'error'=>'Account not found.']);

  // already verified?
  $vs = $pdo->prepare("SELECT verified_at FROM user_email_status WHERE user_id=? LIMIT 1");
  $vs->execute([(int)$u['id']]);
  $row = $vs->fetch();
  if (!empty($row['verified_at'])) json_out(200, ['ok'=>true, 'message'=>'Already verified. Please login.']);

  // Create new token
  $token  = bin2hex(random_bytes(32));
  $hash   = hash('sha256', $token);
  $ttlMin = 60;
  $exp    = (new DateTime("+{$ttlMin} minutes"))->format('Y-m-d H:i:s');

  $pdo->prepare("INSERT INTO email_verifications (user_id,email,token_hash,purpose,expires_at) VALUES (?,?,?,?,?)")
      ->execute([(int)$u['id'],$u['email'],$hash,'verify',$exp]);

  // URL
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
  $host     = $_SERVER['HTTP_HOST'];
  $BASE     = base_path();
  $verifyUrl= $protocol . $host . $BASE . 'auth/verify-email?token=' . $token;

  (new Mailer())->sendEmailVerification($u['email'], $u['first_name'] ?: ($u['username'] ?? ''), $verifyUrl, $ttlMin);

  json_out(200, ['ok'=>true, 'message'=>'Verification email sent.']);
} catch (\Throwable $e) {
  if (APP_DEBUG) error_log('[resend] '.$e->getMessage());
  json_out(500, ['ok'=>false,'error'=>'Server error. Try again.']);
}
