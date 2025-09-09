<?php
// api/appointments/slots.php
// Returns { ok:true, slots:[{value:'09:00 AM', disabled:true|false}, ...] }
// Slots are built from staff_hours (with fallback to clinic_hours) for the given weekday, minus break,
// then disabled if there is any Pending/Confirmed appointment on that datetime.
// Also blocks slots if the assigned staff has an approved time off request.

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['user']);
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/models/Schedule.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? ''; // YYYY-MM-DD
$staffId = (int)($_GET['staff_id'] ?? 0);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  echo json_encode(['ok'=>false,'error'=>'Invalid date']); exit;
}

try {
  $pdo = db();
  $dow = (int) date('w', strtotime($date)); // 0=Sun..6=Sat

  // 1) Load staff hours (if staff_id provided) or clinic hours
  $hours = null;
  if ($staffId > 0) {
      $stmt = $pdo->prepare("
          SELECT is_open, start_time, end_time, slot_minutes, break_start, break_end
          FROM staff_hours
          WHERE staff_user_id = :staff_id AND day_of_week = :dow
          LIMIT 1
      ");
      $stmt->execute([':staff_id' => $staffId, ':dow' => $dow]);
      $hours = $stmt->fetch();
  }
  
  // Fallback to clinic hours
  if (!$hours) {
      $stmt = $pdo->prepare("
          SELECT is_open, start_time, end_time, slot_minutes, break_start, break_end
          FROM clinic_hours
          WHERE day_of_week = :dow
          LIMIT 1
      ");
      $stmt->execute([':dow' => $dow]);
      $hours = $stmt->fetch();
  }

  // Fallback if no hours are found at all
  if (!$hours) {
    $defaults = [
      0 => ['is_open'=>0], 6 => ['is_open'=>0],
      1 => ['is_open'=>1,'start_time'=>'09:00:00','end_time'=>'17:00:00','slot_minutes'=>30],
      2 => ['is_open'=>1,'start_time'=>'09:00:00','end_time'=>'17:00:00','slot_minutes'=>30],
      3 => ['is_open'=>1,'start_time'=>'09:00:00','end_time'=>'17:00:00','slot_minutes'=>30],
      4 => ['is_open'=>1,'start_time'=>'09:00:00','end_time'=>'17:00:00','slot_minutes'=>30],
      5 => ['is_open'=>1,'start_time'=>'09:00:00','end_time'=>'17:00:00','slot_minutes'=>30],
    ];
    $hours = $defaults[$dow] ?? ['is_open'=>0];
  }

  // Check for staff time off
  if ($staffId > 0) {
      $stmtOff = $pdo->prepare("
          SELECT 1 FROM staff_time_off 
          WHERE staff_user_id = :staff_id AND date = :date AND status = 'approved'
          LIMIT 1
      ");
      $stmtOff->execute([':staff_id' => $staffId, ':date' => $date]);
      if ($stmtOff->fetchColumn()) {
          // Staff is on approved leave, no slots are available
          echo json_encode(['ok' => true, 'slots' => []]);
          exit;
      }
  }

  if (!(int)($hours['is_open'] ?? 0)) {
    echo json_encode(['ok'=>true,'slots'=>[]]); exit;
  }

  $start = new DateTime("$date " . ($hours['start_time'] ?? '09:00:00'));
  $end   = new DateTime("$date " . ($hours['end_time']   ?? '17:00:00'));
  $step  = max(5, (int)($hours['slot_minutes'] ?? 30));

  $breakS = !empty($hours['break_start']) ? new DateTime("$date {$hours['break_start']}") : null;
  $breakE = !empty($hours['break_end'])   ? new DateTime("$date {$hours['break_end']}")   : null;

  // 2) Build all slots for the day
  $allSlots = [];
  for ($t = clone $start; $t < $end; $t->modify("+{$step} minutes")) {
    if ($breakS && $breakE && $t >= $breakS && $t < $breakE) continue;
    $allSlots[] = $t->format('h:i A');
  }

  // 3) Mark as disabled if already booked (Pending/Confirmed)
  $busyStmt = $pdo->prepare("
    SELECT DATE_FORMAT(appointment_datetime, '%h:%i %p') AS t12
    FROM appointments
    WHERE DATE(appointment_datetime) = ?
      AND status IN ('Pending','Confirmed')
  ");
  $busyStmt->execute([$date]);
  $busyTimes = array_column($busyStmt->fetchAll(), 't12');

  $slots = [];
  foreach ($allSlots as $s) {
    $slots[] = ['value' => $s, 'disabled' => in_array($s, $busyTimes, true)];
  }

  echo json_encode(['ok'=>true,'slots'=>$slots]);
} catch (Throwable $e) {
  error_log("SLOTS ERR: ".$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}