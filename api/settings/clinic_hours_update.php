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
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['hours']) || !is_array($data['hours'])) {
        jerr('Invalid data provided.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE clinic_hours
        SET is_open = ?, start_time = ?, end_time = ?, break_start = ?, break_end = ?, slot_minutes = ?
        WHERE id = ?
    ");

    foreach ($data['hours'] as $hour) {
        $stmt->execute([
            (int)$hour['is_open'],
            $hour['start_time'],
            $hour['end_time'],
            $hour['break_start'] ?: null,
            $hour['break_end'] ?: null,
            (int)$hour['slot_minutes'],
            $hour['id']
        ]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jerr('Server error: ' . $e->getMessage());
}