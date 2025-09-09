<?php
// api/appointments/list_mine.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['user']);
require_once __DIR__ . '/../../config/connection.php';
header('Content-Type: application/json');

$user_id = (int)($_SESSION['user']['id'] ?? 0);
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.service,
            a.appointment_datetime,
            a.status,
            a.notes,
            p.id AS pet_id,
            p.name AS pet_name,
            p.photo_path,
            CONCAT(vet.first_name, ' ', vet.last_name) AS vet_name
        FROM appointments a
        JOIN pets p ON a.pet_id = p.id
        LEFT JOIN users vet ON a.staff_id_assigned = vet.id
        WHERE a.user_id = ?
          AND a.status IN ('Pending','Confirmed','Completed','Cancelled','No-Show')
        ORDER BY a.appointment_datetime ASC
    ");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll() ?: [];

    $events = [];
    foreach ($rows as $r) {
        $color = '#64748b'; // default slate
        $className = 'fc-event-completed';

        switch ($r['status']) {
            case 'Pending':   $color = '#f59e0b'; $className = 'fc-event-pending'; break;
            case 'Confirmed': $color = '#10b981'; $className = 'fc-event-confirmed'; break;
            case 'Cancelled': $color = '#ef4444'; $className = 'fc-event-cancelled'; break;
            case 'No-Show':   $color = '#9ca3af'; $className = 'fc-event-noshow'; break;
        }

        $photo_url = !empty($r['photo_path']) ? ($BASE . ltrim($r['photo_path'], '/')) : null;

        $events[] = [
            'id'    => (int)$r['id'],
            'title' => $r['service'] . ' for ' . $r['pet_name'],
            'start' => $r['appointment_datetime'],
            'color' => $color,
            'className' => $className,
            'extendedProps' => [
                'service'       => $r['service'],
                'status'        => $r['status'],
                'notes'         => $r['notes'],
                'pet_id'        => (int)$r['pet_id'],
                'pet_name'      => $r['pet_name'],
                'pet_photo_url' => $photo_url,
                'assigned_vet'  => trim($r['vet_name']) ?: null,
            ],
        ];
    }

    echo json_encode($events);
} catch (Throwable $e) {
    error_log("List My Appointments Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([]);
}