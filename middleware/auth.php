<?php
// middleware/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/connection.php';
// --- Include Profile model for user session hydration ---
require_once __DIR__ . '/../app/models/Profile.php';

/** Robust base path detector (works with mod_rewrite + subfolders) */
function base_path(): string {
    // 1) Try SCRIPT_NAME (ok kapag hindi nabasag ng rewrite)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(dirname($scriptName), '/\\');

    // 2) Kapag "/" o empty (common sa rewrites), derive from REQUEST_URI
    if ($base === '' || $base === '/') {
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $parts = array_values(array_filter(explode('/', $uriPath)));
        // Assume first segment is the app folder, UNLESS it's a known route prefix
        if (!empty($parts) && !in_array($parts[0], ['dashboard','auth','api','views','assets'])) {
            $base = '/' . $parts[0];
        } else {
            $base = '';
        }
    }

    return ($base === '' ? '/' : $base . '/');
}

function request_path(): string {
    $reqPath   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $path = trim(str_starts_with($reqPath, $scriptDir) ? substr($reqPath, strlen($scriptDir)) : $reqPath, '/');
    return rtrim($path, '/');
}
function current_user(): array { return $_SESSION['user'] ?? []; }
function is_role(string $role): bool { return ($_SESSION['user']['role'] ?? null) === $role; }
function staff_allowed_slugs(int $user_id): array {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT allowed_json FROM staff_permissions WHERE user_id=:uid");
        $stmt->execute([':uid' => $user_id]);
        $row = $stmt->fetch();
        if (!$row) return [];
        $arr = json_decode((string)$row['allowed_json'], true);
        return is_array($arr) ? $arr : [];
    } catch (Throwable $e) {
        return [];
    }
}

function check_remember_me(): void {
    if (!empty($_SESSION['user'])) return;
    if (empty($_COOKIE['remember_me'])) return;

    [$selector, $validator] = explode(':', $_COOKIE['remember_me'], 2) + [null,null];
    if (!$selector || !$validator) return;

    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM auth_tokens WHERE selector = ? AND expires_at >= NOW() LIMIT 1");
        $stmt->execute([$selector]);
        if ($token = $stmt->fetch()) {
            if (hash_equals($token['validator_hash'], hash('sha256', $validator))) {
                $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                $user_stmt->execute([$token['user_id']]);
                if ($user = $user_stmt->fetch()) {
                    session_regenerate_id(true);
                    $new_session_id = session_id();

                    $profile_data = Profile::getByUserId((int)$user['id']);
                    $BASE = base_path();

                    $must = (int)($user['must_change_password'] ?? 0);
                    try {
                        $stf = $pdo->prepare("SELECT must_change_password FROM user_security_flags WHERE user_id=? LIMIT 1");
                        $stf->execute([(int)$user['id']]);
                        if($sec = $stf->fetch()) { $must = max($must, (int)($sec['must_change_password'] ?? 0)); }
                    } catch (Throwable $e) {}

                    $_SESSION['user'] = [
                        'id'                   => (int)$user['id'],
                        'name'                 => Profile::formatFullName($profile_data ?: $user),
                        'avatar'               => $BASE . ($profile_data['avatar_path'] ?? 'assets/images/person1.jpg'),
                        'designation'          => $profile_data['designation'] ?? null,
                        'role'                 => $user['role'],
                        'must_change_password' => $must,
                        'login_timestamp'      => time()
                    ];
                    $_SESSION['last_alert_check'] = time();

                    $pdo->prepare("UPDATE users SET active_session_id = ?, is_online = 1, last_login_at = NOW() WHERE id = ?")
                        ->execute([$new_session_id, (int)$user['id']]);

                    $pdo->prepare("DELETE FROM auth_tokens WHERE id = ?")->execute([$token['id']]);

                    $new_selector = bin2hex(random_bytes(16));
                    $new_validator = bin2hex(random_bytes(32));
                    $expires_at = (new DateTime())->add(new DateInterval('P30D'))->format('Y-m-d H:i:s');
                    $pdo->prepare("INSERT INTO auth_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)")
                        ->execute([$user['id'], $new_selector, hash('sha256', $new_validator), $expires_at]);
                    setcookie('remember_me', $new_selector . ':' . $new_validator, ['expires' => time() + 86400 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);

                    $target = ($user['role'] === 'admin') ? 'dashboard/admin' : (($user['role'] === 'staff') ? 'dashboard/staffs' : 'dashboard/users');
                    header('Location: ' . $BASE . $target);
                    exit;
                }
            }
        }
    } catch (Throwable $e) { error_log("Remember Me Error: " . $e->getMessage()); }
}

function require_login(array $roles = []): void {
    $BASE = base_path();
    if (empty($_SESSION['user'])) { header('Location: ' . $BASE . 'auth/login?reason=need_login'); exit; }
    $uid = (int)($_SESSION['user']['id'] ?? 0);
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT u.active_session_id, u.is_active, u.role, u.email, u.must_change_password FROM users u WHERE u.id = ? LIMIT 1");
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if (!$row || !(int)$row['is_active']) { logout_now(false); header('Location: ' . $BASE . 'auth/login?reason=inactive'); exit; }
        if (!empty($row['active_session_id']) && $row['active_session_id'] !== session_id()) { logout_now(false); header('Location: ' . $BASE . 'auth/login?reason=session_conflict'); exit; }
        if ($roles && !in_array($row['role'], $roles, true)) { header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden'); echo '403 - Forbidden'; exit; }
        $must = (int)($row['must_change_password'] ?? 0);
        try {
            $stf = $pdo->prepare("SELECT must_change_password FROM user_security_flags WHERE user_id=? LIMIT 1");
            $stf->execute([$uid]);
            if($sec = $stf->fetch()) { $must = max($must, (int)($sec['must_change_password'] ?? 0)); }
        } catch (Throwable $e) {}
        $_SESSION['user']['must_change_password'] = $must;
        if ($must > 0) return;
        if ($row['role'] === 'user') {
            $v = $pdo->prepare("SELECT verified_at FROM user_email_status WHERE user_id=? LIMIT 1");
            $v->execute([$uid]);
            if (empty($v->fetch()['verified_at'])) {
                $_SESSION['pending_verify_email'] = $row['email'] ?? null;
                header('Location: ' . $BASE . 'auth/verify-email?email=' . urlencode($row['email'] ?? ''));
                exit;
            }
        }
    } catch (Throwable $e) { logout_now(false); header('Location: ' . $BASE . 'auth/login?reason=server'); exit; }
}

function require_staff_permission(string $slug): void {
    $user = $_SESSION['user'] ?? null;
    if (!$user || ($user['role'] ?? null) !== 'staff') {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden'); echo '403 - Forbidden'; exit;
    }
    $allowed = staff_allowed_slugs((int)$user['id']);
    if (!in_array($slug, $allowed, true)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden'); echo '403 - Forbidden'; exit;
    }
}

function logout_now(bool $redirect = true): void {
    $BASE = base_path();
    if (isset($_COOKIE['remember_me'])) {
        [$selector] = explode(':', $_COOKIE['remember_me'], 2) + [null];
        if ($selector) { try { db()->prepare("DELETE FROM auth_tokens WHERE selector = ?")->execute([$selector]); } catch (Throwable $e) {} }
        setcookie('remember_me', '', time() - 3600, '/');
    }
    if (!empty($_SESSION['user']['id'])) {
        try {
            db()->prepare("UPDATE users SET active_session_id = NULL, is_online = 0, last_logout_at = NOW() WHERE id = ? AND active_session_id = ?")
               ->execute([(int)$_SESSION['user']['id'], session_id()]);
        } catch (Throwable $e) {}
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    if ($redirect) { header('Location: ' . $BASE . 'auth/login'); exit; }
}
