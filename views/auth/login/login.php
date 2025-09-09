<?php
// views/auth/login/login.php
session_start();
require_once __DIR__ . '/../../../middleware/auth.php';
check_remember_me();

$BASE   = base_path();
$error  = '';
$notice = '';

if (!empty($_GET['reason'])) {
    $reasons = [
        'need_login'       => 'Please sign in to continue.',
        'inactive'         => 'Your account is inactive.',
        'session_conflict' => 'Your account has been logged out because it was accessed from another device.',
        'server'           => 'Temporary server issue. Please sign in again.'
    ];
    $notice = $reasons[$_GET['reason']] ?? '';
}

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'user';
    $target = ($role === 'admin') ? 'dashboard/admin' : (($role === 'staff') ? 'dashboard/staffs' : 'dashboard/users');
    header('Location: ' . $BASE . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    if ($u === '' || $p === '') {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $pdo = db();
            $sql = "SELECT * FROM users WHERE (username = :u1 OR email = :u2) LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':u1' => $u, ':u2' => $u]);
            $row = $stmt->fetch();

            if (!$row) {
                $error = 'Account not found.';
            } elseif (!password_verify($p, $row['password'])) {
                $error = 'Invalid username/email or password.';
            } elseif (!(int)$row['is_active']) {
                $error = 'Account is inactive.';
            } else {
                if (!empty($row['active_session_id'])) {
                    try {
                        $pdo->prepare("INSERT INTO login_attempts (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'session_takeover')")
                            ->execute([$row['id'], $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
                    } catch (Throwable $e) {}
                }

                session_regenerate_id(true);
                $profile_data = Profile::getByUserId((int)$row['id']);
                $must = (int)($row['must_change_password'] ?? 0);
                try {
                    $st = $pdo->prepare("SELECT must_change_password FROM user_security_flags WHERE user_id=:id LIMIT 1");
                    $st->execute([':id' => (int)$row['id']]);
                    if($sec = $st->fetch()) { $must = max($must, (int)($sec['must_change_password'] ?? 0)); }
                } catch (Throwable $e) {}

                $_SESSION['user'] = [
                    'id'                   => (int)$row['id'],
                    'name'                 => Profile::formatFullName($profile_data ?: $row),
                    'avatar'               => $BASE . ($profile_data['avatar_path'] ?? 'assets/images/person1.jpg'),
                    'designation'          => $profile_data['designation'] ?? null,
                    'role'                 => $row['role'],
                    'must_change_password' => $must,
                    'login_timestamp'      => time()
                ];
                $_SESSION['just_logged_in'] = 1;
                $_SESSION['last_alert_check'] = time();

                $pdo->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ?, active_session_id = ?, is_online = 1 WHERE id = ?")
                    ->execute([$_SERVER['REMOTE_ADDR'] ?? null, session_id(), $row['id']]);

                if (!empty($_POST['remember_me'])) {
                    $selector = bin2hex(random_bytes(16));
                    $validator = bin2hex(random_bytes(32));
                    $expires_at = (new DateTime())->add(new DateInterval('P30D'))->format('Y-m-d H:i:s');
                    $pdo->prepare("INSERT INTO auth_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)")
                        ->execute([$row['id'], $selector, hash('sha256', $validator), $expires_at]);
                    setcookie('remember_me', $selector . ':' . $validator, ['expires' => time() + 86400 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
                }

                $target = ($row['role'] === 'admin') ? 'dashboard/admin' : (($row['role'] === 'staff') ? 'dashboard/staffs' : 'dashboard/users');
                header('Location: ' . $BASE . $target);
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Server error. Please try again.';
            error_log('[LOGIN ERROR] '.$e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>PawPals - Sign In</title>
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0 4px; /* Maliit na padding para hindi dikit sa gilid */
        }
        
        /* --- ITO ANG MGA BAGONG DAGDAG --- */
        .form-options a {
            font-size: 0.9rem;
            text-decoration: none; /* Tatanggalin ang underline */
            color: #ef4444;       /* Isang magandang shade ng pula */
            font-weight: 500;
            transition: text-decoration 0.2s;
        }

        .form-options a:hover {
            text-decoration: underline; /* Ibabalik ang underline kapag naka-hover */
        }
        /* --- END OF BAGONG DAGDAG --- */

    </style>
</head>
<body>
    <a class="back-home" href="<?= $BASE ?>"><i class="fa-solid fa-arrow-left"></i><span>Back to Home</span></a>
    <div class="auth-layout">
        <div class="auth-branding-panel">
            <div class="branding-content">
                <div class="logo">🐾 PawPals</div>
                <h1>Hello, Pal! 👋</h1>
                <p>The best place for your best friend. Login to manage your pet's health and appointments.</p>
                <div class="copyright">&copy; <?= date('Y') ?> PawPals. All rights reserved.</div>
            </div>
        </div>
        <div class="auth-form-panel">
            <div class="auth-card">
                <div class="form-header"><h3>Welcome Back!</h3></div>
                <?php if ($error): ?>
                    <script>Swal.fire({icon:'error',title:'Login failed',text:<?= json_encode($error) ?>});</script>
                <?php elseif ($notice): ?>
                    <script>Swal.fire({icon:'info',title:'Notice',text:<?= json_encode($notice) ?>});</script>
                <?php endif; ?>
                <form method="post" novalidate>
                    <div class="form-group">
                        <label for="username">Username / Email</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username or email" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    </div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember_me" value="1">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="<?= $BASE ?>auth/forgot">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary">Login Now</button>
                    
                </form>
                <div class="auth-switch-link">
                    <p>Don't have an account? <a href="<?= $BASE ?>auth/signup">Create one now</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>