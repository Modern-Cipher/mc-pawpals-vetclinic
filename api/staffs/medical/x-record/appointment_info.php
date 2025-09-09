<?php
// api/staffs/medical/appointment_info.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

try {
    $appointment_id = (int)($_GET['appointment_id'] ?? 0);
    $staff_id = (int)($_SESSION['user']['id'] ?? 0);
    if ($appointment_id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'appointment_id is required']);
        exit;
    }

    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.appointment_datetime,
            a.status,
            p.id AS pet_id,
            p.name AS pet_name,
            p.species,
            p.breed,
            p.photo_path,
            CONCAT(u.first_name,' ',u.last_name) AS owner_name
        FROM appointments a
        JOIN pets p ON p.id = a.pet_id
        JOIN users u ON u.id = p.user_id
        WHERE a.id = :aid AND a.staff_id_assigned = :sid
        LIMIT 1
    ");
    $stmt->execute([':aid' => $appointment_id, ':sid' => $staff_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Appointment not found or not assigned to you']);
        exit;
    }

    $BASE = base_path();
    $row['pet_photo_url'] = $row['photo_path'] ? ($BASE . ltrim($row['photo_path'], '/')) : null;

    echo json_encode(['ok' => true, 'item' => $row]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("appointment_info error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}
