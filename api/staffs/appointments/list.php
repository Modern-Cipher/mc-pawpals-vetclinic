<?php
// api/staffs/appointments/list.php
declare(strict_types=1);

require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);

require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json; charset=utf-8');

function isLeadVeterinarian(string $designation): bool {
    $designation = strtolower($designation);
    $negativeKeywords = ['assistant', 'receptionist', 'aide', 'technician', 'trainee', 'intern'];
    foreach ($negativeKeywords as $keyword) {
        if (str_contains($designation, $keyword)) return false;
    }
    $positiveKeywords = ['veterinarian', 'dvm', 'doctor'];
    foreach ($positiveKeywords as $keyword) {
        if (str_contains($designation, $keyword)) return true;
    }
    return false;
}

function jerr(string $msg = 'Server error', int $code = 500) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

try {
    $pdo = db();
    $staffId = (int)($_SESSION['user']['id'] ?? 0);
    if ($staffId <= 0) jerr('Unauthorized', 401);
    
    $stmt_profile = $pdo->prepare("SELECT designation FROM user_profiles WHERE user_id = ? LIMIT 1");
    $stmt_profile->execute([$staffId]);
    $profile = $stmt_profile->fetch();
    $designation = $profile['designation'] ?? '';
    $is_vet = isLeadVeterinarian($designation);

    // Updated to include new scopes
    $scope = $_GET['scope'] ?? 'pending';
    $scope = in_array($scope, ['pending', 'today', 'mine', 'cancelled', 'noshow'], true) ? $scope : 'pending';

    $where = '1=1';
    $params = [];

    if ($scope === 'pending') {
        $where = "a.status = 'Pending'";
    } elseif ($scope === 'today') {
        $where = "DATE(a.appointment_datetime) = CURDATE()";
    } elseif ($scope === 'mine') {
        if ($is_vet) {
            $where = "a.staff_id_assigned = :sid";
            $params[':sid'] = $staffId;
        } else {
            $where = "a.status = 'Confirmed'";
        }
    } elseif ($scope === 'cancelled') {
        $where = "a.status = 'Cancelled'";
    } elseif ($scope === 'noshow') {
        $where = "a.status = 'No-Show'";
    }

    $sql = "
      SELECT
        a.id, a.appointment_datetime, a.service, a.status, a.notes,
        a.staff_id_assigned,
        uo.id AS owner_id,
        CONCAT(uo.first_name, ' ', uo.last_name) AS owner_name,
        uo.email AS owner_email, uo.phone AS owner_phone,
        p.id AS pet_id, p.name AS pet_name,
        p.species AS pet_species, p.breed AS pet_breed, p.photo_path AS pet_photo_path,
        us.id AS vet_id,
        CONCAT(us.first_name, ' ', us.last_name) AS vet_name
      FROM appointments a
        INNER JOIN users uo ON uo.id = a.user_id
        INNER JOIN pets p ON p.id = a.pet_id
        LEFT JOIN users us ON us.id = a.staff_id_assigned
      WHERE $where
      ORDER BY a.updated_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll() ?: [];

    $BASE = base_path();
    $items = array_map(function ($r) use ($BASE) {
        return [
            'id' => (int)$r['id'],
            'appointment_datetime' => $r['appointment_datetime'],
            'service' => $r['service'],
            'status' => $r['status'],
            'notes' => $r['notes'],
            'owner_name' => $r['owner_name'],
            'owner_email' => $r['owner_email'],
            'owner_phone' => $r['owner_phone'],
            'pet_name' => $r['pet_name'],
            'pet_species' => $r['pet_species'],
            'pet_breed' => $r['pet_breed'],
            'pet_photo_url' => $r['pet_photo_path'] ? ($BASE . ltrim($r['pet_photo_path'], '/')) : null,
            'assigned_vet' => $r['vet_id'] ? ['id' => (int)$r['vet_id'], 'name' => trim($r['vet_name'])] : null,
        ];
    }, $rows);
    
    // KPI counts
    $kpi = [
        'pending' => (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Pending'")->fetchColumn(),
        'today' => (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_datetime)=CURDATE()")->fetchColumn(),
        'mine' => (int)$pdo->prepare("SELECT COUNT(*) FROM appointments WHERE staff_id_assigned = ?")->execute([$staffId]) ? (int)$pdo->query("SELECT ROW_COUNT()")->fetchColumn() : 0,
        'week' => (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE YEARWEEK(appointment_datetime, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn(),
    ];
    $stmtMine = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE staff_id_assigned = ?");
    $stmtMine->execute([$staffId]);
    $kpi['mine'] = (int)$stmtMine->fetchColumn();

    echo json_encode(['ok' => true, 'items' => $items, 'kpis' => $kpi]);
} catch (Throwable $e) {
    error_log('appointments/list staff error: ' . $e->getMessage());
    jerr();
}