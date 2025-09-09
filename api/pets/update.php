<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
// TAMA: Siguraduhin na included ito
require_once __DIR__ . '/../../app/models/Pet.php'; 
header('Content-Type: application/json');

require_login(['user']);
$uid = (int)$_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

try {
    $pet_id = (int)($_POST['id'] ?? 0);
    if ($pet_id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid or missing pet ID.']);
        exit;
    }

    $data = $_POST;
    if (!isset($_POST['sterilized'])) {
        $data['sterilized'] = 0;
    }
    
    $file = $_FILES['photo'] ?? null;

    // TAMA: Gamitin ang buong namespace \App\Models\Pet
    $result = \App\Models\Pet::update($uid, $pet_id, $data, $file);

    if ($result['ok']) {
        echo json_encode($result);
    } else {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $result['error']]);
    }

} catch (Throwable $e) {
    error_log('[api/pets/update] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'A server error occurred.']);
}