<?php
// api/staffs/medical/records.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

$pdo = db();
$staff_id = (int)($_SESSION['user']['id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];

function json_response($data, $ok = true, $code = 200) { http_response_code($code); echo json_encode(['ok' => $ok] + $data); exit; }
function can_edit_pet($pet_id, $staff_id, $pdo) {
    $stmt = $pdo->prepare("SELECT 1 FROM appointments WHERE pet_id = :pet_id AND staff_id_assigned = :staff_id AND status IN ('Confirmed', 'Completed') LIMIT 1");
    $stmt->execute([':pet_id' => $pet_id, ':staff_id' => $staff_id]);
    return $stmt->fetchColumn() !== false;
}

$TABLE_MAP = [
    'soap' => 'medical_records', 'vaccination' => 'pet_vaccinations', 'deworming' => 'pet_deworming', 
    'prevention' => 'pet_parasite_preventions', 'medication' => 'pet_medications', 'allergy' => 'pet_allergies'
];

try {
    switch ($method) {
        case 'GET':
            $pet_id = (int)($_GET['pet_id'] ?? 0);
            if (!$pet_id) json_response(['error' => 'Pet ID is required.'], false, 400);
            $records = [];
            foreach ($TABLE_MAP as $key => $table) {
                 $sql = ($key === 'soap')
                    ? "SELECT mr.*, CONCAT(u.first_name, ' ', u.last_name) as staff_name FROM medical_records mr JOIN users u ON mr.staff_id = u.id WHERE mr.pet_id = ? ORDER BY mr.record_date DESC"
                    : "SELECT * FROM `$table` WHERE pet_id = ? ORDER BY id DESC";
                 $stmt = $pdo->prepare($sql);
                 $stmt->execute([$pet_id]);
                 $records[$key] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            json_response(['records' => $records]);
            break;

        case 'POST': case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) json_response(['error' => 'No data received.'], false, 400);

            $type = $data['record_type'] ?? '';
            $pet_id = (int)($data['pet_id'] ?? 0);
            $record_id = (int)($data['record_id'] ?? 0);
            // --- START OF CHANGES ---
            // Get appointment_id from form to link all records of a visit
            $appointment_id = (int)($data['appointment_id'] ?? 0);
            // --- END OF CHANGES ---
            
            if (!isset($TABLE_MAP[$type])) json_response(['error' => 'Invalid record type provided.'], false, 400);
            if (!$pet_id) json_response(['error' => 'Pet ID was not provided.'], false, 400);
            if (!can_edit_pet($pet_id, $staff_id, $pdo)) json_response(['error' => 'You do not have permission to edit records for this pet.'], false, 403);
            
            $table = $TABLE_MAP[$type];
            // Unset metadata fields from the main data array
            unset($data['record_type'], $data['pet_id'], $data['record_id'], $data['appointment_id']);
            
            // --- START OF CHANGES ---
            // Logic to find or create a master medical record for the visit and link sub-records to it
            if ($type !== 'soap') { 
                $data['updated_by_staff_id'] = $staff_id;
                
                if ($appointment_id > 0) {
                    // Check if a SOAP record already exists for this appointment
                    $stmt = $pdo->prepare("SELECT id FROM medical_records WHERE appointment_id = ?");
                    $stmt->execute([$appointment_id]);
                    $medical_record_id = $stmt->fetchColumn();

                    // If not, create a placeholder "stub" record to get an ID
                    if (!$medical_record_id) {
                        $stubSql = "INSERT INTO medical_records (appointment_id, pet_id, staff_id, record_date) VALUES (?, ?, ?, NOW())";
                        $pdo->prepare($stubSql)->execute([$appointment_id, $pet_id, $staff_id]);
                        $medical_record_id = $pdo->lastInsertId();
                    }
                    
                    // Add the linking ID to the data for the sub-record (vaccination, allergy, etc.)
                    if ($medical_record_id) {
                        $data['medical_record_id'] = $medical_record_id;
                    }
                }
            } else { 
                // For SOAP records, ensure staff_id and appointment_id are set
                $data['staff_id'] = $staff_id;
                if ($appointment_id > 0) {
                    $data['appointment_id'] = $appointment_id;
                } else if ($method === 'POST') {
                    // SOAP records require an appointment ID to be created.
                    json_response(['error' => 'Cannot create a S.O.A.P. record without a valid appointment.'], false, 400);
                }
            }
            // --- END OF CHANGES ---

            if ($method === 'POST') {
                $data['pet_id'] = $pet_id;
                $cols = array_keys($data);
                $placeholders = array_map(fn($c) => ":$c", $cols);
                $sql = "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $pdo->prepare($sql)->execute($data);
                json_response(['message' => 'Record added successfully.', 'id' => $pdo->lastInsertId()]);
            } else { // PUT
                if (!$record_id) json_response(['error' => 'Record ID is required for update.'], false, 400);
                $set_parts = array_map(fn($c) => "$c = :$c", array_keys($data));
                $sql = "UPDATE `$table` SET " . implode(', ', $set_parts) . " WHERE id = :record_id AND pet_id = :pet_id";
                $data['record_id'] = $record_id;
                $data['pet_id'] = $pet_id;
                $pdo->prepare($sql)->execute($data);
                json_response(['message' => 'Record updated successfully.']);
            }
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $type = $data['record_type'] ?? ''; $pet_id = (int)($data['pet_id'] ?? 0); $record_id = (int)($data['record_id'] ?? 0);
            if (!isset($TABLE_MAP[$type]) || !$pet_id || !$record_id) json_response(['error' => 'Invalid data for deletion.'], false, 400);
            if (!can_edit_pet($pet_id, $staff_id, $pdo)) json_response(['error' => 'You do not have permission to delete records for this pet.'], false, 403);
            $table = $TABLE_MAP[$type];
            $sql = "DELETE FROM `$table` WHERE id = ? AND pet_id = ?";
            $pdo->prepare($sql)->execute([$record_id, $pet_id]);
            json_response(['message' => 'Record deleted successfully.']);
            break;
    }
} catch (Throwable $e) {
    error_log("API Error in records.php: " . $e->getMessage() . " TRACE: " . $e->getTraceAsString());
    json_response(['error' => 'A server error occurred.'], false, 500);
}