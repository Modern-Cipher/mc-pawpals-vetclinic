<?php
// app/models/Announcement.php
declare(strict_types=1);

require_once __DIR__ . '/../../config/connection.php';

class Announcement
{
    /* ================= Helpers ================= */

    private static function root(): string {
        return realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../';
    }
    private static function abs(string $rel): string {
        return rtrim(self::root(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($rel, DIRECTORY_SEPARATOR);
    }
    private static function toMysqlDT(?string $v): ?string {
        $v = trim((string)$v);
        if ($v === '') return null;
        $v = str_replace('T', ' ', $v);
        $ts = strtotime($v);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
    private static function normAudience(string $a): string {
        $a = strtolower(trim($a));
        return in_array($a, ['all','admins','staff','owners'], true) ? $a : 'all';
    }
    private static function normLocation(string $l): string {
        $l = strtolower(trim($l));
        return in_array($l, ['dashboard','landing','both'], true) ? $l : 'dashboard';
    }
    private static function randCode(int $len = 6): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $s=''; for($i=0;$i<$len;$i++) $s.=$chars[random_int(0,strlen($chars)-1)];
        return $s;
    }
    private static function pickUploadDir(): array {
        $preferredRel='uploads/announcements'; $preferredAbs=self::abs($preferredRel);
        $typoRel='uploads/annoucements';       $typoAbs=self::abs($typoRel);
        if (is_dir($typoAbs) && !is_dir($preferredAbs)) return [$typoRel, $typoAbs];
        return [$preferredRel, $preferredAbs];
    }
    private static function uploadImage(?array $file): array {
        if (!$file || !isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return [null, 'Upload error code: ' . $file['error']];
        if (($file['size'] ?? 0) > 4*1024*1024) return [null, 'Image too large (max 4MB)'];

        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)) ?: 'bin';
        if (class_exists('finfo')) {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
            $map  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (isset($map[$mime])) $ext = $map[$mime];
        }
        if (!in_array($ext, $allowed, true)) return [null, 'Only JPG/PNG/WEBP allowed'];

        [$dirRel, $dirAbs] = self::pickUploadDir();
        if (!is_dir($dirAbs)) {
            if (!@mkdir($dirAbs, 0777, true) && !is_dir($dirAbs)) return [null, 'Cannot create upload directory'];
        }

        $name = self::randCode(6) . '.' . $ext;
        $dest = rtrim($dirAbs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

        $tries=0;
        while (file_exists($dest) && $tries<5) {
            $name = self::randCode(6) . '.' . $ext;
            $dest = rtrim($dirAbs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
            $tries++;
        }

        $ok = @move_uploaded_file($file['tmp_name'], $dest);
        if (!$ok) {
            $ok = @rename($file['tmp_name'], $dest);
            if (!$ok) $ok = @copy($file['tmp_name'], $dest) && @unlink($file['tmp_name']);
        }
        if (!$ok) return [null, 'Failed to move uploaded file'];

        @chmod($dest, 0644);
        return [$dirRel . '/' . $name, null];
    }
    private static function unlinkIfExists(?string $rel): void {
        if (!$rel) return;
        $abs = self::abs($rel);
        if (is_file($abs)) @unlink($abs);
    }

    /* ================= Queries ================= */

    public static function all(): array {
        try {
            $pdo = db();
            $sql = "SELECT id, title, body, audience, location, is_published,
                           published_at, expires_at, image_path, external_url, created_by, updated_by,
                           created_at, updated_at
                      FROM announcements
                     ORDER BY created_at DESC, id DESC";
            return $pdo->query($sql)->fetchAll();
        } catch (Throwable $e) {
            error_log('[ANN_ALL] '.$e->getMessage());
            return [];
        }
    }

    private static function find(int $id): ?array {
        $pdo = db();
        $st = $pdo->prepare("SELECT * FROM announcements WHERE id=:id LIMIT 1");
        $st->execute([':id'=>$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(array $data, ?array $imageFile, int $user_id): array {
        try {
            $title = trim((string)($data['title'] ?? ''));
            $body  = trim((string)($data['body'] ?? ''));
            if ($title === '' || $body === '') return [false, 'Title and body are required'];

            $audience     = self::normAudience((string)($data['audience'] ?? 'all'));
            $location     = self::normLocation((string)($data['location'] ?? 'dashboard'));
            $is_published = (int)($data['is_published'] ?? 0);
            $published_at = self::toMysqlDT($data['published_at'] ?? null);
            $expires_at   = self::toMysqlDT($data['expires_at'] ?? null);
            $external_url = trim((string)($data['external_url'] ?? '')) ?: null; // Set to null if empty

            [$imageRel, $upErr] = self::uploadImage($imageFile);
            if ($upErr) error_log('[ANN_UPLOAD_WARN] '.$upErr);

            $pdo = db();
            $st = $pdo->prepare(
                "INSERT INTO announcements
                    (title, body, audience, location, is_published, published_at, expires_at, image_path, external_url, created_by, updated_by)
                 VALUES
                    (:t, :b, :a, :l, :p, :pub, :exp, :img, :url, :cb, :ub)"
            );
            $ok = $st->execute([
                ':t'   => $title,
                ':b'   => $body,
                ':a'   => $audience,
                ':l'   => $location,
                ':p'   => $is_published,
                ':pub' => $published_at,
                ':exp' => $expires_at,
                ':img' => $imageRel,
                ':url' => $external_url,
                ':cb'  => $user_id,
                ':ub'  => $user_id
            ]);
            
            if (!$ok) {
                $errorInfo = $st->errorInfo();
                $dbErrorMessage = $errorInfo[2] ?? 'Unknown DB error';
                if ($imageRel) self::unlinkIfExists($imageRel);
                return [false, $dbErrorMessage];
            }
            
            return [$ok, null];
        } catch (Throwable $e) {
            error_log('[ANN_CREATE] '.$e->getMessage());
            return [false, $e->getMessage()];
        }
    }

    public static function update(int $id, array $data, ?array $imageFile, int $user_id): array {
        try {
            if ($id <= 0) return [false, 'Invalid ID'];
            $row = self::find($id);
            if (!$row)   return [false, 'Record not found'];

            $title = trim((string)($data['title'] ?? $row['title']));
            $body  = trim((string)($data['body'] ?? $row['body']));
            if ($title === '' || $body === '') return [false, 'Title and body are required'];

            $audience     = self::normAudience((string)($data['audience'] ?? $row['audience']));
            $location     = self::normLocation((string)($data['location'] ?? $row['location']));
            $is_published = (int)($data['is_published'] ?? $row['is_published']);
            $published_at = self::toMysqlDT($data['published_at'] ?? null);
            $expires_at   = self::toMysqlDT($data['expires_at'] ?? null);
            $external_url = trim((string)($data['external_url'] ?? $row['external_url'])) ?: null;

            $newImageRel = null;
            if ($imageFile && ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                [$newImageRel, $upErr] = self::uploadImage($imageFile);
                if ($upErr) return [false, $upErr];
            }

            $sql = "UPDATE announcements
                       SET title=:t, body=:b, audience=:a, location=:l,
                           is_published=:p, published_at=:pub, expires_at=:exp, external_url=:url,
                           updated_by=:ub"
                   . ($newImageRel ? ", image_path=:img" : "")
                   . " WHERE id=:id";
            $params = [
                ':id'  => $id,
                ':t'   => $title,
                ':b'   => $body,
                ':a'   => $audience,
                ':l'   => $location,
                ':p'   => $is_published,
                ':pub' => $published_at,
                ':exp' => $expires_at,
                ':url' => $external_url,
                ':ub'  => $user_id
            ];
            if ($newImageRel) $params[':img'] = $newImageRel;

            $st = db()->prepare($sql);
            $ok = $st->execute($params);

            if (!$ok) {
                $errorInfo = $st->errorInfo();
                $dbErrorMessage = $errorInfo[2] ?? 'Unknown DB error';
                if ($newImageRel) self::unlinkIfExists($newImageRel);
                return [false, $dbErrorMessage];
            }

            if ($ok && $newImageRel && !empty($row['image_path'])) {
                self::unlinkIfExists($row['image_path']);
            }
            
            return [$ok, null];
        } catch (Throwable $e) {
            error_log('[ANN_UPDATE] '.$e->getMessage());
            return [false, $e->getMessage()];
        }
    }

    public static function delete(int $id): array {
        try {
            if ($id <= 0) return [false, 'Invalid ID'];
            $row = self::find($id);
            if (!$row)   return [true, null];

            $pdo = db();
            $st = $pdo->prepare("DELETE FROM announcements WHERE id=:id");
            $ok = $st->execute([':id'=>$id]);

            if (!$ok) {
                 $errorInfo = $st->errorInfo();
                 return [false, $errorInfo[2] ?? 'DB delete failed'];
            }

            if ($ok && !empty($row['image_path'])) {
                self::unlinkIfExists($row['image_path']);
            }
            return [$ok, null];
        } catch (Throwable $e) {
            error_log('[ANN_DELETE] '.$e->getMessage());
            return [false, $e->getMessage()];
        }
    }
}
