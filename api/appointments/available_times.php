<?php
// api/appointments/available_times.php
require_once __DIR__ . '/../_bootstrap.php';   // <= use your bootstrap that gives $pdo + auth helpers
require_login(['user','staff','admin']);

header('Content-Type: application/json; charset=utf-8');

try {
  $date      = isset($_GET['date']) ? trim($_GET['date']) : '';
  $staffId   = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : null;

  if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'Invalid date.']); exit;
  }

  $dow = (int)date('w', strtotime($date)); // 0..6

  // 1) find schedule (staff override → else clinic)
  if ($staffId) {
    $stmt = $pdo->prepare("SELECT * FROM staff_hours WHERE staff_user_id=? AND day_of_week=? LIMIT 1");
    $stmt->execute([$staffId, $dow]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
  }
  if (empty($schedule)) {
    $stmt = $pdo->prepare("SELECT * FROM clinic_hours WHERE day_of_week=? LIMIT 1");
    $stmt->execute([$dow]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
  }
  if (!$schedule || !$schedule['is_open']) {
    echo json_encode(['ok'=>true,'slots'=>[],'source'=>$staffId?'staff_hours':'clinic_hours']); exit;
  }

  $slotMinutes = (int)($schedule['slot_minutes'] ?: 30);
  $start = new DateTimeImmutable("$date " . substr($schedule['start_time'],0,5));
  $end   = new DateTimeImmutable("$date " . substr($schedule['end_time'],0,5));

  $breakStart = !empty($schedule['break_start']) ? new DateTimeImmutable("$date " . substr($schedule['break_start'],0,5)) : null;
  $breakEnd   = !empty($schedule['break_end'])   ? new DateTimeImmutable("$date " . substr($schedule['break_end'],0,5))   : null;

  // 2) collect occupied times for the day
  $params = [$date.' 00:00:00', $date.' 23:59:59'];
  $sql = "SELECT TIME(appointment_datetime) t
          FROM appointments
          WHERE appointment_datetime BETWEEN ? AND ?
            AND status IN ('Pending','Confirmed')";
  if ($staffId) { $sql .= " AND (staff_id_assigned = ? OR staff_id_assigned IS NULL)"; $params[] = $staffId; }
  $occupied = $pdo->prepare($sql);
  $occupied->execute($params);
  $taken = array_column($occupied->fetchAll(PDO::FETCH_ASSOC), 't'); // ['10:00:00',...]

  // 3) day-off?
  if ($staffId) {
    $stmtOff = $pdo->prepare("SELECT 1 FROM staff_time_off WHERE staff_user_id=? AND date=? LIMIT 1");
    $stmtOff->execute([$staffId, $date]);
    if ($stmtOff->fetchColumn()) { echo json_encode(['ok'=>true,'slots'=>[],'source'=>'staff_time_off']); exit; }
  }

  // 4) generate free slots
  $now = new DateTimeImmutable('now');
  $slots = [];
  for ($t = $start; $t < $end; $t = $t->modify("+{$slotMinutes} minutes")) {
    // skip lunch
    if ($breakStart && $breakEnd && $t >= $breakStart && $t < $breakEnd) continue;
    // skip past (if today)
    if ($t->format('Y-m-d') === $now->format('Y-m-d') && $t <= $now) continue;
    // skip taken
    if (in_array($t->format('H:i:s'), $taken, true)) continue;
    $slots[] = $t->format('H:i');
  }

  echo json_encode(['ok'=>true,'slots'=>$slots,'source'=>$staffId?'staff_hours/clinic_hours':'clinic_hours']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error','trace'=>$e->getMessage()]);
}
