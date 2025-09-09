<?php
// api/pet-documents/upload_document.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/models/Pet.php';

header('Content-Type: application/json');

function jerr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jerr('Method Not Allowed.', 405);
}

$petId = (int)($_POST['pet_id'] ?? 0);
if ($petId <= 0) {
    jerr('Invalid or missing Pet ID.');
}

if (empty($_FILES['doc_file'])) {
    jerr('No files were uploaded.');
}

// Re-structure the $_FILES array for easier iteration
$files = [];
foreach ($_FILES['doc_file']['name'] as $key => $name) {
    if ($_FILES['doc_file']['error'][$key] === UPLOAD_ERR_OK) {
        $files[] = [
            'name' => $name,
            'type' => $_FILES['doc_file']['type'][$key],
            'tmp_name' => $_FILES['doc_file']['tmp_name'][$key],
            'error' => $_FILES['doc_file']['error'][$key],
            'size' => $_FILES['doc_file']['size'][$key],
            'title' => $_POST['doc_title'][$key] ?? pathinfo($name, PATHINFO_FILENAME),
            'doc_type' => $_POST['doc_type'][$key] ?? 'others',
        ];
    }
}

if (empty($files)) {
    jerr('Uploaded files contain errors.');
}

$successCount = 0;
$errors = [];

foreach ($files as $file) {
    try {
        $result = \App\Models\Pet::saveDocumentForPet($petId, $file, $file['title'], $file['doc_type']);
        if ($result['ok']) {
            $successCount++;
        } else {
            $errors[] = $file['name'] . ': ' . $result['error'];
        }
    } catch (Throwable $e) {
        error_log('[API upload_document] ' . $e->getMessage());
        $errors[] = $file['name'] . ': Server error during upload.';
    }
}

if ($successCount > 0) {
    echo json_encode([
        'ok' => true,
        'message' => "Successfully uploaded {$successCount} of " . count($files) . " documents.",
        'errors' => $errors
    ]);
} else {
    jerr('Failed to upload any documents. Errors: ' . implode(', ', $errors), 500);
}