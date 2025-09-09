<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/Mailer.php';
require_once __DIR__ . '/../middleware/auth.php';
require_login(['admin']);

use App\Services\Mailer;

$to = $_GET['to'] ?? null;
if (!$to) {
  // default to active admin email (your account)
  $pdo = db();
  $to = $pdo->query("SELECT email FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 'menongdc@gmail.com';
}
$ok = (new Mailer())->send($to, 'Admin', 'Test mail', '<p>Hello from PawPals email config ✅</p>');
header('Content-Type: text/plain');
echo $ok ? "Sent to $to" : "Failed";
