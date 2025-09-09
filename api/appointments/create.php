<?php
// api/appointments/create.php
// Creates an appointment; blocks if the slot is already taken (Pending/Confirmed)
// and (optionally) blocks "same pet + same service + same date".

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['user']);
require_once __DIR__ . '/../../config/connection.php';
header('Content-Type: application/json');

$userId = (int)($_SESSION['user']['id'] ?? 0);

$pet_id  = (int)($_POST['pet_id'] ?? 0);
$service = trim((string)($_POST['service'] ?? ''));
$notes   = trim((string)($_POST['notes'] ?? ''));
$date    = $_POST['appointment_date'] ?? '';
$time    = $_POST['appointment_time'] ?? '';
$service_other = isset($_POST['service_other']) ? trim((string)$_POST['service_other']) : '';

if ($service === 'Other' && $service_other !== '') {
  $service = $service_other;
}

if (!$userId || !$pet_id || !$service || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{1,2}:\d{2}\s?(AM|PM)$/i', $time)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Please fill in all required fields.']);
  exit;
}

try {
  $pdo = db();
  $pdo->beginTransaction();

  // Normalize datetime from date + time (12h)
  $dt = DateTime::createFromFormat('Y-m-d h:i A', $date.' '.$time);
  if (!$dt) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid date/time.']); exit;
  }
  $dtStr = $dt->format('Y-m-d H:i:00');

  // (Optional) Rule: same pet + same service + same date is not allowed
  $dupeDay = $pdo->prepare("
    SELECT 1 FROM appointments
    WHERE user_id=? AND pet_id=? AND service=? AND DATE(appointment_datetime)=DATE(?)
      AND status IN ('Pending','Confirmed')
    LIMIT 1 FOR UPDATE
  ");
  $dupeDay->execute([$userId, $pet_id, $service, $dtStr]);
  if ($dupeDay->fetchColumn()) {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(['ok'=>false,'error'=>'This pet already has the same service on that date.']);
    exit;
  }

  // Hard block: slot already taken (global)
  $slotTaken = $pdo->prepare("
    SELECT 1 FROM appointments
    WHERE appointment_datetime = ? AND status IN ('Pending','Confirmed')
    LIMIT 1 FOR UPDATE
  ");
  $slotTaken->execute([$dtStr]);
  if ($slotTaken->fetchColumn()) {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(['ok'=>false,'error'=>'This time slot was just taken.']);
    exit;
  }

  // Insert
  $ins = $pdo->prepare("
    INSERT INTO appointments (user_id, pet_id, service, notes, appointment_datetime, status, created_at)
    VALUES (?,?,?,?,?,'Pending', NOW())
  ");
  $ins->execute([$userId, $pet_id, $service, $notes, $dtStr]);

  $pdo->commit();
  http_response_code(201);
  echo json_encode(['ok'=>true,'message'=>'Appointment created.']);
} catch (Throwable $e) {
  error_log("APPT CREATE ERR: ".$e->getMessage());
  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}
