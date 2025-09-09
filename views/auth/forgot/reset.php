<?php
// auth/reset/index.php (token reset via email link)

require_once __DIR__ . '/../../../config/connection.php';

$BASE  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$pdo   = db();

// Accept token from GET or POST (hidden input)
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$ok=false; $err='';

$isValidToken = false;
$pr = null;

if ($token) {
    $hash = hash('sha256', $token);
    $st = $pdo->prepare("SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL LIMIT 1");
    $st->execute([$hash]);
    $pr = $st->fetch();

    if ($pr && new DateTime() < new DateTime($pr['expires_at'])) {
        $isValidToken = true;
    } else {
        $err = "This reset link is invalid or has expired. Please request a new one.";
    }
} else {
    $err = "No reset token provided.";
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $isValidToken) {
  $p1   = $_POST['password'] ?? '';
  $p2   = $_POST['confirm']  ?? '';
  $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

  if ($p1 !== $p2) {
    $err = 'Passwords do not match.';
  } elseif (!preg_match($password_regex, $p1)) {
    $err = 'Password does not meet the requirements.';
  } else {
    $new_pwd_hash = password_hash($p1, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE email=?")
        ->execute([$new_pwd_hash, $pr['email']]);
    $pdo->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=?")
        ->execute([$pr['id']]);
    $ok = true;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Reset Password</title>
  <link rel="stylesheet" href="<?=$BASE?>assets/css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .rules { list-style:none; padding:0; font-size:.9rem; color: var(--text-medium); }
    .rules li.ok { color: var(--success-color, #0a7e51); font-weight: 600; }
  </style>
</head>
<body>
  <div class="auth-layout">
    <div class="auth-branding-panel">
        <div class="branding-content">
            <div class="logo">🐾 PawPals</div>
            <h1>Set a New Password</h1>
            <p>Your security is important. Choose a strong, new password for your account.</p>
        </div>
        <div class="copyright">&copy; <?= date('Y') ?> PawPals. All rights reserved.</div>
    </div>
    <div class="auth-form-panel">
      <div class="auth-card">
        <div class="form-header">
            <h3>Reset Your Password</h3>
        </div>

        <?php if($ok): ?>
          <!-- Success modal + redirect -->
          <script>
            document.addEventListener('DOMContentLoaded', function() {
              Swal.fire({
                icon:'success',
                title:'Success!',
                text:'Password changed successfully! You can now login.',
                confirmButtonText:'Go to Login'
              }).then(() => window.location.href = '<?=$BASE?>auth/login');
            });
          </script>
        <?php elseif(!$isValidToken): ?>
          <!-- Invalid/expired token modal -->
          <script>
            document.addEventListener('DOMContentLoaded', function() {
              Swal.fire({
                icon:'error',
                title:'Invalid or Expired Link',
                text:<?=json_encode($err)?>,
                confirmButtonText:'Request new link'
              }).then(() => window.location.href = '<?=$BASE?>auth/forgot');
            });
          </script>
          <noscript>
            <div class="error-message show"><?=htmlspecialchars($err)?></div>
            <div class="auth-switch-link" style="border:none; padding-top:1rem;">
              <p><a href="<?=$BASE?>auth/forgot">Request a new link</a></p>
            </div>
          </noscript>
        <?php else: ?>
          <?php if($err): ?>
            <!-- Form validation errors -->
            <script>
              document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({icon:'error', title:'Oops...', text:<?=json_encode($err)?>});
              });
            </script>
            <noscript><div class="error-message show"><?=htmlspecialchars($err)?></div></noscript>
          <?php endif; ?>

          <form method="post" id="tokenForm" action="<?=$BASE?>auth/reset?token=<?=urlencode($token)?>" novalidate>
            <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
            
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

            <button class="btn">Change Password</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

<script>
(function(){
  const $ = s => document.querySelector(s);
  const form = $('#tokenForm');
  if (!form) return;

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
})();
</script>
</body>
</html>
