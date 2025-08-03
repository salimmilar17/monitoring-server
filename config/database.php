<?php
// Database configuration
define('DB_HOST', 'sql200.infinityfree.com');
define('DB_NAME', 'if0_39586717_db_servermonitor');
define('DB_USER', 'if0_39586717');
define('DB_PASS', 'iniUntukTA53');

// Site configuration
define('SITE_NAME', 'Monitoring Server System');
define('SITE_URL', 'http://www.server-monitoring-systemesp32.gt.tc');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session with specific settings
session_start();

// Create database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>