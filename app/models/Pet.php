<?php
// app/models/Pet.php
namespace App\Models;

require_once __DIR__ . '/../../config/connection.php';
use PDO;

class Pet
{
    public static array $speciesEnum = ['dog', 'cat', 'bird', 'rabbit', 'hamster', 'fish', 'reptile', 'other'];
    public static array $sexEnum     = ['male', 'female', 'unknown'];
    private static array $protectedFields = ['name', 'species', 'breed', 'sex', 'birthdate', 'microchip_no', 'rabies_tag_no'];

    public static function searchForStaff(string $q, int $limit = 20): array
    {
        $q = trim($q);
        if ($q === '') return [];
        $pdo = db();
        $limit = max(1, min(100, $limit));
        $term = '%' . $q . '%';
        $idExact = ctype_digit($q) ? (int)$q : 0;
        $sql = "
            SELECT
                p.id, p.name AS pet_name, p.photo_path, p.species, p.breed,
                CONCAT_WS(' ', u.first_name, u.last_name) AS owner_name
            FROM pets p JOIN users u ON u.id = p.user_id
            WHERE p.deleted_at IS NULL AND (
                p.name LIKE :term OR CONCAT_WS(' ', u.first_name, u.last_name) LIKE :term OR
                u.email LIKE :term OR p.breed LIKE :term OR p.species LIKE :term OR
                p.microchip_no LIKE :term OR p.id = :id_exact
            ) ORDER BY p.name ASC LIMIT :lim";
        try {
            $st = $pdo->prepare($sql);
            $st->bindValue(':term', $term, PDO::PARAM_STR);
            $st->bindValue(':id_exact', $idExact, PDO::PARAM_INT);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[Pet::searchForStaff failed] ' . $e->getMessage());
            return [];
        }
    }
    
    private static function clean(?string $s): ?string
    {
        if ($s === null) return null;
        $s = trim($s);
        return ($s === '') ? null : $s;
    }

    private static function _generateFilename(int $user_id, string $original_extension): ?string
    {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            if (!$user) return null;
            $fname = strtolower(preg_replace('/[^a-z0-9]/i', '', $user['first_name'] ?: 'user'));
            $lname = strtolower(preg_replace('/[^a-z0-9]/i', '', $user['last_name'] ?: ''));
            $random = substr(bin2hex(random_bytes(6)), 0, 6);
            $ext = strtolower($original_extension);
            return "{$fname}_{$lname}_{$random}.{$ext}";
        } catch (\Throwable $e) {
            error_log('[PET_FILENAME_GEN] ' . $e->getMessage());
            return null;
        }
    }

    public static function hasMedicalHistory(int $pet_id): bool
    {
        $pdo = db();
        $tables = ['medical_records', 'pet_vaccinations', 'pet_deworming', 'pet_parasite_preventions', 'pet_allergies', 'pet_medications', 'pet_documents'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->prepare("SELECT 1 FROM `{$table}` WHERE pet_id = ? LIMIT 1");
                $stmt->execute([$pet_id]);
                if ($stmt->fetch()) { return true; }
            } catch (\Throwable $e) { error_log("[PET_HISTORY_CHECK:{$table}] " . $e->getMessage()); }
        }
        return false;
    }

    public static function create(int $user_id, array $d, ?array $file): array
    {
        if (empty($d['name'])) return ['ok' => false, 'error' => 'Pet name is required.'];
        $pdo = db();
        try {
            $photo_path = null;
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $uploadResult = self::savePhoto($user_id, 0, $file, false);
                if (!$uploadResult['ok']) { return $uploadResult; }
                $photo_path = $uploadResult['path'];
            }
            $sql = "INSERT INTO pets (user_id, name, species, breed, sex, color, birthdate, weight_kg, microchip_no, rabies_tag_no, sterilized, blood_type, species_other, notes, photo_path) VALUES (:user_id, :name, :species, :breed, :sex, :color, :birthdate, :weight_kg, :microchip_no, :rabies_tag_no, :sterilized, :blood_type, :species_other, :notes, :photo_path)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id'=> $user_id,':name'=> trim($d['name']),':species'=> $d['species'],':breed'=> self::clean($d['breed'] ?? null),':sex'=> self::clean($d['sex'] ?? 'unknown'),':color'=> self::clean($d['color'] ?? null),':birthdate'=> self::clean($d['birthdate'] ?? null),':weight_kg'=> self::clean($d['weight_kg'] ?? null),':microchip_no'=> self::clean($d['microchip_no'] ?? null),':rabies_tag_no'=> self::clean($d['rabies_tag_no'] ?? null),':sterilized'=> !empty($d['sterilized']) ? 1 : 0,':blood_type'=> self::clean($d['blood_type'] ?? null),':species_other'=> self::clean($d['species_other'] ?? null),':notes'=> self::clean($d['notes'] ?? null),':photo_path'=> $photo_path]);
            return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
        } catch (\Throwable $e) {
            error_log('[PET_CREATE] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Server error during pet creation.'];
        }
    }

    public static function update(int $user_id, int $pet_id, array $d, ?array $file): array
    {
        $pdo = db();
        $pet = self::getOne($user_id, $pet_id);
        if (!$pet) return ['ok' => false, 'error' => 'Pet not found or you do not have permission.'];
        $hasHistory = self::hasMedicalHistory($pet_id);
        $map = ['name', 'species', 'breed', 'sex', 'color', 'birthdate', 'weight_kg', 'microchip_no', 'rabies_tag_no', 'sterilized', 'blood_type', 'species_other', 'notes'];
        $set = [];
        $params = [':id' => $pet_id, ':uid' => $user_id];
        foreach ($map as $f) {
            if ($hasHistory && in_array($f, self::$protectedFields)) { continue; }
            if (array_key_exists($f, $d)) {
                $val = ($f === 'sterilized') ? (!empty($d[$f]) ? 1 : 0) : self::clean($d[$f]);
                $set[] = "$f = :$f";
                $params[":$f"] = $val;
            }
        }
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $uploadResult = self::savePhoto($user_id, $pet_id, $file, true, $pet['photo_path']);
            if (!$uploadResult['ok']) { return $uploadResult; }
            $newPhotoPath = $uploadResult['path'];
            $set[] = "photo_path = :photo_path";
            $params[':photo_path'] = $newPhotoPath;
        }
        if (empty($set)) return ['ok' => true, 'updated' => false, 'message' => 'No changes detected.'];
        try {
            $sql = "UPDATE pets SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id=:id AND user_id=:uid AND deleted_at IS NULL";
            $pdo->prepare($sql)->execute($params);
            return ['ok' => true, 'updated' => true];
        } catch (\Throwable $e) {
            error_log('[PET_UPDATE] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Server error during update.'];
        }
    }

    public static function softDelete(int $user_id, int $pet_id): array
    {
        if (self::hasMedicalHistory($pet_id)) { return ['ok' => false, 'error' => 'This pet has a medical history and cannot be deleted.']; }
        $pdo = db();
        try {
            $st = $pdo->prepare("UPDATE pets SET deleted_at = NOW() WHERE id=? AND user_id=? AND deleted_at IS NULL");
            $st->execute([$pet_id, $user_id]);
            return ['ok' => $st->rowCount() > 0];
        } catch (\Throwable $e) {
            error_log('[PET_DELETE] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Server error.'];
        }
    }

    public static function listByUser(int $user_id): array
    {
        $pdo = db();
        try {
            $st = $pdo->prepare("SELECT * FROM pets WHERE user_id=? AND deleted_at IS NULL ORDER BY created_at DESC");
            $st->execute([$user_id]);
            $pets = $st->fetchAll() ?: [];
            foreach ($pets as &$pet) {
                $pet['has_medical_history'] = self::hasMedicalHistory((int)$pet['id']);
            }
            return $pets;
        } catch (\Throwable $e) {
            error_log('[PET_LIST] ' . $e->getMessage());
            return [];
        }
    }

    public static function getOne(int $user_id, int $pet_id): ?array
    {
        $pdo = db();
        try {
            $st = $pdo->prepare("SELECT * FROM pets WHERE id=? AND user_id=? AND deleted_at IS NULL LIMIT 1");
            $st->execute([$pet_id, $user_id]);
            return $st->fetch() ?: null;
        } catch (\Throwable $e) { return null; }
    }

    public static function savePhoto(int $user_id, int $pet_id, array $file, bool $is_update, ?string $old_photo_path = null): array
    {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) { return ['ok' => false, 'error' => 'No file uploaded or upload error occurred.']; }
        if ($is_update) { $pet = self::getOne($user_id, $pet_id); if (!$pet) return ['ok' => false, 'error' => 'Pet not found.']; }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!in_array($file['type'], array_keys($allowed))) { return ['ok' => false, 'error' => 'Invalid image type. Only JPG, PNG, WEBP, GIF are allowed.']; }
        $dir = __DIR__ . '/../../uploads/pets/pet-images/';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $ext = $allowed[$file['type']];
        $filename = self::_generateFilename($user_id, $ext);
        if (!$filename) { return ['ok' => false, 'error' => 'Could not generate a filename.']; }
        $full_path = $dir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $full_path)) { return ['ok' => false, 'error' => 'Failed to save the uploaded file.']; }
        if ($is_update && $old_photo_path) { $old_full_path = __DIR__ . '/../../' . $old_photo_path; if (is_file($old_full_path)) { @unlink($old_full_path); } }
        $db_path = 'uploads/pets/pet-images/' . $filename;
        if (!$is_update) { return ['ok' => true, 'path' => $db_path]; }
        try {
            $pdo = db();
            $stmt = $pdo->prepare("UPDATE pets SET photo_path = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$db_path, $pet_id, $user_id]);
            return ['ok' => true, 'path' => $db_path];
        } catch (\Throwable $e) {
            @unlink($full_path);
            error_log('[PET_SAVE_PHOTO_DB] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Server error updating photo path.'];
        }
    }

    public static function docsList(int $user_id, int $pet_id): array
    {
        $pdo = db();
        try {
            $own = $pdo->prepare("SELECT id FROM pets WHERE id=? AND user_id=? AND deleted_at IS NULL");
            $own->execute([$pet_id, $user_id]);
            if (!$own->fetch()) return [];
            $st = $pdo->prepare("SELECT * FROM pet_documents WHERE pet_id=? AND user_id=? ORDER BY uploaded_at DESC");
            $st->execute([$pet_id, $user_id]);
            return $st->fetchAll() ?: [];
        } catch (\Throwable $e) { return []; }
    }

    public static function saveDocumentForPet(int $petId, array $file, string $title, string $docType): array
    {
        $staffId = (int)($_SESSION['user']['id'] ?? 0);
        if ($staffId === 0) {
            return ['ok' => false, 'error' => 'Invalid staff session. Cannot upload.'];
        }
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf', 'doc', 'docx', 'txt', 'csv', 'xls', 'xlsx', 'ppt', 'pptx', 'wps', 'et', 'dps'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            return ['ok' => false, 'error' => "File type '{$fileExtension}' is not allowed."];
        }
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'File upload error.'];
        }
        $pdo = db();
        $stmt = $pdo->prepare("SELECT user_id FROM pets WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$petId]);
        $pet = $stmt->fetch();
        if (!$pet) {
            return ['ok' => false, 'error' => 'Pet not found.'];
        }
        $owner_user_id = $pet['user_id'];
        $maxSize = 20 * 1024 * 1024; 
        if ($file['size'] > $maxSize) {
            return ['ok' => false, 'error' => 'File size exceeds 20MB limit.'];
        }
        $dir = __DIR__ . '/../../uploads/pets/' . $petId . '/';
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            return ['ok' => false, 'error' => 'Failed to create upload directory for pet.'];
        }
        $safeFilename = 'doc_' . uniqid() . '_' . time() . '.' . $fileExtension;
        $fullPath = $dir . $safeFilename;
        $dbPath = 'uploads/pets/' . $petId . '/' . $safeFilename;
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['ok' => false, 'error' => 'Failed to move the uploaded file.'];
        }
        try {
            $sql = "INSERT INTO pet_documents (pet_id, user_id, uploaded_by_staff_id, doc_type, title, file_path, mime_type, size_bytes) 
                    VALUES (:pet_id, :user_id, :staff_id, :doc_type, :title, :file_path, :mime_type, :size_bytes)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':pet_id' => $petId,
                ':user_id' => $owner_user_id, 
                ':staff_id' => $staffId,
                ':doc_type' => $docType,
                ':title' => $title,
                ':file_path' => $dbPath,
                ':mime_type' => $file['type'],
                ':size_bytes' => $file['size']
            ]);
            return ['ok' => true, 'id' => $pdo->lastInsertId()];
        } catch (\Throwable $e) {
            @unlink($fullPath);
            error_log('[PET_DOC_SAVE_STAFF] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Server error occurred while saving the document.'];
        }
    }

    public static function docsListForStaff(int $petId): array
    {
        $pdo = db();
        try {
            $sql = "SELECT 
                        pd.*,
                        TRIM(CONCAT_WS(' ', up.prefix, u.first_name, u.last_name, up.suffix)) as uploader_name
                    FROM pet_documents pd
                    LEFT JOIN users u ON pd.uploaded_by_staff_id = u.id
                    LEFT JOIN user_profiles up ON u.id = up.user_id
                    WHERE pd.pet_id = ? 
                    ORDER BY pd.uploaded_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$petId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[PET_DOC_LIST_STAFF] ' . $e->getMessage());
            return [];
        }
    }

    public static function deleteDocumentByStaff(int $docId): bool
    {
        $pdo = db();
        try {
            $stmt = $pdo->prepare("SELECT file_path FROM pet_documents WHERE id = ? LIMIT 1");
            $stmt->execute([$docId]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$document) { return false; }
            $pdo->beginTransaction();
            $deleteStmt = $pdo->prepare("DELETE FROM pet_documents WHERE id = ?");
            $deleteStmt->execute([$docId]);
            if ($deleteStmt->rowCount() > 0) {
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
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[PET_DOC_DELETE_STAFF] ' . $e->getMessage());
            return false;
        }
    }

    public static function docsListForOwner(int $userId): array
    {
        $pdo = db();
        try {
            // ===== INAYOS NA QUERY AT SYNTAX DITO =====
            $sql = "SELECT 
                        pd.id, 
                        pd.title, 
                        pd.doc_type, 
                        pd.file_path, 
                        pd.uploaded_at,
                        p.name as pet_name,
                        TRIM(CONCAT_WS(' ', up.prefix, u.first_name, u.last_name, up.suffix)) as uploader_name
                    FROM pet_documents pd
                    JOIN pets p ON pd.pet_id = p.id
                    LEFT JOIN users u ON pd.uploaded_by_staff_id = u.id
                    LEFT JOIN user_profiles up ON u.id = up.user_id
                    WHERE pd.user_id = ?
                    ORDER BY pd.uploaded_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[PET_DOC_LIST_OWNER] ' . $e->getMessage());
            return [];
        }
    }
}