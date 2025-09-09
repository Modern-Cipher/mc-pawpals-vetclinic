<?php
// api/users/medical-summary.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['user']);
require_once __DIR__ . '/../../config/connection.php';
header('Content-Type: application/json');

$pdo = db();
$user_id = (int)($_SESSION['user']['id'] ?? 0);
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$BASE = preg_replace('/api\/users\/?$/', '', $BASE);
$today = date('Y-m-d');

try {
    // --- 1. Fetch Upcoming Reminders ---
    $reminders_query = "
        (SELECT p.id as pet_id, p.name as pet_name, p.photo_path, pv.next_due_date as due_date, CONCAT('Vaccination: ', pv.vaccine_name) as type FROM pet_vaccinations pv JOIN pets p ON pv.pet_id = p.id WHERE p.user_id = :user_id1 AND pv.next_due_date >= :today1)
        UNION ALL
        (SELECT p.id as pet_id, p.name as pet_name, p.photo_path, pd.next_due_date as due_date, CONCAT('Deworming: ', pd.product_name) as type FROM pet_deworming pd JOIN pets p ON pd.pet_id = p.id WHERE p.user_id = :user_id2 AND pd.next_due_date >= :today2)
        UNION ALL
        (SELECT p.id as pet_id, p.name as pet_name, p.photo_path, pp.next_due_date as due_date, CONCAT('Prevention: ', pp.product_name) as type FROM pet_parasite_preventions pp JOIN pets p ON pp.pet_id = p.id WHERE p.user_id = :user_id3 AND pp.next_due_date >= :today3)
        ORDER BY due_date ASC
        LIMIT 10
    ";
    $stmt_reminders = $pdo->prepare($reminders_query);
    $stmt_reminders->execute([
        ':user_id1' => $user_id, ':today1' => $today,
        ':user_id2' => $user_id, ':today2' => $today,
        ':user_id3' => $user_id, ':today3' => $today,
    ]);
    $reminders = $stmt_reminders->fetchAll(PDO::FETCH_ASSOC);

    // --- 2. Fetch Recent Health Records (Get all SOAP fields) ---
    $recent_query = "
        SELECT p.name as pet_name, p.photo_path, mr.id, mr.record_date, 'Consultation' as type, 
               mr.assessment as details, mr.subjective, mr.objective, mr.assessment, mr.plan
        FROM medical_records mr
        JOIN pets p ON mr.pet_id = p.id
        WHERE p.user_id = :user_id AND mr.assessment IS NOT NULL AND mr.assessment != ''
        ORDER BY mr.record_date DESC
        LIMIT 10
    ";
    $stmt_recent = $pdo->prepare($recent_query);
    $stmt_recent->execute([':user_id' => $user_id]);
    $recent_records_raw = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

    $recent_records = [];
    foreach ($recent_records_raw as $rec) {
        $recent_records[] = [
            'id' => $rec['id'],
            'pet_name' => $rec['pet_name'],
            'photo_path' => $rec['photo_path'],
            'record_date' => $rec['record_date'],
            'type' => $rec['type'],
            'details' => $rec['details'],
            'full_details' => [
                'subjective' => $rec['subjective'],
                'objective' => $rec['objective'],
                'assessment' => $rec['assessment'],
                'plan' => $rec['plan'],
            ]
        ];
    }
    
    // Add base path to photo URLs
    $url_formatter = function(&$row) use ($BASE) {
        $row['pet_photo_url'] = $row['photo_path'] ? $BASE . ltrim($row['photo_path'], '/') : null;
    };
    foreach ($reminders as &$r) { $url_formatter($r); }
    foreach ($recent_records as &$r) { $url_formatter($r); }

    echo json_encode([
        'ok' => true,
        'reminders' => $reminders,
        'recent_records' => $recent_records
    ]);

} catch (Throwable $e) {
    error_log('[API users/medical-summary] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error while fetching health summary.']);
}