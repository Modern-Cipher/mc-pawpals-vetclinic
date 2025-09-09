<?php
// api/staffs/pets/today_upcoming.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../middleware/auth.php';
header('Content-Type: application/json');

try {
    require_login(['staff']);
    $pdo = db();
    $staff_id = (int)($_SESSION['user']['id'] ?? 0);

    $todayStart = (new DateTime('today'))->format('Y-m-d 00:00:00');
    $todayEnd   = (new DateTime('today 23:59:59'))->format('Y-m-d H:i:s');
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

    // TODAY (Confirmed + assigned to current vet + today)
    $sqlToday = "SELECT 
                    p.id, p.name AS pet_name, p.photo_path,
                    CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS owner_name,
                    a.appointment_datetime
                 FROM appointments a
                 JOIN pets p  ON p.id = a.pet_id
                 JOIN users u ON u.id = p.user_id
                 WHERE a.status='Confirmed'
                   AND a.staff_id_assigned = :sid
                   AND a.appointment_datetime BETWEEN :ts AND :te
                 ORDER BY a.appointment_datetime ASC";
    $st = $pdo->prepare($sqlToday);
    $st->execute([':sid'=>$staff_id, ':ts'=>$todayStart, ':te'=>$todayEnd]);
    $today = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // UPCOMING (Confirmed + assigned to current vet + future)
    $sqlUpcoming = "SELECT 
                        p.id, p.name AS pet_name, p.photo_path,
                        CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS owner_name,
                        a.appointment_datetime
                    FROM appointments a
                    JOIN pets p  ON p.id = a.pet_id
                    JOIN users u ON u.id = p.user_id
                    WHERE a.status='Confirmed'
                      AND a.staff_id_assigned = :sid
                      AND a.appointment_datetime > :after
                    ORDER BY a.appointment_datetime ASC
                    LIMIT 200";
    $su = $pdo->prepare($sqlUpcoming);
    $su->execute([':sid'=>$staff_id, ':after'=>$todayEnd]);
    $upcoming = $su->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $fmt = function(array $rows) use ($base): array {
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'          => (int)$r['id'],
                'pet_name'    => $r['pet_name'],
                'owner_name'  => $r['owner_name'],
                'photo_url'   => $r['photo_path'] ? ($base . ltrim($r['photo_path'], '/')) : null,
                'status_badge'=> 'confirmed',
                'time_text'   => date('M j, Y g:i A', strtotime($r['appointment_datetime'])),
            ];
        }
        return $out;
    };

    echo json_encode(['ok'=>true, 'today'=>$fmt($today), 'upcoming'=>$fmt($upcoming)]);
} catch (Throwable $e) {
    error_log('[API staff/pets/today_upcoming] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Server error while listing today/upcoming.']);
}
