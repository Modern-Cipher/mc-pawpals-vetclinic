<?php
// api/check-session.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/connection.php';
header('Content-Type: application/json');

$response = ['status' => 'ok'];

if (empty($_SESSION['user']['id'])) {
    $response['status'] = 'logged_out';
    echo json_encode($response);
    exit;
}

try {
    $pdo = db();
    $uid = (int)$_SESSION['user']['id'];

    $stmt = $pdo->prepare("SELECT active_session_id FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $current_sid = $stmt->fetchColumn();

    if ($current_sid !== session_id()) {
        $response['status'] = 'conflict';
        require_once __DIR__ . '/../middleware/auth.php';
        logout_now(false);
        echo json_encode($response);
        exit;
    }

    $last_check_time = $_SESSION['last_alert_check'] ?? time();
    $last_check_datetime = date('Y-m-d H:i:s', $last_check_time);

    $attempt_stmt = $pdo->prepare(
        "SELECT status FROM login_attempts WHERE user_id = ? AND attempted_at > ? ORDER BY attempted_at DESC LIMIT 1"
    );
    $attempt_stmt->execute([$uid, $last_check_datetime]);
    
    if ($attempt = $attempt_stmt->fetch()) {
        $response['status'] = 'attempt_detected';
    }
    
    $_SESSION['last_alert_check'] = time();

} catch (Throwable $e) {
    $response['status'] = 'error';
    $response['error'] = 'Database error.';
    error_log('Check-session error: ' . $e->getMessage());
}

echo json_encode($response);