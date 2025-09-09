<?php
// api/staffs/pets/all.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../middleware/auth.php';
header('Content-Type: application/json');

try {
    require_login(['staff']);
    $pdo = db();

    $sql = "SELECT 
                p.id,
                p.name AS pet_name,
                p.species,
                p.breed,
                p.color,
                p.microchip_no,
                p.photo_path,
                CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS owner_name
            FROM pets p
            JOIN users u ON u.id = p.user_id
            WHERE p.deleted_at IS NULL
            ORDER BY p.created_at DESC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
    foreach ($rows as &$r) {
        $r['photo_url'] = $r['photo_path'] ? ($base . ltrim($r['photo_path'], '/')) : null;
    }

    echo json_encode(['ok' => true, 'pets' => $rows]);
} catch (Throwable $e) {
    error_log('[API staff/pets/all] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Server error while listing pets.']);
}
