<?php
// app/models/StaffDocument.php
namespace App\Models;

require_once __DIR__ . '/../../config/connection.php';

class StaffDocument
{
    /**
     * Staff can upload a document for themselves.
     */
    public static function saveDocument(int $userId, array $file, string $kind, ?string $originalName = null): array
    {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'No file uploaded or upload error occurred.'];
        }

        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            return ['ok' => false, 'error' => 'User not found.'];
        }
        
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['ok' => false, 'error' => 'Invalid file type. Only PDF, JPG, PNG, WEBP are allowed.'];
        }
        
        $maxSize = 20 * 1024 * 1024; // 20 MB
        if ($file['size'] > $maxSize) {
            return ['ok' => false, 'error' => 'File size exceeds 20MB limit.'];
        }

        $dir = __DIR__ . '/../../uploads/staffs/' . $userId . '/';
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true)) {
                return ['ok' => false, 'error' => 'Failed to create upload directory.'];
            }
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        $full_path = $dir . $filename;
        $db_path = 'uploads/staffs/' . $userId . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $full_path)) {
            return ['ok' => false, 'error' => 'Failed to save the uploaded file.'];
        }

        try {
            $sql = "INSERT INTO staff_documents (user_id, kind, orig_name, file_path, mime_type, size_bytes) 
                    VALUES (:user_id, :kind, :orig_name, :file_path, :mime_type, :size_bytes)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':kind' => $kind,
                ':orig_name' => $originalName ?? $file['name'],
                ':file_path' => $db_path,
                ':mime_type' => $file['type'],
                ':size_bytes' => $file['size']
            ]);
            return ['ok' => true, 'path' => $db_path];
        } catch (\Throwable $e) {
            @unlink($full_path);
            error_log('[STAFF_DOC_SAVE_DB] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Server error saving document.'];
        }
    }

    /**
     * Get a list of documents for a specific user, grouped by kind.
     */
    public static function listByUserId(int $userId): array
    {
        $pdo = db();
        try {
            $stmt = $pdo->prepare("SELECT id, kind, orig_name, file_path, mime_type FROM staff_documents WHERE user_id = ? ORDER BY uploaded_at DESC");
            $stmt->execute([$userId]);
            $docs = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            
            $groups = [];
            foreach ($docs as $doc) {
                if (!isset($groups[$doc['kind']])) {
                    $groups[$doc['kind']] = [];
                }
                $groups[$doc['kind']][] = $doc;
            }
            return $groups;
        } catch (\Throwable $e) {
            error_log('[STAFF_DOC_LIST_BY_USER] ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Deletes a document by ID.
     */
    public static function deleteDocument(int $docId): bool
    {
        $pdo = db();
        try {
            $stmt = $pdo->prepare("SELECT file_path FROM staff_documents WHERE id = ? LIMIT 1");
            $stmt->execute([$docId]);
            $document = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$document) {
                return false;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM staff_documents WHERE id = ?");
            $stmt->execute([$docId]);

            if ($stmt->rowCount() > 0) {
                $fullPath = __DIR__ . '/../../' . $document['file_path'];
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
                $pdo->commit();
                return true;
            }

            $pdo->rollBack();
            return false;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[STAFF_DOC_DELETE] ' . $e->getMessage());
            return false;
        }
    }
}