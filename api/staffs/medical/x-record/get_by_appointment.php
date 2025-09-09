<?php
// api/staffs/medical/get_by_appointment.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $appointment_id = (int)($_GET['appointment_id'] ?? 0);
  if ($appointment_id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'appointment_id required']); exit; }

  $pdo = db();

  $mr = $pdo->prepare("SELECT * FROM medical_records WHERE appointment_id=? LIMIT 1");
  $mr->execute([$appointment_id]);
  $rec = $mr->fetch();
  if (!$rec) { echo json_encode(['ok'=>true,'exists'=>false]); exit; }

  $rid = (int)$rec['id'];
  $pet_id = (int)$rec['pet_id'];

  $fetchAll = function(string $sql, array $args) use ($pdo) {
      $st = $pdo->prepare($sql); $st->execute($args); return $st->fetchAll() ?: [];
  };

  $vacc  = $fetchAll("SELECT id, vaccine_name, dose_no, date_administered, next_due_date, remarks FROM pet_vaccinations WHERE medical_record_id=? ORDER BY date_administered DESC, id DESC", [$rid]);
  $dewrm = $fetchAll("SELECT id, product_name, dose, targets, date_administered, next_due_date, remarks FROM pet_deworming WHERE medical_record_id=? ORDER BY date_administered DESC, id DESC", [$rid]);
  $prev  = $fetchAll("SELECT id, type, product_name, route, date_administered, next_due_date, remarks FROM pet_parasite_preventions WHERE medical_record_id=? ORDER BY date_administered DESC, id DESC", [$rid]);
  $meds  = $fetchAll("SELECT id, drug_name, dosage, frequency, start_date, end_date, notes FROM pet_medications WHERE medical_record_id=? ORDER BY id DESC", [$rid]);
  $allg  = $fetchAll("SELECT id, allergen, reaction, severity, notes FROM pet_allergies WHERE medical_record_id=? ORDER BY id DESC", [$rid]);

  echo json_encode([
    'ok'=>true,'exists'=>true,
    'record'=>[
      'id'=>$rid,
      'appointment_id'=>(int)$rec['appointment_id'],
      'pet_id'=>$pet_id,
      'weight_kg'=>$rec['weight_kg'],
      'temperature_c'=>$rec['temperature_c'],
      'subjective'=>$rec['subjective'],
      'objective'=>$rec['objective'],
      'assessment'=>$rec['assessment'],
      'plan'=>$rec['plan'],
    ],
    'services'=>[
      'vaccinations'=>$vacc,
      'deworming'=>$dewrm,
      'preventions'=>$prev,
      'medications'=>$meds,
      'allergies'=>$allg,
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  error_log('[get_by_appointment] '.$e->getMessage());
  echo json_encode(['ok'=>false,'error'=>'Server error']);
}
