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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jerr('Invalid request method.');
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;
    $staffId = (int)($_SESSION['user']['id'] ?? 0);

    if (empty($id)) {
        jerr('ID is required');
    }

    if ($staffId === 0) {
        jerr('Invalid staff ID.');
    }

    $pdo = db();
    $query = "DELETE FROM staff_time_off WHERE id = :id AND staff_user_id = :staff_user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':staff_user_id', $staffId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    jerr('Server error: ' . $e->getMessage());
}
?>