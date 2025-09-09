<?php
// app/models/Settings.php
declare(strict_types=1);

require_once __DIR__ . '/../../config/connection.php';

class Settings {
    private const UPLOAD_DIR = __DIR__ . '/../../uploads/settings/';
    private const MAX_BYTES  = 4 * 1024 * 1024; // 4MB
    private const ALLOWED_EXT = ['png','jpg','jpeg','webp'];
    private const ALLOWED_MIME = ['image/png','image/jpeg','image/webp'];

    public static function getAll(): array {
        try {
            $pdo = db();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $pairs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            return $pairs ?: [];
        } catch (Throwable $e) {
            error_log('[SETTINGS_MODEL_GET] ' . $e->getMessage());
            return [];
        }
    }

    public static function save(array $data, int $user_id): bool {
        if ($user_id <= 0) return false;

        // remove submit button fields
        foreach ($data as $k => $v) {
            if (strpos($k, 'save_') === 0) unset($data[$k]);
        }
        if (empty($data)) return true;

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "INSERT INTO settings (setting_key, setting_value, updated_by)
                 VALUES (:key, :val, :uid)
                 ON DUPLICATE KEY UPDATE
                   setting_value = VALUES(setting_value),
                   updated_by    = VALUES(updated_by)"
            );
            foreach ($data as $key => $value) {
                $stmt->execute([
                    ':key' => (string)$key,
                    ':val' => is_array($value) ? json_encode($value) : (string)$value,
                    ':uid' => $user_id
                ]);
            }
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[SETTINGS_MODEL_SAVE] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload hero image with:
     * - 4MB limit
     * - mime & extension check
     * - deletes old file (if not default)
     * - saves DB path as 'uploads/settings/<file>'
     */
    public static function handleImageUpload(string $setting_key, array $file, int $user_id): bool {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return false;

        // size check
        if (!isset($file['size']) || $file['size'] > self::MAX_BYTES) {
            error_log('[SETTINGS_UPLOAD] file too large');
            return false;
        }

        // ext + mime check
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) return false;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) finfo_close($finfo);
        if (!$mime || !in_array($mime, self::ALLOWED_MIME, true)) return false;

        // ensure dir
        if (!is_dir(self::UPLOAD_DIR)) {
            @mkdir(self::UPLOAD_DIR, 0775, true);
        }

        // delete old if exists (and not default)
        $settings   = self::getAll();
        $old_dbpath = $settings[$setting_key] ?? null;
        if ($old_dbpath) {
            $full_old = __DIR__ . '/../../' . ltrim($old_dbpath, '/');
            if (is_file($full_old) &&
                !str_contains($old_dbpath, 'veterinarian_2.jpg') &&
                !str_contains($old_dbpath, 'default')) {
                @unlink($full_old);
            }
        }

        // safe filename
        $base = preg_replace('/[^a-zA-Z0-9-_]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
        $rand = bin2hex(random_bytes(6));
        $new  = "{$base}_{$rand}.{$ext}";
        $dest = self::UPLOAD_DIR . $new;

        if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

        $db_path = 'uploads/settings/' . $new;
        return self::save([$setting_key => $db_path], $user_id);
    }
}
