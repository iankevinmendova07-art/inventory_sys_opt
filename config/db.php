<?php
// Ensure session security defaults before session starts anywhere
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
}

// Database configuration settings
// Automatically reads environment variables if defined on live hosting/cPanel/Docker, or falls back to local defaults
$appEnv   = strtolower((string)(getenv('APP_ENV') ?: 'local'));
$host     = getenv('DB_HOST')     ?: 'localhost';
$db_name  = getenv('DB_NAME')     ?: 'inventory_sys_db';
$username = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

if ($appEnv === 'production' && ($username === 'root' || $password === '')) {
    error_log('Production database configuration is incomplete or uses unsafe defaults.');
    http_response_code(500);
    die('Database configuration is incomplete. Please contact the administrator.');
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db_name};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Log detailed connection error to server error log without leaking credentials to web visitors
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Friendly error display for visitors
    http_response_code(500);
    die("Database Connection Error. Please verify server database configuration or contact system administrator.");
}
?>