<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$pdo  = db();

$ok=false; $err='';
$token = $_GET['token'] ?? '';
$email = trim($_GET['email'] ?? ($_SESSION['pending_verify_email'] ?? ''));

function auto_login(array $user, PDO $pdo): void {
  $_SESSION['user'] = [
    'id'         => (int)$user['id'],
    'email'      => $user['email'],
    'username'   => $user['username'],
    'first_name' => $user['first_name'],
    'last_name'  => $user['last_name'],
    'role'       => $user['role'],
    'login_timestamp' => time(),                 // <<< ADD
  ];
  if (!isset($_SESSION['last_seen_attempt'])) {  // <<< ADD
    $_SESSION['last_seen_attempt'] = '2000-01-01 00:00:00';
  }
  try {
    $pdo->prepare("UPDATE users SET active_session_id=?, is_online=1, last_login_at=NOW() WHERE id=?")
        ->execute([session_id(), (int)$user['id']]);
  } catch (\Throwable $e) {}
}

$redirect = $BASE.'dashboard/users';

if ($token) {
  try {
    $hash = hash('sha256', $token);
    $st = $pdo->prepare("SELECT * FROM email_verifications WHERE token_hash=? AND purpose='verify' AND used_at IS NULL LIMIT 1");
    $st->execute([$hash]);
    if ($row = $st->fetch()) {
      if (new DateTime() > new DateTime($row['expires_at'])) {
        $err = 'This verification link has expired. Please request a new one.';
      } else {
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("
          INSERT INTO user_email_status (user_id,email,verified_at,created_at,updated_at)
          VALUES (?,?,?,?,?)
          ON DUPLICATE KEY UPDATE email=VALUES(email), verified_at=VALUES(verified_at), updated_at=VALUES(updated_at)
        ")->execute([$row['user_id'], $row['email'], $now, $now, $now]);

        $pdo->prepare("UPDATE email_verifications SET used_at=NOW() WHERE id=?")->execute([$row['id']]);

        $u = $pdo->prepare("SELECT id,email,username,first_name,last_name,role FROM users WHERE id=? LIMIT 1");
        $u->execute([(int)$row['user_id']]);
        if ($user = $u->fetch()) {
          auto_login($user, $pdo);
          $roleRoutes = ['admin' => 'dashboard/admin', 'staff' => 'dashboard/staff', 'user' => 'dashboard/users'];
          $redirect   = $BASE . ($roleRoutes[$user['role']] ?? 'dashboard/users');
          $ok = true;
          unset($_SESSION['pending_verify_email']);
        } else {
          $err = 'User account not found.';
        }
      }
    } else {
      $err = 'This verification link is invalid or already used.';
    }
  } catch (\Throwable $e) {
    $err = 'Server error. Please try again.';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Verify Email</title>
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .muted{color:var(--text-medium)}
    .inline-btn{background:none;border:none;color:var(--primary-color);font-weight:600;cursor:pointer;padding:0}
    .countdown{font-variant-numeric:tabular-nums}
  </style>
</head>
<body>
  <a class="back-home" href="<?= $BASE ?>"><i class="fa-solid fa-arrow-left"></i><span>Back to Home</span></a>

  <div class="auth-layout">
    <div class="auth-branding-panel">
      <div class="branding-content">
        <div class="logo">🐾 PawPals</div>
        <h1>Verify Your Email</h1>
        <p>We sent you a secure link. Click it to activate your account and start using PawPals.</p>
        <div class="copyright">&copy; <?= date('Y') ?> PawPals. All rights reserved.</div>
      </div>
    </div>

    <div class="auth-form-panel">
      <div class="auth-card" style="max-width:560px">
        <div class="form-header"><h3>Email Verification</h3></div>

        <?php if ($ok): ?>
          <p class="muted" style="text-align:center">Redirecting to your dashboard…</p>
          <script>
            document.addEventListener('DOMContentLoaded', ()=>{
              Swal.fire({icon:'success', title:'Verified!', timer:1400, showConfirmButton:false})
                .then(()=>{ location.href = <?= json_encode($redirect) ?>; });
            });
          </script>
        <?php else: ?>
          <?php if ($token && $err): ?>
            <script>
              document.addEventListener('DOMContentLoaded', ()=>{
                Swal.fire({icon:'error', title:'Oops…', text: <?= json_encode($err) ?>});
              });
            </script>
          <?php endif; ?>

          <p class="muted" style="text-align:center;margin-bottom:12px">
            We sent a verification link to <strong><?= htmlspecialchars($email ?: 'your email') ?></strong>.
          </p>
          <div class="form-group" style="text-align:center;">
            <small class="muted">Didn’t receive it? Check Spam/Promotions.</small>
          </div>

          <div class="form-group" style="text-align:center;">
            <button class="inline-btn" id="resendBtn"><i class="fa-solid fa-paper-plane"></i> Resend email</button>
            <span id="resendCooldown" class="muted" style="display:none;">Try again in <span class="countdown" id="resendSec">60</span>s</span>
          </div>

          <div class="auth-switch-link" style="border:none;text-align:center;">
            <a href="<?= $BASE ?>auth/login"><i class="fa-solid fa-right-to-bracket"></i> Back to Login</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php if (!$ok): ?>
<script>
(function(){
  const btn   = document.getElementById('resendBtn');
  const cd    = document.getElementById('resendCooldown');
  const secEl = document.getElementById('resendSec');
  if (!btn) return;

  let busy = false, cooldown = 60;

  function startCooldown(){
    btn.style.display='none';
    cd.style.display='inline';
    secEl.textContent = cooldown;
    const t = setInterval(()=>{
      cooldown--; secEl.textContent = cooldown;
      if (cooldown<=0){ clearInterval(t); cd.style.display='none'; btn.style.display='inline'; cooldown=60; }
    },1000);
  }

  btn.addEventListener('click', async ()=>{
    if (busy) return;
    busy = true;
    try{
      const fd = new FormData();
      fd.append('email', <?= json_encode($email) ?>);
      const res = await fetch('<?= $BASE ?>api/resend-verification', { method:'POST', body: fd });
      const j = await res.json().catch(()=>({}));
      if (!res.ok || !j.ok) throw new Error(j.error || 'Failed to resend');
      Swal.fire({icon:'success', title:'Sent!', text:'We emailed you a new verification link.'});
      startCooldown();
    }catch(err){
      Swal.fire({icon:'error', title:'Cannot resend', text:String(err.message || err)});
    }finally{ busy = false; }
  });
})();
</script>
<?php endif; ?>
</body>
</html>
