<?php
// api/staffs/schedule/hours.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../app/models/Schedule.php';

header('Content-Type: application/json');

function jerr($msg) {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

try {
    $staffId = (int)($_SESSION['user']['id'] ?? 0);
    if ($staffId === 0) {
        jerr('Invalid staff ID.');
    }

    $hours = App\Models\Schedule::getStaffHours($staffId);
    echo json_encode(['ok' => true, 'items' => $hours]);
} catch (Throwable $e) {
    jerr('Server error: ' . $e->getMessage());
}