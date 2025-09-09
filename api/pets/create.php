<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
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
    $data = $_POST;
    $file = $_FILES['photo'] ?? null;

    // TAMA: Gamitin ang buong namespace \App\Models\Pet
    $result = \App\Models\Pet::create($uid, $data, $file);

    if ($result['ok']) {
        echo json_encode(['ok' => true, 'id' => $result['id']]);
    } else {
        http_response_code(422); // Unprocessable Entity
        echo json_encode(['ok' => false, 'error' => $result['error']]);
    }

} catch (Throwable $e) {
    error_log('[api/pets/create] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'A server error occurred.']);
}