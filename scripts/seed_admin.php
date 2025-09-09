<?php
require_once __DIR__ . '/../config/connection.php';

try {
  $pdo = db();

  // Skip if admin already exists
  $exists = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
  $exists->execute(['admin','admin@example.com']);
  if ($exists->fetch()) {
    echo "Admin already exists.\n";
    exit;
  }

  $hash = password_hash('admin123', PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("
    INSERT INTO users (username,email,password,role,first_name,last_name,sex,phone,is_active)
    VALUES (?,?,?,?,?,?,?,?,1)
  ");
  $stmt->execute([
    'admin',
    'admin@example.com',
    $hash,
    'admin',
    'Alex',     // first_name
    'Moreno',   // last_name
    'male',     // sex
    '+63 912 345 6789' // phone
  ]);

  echo "Seeded admin (admin / admin123)\n";
} catch (Throwable $e) {
  http_response_code(500);
  echo "Error: " . $e->getMessage();
}
