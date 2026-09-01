<?php
// controllers/setting/setting_controller.php

require_once dirname(__DIR__, 2) . '/config/db.php';

// Get admin info from session
$adminName = isset($_SESSION['admin_name']) ? strtoupper($_SESSION['admin_name']) : 'ADMIN';
$adminRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator';

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
    try {
        $stmtEmp = $pdo->query("SELECT * FROM employee ORDER BY id DESC");
        $employees = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        $employees = [];
    }
}

// Fetch units of measure from the database
try {
    $stmtCat = $pdo->query("SELECT * FROM unit_measure ORDER BY id DESC");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $stmtCat = $pdo->query("SELECT * FROM unit_measure ORDER BY id DESC");
        $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        $categories = [];
    }
}

// Fetch admin profile data
try {
    $stmtAdmin = $pdo->prepare("SELECT * FROM admin LIMIT 1");
    $stmtAdmin->execute();
    $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $adminData = [];
}
