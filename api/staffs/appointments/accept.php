<?php
// api/staffs/appointments/accept.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../app/services/Mailer.php'; // Include Mailer

use App\Services\Mailer;

header('Content-Type: application/json');

$pdo = null;

try {
    $id = (int)($_POST['id'] ?? 0);
    $assign_to = (int)($_POST['assign_to'] ?? 0);
    if (!$id || !$assign_to) {
        http_response_code(422);
        throw new Exception('Missing required fields.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    
    $cur = $pdo->prepare("SELECT status FROM appointments WHERE id=? FOR UPDATE");
    $cur->execute([$id]);
    $row = $cur->fetch();
    
    if (!$row) throw new Exception('Appointment not found.');
    if (strcasecmp($row['status'], 'Pending') !== 0) throw new Exception('This appointment has already been processed.');

    $upd = $pdo->prepare("UPDATE appointments SET staff_id_assigned=?, status='Confirmed', updated_at=NOW() WHERE id=?");
    $upd->execute([$assign_to, $id]);
    
    $pdo->commit();

    // --- SEND CONFIRMATION EMAIL ---
    try {
        $stmt = $pdo->prepare("
            SELECT
                a.service, a.appointment_datetime,
                owner.email AS owner_email,
                CONCAT(owner.first_name, ' ', owner.last_name) AS owner_name,
                pet.name AS pet_name,
                vet.email AS vet_email,
                CONCAT(vet.first_name, ' ', vet.last_name) AS vet_name
            FROM appointments a
            JOIN users owner ON a.user_id = owner.id
            JOIN pets pet ON a.pet_id = pet.id
            JOIN users vet ON a.staff_id_assigned = vet.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $details = $stmt->fetch();

        if ($details) {
            $mailer = new Mailer();
            @$mailer->sendAppointmentConfirmation($details);
        }
    } catch (\Throwable $e) {
        error_log("Failed to send confirmation email for appointment #{$id}: " . $e->getMessage());
    }
    // --- END SEND EMAIL ---

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    if (http_response_code() === 200) http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage() ?: 'A server error occurred.']);
}