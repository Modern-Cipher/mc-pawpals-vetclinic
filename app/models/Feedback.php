<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/connection.php';

class Feedback
{
    /** Insert a feedback (supports guest or logged-in user) */
    public static function create(array $data): array {
        $pdo = db();
        $sql = "INSERT INTO feedbacks
                  (user_id, name, email, message, rating, status, created_at)
                VALUES
                  (:user_id, :name, :email, :message, :rating, 'pending', NOW())";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'] ?? null,
                ':name'    => trim((string)$data['name']),
                ':email'   => trim((string)$data['email']),
                ':message' => trim((string)$data['message']),
                ':rating'  => (float)$data['rating'],
            ]);
            return ['success'=>true, 'id'=>$pdo->lastInsertId()];
        } catch (Throwable $e) {
            return ['success'=>false, 'message'=>$e->getMessage()];
        }
    }

    /** List for moderation table */
    public static function all(array $opts = []): array {
        $pdo = db();
        $where = [];
        $args  = [];
        if (!empty($opts['status'])) { $where[] = "f.status = :status"; $args[':status']=$opts['status']; }

        // approver/archiver names + role
        $sql = "SELECT
                  f.*,
                  CONCAT(au.first_name, COALESCE(CONCAT(' ', au.last_name), ''))   AS approved_by_name,
                  CONCAT(ar.first_name, COALESCE(CONCAT(' ', ar.last_name), ''))   AS archived_by_name,
                  au.role AS approved_by_role,
                  ar.role AS archived_by_role,

                  /* submitter avatar (1) by user_id -> user_profiles.avatar_path
                     fallback (2) by email match -> user_profiles.avatar_path
                  */
                  COALESCE(up_id.avatar_path, up_em.avatar_path) AS submitter_avatar_url
                FROM feedbacks f
                LEFT JOIN users au ON au.id = f.approved_by
                LEFT JOIN users ar ON ar.id = f.archived_by

                LEFT JOIN users su_id ON su_id.id = f.user_id
                LEFT JOIN user_profiles up_id ON up_id.user_id = su_id.id

                LEFT JOIN users su_em ON su_em.email = f.email
                LEFT JOIN user_profiles up_em ON up_em.user_id = su_em.id

                " . ( $where ? " WHERE ".implode(' AND ',$where) : "" ) . "
                ORDER BY
                  CASE f.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END,
                  f.created_at DESC";
        $st = $pdo->prepare($sql);
        $st->execute($args);
        return $st->fetchAll();
    }

    /** Approve one */
    public static function approve(int $id, int $userId): bool {
        $pdo = db();
        $st = $pdo->prepare("UPDATE feedbacks SET status='approved', approved_at=NOW(), approved_by=:u WHERE id=:id");
        return $st->execute([':u'=>$userId, ':id'=>$id]);
    }

    /** Archive one */
    public static function archive(int $id, int $userId): bool {
        $pdo = db();
        $st = $pdo->prepare("UPDATE feedbacks SET status='archived', archived_at=NOW(), archived_by=:u WHERE id=:id");
        return $st->execute([':u'=>$userId, ':id'=>$id]);
    }

    /** Delete (hard) */
    public static function delete(int $id): bool {
        $pdo = db();
        return $pdo->prepare("DELETE FROM feedbacks WHERE id=?")->execute([$id]);
    }

    /**
     * Approved items for public testimonials (latest first)
     * Includes best-effort submitter avatar (by user_id then by email).
     */
    public static function approvedForPublic(int $limit = 12): array {
        $pdo = db();
        $sql = "SELECT
                  f.id, f.user_id, f.name, f.email, f.message, f.rating,
                  f.created_at, f.approved_at,

                  -- avatar same logic as in moderation
                  COALESCE(up_id.avatar_path, up_em.avatar_path) AS submitter_avatar_url
                FROM feedbacks f
                LEFT JOIN users su_id ON su_id.id = f.user_id
                LEFT JOIN user_profiles up_id ON up_id.user_id = su_id.id
                LEFT JOIN users su_em ON su_em.email = f.email
                LEFT JOIN user_profiles up_em ON up_em.user_id = su_em.id
                WHERE f.status='approved'
                ORDER BY f.rating DESC, f.approved_at DESC, f.created_at DESC
                LIMIT :lim";
        $st = $pdo->prepare($sql);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
