<?php
// controllers/setting/setting_controller.php

require_once dirname(__DIR__) . '/auth/auth.php';
require_once dirname(__DIR__, 2) . '/config/db.php';

// Get admin info from session
$adminName = isset($_SESSION['admin_name']) ? strtoupper($_SESSION['admin_name']) : 'ADMIN';
$adminRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator';
$schoolDetailsStatus = $_SESSION['school_details_status'] ?? '';
unset($_SESSION['school_details_status']);

// Fetch positions from the database
try {
    $stmtPos = $pdo->query("SELECT * FROM position ORDER BY id DESC");
    $positions = $stmtPos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $positions = [];
}

// Fetch employees from the database
try {
    $stmtEmp = $pdo->query("SELECT * FROM employee ORDER BY id DESC");
    $employees = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('setting_controller.php employee fetch error: ' . $e->getMessage());
    $employees = [];
}

// Fetch units of measure from the database
try {
    $stmtCat = $pdo->query("SELECT * FROM unit_measure ORDER BY id DESC");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('setting_controller.php unit_measure fetch error: ' . $e->getMessage());
    $categories = [];
}

// Fetch admin profile data
try {
    $stmtAdmin = $pdo->prepare("SELECT * FROM admin LIMIT 1");
    $stmtAdmin->execute();
    $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $adminData = [];
}

// School details are stored as a single editable record.
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS school_details (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        school_name VARCHAR(255) NOT NULL DEFAULT '',
        school_address VARCHAR(255) NOT NULL DEFAULT '',
        head_name VARCHAR(255) NOT NULL,
        head_position VARCHAR(255) NOT NULL,
        custodian_name VARCHAR(255) NOT NULL,
        custodian_position VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $schoolDetails = $pdo->query("SELECT id, school_name, school_address, head_name, head_position, custodian_name, custodian_position FROM school_details ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
    $schoolDetailsAvailable = true;
} catch (PDOException $e) {
    error_log('setting_controller.php school_details fetch error: ' . $e->getMessage());
    $schoolDetails = [];
    $schoolDetailsAvailable = false;
}
