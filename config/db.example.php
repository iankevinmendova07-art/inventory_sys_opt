<?php
// Example Database Configuration
// Copy or rename this file to db.php on your production server if needed.

$host     = 'localhost';              // Usually localhost on cPanel / shared hosts
$db_name  = 'your_cpanel_database';   // e.g. u123456_inventory_sys
$username = 'your_cpanel_username';   // e.g. u123456_admin
$password = 'your_strong_password';   // Database user password

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
    error_log("Database Connection Error: " . $e->getMessage());
    http_response_code(500);
    die("Database Connection Error. Please contact administrator.");
}
?>

