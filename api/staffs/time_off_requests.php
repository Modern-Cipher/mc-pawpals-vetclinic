<?php
// api/staffs/time_off_requests.php
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
    $requests = \App\Models\Schedule::getAllTimeOffRequests();
    echo json_encode(['ok' => true, 'items' => $requests]);
} catch (Throwable $e) {
    jerr('Server error: ' . $e->getMessage());
}
?>