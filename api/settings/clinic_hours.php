<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../config/connection.php';

header('Content-Type: application/json');

function jerr($msg) {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->query("SELECT * FROM clinic_hours ORDER BY day_of_week ASC");
    $hours = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'items' => $hours]);
} catch (Throwable $e) {
    jerr('Server error: ' . $e->getMessage());
}