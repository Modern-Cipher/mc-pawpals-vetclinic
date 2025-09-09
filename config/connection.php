<?php
/**
 * Dynamic PDO connection with environment detection (Local vs. Live)
 * and PH time zone (XAMPP-safe)
 */
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

if (!defined('APP_DEBUG')) {
    // Set to false when your site is fully live to hide detailed errors from public
    define('APP_DEBUG', true); 
}

// --- Environment Detection ---
// This condition checks if the code is running on your local machine ('localhost')
// or on a live server.

if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') {
    // ** LOCAL (XAMPP) Settings **
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'pawpals');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // ** LIVE (HOSTINGER) Settings **
    // Kinuha natin ang mga ito mula sa screenshot mo
    define('DB_HOST', 'localhost'); // Usually 'localhost' on Hostinger
    define('DB_PORT', '3306');
    define('DB_NAME', 'u317770660_pawpals');
    define('DB_USER', 'u317770660_pawpals');
    
    // ⚠️ PINAKA-IMPORTANTE: PALITAN MO ITO!
    define('DB_PASS', 'Moderncipher2025@'); 
}


/**
 * Global PDO accessor
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn    = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
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
            // Provide a more user-friendly error
            die('Database Connection Failed. Please check the credentials or contact support. Error: ' . htmlspecialchars($e->getMessage()));
        }
        http_response_code(500);
        die('A database error occurred. Please try again later.');
    }
}