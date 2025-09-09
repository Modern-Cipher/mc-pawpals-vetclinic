<?php
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

    $timeOff = App\Models\Schedule::getStaffTimeOff($staffId);
    echo json_encode(['ok' => true, 'items' => $timeOff]);
} catch (Throwable $e) {
    jerr('Server error: ' . $e->getMessage());
}