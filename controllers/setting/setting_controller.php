<?php
// controllers/setting/setting_controller.php

require_once dirname(__DIR__) . '/auth/auth.php';
require_once dirname(__DIR__, 2) . '/config/db.php';

// Get admin info from session
$adminName = isset($_SESSION['admin_name']) ? strtoupper($_SESSION['admin_name']) : 'ADMIN';
$adminRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator';

// Keep SMS availability in the database so the setting applies to every SMS workflow.
$smsSettingsAvailable = false;
$smsNotificationsEnabled = false;
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('sms_enabled', '1')");
    $smsNotificationsEnabled = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'sms_enabled'")->fetchColumn() !== '0';
    $smsSettingsAvailable = true;
} catch (PDOException $e) {
    error_log('setting_controller.php SMS setting error: ' . $e->getMessage());
}

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
