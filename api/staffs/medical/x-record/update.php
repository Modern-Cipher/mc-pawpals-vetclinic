<?php
// api/staffs/medical/update.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

function jerr($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'error'=>$m]); exit; }

try {
  $pdo = db();
  $staff_id = (int)($_SESSION['user']['id'] ?? 0);
  $appointment_id = (int)($_POST['appointment_id'] ?? 0);
  if (!$staff_id || !$appointment_id) jerr('Missing staff or appointment.');

  $row = $pdo->prepare("SELECT id, pet_id FROM medical_records WHERE appointment_id=? LIMIT 1");
  $row->execute([$appointment_id]);
  $rec = $row->fetch();
  if (!$rec) jerr('Record not found for this appointment.',404);

  $rid = (int)$rec['id']; $pet_id = (int)$rec['pet_id'];

  $data = [
    ':weight_kg'     => empty($_POST['weight_kg']) ? null : (float)$_POST['weight_kg'],
    ':temperature_c' => empty($_POST['temperature_c']) ? null : (float)$_POST['temperature_c'],
    ':subjective'    => trim($_POST['subjective'] ?? ''),
    ':objective'     => trim($_POST['objective'] ?? ''),
    ':assessment'    => trim($_POST['assessment'] ?? ''),
    ':plan'          => trim($_POST['plan'] ?? ''),
    ':id'            => $rid
  ];
  $sql = "UPDATE medical_records SET weight_kg=:weight_kg, temperature_c=:temperature_c, subjective=:subjective, objective=:objective, assessment=:assessment, plan=:plan WHERE id=:id";
  $pdo->prepare($sql)->execute($data);

  // Services
  $services = json_decode($_POST['services_json'] ?? 'null', true);
  if (is_array($services)) {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM pet_vaccinations WHERE medical_record_id=?")->execute([$rid]);
    $pdo->prepare("DELETE FROM pet_deworming WHERE medical_record_id=?")->execute([$rid]);
    $pdo->prepare("DELETE FROM pet_parasite_preventions WHERE medical_record_id=?")->execute([$rid]);
    $pdo->prepare("DELETE FROM pet_medications WHERE medical_record_id=?")->execute([$rid]);
    $pdo->prepare("DELETE FROM pet_allergies WHERE medical_record_id=?")->execute([$rid]);

    $ins = function($sql,$rows) use($pdo,$pet_id,$rid){
      if (!is_array($rows) || !count($rows)) return;
      $st = $pdo->prepare($sql);
      foreach ($rows as $r) $st->execute($r + [':pet_id'=>$pet_id, ':mrid'=>$rid]);
    };

    $ins("INSERT INTO pet_vaccinations (pet_id, medical_record_id, vaccine_name, dose_no, date_administered, next_due_date, remarks) 
          VALUES (:pet_id,:mrid,:vaccine_name,:dose_no,:date_administered,:next_due_date,:remarks)", $services['vaccinations'] ?? []);

    $ins("INSERT INTO pet_deworming (pet_id, medical_record_id, product_name, dose, targets, date_administered, next_due_date, remarks) 
          VALUES (:pet_id,:mrid,:product_name,:dose,:targets,:date_administered,:next_due_date,:remarks)", $services['deworming'] ?? []);

    $ins("INSERT INTO pet_parasite_preventions (pet_id, medical_record_id, type, product_name, route, date_administered, next_due_date, remarks) 
          VALUES (:pet_id,:mrid,:type,:product_name,:route,:date_administered,:next_due_date,:remarks)", $services['preventions'] ?? []);

    $ins("INSERT INTO pet_medications (pet_id, medical_record_id, drug_name, dosage, frequency, start_date, end_date, notes) 
          VALUES (:pet_id,:mrid,:drug_name,:dosage,:frequency,:start_date,:end_date,:notes)", $services['medications'] ?? []);

    $ins("INSERT INTO pet_allergies (pet_id, medical_record_id, allergen, reaction, severity, notes) 
          VALUES (:pet_id,:mrid,:allergen,:reaction,:severity,:notes)", $services['allergies'] ?? []);

    $pdo->commit();
  }

  echo json_encode(['ok'=>true,'message'=>'Record updated','record_id'=>$rid]);
} catch (Throwable $e) {
  if (!empty($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  error_log('[medical/update] '.$e->getMessage());
  jerr('Server error',500);
}
