<?php
// api/pet-documents/download.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['staff']); // Tiyakin na staff lang ang makaka-access

require_once __DIR__ . '/../../config/connection.php';

$docId = (int)($_GET['id'] ?? 0);
if ($docId <= 0) {
    http_response_code(400);
    die('Invalid document ID.');
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM pet_documents WHERE id = ?");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        die('Document not found.');
    }

    $filePath = __DIR__ . '/../../' . $doc['file_path'];

    if (!file_exists($filePath)) {
        http_response_code(404);
        die('File not found on server.');
    }

    header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . basename($doc['title'] . '.' . pathinfo($filePath, PATHINFO_EXTENSION)) . '"');
    
    readfile($filePath);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    error_log('[DOWNLOAD_DOC] ' . $e->getMessage());
    die('An internal server error occurred.');
}