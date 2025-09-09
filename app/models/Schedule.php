<?php
// app/models/Schedule.php
namespace App\Models;

require_once __DIR__ . '/../../config/connection.php';

class Schedule {
    public static function getStaffHours(int $staffId): array {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT * FROM staff_hours WHERE staff_user_id = :id ORDER BY day_of_week ASC");
            $stmt->execute([':id' => $staffId]);
            $hours = $stmt->fetchAll() ?: [];

            // If no custom hours are set, return default clinic hours
            if (empty($hours)) {
                $stmt = $pdo->query("SELECT * FROM clinic_hours ORDER BY day_of_week ASC");
                return $stmt->fetchAll() ?: [];
            }
            return $hours;
        } catch (\Throwable $e) {
            error_log('Schedule Model Error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getStaffTimeOff(int $staffId): array {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT * FROM staff_time_off WHERE staff_user_id = :id ORDER BY date ASC");
            $stmt->execute([':id' => $staffId]);
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('Time Off Model Error: ' . $e->getMessage());
            return [];
        }
    }

    // New model function to get all time off requests for admin
    public static function getAllTimeOffRequests(): array {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("
                SELECT sto.*, u.first_name, u.last_name
                FROM staff_time_off sto
                JOIN users u ON sto.staff_user_id = u.id
                ORDER BY sto.status ASC, sto.date ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('Admin Time Off Model Error: ' . $e->getMessage());
            return [];
        }
    }

    // New model function to update time off request status
    public static function updateTimeOffStatus(int $id, string $status, int $adminId): bool {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("
                UPDATE staff_time_off 
                SET status = :status, 
                    approved_by_admin_id = :admin_id, 
                    approved_at = NOW() 
                WHERE id = :id
            ");
            $stmt->execute([
                ':status' => $status,
                ':admin_id' => $adminId,
                ':id' => $id
            ]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            error_log('Time Off Status Update Error: ' . $e->getMessage());
            return false;
        }
    }
}