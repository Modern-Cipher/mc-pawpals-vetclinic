<?php
// api/staffs/medical/get_pet_details.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

try {
    $pet_id = (int)($_GET['pet_id'] ?? 0);
    $staff_id = (int)($_SESSION['user']['id'] ?? 0);

    if (!$pet_id) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Pet ID is required.']);
        exit;
    }

    $pdo = db();
    
    // Find eligible appointments (Confirmed and assigned to current staff)
    $stmt = $pdo->prepare("
        SELECT id, appointment_datetime
        FROM appointments
        WHERE pet_id = :pet_id
          AND staff_id_assigned = :staff_id
          AND status = 'Confirmed'
        ORDER BY appointment_datetime DESC
    ");
    $stmt->execute([':pet_id' => $pet_id, ':staff_id' => $staff_id]);
    $eligible_appointments = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'eligible_appointments' => $eligible_appointments]);

} catch (Throwable $e) {
    http_response_code(500);
    error_log("Get pet details failed: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Server error.']);
}