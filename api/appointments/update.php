<?php
// api/appointments/update.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['user']);
require_once __DIR__ . '/../../config/connection.php';
header('Content-Type: application/json');

$userId = (int)($_SESSION['user']['id'] ?? 0);
$id     = (int)($_POST['id'] ?? 0);

if (!$userId || !$id) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Missing appointment id.']); exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $curStmt = $pdo->prepare("SELECT * FROM appointments WHERE id=? AND user_id=? LIMIT 1 FOR UPDATE");
    $curStmt->execute([$id, $userId]);
    $cur = $curStmt->fetch();

    if (!$cur) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok'=>false,'error'=>'Appointment not found.']); exit;
    }

    // UPDATED RULE: Only allow edit when status is 'Pending'.
    if ($cur['status'] !== 'Pending') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['ok'=>false,'error'=>'Only pending appointments can be edited.']); exit;
    }

    // Build new values
    $pet_id  = isset($_POST['pet_id']) ? (int)$_POST['pet_id'] : (int)$cur['pet_id'];
    $service = isset($_POST['service']) ? trim((string)$_POST['service']) : (string)$cur['service'];
    $notes   = array_key_exists('notes', $_POST) ? trim((string)$_POST['notes']) : (string)$cur['notes'];

    $service_other = isset($_POST['service_other']) ? trim((string)$_POST['service_other']) : '';
    if ($service === 'Other' && $service_other !== '') $service = $service_other;

    $newDate = $_POST['appointment_date'] ?? date('Y-m-d', strtotime($cur['appointment_datetime']));
    $newTime12 = $_POST['appointment_time'] ?? date('h:i A', strtotime($cur['appointment_datetime']));

    $dt = DateTime::createFromFormat('Y-m-d h:i A', $newDate.' '.$newTime12);
    if (!$dt) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Invalid date/time format.']); exit;
    }
    $dtStr = $dt->format('Y-m-d H:i:00');

    // Slot conflict check (excluding self)
    $conflict = $pdo->prepare("
        SELECT 1 FROM appointments
        WHERE appointment_datetime = ?
          AND status IN ('Pending','Confirmed')
          AND id <> ?
        LIMIT 1 FOR UPDATE
    ");
    $conflict->execute([$dtStr, $id]);
    if ($conflict->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['ok'=>false,'error'=>'That time is no longer available.']);
        exit;
    }

    // Do update
    $upd = $pdo->prepare("
        UPDATE appointments
          SET pet_id=?, service=?, notes=?, appointment_datetime=?, status='Pending', updated_at=NOW()
        WHERE id=? AND user_id=?
    ");
    $upd->execute([$pet_id, $service, $notes, $dtStr, $id, $userId]);

    $pdo->commit();
    echo json_encode(['ok'=>true,'message'=>'Appointment updated.']);
} catch (Throwable $e) {
    error_log("APPT UPDATE ERR: ".$e->getMessage());
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Server error.']);
}