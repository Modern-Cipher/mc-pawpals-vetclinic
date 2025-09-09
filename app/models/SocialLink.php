<?php
// app/models/SocialLink.php
declare(strict_types=1);

require_once __DIR__ . '/../../config/connection.php';

class SocialLink {
    public static function getAll(): array {
        try {
            $pdo = db();
            $stmt = $pdo->query(
                "SELECT id, platform, icon_class, url, display_order, created_at, updated_at
                 FROM social_links
                 ORDER BY display_order ASC, platform ASC, id ASC"
            );
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('[SOCIAL_LINK_GETALL] ' . $e->getMessage());
            return [];
        }
    }

    /** Normalize and validate incoming payload */
    private static function normalize(array $data): array {
        $platform = trim((string)($data['platform'] ?? ''));
        $icon     = trim((string)($data['icon_class'] ?? ($data['icon'] ?? '')));
        $url      = trim((string)($data['url'] ?? ''));
        $order    = isset($data['display_order']) ? (int)$data['display_order'] : 0;

        if ($platform === '' || $icon === '' || $url === '') {
            throw new InvalidArgumentException('Platform, icon_class and url are required.');
        }
        if (!preg_match('#^https?://#i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('URL must start with http:// or https:// and be valid.');
        }
        return compact('platform','icon','url','order');
    }

    /**
     * Create a social link.
     * @param array $data keys: platform, icon_class, url, display_order
     * @param int $user_id (unused, kept for signature compatibility)
     * @param string|null $err will contain the reason on failure
     */
    public static function create(array $data, int $user_id = 0, ?string &$err = null): bool {
        try {
            $p = self::normalize($data);

            $pdo = db();

            // Optional duplicate guard (either platform or url already exists)
            $chk = $pdo->prepare("SELECT id FROM social_links WHERE platform = :p OR url = :u LIMIT 1");
            $chk->execute([':p' => $p['platform'], ':u' => $p['url']]);
            if ($chk->fetch()) {
                throw new RuntimeException('Platform or URL already exists.');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO social_links (platform, icon_class, url, display_order)
                 VALUES (:p, :i, :u, :o)"
            );
            return $stmt->execute([
                ':p' => $p['platform'],
                ':i' => $p['icon'],
                ':u' => $p['url'],
                ':o' => $p['order'],
            ]);
        } catch (Throwable $e) {
            $err = $e->getMessage();
            error_log('[SOCIAL_LINK_CREATE] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a social link.
     * @param int $id
     * @param array $data
     * @param int $user_id (unused)
     * @param string|null $err will contain the reason on failure
     */
    public static function update(int $id, array $data, int $user_id = 0, ?string &$err = null): bool {
        try {
            if ($id <= 0) throw new InvalidArgumentException('Invalid ID');

            $p = self::normalize($data);
            $pdo = db();

            // Optional duplicate guard (exclude self)
            $chk = $pdo->prepare("SELECT id FROM social_links WHERE (platform = :p OR url = :u) AND id <> :id LIMIT 1");
            $chk->execute([':p' => $p['platform'], ':u' => $p['url'], ':id' => $id]);
            if ($chk->fetch()) {
                throw new RuntimeException('Platform or URL already exists.');
            }

            $stmt = $pdo->prepare(
                "UPDATE social_links
                   SET platform = :p,
                       icon_class = :i,
                       url = :u,
                       display_order = :o
                 WHERE id = :id"
            );
            return $stmt->execute([
                ':id' => $id,
                ':p'  => $p['platform'],
                ':i'  => $p['icon'],
                ':u'  => $p['url'],
                ':o'  => $p['order'],
            ]);
        } catch (Throwable $e) {
            $err = $e->getMessage();
            error_log('[SOCIAL_LINK_UPDATE] ' . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool {
        try {
            if ($id <= 0) return false;
            $pdo = db();
            $stmt = $pdo->prepare("DELETE FROM social_links WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            error_log('[SOCIAL_LINK_DELETE] ' . $e->getMessage());
            return false;
        }
    }
}
