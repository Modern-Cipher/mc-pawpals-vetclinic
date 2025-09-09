<?php
// api/staffs/medical/create.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

function jerr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

try {
    $staff_id = (int)($_SESSION['user']['id'] ?? 0);
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    if (!$staff_id || !$appointment_id) jerr('Missing required information (staff or appointment).');

    $pdo = db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT pet_id FROM appointments WHERE id = ? LIMIT 1");
    $stmt->execute([$appointment_id]);
    $appt = $stmt->fetch();
    if (!$appt) jerr('Appointment not found.');

    $pet_id = (int)$appt['pet_id'];

    $data = [
        ':appointment_id' => $appointment_id,
        ':pet_id' => $pet_id,
        ':staff_id' => $staff_id,
        ':record_date' => date('Y-m-d H:i:s'),
        ':weight_kg' => empty($_POST['weight_kg']) ? null : (float)$_POST['weight_kg'],
        ':temperature_c' => empty($_POST['temperature_c']) ? null : (float)$_POST['temperature_c'],
        ':subjective' => trim($_POST['subjective'] ?? ''),
        ':objective' => trim($_POST['objective'] ?? ''),
        ':assessment' => trim($_POST['assessment'] ?? ''),
        ':plan' => trim($_POST['plan'] ?? ''),
    ];

    $sql = "INSERT INTO medical_records (appointment_id, pet_id, staff_id, record_date, weight_kg, temperature_c, subjective, objective, assessment, plan) 
            VALUES (:appointment_id, :pet_id, :staff_id, :record_date, :weight_kg, :temperature_c, :subjective, :objective, :assessment, :plan)";
    $pdo->prepare($sql)->execute($data);
    $record_id = (int)$pdo->lastInsertId();

    // Save services (if any)
    $services = json_decode($_POST['services_json'] ?? 'null', true);
    if (is_array($services)) {
        $ins = function($sql,$rows) use($pdo,$pet_id,$record_id){
            if (!is_array($rows) || !count($rows)) return;
            $st = $pdo->prepare($sql);
            foreach ($rows as $r) $st->execute($r + [':pet_id'=>$pet_id, ':mrid'=>$record_id]);
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
    }

    // mark appointment completed
    $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ? AND status IN ('Confirmed','Pending')")->execute([$appointment_id]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'message' => 'Medical record created.', 'record_id' => $record_id]);

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Medical record creation failed: " . $e->getMessage());
    jerr('Server error occurred.', 500);
}
