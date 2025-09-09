<?php
// api/staffs/medical/appointment_meta.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $appointment_id = (int)($_GET['appointment_id'] ?? 0);
    if ($appointment_id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'appointment_id is required']);
        exit;
    }

    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT 
          a.id, a.appointment_datetime, a.status,
          p.id AS pet_id, p.name AS pet_name, p.species, p.breed, p.photo_path,
          CONCAT(u.first_name,' ',u.last_name) AS owner_name
        FROM appointments a
        JOIN pets p ON p.id = a.pet_id
        JOIN users u ON u.id = p.user_id
        WHERE a.id = ?
        LIMIT 1
    ");
    $stmt->execute([$appointment_id]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok'=>false,'error'=>'Appointment not found']);
        exit;
    }

    // check existing record for this appointment
    $rec = $pdo->prepare("SELECT id, staff_id FROM medical_records WHERE appointment_id=? LIMIT 1");
    $rec->execute([$appointment_id]);
    $recRow = $rec->fetch();

    $BASE = base_path();
    $photo_url = $row['photo_path'] ? ($BASE . ltrim($row['photo_path'], '/')) : null;

    echo json_encode([
        'ok'         => true,
        'owner_name' => $row['owner_name'],
        'appt_dt'    => $row['appointment_datetime'],
        'appt_human' => date('M j, Y, g:i A', strtotime($row['appointment_datetime'])),
        'status'     => $row['status'],
        'has_record' => $recRow ? true : false,
        'record_id'  => $recRow ? (int)$recRow['id'] : null,
        'pet' => [
            'id'        => (int)$row['pet_id'],
            'name'      => $row['pet_name'],
            'species'   => $row['species'],
            'breed'     => $row['breed'],
            'photo_url' => $photo_url
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[appointment_meta] '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'Server error']);
}
