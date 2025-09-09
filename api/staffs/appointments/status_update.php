<?php
// api/staffs/appointments/status_update.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../app/services/Mailer.php';

use App\Services\Mailer;

header('Content-Type: application/json');

try {
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    $reason = trim((string)($_POST['reason'] ?? ''));
    $staff_name = $_SESSION['user']['name'] ?? 'Staff'; // Kinukuha ang pangalan ng naka-login na staff

    if (!$id || !in_array($status, ['Cancelled', 'No-Show'])) {
        http_response_code(422);
        throw new Exception('Invalid input provided.');
    }
    if (empty($reason)) {
        $reason = 'No reason provided by clinic staff.';
    }

    // Isasama ang pangalan ng staff sa rason
    $final_note = "{$status} by {$staff_name}: {$reason}";

    $pdo = db();
    
    // I-save sa 'notes' column ang buong detalye
    $stmt = $pdo->prepare("UPDATE appointments SET status = ?, notes = ? WHERE id = ?");
    $stmt->execute([$status, $final_note, $id]);

    // --- Magpadala ng Email ---
    try {
        $stmt_details = $pdo->prepare("
            SELECT
                a.appointment_datetime,
                owner.email AS owner_email,
                CONCAT(owner.first_name, ' ', owner.last_name) AS owner_name,
                pet.name AS pet_name
            FROM appointments a
            JOIN users owner ON a.user_id = owner.id
            JOIN pets pet ON a.pet_id = pet.id
            WHERE a.id = ?
        ");
        $stmt_details->execute([$id]);
        $details = $stmt_details->fetch();

        if ($details) {
            $details['reason'] = $reason; // Ang ipapadala sa email ay ang rason lang, hindi kasama pangalan ng staff
            $mailer = new Mailer();
            $slug = ($status === 'Cancelled') ? 'appt_cancelled' : 'appt_noshow';
            @$mailer->sendAppointmentStatusUpdate($slug, $details);
        }
    } catch (\Throwable $e) {
        error_log("Failed to send status update email for appointment #{$id}: " . $e->getMessage());
    }

    echo json_encode(['ok' => true, 'message' => "Appointment status updated to {$status}."]);

} catch (Throwable $e) {
    if (http_response_code() === 200) http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage() ?: 'Server error.']);
}