<?php
// app/models/PetCare.php
require_once __DIR__ . '/../../config/connection.php';

class PetCare {
  /* ---------- helpers ---------- */
  private static function db(){ return db(); }

  private static function randKey($len=6){
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $out=''; for($i=0;$i<$len;$i++) $out .= $chars[random_int(0, strlen($chars)-1)];
    return $out;
  }
  private static function ensureDir($path){ if(!is_dir($path)) @mkdir($path, 0775, true); }

  public static function handleImageUpload(?array $f): ?string {
    if(!$f || ($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    if(($f['error']??0)!==UPLOAD_ERR_OK) return null;
    $ok = ['image/jpeg','image/png','image/webp'];
    if(!in_array(mime_content_type($f['tmp_name']), $ok, true)) return null;
    if(($f['size']??0) > 4*1024*1024) return null;

    $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $name = self::randKey(6) . '.' . $ext;
    $rel  = 'uploads/petcare/images/' . $name;
    $abs  = __DIR__ . '/../../' . $rel;
    self::ensureDir(dirname($abs));
    return move_uploaded_file($f['tmp_name'], $abs) ? $rel : null;
  }

  public static function handleFileUpload(?array $f): ?string {
    if(!$f || ($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    if(($f['error']??0)!==UPLOAD_ERR_OK) return null;
    $ok = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/vnd.ms-powerpoint',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation'
    ];
    $detected = mime_content_type($f['tmp_name']);
    if(!in_array($detected, $ok, true)) return null;
    if(($f['size']??0) > 10*1024*1024) return null;

    $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $name = self::randKey(6) . '.' . $ext;
    $rel  = 'uploads/petcare/files/' . $name;
    $abs  = __DIR__ . '/../../' . $rel;
    self::ensureDir(dirname($abs));
    return move_uploaded_file($f['tmp_name'], $abs) ? $rel : null;
  }

  /* ---------- queries ---------- */
  public static function all(): array {
    $sql = "SELECT * FROM pet_care_tips ORDER BY COALESCE(published_at, created_at) DESC, id DESC";
    return self::db()->query($sql)->fetchAll();
  }

  public static function allPublic(): array {
    $sql = "SELECT * FROM pet_care_tips
            WHERE is_published = 1
              AND (published_at IS NULL OR published_at <= NOW())
              AND (expires_at  IS NULL OR expires_at >= NOW())
            ORDER BY COALESCE(published_at, created_at) DESC, id DESC";
    return self::db()->query($sql)->fetchAll();
  }

  public static function find(int $id): ?array {
    $st = self::db()->prepare("SELECT * FROM pet_care_tips WHERE id=:id LIMIT 1");
    $st->execute([':id'=>$id]); $r = $st->fetch();
    return $r ?: null;
  }

  public static function create(array $data, array $files, int $user_id): bool {
    $imgRel  = self::handleImageUpload($files['image'] ?? null);
    $fileRel = self::handleFileUpload($files['attachment'] ?? null);

    $st = self::db()->prepare(
      "INSERT INTO pet_care_tips
       (title, category, summary, body, content_type, external_url, image_path, file_path,
        is_published, published_at, expires_at, created_by, updated_by)
       VALUES
       (:title,:category,:summary,:body,:content_type,:external_url,:image_path,:file_path,
        :is_published,:published_at,:expires_at,:created_by,:updated_by)"
    );
    return $st->execute([
      ':title'        => trim($data['title'] ?? ''),
      ':category'     => $data['category'] ?? 'health',
      ':summary'      => $data['summary'] ?? null,
      ':body'         => $data['body'] ?? null,
      ':content_type' => $data['content_type'] ?? 'text',
      ':external_url' => $data['external_url'] ?? null,
      ':image_path'   => $imgRel,
      ':file_path'    => $fileRel,
      ':is_published' => (int)($data['is_published'] ?? 1),
      ':published_at' => $data['published_at'] ?: null,
      ':expires_at'   => $data['expires_at']   ?: null,
      ':created_by'   => $user_id,
      ':updated_by'   => $user_id
    ]);
  }

  public static function update(int $id, array $data, array $files, int $user_id): bool {
    $row = self::find($id); if(!$row) return false;

    $imgRel  = self::handleImageUpload($files['image'] ?? null) ?: $row['image_path'];
    $fileRel = self::handleFileUpload($files['attachment'] ?? null) ?: $row['file_path'];

    $st = self::db()->prepare(
      "UPDATE pet_care_tips SET
         title=:title, category=:category, summary=:summary, body=:body,
         content_type=:content_type, external_url=:external_url,
         image_path=:image_path, file_path=:file_path,
         is_published=:is_published, published_at=:published_at, expires_at=:expires_at,
         updated_by=:updated_by
       WHERE id=:id"
    );
    return $st->execute([
      ':id'           => $id,
      ':title'        => trim($data['title'] ?? ''),
      ':category'     => $data['category'] ?? 'health',
      ':summary'      => $data['summary'] ?? null,
      ':body'         => $data['body'] ?? null,
      ':content_type' => $data['content_type'] ?? 'text',
      ':external_url' => $data['external_url'] ?? null,
      ':image_path'   => $imgRel,
      ':file_path'    => $fileRel,
      ':is_published' => (int)($data['is_published'] ?? 1),
      ':published_at' => $data['published_at'] ?: null,
      ':expires_at'   => $data['expires_at']   ?: null,
      ':updated_by'   => $user_id
    ]);
  }

  public static function delete(int $id): bool {
    $st = self::db()->prepare("DELETE FROM pet_care_tips WHERE id=:id");
    return $st->execute([':id'=>$id]);
  }
}
