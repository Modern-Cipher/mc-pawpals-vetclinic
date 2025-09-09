<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/connection.php';
// TAMA: Idagdag ang require_once
require_once __DIR__ . '/../../app/models/Pet.php';
header('Content-Type: application/json');

if (empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$uid  = (int)$_SESSION['user']['id'];
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

try {
    // TAMA: Gamitin ang buong namespace \App\Models\Pet
    $rows = \App\Models\Pet::listByUser($uid);

    foreach ($rows as &$r) {
        $r['photo_url'] = $r['photo_path'] ? ($BASE . ltrim($r['photo_path'], '/')) : null;
    }

    echo json_encode(['ok' => true, 'pets' => $rows]);
} catch (Throwable $e) {
    error_log('[pets/list] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}