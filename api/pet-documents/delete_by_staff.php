<?php
// api/pet-documents/delete_by_staff.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/models/Pet.php'; // <-- IDAGDAG ITONG LINYA

header('Content-Type: application/json');

function jerr($msg) {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jerr('Method Not Allowed.');
}

$staffId = (int)($_SESSION['user']['id'] ?? 0);
$docId = (int)($_POST['doc_id'] ?? 0);

if ($docId <= 0) {
    jerr('Missing or invalid document ID.');
}

try {
    $success = \App\Models\Pet::deleteDocumentByStaff($docId);
    if ($success) {
        echo json_encode(['ok' => true]);
    } else {
        jerr('Failed to delete document.');
    }
} catch (Throwable $e) {
    error_log('[API pet-documents/delete_by_staff] ' . $e->getMessage());
    jerr('Server error.');
}