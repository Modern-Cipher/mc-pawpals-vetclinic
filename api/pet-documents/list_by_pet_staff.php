<?php
// api/pet-documents/list_by_pet_staff.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/models/Pet.php';

header('Content-Type: application/json');

function jerr($msg) {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

try {
    $petId = (int)($_GET['pet_id'] ?? 0);
    if ($petId <= 0) {
        jerr('Missing or invalid pet ID.');
    }

    $documents = \App\Models\Pet::docsListForStaff($petId);
    echo json_encode(['ok' => true, 'documents' => $documents]);
} catch (Throwable $e) {
    error_log('[API pet-documents/list_by_pet_staff] ' . $e->getMessage());
    jerr('Server error.');
}