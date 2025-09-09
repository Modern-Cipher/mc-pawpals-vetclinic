<?php
// api/staffs/medical/history.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

try {
    require_login(['staff']);
    $pdo = db();
    $staff_id = (int)($_SESSION['user']['id'] ?? 0);
    $pet_id = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : null;

    $base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
    $response = [];

    // Case 1: Get list of MY completed appointments for the left panel
    if (isset($_GET['list']) && $_GET['list'] === 'mine') {
        $sql = "SELECT 
                    p.id,
                    p.name AS pet_name,
                    p.photo_path,
                    a.id as appointment_id,
                    CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS owner_name,
                    a.appointment_datetime
                FROM appointments a
                JOIN pets p ON p.id = a.pet_id
                JOIN users u ON u.id = p.user_id
                WHERE a.status = 'Completed'
                  AND a.staff_id_assigned = :sid
                ORDER BY a.appointment_datetime DESC
                LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':sid' => $staff_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($history as &$row) {
            $row['photo_url'] = $row['photo_path'] ? ($base_url . ltrim($row['photo_path'], '/')) : null;
            $row['time_text'] = date('M j, Y', strtotime($row['appointment_datetime']));
        }
        $response['history'] = $history;
    }
    // Case 2: Get SOAP history for a specific pet for the right panel
    elseif ($pet_id) {
        $sql = "SELECT 
                    mr.*,
                    CONCAT(COALESCE(s.first_name,''),' ',COALESCE(s.last_name,'')) AS staff_name
                FROM medical_records mr
                JOIN users s ON s.id = mr.staff_id
                WHERE mr.pet_id = :pid
                ORDER BY mr.record_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pid' => $pet_id]);
        $response['records'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid request parameters.']);
        exit;
    }

    echo json_encode(['ok' => true] + $response);

} catch (Throwable $e) {
    error_log('[API staff/medical/history] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error while fetching history.']);
}