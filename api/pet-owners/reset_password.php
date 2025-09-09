<?php
// api/pet-owners/reset_password.php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/services/Mailer.php';

use App\Services\Mailer;

header('Content-Type: application/json');

function site_base(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $root = preg_replace('~/api(?:/.*)?$~', '', $path);
    return "{$scheme}://{$host}{$root}/";
}
function fail($msg = 'Server error'){ echo json_encode(['ok'=>false,'error'=>$msg]); exit; }

try {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid <= 0) fail('Invalid User ID');

    $pdo = db();
    $st  = $pdo->prepare("SELECT id,email,username,first_name,last_name FROM users WHERE id=? AND role='user' LIMIT 1");
    $st->execute([$uid]);
    $u = $st->fetch();
    if (!$u) fail('Pet owner not found.');

    // Generate & save temp password
    $tmp  = bin2hex(random_bytes(4)) . '@' . random_int(10,99);
    $hash = password_hash($tmp, PASSWORD_BCRYPT);

    $pdo->beginTransaction();
    $up = $pdo->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE id=?");
    $up->execute([$hash, $uid]);
    $flag_stmt = $pdo->prepare(
        "INSERT INTO user_security_flags (user_id, must_change_password) VALUES (?, 1)
         ON DUPLICATE KEY UPDATE must_change_password = 1"
    );
    $flag_stmt->execute([$uid]);
    $pdo->commit();

    // Respond to client immediately para hindi mag-antay ang admin
    ignore_user_abort(true);
    echo json_encode(['ok'=>true]);
    flush();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // --- GAGAMITIN NA ANG BAGONG MAILER FUNCTION ---
    $loginUrl = site_base() . 'auth/login';
    $mailer = new Mailer();
    $mailer->sendOwnerPasswordReset(
        $u['email'],
        $u['first_name'] . ' ' . $u['last_name'],
        $u['username'],
        $tmp,
        $loginUrl
    );
    // --- END ---

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Pet Owner PWD Reset Error: " . $e->getMessage());
    if (!headers_sent()) fail();
}