<?php
// app/models/Profile.php
require_once __DIR__ . '/../../config/connection.php';

class Profile {
    /**
     * Helper function to format a full name with prefix, middle initial, etc.
     */
    public static function formatFullName(array $profile_data): string {
        $parts = [
            $profile_data['prefix'] ?? null,
            $profile_data['first_name'] ?? null,
            !empty($profile_data['middle_name']) ? substr($profile_data['middle_name'], 0, 1) . '.' : null,
            $profile_data['last_name'] ?? null,
            $profile_data['suffix'] ?? null
        ];
        return implode(' ', array_filter($parts));
    }

    /**
     * NEW HELPER FUNCTION: To format a phone number
     */
    public static function formatPhoneNumber(?string $number): string {
        if (empty($number)) return '';
        $digits = preg_replace('/[^0-9]/', '', $number);
        if (strlen($digits) !== 11) return $number; // Return original if not 11 digits
        return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7);
    }

    public static function getByUserId(int $user_id): ?array {
        if ($user_id <= 0) return null;
        try {
            $pdo = db();
            $stmt = $pdo->prepare(
                "SELECT 
                    u.id, u.username, u.email, u.role, u.first_name, u.last_name, u.phone, u.username_last_changed_at,
                    p.prefix, p.middle_name, p.suffix, p.designation, 
                    p.address_line1, p.address_street, p.address_city, p.address_province, p.address_zip, p.avatar_path
                 FROM users u
                 LEFT JOIN user_profiles p ON u.id = p.user_id
                 WHERE u.id = :user_id"
            );
            $stmt->execute([':user_id' => $user_id]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            error_log('[PROFILE_MODEL_GET] ' . $e->getMessage());
            return null;
        }
    }

    public static function update(int $user_id, array $data): array {
        if ($user_id <= 0) return ['success' => false, 'message' => 'Invalid user.'];

        $pdo = db();
        $current_profile = self::getByUserId($user_id);
        if (!$current_profile) return ['success' => false, 'message' => 'User not found.'];

        // ---- USERNAME RULES ----
        // Current username (may be legacy blank)
        $current_username = trim((string)($current_profile['username'] ?? ''));

        // Proposed username (if field was sent, use it; else treat as unchanged)
        $proposed_username = array_key_exists('username', $data)
            ? trim((string)$data['username'])
            : $current_username;

        // Disallow blank usernames ALWAYS (even if unchanged and currently blank)
        if ($proposed_username === '') {
            return ['success' => false, 'message' => 'Username is required.'];
        }

        $changing_username = ($proposed_username !== $current_username);
        if ($changing_username) {
            // Enforce regex only when changing username
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9_]{4,20}$/', $proposed_username)) {
                return ['success' => false, 'message' => 'Username must be 4–20 chars, include letters and numbers (underscore allowed).'];
            }
            // Cooldown check
            if (!empty($current_profile['username_last_changed_at'])) {
                try {
                    $last_change = new \DateTime($current_profile['username_last_changed_at']);
                    $now  = new \DateTime();
                    $diff = $now->diff($last_change);
                    if ($diff->days < 30) {
                        $days_left = 30 - $diff->days;
                        return ['success' => false, 'message' => "You can change your username again in {$days_left} day(s)."];
                    }
                } catch (\Throwable $e) { /* ignore parse */ }
            }
            // Uniqueness
            try {
                $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
                $stmt_check->execute([':username' => $proposed_username, ':id' => $user_id]);
                if ($stmt_check->fetch()) {
                    return ['success' => false, 'message' => 'Username is already taken.'];
                }
            } catch (\Throwable $e) {
                error_log('[PROFILE_MODEL_UPDATE:USERNAME_UNIQ] ' . $e->getMessage());
                return ['success' => false, 'message' => 'Server error during username check.'];
            }
            // Place back the normalized username into $data so it gets updated
            $data['username'] = $proposed_username;
        } else {
            // Not changing; ensure $data carries the current username for comparison logic
            $data['username'] = $current_username;
        }

        // ---- PHONE RULES ----
        if (isset($data['phone']) && $data['phone'] !== null && $data['phone'] !== '') {
            $phone = preg_replace('/[^0-9]/', '', (string)$data['phone']);
            if (strlen($phone) !== 11) {
                return ['success' => false, 'message' => 'Phone number must be exactly 11 digits.'];
            }
            $data['phone'] = $phone;
        }

        // ZIP numeric only
        if (isset($data['address_zip']) && $data['address_zip'] !== '') {
            if (!is_numeric($data['address_zip'])) {
                return ['success' => false, 'message' => 'ZIP Code must only contain numbers.'];
            }
        }

        $changes_made = false;

        try {
            $pdo->beginTransaction();

            // Phone uniqueness if changed
            if (isset($data['phone']) && $data['phone'] !== '' && $data['phone'] !== ($current_profile['phone'] ?? null)) {
                $stmt_check = $pdo->prepare("SELECT id FROM users WHERE phone = :phone AND id != :id");
                $stmt_check->execute([':phone' => $data['phone'], ':id' => $user_id]);
                if ($stmt_check->fetch()) {
                    $pdo->rollBack();
                    return ['success' => false, 'message' => 'Phone number is already in use by another account.'];
                }
            }

            // Build users update
            $users_fields = ['first_name', 'last_name', 'phone', 'username'];
            $users_params = [];
            $users_set_parts = [];
            foreach ($users_fields as $field) {
                $curr = $current_profile[$field] ?? null;
                if (isset($data[$field]) && $data[$field] !== $curr) {
                    $users_set_parts[] = "$field = :$field";
                    $users_params[':' . $field] = $data[$field];
                }
            }
            if ($changing_username) {
                $users_set_parts[] = "username_last_changed_at = NOW()";
            }

            if (!empty($users_set_parts)) {
                $changes_made = true;
                $users_params[':id'] = $user_id;
                $sql_users = "UPDATE users SET " . implode(', ', $users_set_parts) . " WHERE id = :id";
                $pdo->prepare($sql_users)->execute($users_params);
            }

            // Build profile update/insert
            $profiles_fields = [
                'prefix', 'middle_name', 'suffix', 'designation',
                'address_line1', 'address_street', 'address_city',
                'address_province', 'address_zip'
            ];
            $profiles_params = [':user_id' => $user_id];
            $profiles_update_parts = [];
            foreach ($profiles_fields as $field) {
                $curr = $current_profile[$field] ?? null;
                if (isset($data[$field]) && ($data[$field] !== $curr)) {
                    $profiles_update_parts[] = "$field = :$field";
                    $profiles_params[':' . $field] = ($data[$field] === '') ? null : $data[$field];
                }
            }

            if (!empty($profiles_update_parts)) {
                $changes_made = true;
                $stmt_check = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = :user_id");
                $stmt_check->execute([':user_id' => $user_id]);
                $set_clause = implode(', ', $profiles_update_parts);
                if ($stmt_check->fetch()) {
                    $sql_profiles = "UPDATE user_profiles SET $set_clause WHERE user_id = :user_id";
                } else {
                    $cols = array_keys($profiles_params);
                    $placeholders = implode(', ', $cols);
                    $cols_str = str_replace(':', '', $placeholders);
                    $sql_profiles = "INSERT INTO user_profiles ($cols_str) VALUES ($placeholders)";
                }
                $pdo->prepare($sql_profiles)->execute($profiles_params);
            }

            $pdo->commit();
            return ['success' => true, 'changes_made' => $changes_made];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[PROFILE_MODEL_UPDATE] ' . $e->getMessage());
            return ['success' => false, 'message' => 'A server error occurred.'];
        }
    }

    public static function handleAvatarUpload(int $user_id, array $file): ?string {
        if ($user_id <= 0 || empty($file) || ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) { return null; }

        $pdo = db();
        $stmt_old_path = $pdo->prepare("SELECT avatar_path FROM user_profiles WHERE user_id = :user_id");
        $stmt_old_path->execute([':user_id' => $user_id]);
        $old_profile = $stmt_old_path->fetch();
        $old_avatar_path = $old_profile ? $old_profile['avatar_path'] : null;

        $upload_dir = __DIR__ . '/../../views/profile-images/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0775, true); }

        try {
            $stmt_user = $pdo->prepare("SELECT username FROM users WHERE id = :user_id");
            $stmt_user->execute([':user_id' => $user_id]);
            $user_row = $stmt_user->fetch();
            if (!$user_row) return null;
            $safe_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string)$user_row['username']));
        } catch (\Throwable $e) {
            error_log('[PROFILE_AVATAR_GET_USER] ' . $e->getMessage());
            return null;
        }

        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $random_chars = substr(bin2hex(random_bytes(3)), 0, 6);
        $new_filename = $random_chars . '_' . $safe_username . '.' . $extension;
        $new_path_on_server = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $new_path_on_server)) {
            $db_path = 'views/profile-images/' . $new_filename;
            try {
                $stmt_update = $pdo->prepare("UPDATE user_profiles SET avatar_path = :path WHERE user_id = :user_id");
                $stmt_update->execute([':path' => $db_path, ':user_id' => $user_id]);

                if ($old_avatar_path && strpos($old_avatar_path, 'default') === false && strpos($old_avatar_path, 'person1.jpg') === false) {
                    $full_old_path = __DIR__ . '/../../' . $old_avatar_path;
                    if (file_exists($full_old_path)) { @unlink($full_old_path); }
                }
                return $db_path;
            } catch (\Throwable $e) {
                error_log('[PROFILE_AVATAR_DB] ' . $e->getMessage());
                return null;
            }
        }
        return null;
    }
}
