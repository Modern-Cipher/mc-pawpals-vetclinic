<?php
// auth/forgot/otp/index.php (or equivalent route)

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/services/Mailer.php';

use App\Services\Mailer;

session_start();

$BASE  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$pdo   = db();
$email = trim($_GET['email'] ?? ($_POST['email'] ?? ''));
$ok=false; $err=''; $msg='';

// --- Settings ---
$OTP_TTL_MIN   = 10;   // OTP validity window
$RESEND_COOLDOWN_SEC = 60; // Cooldown between resends

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: '.$BASE.'auth/forgot');
    exit;
}

// Helper: last send timestamp per email (session-based)
if (!isset($_SESSION['otp_last_send_ts'])) $_SESSION['otp_last_send_ts'] = [];

// 1) HANDLE RESEND (GET only) with PRG, cooldown, and invalidate old OTPs
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['resend'])) {
    // Cooldown check (session)
    $now = time();
    $last = $_SESSION['otp_last_send_ts'][$email] ?? 0;
    $remaining = ($last + $RESEND_COOLDOWN_SEC) - $now;

    if ($remaining > 0) {
        // Still on cooldown — redirect back without re-sending
        header('Location: '.$BASE.'auth/forgot/otp?email='.urlencode($email).'&resent=0&cooldown='.$remaining);
        exit;
    }

    // Lookup user
    $st = $pdo->prepare("SELECT id,first_name,username,email FROM users WHERE email=? LIMIT 1");
    $st->execute([$email]);
    if ($u = $st->fetch()) {
        // Invalidate any previous unused OTPs for this purpose
        $pdo->prepare("UPDATE otp_codes 
                       SET used_at=NOW() 
                       WHERE email=? AND purpose='reset' AND used_at IS NULL")
            ->execute([$email]);

        // Create new OTP
        $code   = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash   = password_hash($code, PASSWORD_DEFAULT);
        $exp    = (new DateTime("+{$OTP_TTL_MIN} minutes"))->format('Y-m-d H:i:s');

        $pdo->prepare("INSERT INTO otp_codes (user_id,email,purpose,code_hash,expires_at) VALUES (?,?,?,?,?)")
            ->execute([$u['id'],$u['email'],'reset',$hash,$exp]);

        // Send email
        (new Mailer())->sendOTP(
            $u['email'],
            $u['first_name'] ?? $u['username'] ?? '',
            $code,
            $OTP_TTL_MIN
        );

        // Update cooldown marker
        $_SESSION['otp_last_send_ts'][$email] = $now;

        // PRG: go back WITHOUT ?resend=1, carry flags for UI
        header('Location: '.$BASE.'auth/forgot/otp?email='.urlencode($email).'&resent=1&cooldown='.$RESEND_COOLDOWN_SEC);
        exit;
    } else {
        // Don't leak existence; just redirect back silently
        header('Location: '.$BASE.'auth/forgot/otp?email='.urlencode($email).'&resent=1&cooldown='.$RESEND_COOLDOWN_SEC);
        exit;
    }
}

// For UI messaging after PRG
if (isset($_GET['resent'])) {
    if ($_GET['resent'] === '1') {
        $msg = 'A new code has been sent to your email.';
    } else {
        $msg = 'Please wait before requesting another code.';
    }
}

// 2) HANDLE PASSWORD RESET (POST) — verify latest valid OTP and change password
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $code = trim($_POST['code'] ?? '');
  $p1   = $_POST['password'] ?? '';
  $p2   = $_POST['confirm']  ?? '';

  $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

  if (!preg_match('/^\d{6}$/', $code)) {
    $err = 'Please enter the 6-digit code.';
  } elseif ($p1 !== $p2) {
    $err = 'Passwords do not match.';
  } elseif (!preg_match($password_regex, $p1)) {
    $err = 'Password does not meet the requirements.';
  } else {
    // Get most recent unused OTP for this email/purpose
    $st = $pdo->prepare("SELECT * FROM otp_codes 
                         WHERE email=? AND purpose='reset' AND used_at IS NULL 
                         ORDER BY id DESC LIMIT 1");
    $st->execute([$email]);
    $otp = $st->fetch();
    
    if (!$otp) {
        $err = 'No valid code found. Please request a new one.';
    } else if (new DateTime() > new DateTime($otp['expires_at'])) {
        $err = 'This code has expired. Please request a new one.';
    } else if (!password_verify($code, $otp['code_hash'])) {
        $err = 'The code you entered is incorrect.';
    } else {
      // Change password
      $new_pwd_hash = password_hash($p1, PASSWORD_DEFAULT);
      $pdo->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE email=?")
          ->execute([$new_pwd_hash, $email]);

      // Consume this OTP
      $pdo->prepare("UPDATE otp_codes SET used_at=NOW() WHERE id=?")->execute([$otp['id']]);

      $ok = true;
    }
  }
}

// Compute remaining cooldown for UI (in case page loads from history)
$cooldownFromQuery = isset($_GET['cooldown']) ? max(0, (int)$_GET['cooldown']) : 0;
$now = time();
$last = $_SESSION['otp_last_send_ts'][$email] ?? 0;
$remainingCooldown = $cooldownFromQuery > 0
    ? $cooldownFromQuery
    : max(0, ($last + $RESEND_COOLDOWN_SEC) - $now);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Verify & Reset Password</title>
  <link rel="stylesheet" href="<?=$BASE?>assets/css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .resend-wrap { margin-top: 1rem; text-align:center; color: var(--text-medium); }
    .resend-btn {
      display:inline-block; padding:.6rem 1rem; border-radius:8px;
      border:1px solid var(--border-color); text-decoration:none;
    }
    .resend-btn[aria-disabled="true"] { pointer-events:none; opacity:.6; }
    .rules { list-style:none; padding:0; font-size:.9rem; color:var(--text-medium); }
    .rules li.ok { color: var(--success-color, #0a7e51); font-weight: 600; }
  </style>
</head>
<body>
  <div class="auth-layout">
    <div class="auth-branding-panel">
        <div class="branding-content">
            <div class="logo">🐾 PawPals</div>
            <h1>Almost There!</h1>
            <p>Check your email for a 6-digit code and enter it below to set a new password for your account.</p>
        </div>
        <div class="copyright">&copy; <?= date('Y') ?> PawPals. All rights reserved.</div>
    </div>
    <div class="auth-form-panel">
      <div class="auth-card">
        <div class="form-header">
            <h3>Reset Your Password</h3>
        </div>

        <?php if($ok): ?>
          <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon:'success',
                    title:'Success!',
                    text:'Password changed successfully! You can now login.',
                    confirmButtonText: 'Go to Login'
                }).then(() => window.location.href = '<?=$BASE?>auth/login');
            });
          </script>
        <?php else: ?>
          <p style="text-align:center; margin-bottom: 16px; color: var(--text-medium);">
            A code was sent to <strong><?=htmlspecialchars($email)?></strong>.
          </p>
          
          <?php if($err):?>
            <script>
              document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({icon:'error', title:'Oops...', text:<?=json_encode($err)?>});
              });
            </script>
          <?php endif;?>
          <?php if($msg):?>
            <script>
              document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({icon:'info', title:'Notice', text:<?=json_encode($msg)?>});
              });
            </script>
          <?php endif;?>

          <!-- IMPORTANT: Explicit action WITHOUT resend=1 -->
          <form method="post" id="otpForm" action="<?=$BASE?>auth/forgot/otp?email=<?=urlencode($email)?>" novalidate>
            <input type="hidden" name="email" value="<?=htmlspecialchars($email)?>">

            <div class="form-group">
              <label for="code">6-Digit Code</label>
              <input type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" name="code" id="code" required>
            </div>

            <div class="form-group">
              <label for="pwd">New Password</label>
              <input type="password" name="password" id="pwd" required autocomplete="new-password">
            </div>

            <div class="form-group">
              <label for="cpwd">Confirm New Password</label>
              <input type="password" name="confirm" id="cpwd" required autocomplete="new-password">
            </div>

            <ul class="rules" id="rules">
              <li id="r-len">• At least 8 characters</li>
              <li id="r-upper">• An uppercase letter</li>
              <li id="r-lower">• A lowercase letter</li>
              <li id="r-num">• A number</li>
              <li id="r-spec">• A special character (@$!%*?&)</li>
              <li id="r-match">• Passwords match</li>
            </ul>

            <button class="btn">Reset Password</button>
          </form>

          <div class="resend-wrap">
            <a
              id="resendBtn"
              class="resend-btn"
              href="<?=$BASE?>auth/forgot/otp?email=<?=urlencode($email)?>&resend=1"
              aria-disabled="false"
              >Resend Code</a>
            <div id="cooldownText" style="margin-top:.5rem;"></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<script>
(function(){
  const $ = s => document.querySelector(s);
  const otpForm = $('#otpForm');

  // Password rules live update
  if (otpForm) {
    const pwd = $('#pwd'), cpwd = $('#cpwd'), rules = {
      len: $('#r-len'), upper: $('#r-upper'), lower: $('#r-lower'),
      num: $('#r-num'), spec: $('#r-spec'), match: $('#r-match'),
    };
    const re = { upper: /[A-Z]/, lower: /[a-z]/, num: /\d/, spec: /[@$!%*?&]/ };

    function ok(li){ li.classList.add('ok'); li.innerHTML = li.innerHTML.replace('•','✓'); }
    function bad(li){ li.classList.remove('ok'); li.innerHTML = li.innerHTML.replace('✓','•'); }
    function update(){
      const v = pwd.value || '', v2 = cpwd.value || '';
      (v.length >= 8) ? ok(rules.len) : bad(rules.len);
      (re.upper.test(v)) ? ok(rules.upper) : bad(rules.upper);
      (re.lower.test(v)) ? ok(rules.lower) : bad(rules.lower);
      (re.num.test(v))   ? ok(rules.num)   : bad(rules.num);
      (re.spec.test(v))  ? ok(rules.spec)  : bad(rules.spec);
      (v && v === v2)    ? ok(rules.match) : bad(rules.match);
    }
    pwd.addEventListener('input', update);
    cpwd.addEventListener('input', update);
    update();
  }

  // Cooldown timer for resend
  const resendBtn = $('#resendBtn');
  const cooldownText = $('#cooldownText');
  let remaining = <?= (int)$remainingCooldown ?>; // seconds from PHP

  function format(s){
    const m = Math.floor(s/60), r = s%60;
    return m > 0 ? `${m}m ${r}s` : `${r}s`;
  }

  function tick(){
    if (remaining > 0) {
      resendBtn.setAttribute('aria-disabled','true');
      cooldownText.textContent = `You can resend another code in ${format(remaining)}.`;
      remaining--;
      setTimeout(tick, 1000);
    } else {
      resendBtn.setAttribute('aria-disabled','false');
      cooldownText.textContent = '';
    }
  }

  if (resendBtn) tick();
})();
</script>
</body>
</html>
