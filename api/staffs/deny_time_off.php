<?php
// api/staffs/deny_time_off.php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/models/Schedule.php';

header('Content-Type: application/json');

function jerr($msg) {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jerr('Invalid request method.');
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;
    $adminId = $_SESSION['user']['id'] ?? 0;

    if (empty($id)) {
        jerr('ID is required');
    }

    if ($adminId === 0) {
        jerr('Invalid admin ID.');
    }

    $success = \App\Models\Schedule::updateTimeOffStatus((int)$id, 'denied', $adminId);

    if (!$success) {
        jerr('Failed to update request status.');
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    jerr('Server error: ' . $e->getMessage());
}
?>