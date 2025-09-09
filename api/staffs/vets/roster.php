<?php
// api/staffs/vets/roster.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff', 'admin']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

// Function to determine if a user is a lead vet
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

try {
    $pdo = db();
    $staff_id = (int)($_SESSION['user']['id'] ?? 0);

    // Get the current user's fresh designation directly from the database
    $stmt_profile = $pdo->prepare("SELECT designation FROM user_profiles WHERE user_id = ? LIMIT 1");
    $stmt_profile->execute([$staff_id]);
    $profile = $stmt_profile->fetch();
    $designation = $profile['designation'] ?? '';

    $is_vet = isLeadVeterinarian($designation);

    $params = [];
    $baseSql = "
      SELECT
        u.id,
        CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS name,
        up.designation
      FROM users u
      JOIN user_profiles up ON u.id = up.user_id
      WHERE
        u.role = 'staff'
        AND u.is_active = 1
        AND (up.designation LIKE '%Veterinarian%' OR up.designation LIKE '%Vet%')
    ";

    // If the user IS a vet, only fetch their own profile.
    // Otherwise, fetch all vets.
    if ($is_vet) {
        $finalSql = $baseSql . " AND u.id = ?";
        $params[] = $staff_id;
    } else {
        $finalSql = $baseSql . " ORDER BY name ASC";
    }

    $stmt = $pdo->prepare($finalSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll() ?: [];

    $vets = array_map(function($r) {
        return [
            'id' => (int)$r['id'],
            'name' => trim($r['name']) ?: 'Staff',
            'designation' => $r['designation'] ?: null,
        ];
    }, $rows);

    echo json_encode(['ok' => true, 'vets' => $vets]);

} catch (Throwable $e) {
    error_log("Error in /api/staffs/vets/roster.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}