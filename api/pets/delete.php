<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
// TAMA: Siguraduhin na included ito
require_once __DIR__ . '/../../app/models/Pet.php';
header('Content-Type: application/json');

require_login(['user']);
$uid = (int)($_SESSION['user']['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    // TAMA: Gamitin ang buong namespace \App\Models\Pet
    $result = \App\Models\Pet::softDelete($uid, $id);
    if ($result['ok']) {
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'Could not delete pet.']);
    }
} catch (Throwable $e) {
    error_log('[pets/delete] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}