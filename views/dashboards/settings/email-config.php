<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../../config/connection.php';

$pdo = db();
$BASE = base_path();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $d = [
    'provider'   => trim($_POST['provider'] ?? 'gmail'),
    'smtp_host'  => trim($_POST['smtp_host'] ?? 'smtp.gmail.com'),
    'smtp_port'  => (int)($_POST['smtp_port'] ?? 587),
    'smtp_secure'=> in_array($_POST['smtp_secure'] ?? 'tls', ['tls','ssl']) ? $_POST['smtp_secure'] : 'tls',
    'smtp_user'  => trim($_POST['smtp_user'] ?? ''),
    'smtp_pass_enc' => trim($_POST['smtp_pass'] ?? ''),  // store app password here
    'from_email' => trim($_POST['from_email'] ?? ''),
    'from_name'  => trim($_POST['from_name'] ?? 'PawPals'),
  ];
  // upsert: deactivate others, then insert new active row
  $pdo->exec("UPDATE email_configs SET is_active=0");
  $sql = "INSERT INTO email_configs(provider,smtp_host,smtp_port,smtp_secure,smtp_user,smtp_pass_enc,from_email,from_name,is_active)
          VALUES(:provider,:smtp_host,:smtp_port,:smtp_secure,:smtp_user,:smtp_pass_enc,:from_email,:from_name,1)";
  $pdo->prepare($sql)->execute($d);
  $msg = 'Saved SMTP configuration.';
}

$cur = $pdo->query("SELECT * FROM email_configs WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch();
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Email Configuration</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
</head><body class="p-4">
  <h3>Email configuration (hidden)</h3>
  <?php if ($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif;?>
  <form method="post" class="row g-3">
    <div class="col-md-3"><label class="form-label">Provider</label>
      <input class="form-control" name="provider" value="<?=htmlspecialchars($cur['provider']??'gmail')?>"></div>
    <div class="col-md-4"><label class="form-label">SMTP Host</label>
      <input class="form-control" name="smtp_host" value="<?=htmlspecialchars($cur['smtp_host']??'smtp.gmail.com')?>"></div>
    <div class="col-md-2"><label class="form-label">Port</label>
      <input class="form-control" name="smtp_port" type="number" value="<?=htmlspecialchars($cur['smtp_port']??587)?>"></div>
    <div class="col-md-3"><label class="form-label">Secure</label>
      <select class="form-select" name="smtp_secure">
        <?php $sec=$cur['smtp_secure']??'tls'; ?>
        <option value="tls" <?=$sec==='tls'?'selected':''?>>TLS (587)</option>
        <option value="ssl" <?=$sec==='ssl'?'selected':''?>>SSL (465)</option>
      </select>
    </div>
    <div class="col-md-6"><label class="form-label">SMTP User (Gmail address)</label>
      <input class="form-control" name="smtp_user" value="<?=htmlspecialchars($cur['smtp_user']??'')?>"></div>
    <div class="col-md-6"><label class="form-label">App Password (16-char)</label>
      <input class="form-control" name="smtp_pass" value="<?=htmlspecialchars($cur['smtp_pass_enc']??'')?>">
      <div class="form-text">Gmail → Security → App passwords → generate 16-character code.</div>
    </div>
    <div class="col-md-6"><label class="form-label">From Email</label>
      <input class="form-control" name="from_email" value="<?=htmlspecialchars($cur['from_email']??'')?>"></div>
    <div class="col-md-6"><label class="form-label">From Name</label>
      <input class="form-control" name="from_name" value="<?=htmlspecialchars($cur['from_name']??'PawPals')?>"></div>
    <div class="col-12">
      <button class="btn btn-success">Save</button>
      <a class="btn btn-secondary" href="<?=$BASE?>api/mail-test">Send test</a>
    </div>
  </form>
</body></html>
