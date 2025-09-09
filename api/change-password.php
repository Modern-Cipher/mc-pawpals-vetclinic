<?php
// api/change-password.php
require_once __DIR__ . '/../middleware/auth.php';

require_login(); 

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user']['id'];

$current_pass = $data['current_password'] ?? '';
$new_pass = $data['new_password'] ?? '';
$confirm_pass = $data['confirm_password'] ?? '';

// --- Validation Logic ---
if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    exit;
}

if ($new_pass !== $confirm_pass) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
    exit;
}

$password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
if (!preg_match($password_regex, $new_pass)) {
    http_response_code(400);
    $message = 'Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&).';
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}
// --- End of Validation ---

$pdo = db();

try {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        exit;
    }

    if (!password_verify($current_pass, $user['password'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
        exit;
    }

    $new_password_hash = password_hash($new_pass, PASSWORD_DEFAULT);
    
    // Start a transaction to ensure all updates succeed or none at all
    $pdo->beginTransaction();

    // 1. Update the password AND reset the must_change_password flag in the users table
    $update_user_stmt = $pdo->prepare(
        "UPDATE users SET password = :password, must_change_password = 0 WHERE id = :id"
    );
    $update_user_stmt->execute([
        ':password' => $new_password_hash,
        ':id' => $user_id
    ]);

    // 2. Also remove any flag from the user_security_flags table
    $delete_flag_stmt = $pdo->prepare("DELETE FROM user_security_flags WHERE user_id = :id");
    $delete_flag_stmt->execute([':id' => $user_id]);

    // If everything is okay, commit the changes
    $pdo->commit();
    
    // 3. Update the session immediately so the user doesn't see the prompt again
    $_SESSION['user']['must_change_password'] = 0;

    echo json_encode(['status' => 'success', 'message' => 'Password changed successfully!']);

} catch (Throwable $e) {
    // If an error occurred, roll back the transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[CHANGE_PASSWORD_API] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A server error occurred. Please try again later.']);
}