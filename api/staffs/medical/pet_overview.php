<?php
// api/staffs/medical/pet_overview.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

try {
    require_login(['staff']);
    $pdo = db();
    $staff_id = (int)($_SESSION['user']['id'] ?? 0);
    $pet_id = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;
    if (!$pet_id) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Pet ID is required.']); exit; }

    $base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

    // Get more pet details for the "pro" header
    $stmt = $pdo->prepare("
        SELECT p.*, u.id as owner_id, u.first_name as owner_first_name, u.last_name as owner_last_name 
        FROM pets p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.deleted_at IS NULL
    ");
    $stmt->execute([$pet_id]);
    $pet_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pet_data) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'Pet not found.']); exit; }

    $pet_data['photo_url'] = $pet_data['photo_path'] ? ($base_url . ltrim($pet_data['photo_path'], '/')) : null;

    // Get Pet Stats
    $stats = [];
    $stats['age'] = 'N/A';
    if ($pet_data['birthdate']) {
        $birthDate = new DateTime($pet_data['birthdate']);
        $age = (new DateTime())->diff($birthDate);
        $stats['age'] = ($age->y > 0 ? $age->y . 'y ' : '') . ($age->m >= 0 ? $age->m . 'm' : '');
        if ($age->y == 0 && $age->m == 0) $stats['age'] = '0m';
    }
    $stmt_visits = $pdo->prepare("SELECT COUNT(id) as total_visits, MAX(appointment_datetime) as last_visit FROM appointments WHERE pet_id = ? AND status = 'Completed'");
    $stmt_visits->execute([$pet_id]);
    $visit_data = $stmt_visits->fetch(PDO::FETCH_ASSOC);
    $stats['total_visits'] = (int)$visit_data['total_visits'];
    $stats['last_visit_human'] = $visit_data['last_visit'] ? date('M j, Y', strtotime($visit_data['last_visit'])) : 'N/A';

    // Permission check for edit/delete buttons
    $stmt_perm = $pdo->prepare("SELECT id FROM appointments WHERE pet_id = :pet_id AND staff_id_assigned = :staff_id AND status IN ('Confirmed', 'Completed') ORDER BY appointment_datetime DESC LIMIT 1");
    $stmt_perm->execute([':pet_id' => $pet_id, ':staff_id' => $staff_id]);
    $appt_for_form = $stmt_perm->fetch(PDO::FETCH_ASSOC);
    $stats['has_permission_to_edit'] = !empty($appt_for_form);
    $stats['appointment_id_for_form'] = $appt_for_form['id'] ?? null;

    echo json_encode(['ok' => true,
        'pet' => $pet_data, // Send all pet data
        'owner' => [ 'id' => (int)$pet_data['owner_id'], 'full_name' => trim($pet_data['owner_first_name'] . ' ' . $pet_data['owner_last_name']) ],
        'stats' => $stats
    ]);

} catch (Throwable $e) {
    error_log('[API staff/pet_overview] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error while fetching pet overview.']);
}