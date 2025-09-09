<?php
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/services/Mailer.php';

use App\Services\Mailer;

$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$msg=''; $err='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email  = trim($_POST['email'] ?? '');
  $method = $_POST['method'] ?? 'link';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Please enter a valid email address.';
  } else {
    $pdo  = db();
    $user = null;
    $st = $pdo->prepare("SELECT id,first_name,username,email FROM users WHERE email=? LIMIT 1");
    $st->execute([$email]);
    $user = $st->fetch();

    $msg = 'If an account with that email exists, instructions have been sent.';

    if ($user) {
      if ($method === 'otp') {
        $code   = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash   = password_hash($code, PASSWORD_DEFAULT);
        $ttlMin = 10;
        $exp    = (new DateTime("+{$ttlMin} minutes"))->format('Y-m-d H:i:s');
        
        $pdo->prepare("INSERT INTO otp_codes (user_id,email,purpose,code_hash,expires_at) VALUES (?,?,?,?,?)")
            ->execute([$user['id'],$user['email'],'reset',$hash,$exp]);

        (new Mailer())->sendOTP($user['email'], $user['first_name'] ?? $user['username'] ?? '', $code, $ttlMin);

        header('Location: '.$BASE.'auth/forgot/otp?email='.urlencode($user['email']));
        exit;
      } else { // 'link' method
        $token  = bin2hex(random_bytes(32));
        $hash   = hash('sha256', $token);
        $ttlMin = 30;
        $exp    = (new DateTime("+{$ttlMin} minutes"))->format('Y-m-d H:i:s');
        $pdo->prepare("INSERT INTO password_resets (user_id,email,token_hash,expires_at) VALUES (?,?,?,?)")
            ->execute([$user['id'],$user['email'],$hash,$exp]);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $resetUrl = $protocol . $host . $BASE . 'auth/reset?token=' . $token;
        
        (new Mailer())->sendReset($user['email'], $user['first_name'] ?? $user['username'] ?? '', $resetUrl, $ttlMin);
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Forgot Password</title>
  <link rel="stylesheet" href="<?=$BASE?>assets/css/auth.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .method-group {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    .method-card {
        display: block;
        padding: 1rem;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        cursor: pointer;
        transition: border-color 0.2s, box-shadow 0.2s;
        text-align: left;
    }
    .method-card:has(input:checked) {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(32, 178, 170, 0.2);
    }
    .method-card input[type="radio"] {
        display: none;
    }
    .method-card strong {
        font-weight: 600;
        color: var(--text-dark);
    }
    .method-card p {
        font-size: 0.9rem;
        color: var(--text-medium);
        margin: 0;
    }
  </style>
</head>
<body>
  <div class="auth-layout">
    <div class="auth-branding-panel">
        <div class="branding-content">
            <div class="logo">🐾 PawPals</div>
            <h1>Forgot Your Password?</h1>
            <p>No worries! Choose a method below to reset your password.</p>
        </div>
        <div class="copyright">&copy; <?= date('Y') ?> PawPals. All rights reserved.</div>
    </div>
    <div class="auth-form-panel">
      <div class="auth-card">
        <div class="form-header">
            <h3>Reset Password</h3>
        </div>

        <?php if ($err): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({icon:'error', title:'Oops...', text:<?=json_encode($err)?>});
                });
            </script>
        <?php elseif ($msg && $_SERVER['REQUEST_METHOD']==='POST'): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({icon:'info', title:'Check Your Email', text:<?=json_encode($msg)?>});
                });
            </script>
        <?php endif; ?>

        <form method="post" novalidate>
          <div class="form-group">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" placeholder="you@example.com" required>
          </div>

          <div class="form-group">
            <label>Delivery Method</label>
            <div class="method-group">
                <label class="method-card">
                    <input type="radio" name="method" value="link" checked>
                    <strong>Reset link (recommended)</strong>
                    <p>We’ll email a secure link that expires.</p>
                </label>
                <label class="method-card">
                    <input type="radio" name="method" value="otp">
                    <strong>One-time code (OTP)</strong>
                    <p>Enter a 6-digit code we send to your email.</p>
                </label>
            </div>
          </div>
          
          <button class="btn" type="submit">Send Instructions</button>
        </form>

        <div class="auth-switch-link">
            <p><a href="<?=$BASE?>auth/login">Back to Login</a></p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>