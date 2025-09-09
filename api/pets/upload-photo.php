<?php
// api/pets/upload-photo.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['user']);
require_once __DIR__ . '/../../app/models/Pet.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['ok' => false, 'error' => 'Only POST method is allowed.']);
    exit;
}

$user_id = (int)($_SESSION['user']['id'] ?? 0);
$pet_id  = (int)($_POST['pet_id'] ?? 0);
if ($pet_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing or invalid pet_id']);
    exit;
}

$file = $_FILES['photo'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
        UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
        UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
        UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
        UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload."
    ];
    $error_message = $upload_errors[$file['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'Unknown upload error.';
    echo json_encode(['ok' => false, 'error' => $error_message]);
    exit;
}

try {
    // TAMA: Gamitin ang buong namespace \App\Models\Pet
    $res = \App\Models\Pet::savePhoto($user_id, $pet_id, $file, true, $_POST['old_photo_path'] ?? null);
    
    if (!$res['ok']) {
        http_response_code(422); // Unprocessable Entity
    } else {
        http_response_code(200); // OK
    }
    echo json_encode($res);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log('Upload photo error for pet_id ' . $pet_id . ': ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'An internal server error occurred. Please try again later.']);
}