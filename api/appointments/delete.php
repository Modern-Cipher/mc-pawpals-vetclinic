<?php
// api/appointments/delete.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['user']);
require_once __DIR__ . '/../../config/connection.php';
header('Content-Type: application/json');

$user_id = (int)($_SESSION['user']['id'] ?? 0);
$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing appointment id.']);
    exit;
}

try {
    $pdo = db();
    // Only Pending can be deleted by owner
    $st = $pdo->prepare("DELETE FROM appointments WHERE id=? AND user_id=? AND status='Pending'");
    $st->execute([$id, $user_id]);
    if ($st->rowCount() < 1) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'Only your pending appointments can be deleted.']);
        exit;
    }
    echo json_encode(['ok' => true, 'message' => 'Appointment deleted.']);
} catch (Throwable $e) {
    error_log("Appointment Delete Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error occurred.']);
}
