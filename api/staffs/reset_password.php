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
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $root = preg_replace('~/api(?:/.*)?$~', '', $path);
    return "{$scheme}://{$host}{$root}/";
}
function fail(){ echo json_encode(['ok'=>false,'error'=>'Server error']); exit; }

try {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid user ID.']);
        exit;
    }

    $pdo = db();
    $st  = $pdo->prepare("SELECT id,email,username,first_name,last_name FROM users WHERE id=? AND role='staff' LIMIT 1");
    $st->execute([$uid]);
    $u = $st->fetch();
    if (!$u) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Staff user not found.']);
        exit;
    }

    // Begin transaction for safety
    $pdo->beginTransaction();

    // Generate & save temp password
    $tmp  = bin2hex(random_bytes(4)) . '@' . random_int(10,99);
    $hash = password_hash($tmp, PASSWORD_BCRYPT);
    
    // 1. Update ONLY the password in the main users table
    $up = $pdo->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE id=? LIMIT 1");
    $up->execute([$hash, $uid]);

    // 2. Add/update the flag in the user_security_flags table
    // This will insert a row if it doesn't exist, or update it if it does.
    $flag_stmt = $pdo->prepare(
        "INSERT INTO user_security_flags (user_id, must_change_password) VALUES (?, 1)
         ON DUPLICATE KEY UPDATE must_change_password = 1"
    );
    $flag_stmt->execute([$uid]);

    // Commit the changes if both queries were successful
    $pdo->commit();

    // Respond to client immediately
    ignore_user_abort(true);
    $payload = json_encode(['ok'=>true]);
    header('Connection: close');
    header('Content-Length: '.strlen($payload));
    echo $payload;
    flush();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // Send the email
    $loginUrl = site_base() . 'auth/login';
    $mailer = new Mailer();
    $sent = $mailer->sendTemplate('staff_welcome', $u['email'], $u['first_name'].' '.$u['last_name'], [
        'first_name'    => $u['first_name'],
        'username'      => $u['username'],
        'temp_password' => $tmp,
        'login_url'     => $loginUrl,
    ]);

    // Fallback in case template sending fails (optional, but good practice)
    if (!$sent) {
        error_log("Failed to send 'staff_welcome' template for user ID {$uid}. Sending fallback.");
        $html = "<h2>Your temporary password</h2>
                 <p>Username: <b>{$u['username']}</b></p>
                 <p>Password: <b>{$tmp}</b></p>
                 <p><a href='{$loginUrl}' style='background:#16a34a;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none'>Log in</a></p>";
        $mailer->send($u['email'], $u['first_name'].' '.$u['last_name'], 'Your temporary password', $html);
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Password Reset Error: " . $e->getMessage());
    if (!headers_sent()) fail();
}