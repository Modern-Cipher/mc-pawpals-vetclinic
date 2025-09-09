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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jerr('Invalid request method.');
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $date = $data['date'] ?? null;
    $reason = $data['reason'] ?? null;
    $staffId = (int)($_SESSION['user']['id'] ?? 0);

    if (empty($date)) {
        jerr('Date is required');
    }

    if ($staffId === 0) {
        jerr('Invalid staff ID.');
    }

    $pdo = db();
    $query = "INSERT INTO staff_time_off (staff_user_id, date, reason) VALUES (:staff_user_id, :date, :reason)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':staff_user_id', $staffId, PDO::PARAM_INT);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':reason', $reason);
    $stmt->execute();

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    jerr('Server error: ' . $e->getMessage());
}
?>