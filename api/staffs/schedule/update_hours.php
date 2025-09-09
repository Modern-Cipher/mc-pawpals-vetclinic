<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';

header('Content-Type: application/json');

function jerr($msg) {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

try {
    $pdo = db();
    $data = json_decode(file_get_contents('php://input'), true);
    $staffId = (int)($_SESSION['user']['id'] ?? 0);

    if ($staffId === 0) {
        jerr('Invalid staff ID.');
    }

    if (!isset($data['hours']) || !is_array($data['hours'])) {
        jerr('Invalid data provided.');
    }

    $pdo->beginTransaction();

    // Prepare statement for insertion/update
    $stmt = $pdo->prepare("
        INSERT INTO staff_hours (staff_user_id, day_of_week, is_open, start_time, end_time)
        VALUES (:staff_user_id, :day_of_week, :is_open, :start_time, :end_time)
        ON DUPLICATE KEY UPDATE 
            is_open = VALUES(is_open), 
            start_time = VALUES(start_time), 
            end_time = VALUES(end_time)
    ");

    foreach ($data['hours'] as $hour) {
        $stmt->execute([
            ':staff_user_id' => $staffId,
            ':day_of_week' => (int)$hour['day_of_week'],
            ':is_open' => (int)$hour['is_open'],
            ':start_time' => $hour['start_time'],
            ':end_time' => $hour['end_time']
        ]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jerr('Server error: ' . $e->getMessage());
}
?>