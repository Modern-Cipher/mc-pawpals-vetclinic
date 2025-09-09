<?php
/**
 * Single PDO connection + PH time zone (XAMPP-safe)
 * Edit credentials below or via environment variables.
 */
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

if (!defined('APP_DEBUG')) {
  define('APP_DEBUG', true); // set to false in production
}

/**
 * Prefer environment variables if present; fallback to defaults.
 * This avoids $GLOBALS issues when this file is included inside a function.
 */
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost'); // or 'localhost'
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'pawpals');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');

/**
 * Global PDO accessor
 */
function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $dsn  = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
  $opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];

  try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    // Set timezone per-connection (for NOW(), TIMESTAMP, etc.)
    $pdo->exec("SET time_zone = '+08:00'");
    return $pdo;
  } catch (Throwable $e) {
    error_log('[DB] ' . $e->getMessage());
    if (APP_DEBUG) {
      http_response_code(500);
      die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
    }
    http_response_code(500);
    die('DB connection failed.');
  }
}
