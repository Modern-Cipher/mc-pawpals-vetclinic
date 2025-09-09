<?php
// views/auth/forgot/process_forgot.php
// This is a dedicated processing file to isolate the logic.

ob_start();
session_start();
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/services/Mailer.php';

use App\Services\Mailer;

$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If accessed directly, just redirect away.
    header('Location: ' . $BASE . 'auth/forgot');
    exit;
}

// Trim all inputs to be safe
$email = trim($_POST['email'] ?? '');
$method = trim($_POST['method'] ?? 'link');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $BASE . 'auth/forgot?error=invalid_email');
    exit;
}

try {
    $pdo = db();
    $st = $pdo->prepare("SELECT id, first_name, username, email FROM users WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $user = $st->fetch();

    if ($user) {
        if ($method === 'otp') {
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash = password_hash($code, PASSWORD_DEFAULT);
            $ttlMin = 10;
            $exp = (new DateTime("+{$ttlMin} minutes"))->format('Y-m-d H:i:s');
            
            $pdo->prepare("INSERT INTO otp_codes (user_id, email, purpose, code_hash, expires_at) VALUES (?, ?, ?, ?, ?)")
                ->execute([$user['id'], $user['email'], 'reset', $hash, $exp]);

            $mailer = new Mailer();
            $sent = $mailer->sendOTP($user['email'], $user['first_name'] ?? $user['username'] ?? '', $code, $ttlMin);

            if ($sent) {
                header('Location: ' . $BASE . 'auth/forgot/otp?email=' . urlencode($user['email']));
                exit;
            } else {
                header('Location: ' . $BASE . 'auth/forgot?error=email_failed');
                exit;
            }
        } else { // 'link' method
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $ttlMin = 30;
            $exp = (new DateTime("+{$ttlMin} minutes"))->format('Y-m-d H:i:s');
            $pdo->prepare("INSERT INTO password_resets (user_id, email, token_hash, expires_at) VALUES (?, ?, ?, ?)")
                ->execute([$user['id'], $user['email'], $hash, $exp]);

            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://");
            $host = $_SERVER['HTTP_HOST'];
            $resetUrl = $protocol . $host . $BASE . 'auth/reset?token=' . $token;
            
            $mailer = new Mailer();
            $mailer->sendReset($user['email'], $user['first_name'] ?? $user['username'] ?? '', $resetUrl, $ttlMin);
        }
    }
    
    // For both link method and non-existent users, show the same confirmation page.
    header('Location: ' . $BASE . 'auth/forgot?notice=sent');
    exit;

} catch (Throwable $e) {
    error_log("Forgot Password Fatal Error: " . $e->getMessage());
    header('Location: ' . $BASE . 'auth/forgot?error=server');
    exit;
}