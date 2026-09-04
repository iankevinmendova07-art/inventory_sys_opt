<?php
// controllers/index/index_controller.php

require_once dirname(__DIR__, 2) . '/config/db.php';

// Get admin info from session
$adminName = isset($_SESSION['admin_name']) ? strtoupper($_SESSION['admin_name']) : 'ADMIN';
$adminRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator';

// 1. Single consolidated query for high-level KPIs
$totalConsumablesWithStock   = 0;
$totalConsumablesOutStock    = 0;
$totalNonConsumables         = 0;
$totalConsumableTransactions = 0;
$totalNonConsumableTransactions = 0;
$totalEmployees              = 0;
$scienceCount                = 0;
$mathCount                   = 0;

try {
    $kpiSql = "SELECT 
        (SELECT COUNT(*) FROM supplies WHERE supply_qty > 0) AS consumable_in_stock,
        (SELECT COUNT(*) FROM supplies WHERE supply_qty <= 0) AS consumable_out_stock,
        (SELECT COUNT(*) FROM nonconsumable) AS total_non_consumables,
        (SELECT COUNT(DISTINCT trans_code) FROM transaction_log) AS total_consumable_tx,
        (SELECT COUNT(DISTINCT trans_code) FROM nonconsumable WHERE trans_code IS NOT NULL AND trans_code != '') AS total_nonconsumable_tx,
        (SELECT COUNT(*) FROM employee) AS total_employees,
        (SELECT COUNT(*) FROM lr_sme WHERE lr_type LIKE 'Science%') AS science_count,
        (SELECT COUNT(*) FROM lr_sme WHERE lr_type LIKE 'Math%') AS math_count";
    
    $stmt = $pdo->query($kpiSql);
    $kpis = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($kpis) {
        $totalConsumablesWithStock      = (int)$kpis['consumable_in_stock'];
        $totalConsumablesOutStock       = (int)$kpis['consumable_out_stock'];
        $totalNonConsumables            = (int)$kpis['total_non_consumables'];
        $totalConsumableTransactions    = (int)$kpis['total_consumable_tx'];
        $totalNonConsumableTransactions = (int)$kpis['total_nonconsumable_tx'];
        if ($totalNonConsumableTransactions === 0) {
            $totalNonConsumableTransactions = $totalNonConsumables;
        }
        $totalEmployees                 = (int)$kpis['total_employees'];
        $scienceCount                   = (int)$kpis['science_count'];
        $mathCount                      = (int)$kpis['math_count'];
    }
} catch (PDOException $e) {
    error_log('controllers/index/index_controller.php KPI query error: ' . $e->getMessage());
}

// 2. Standard subjects & grades definitions
$allSubjects = [
    'Kindergarten', 'Language', 'Reading and Literacy', 'Filipino', 
    'English', 'Mathematics', 'Science', 'Araling Panlipunan', 
    'Makabansa', 'GMRC – Good Manners and Right Conduct', 
    'Edukasyong Pantahanan at Pangkabuhayan (EPP)', 'MAPEH'
];

$allGrades = [
    'Kinder', 'Grade I', 'Grade II', 'Grade III', 
    'Grade IV', 'Grade V', 'Grade VI'
];

// 3. Optimized Textbook Aggregations (Single fetch per dimension)
$subjects = [];
$subjectCounts = [];
$grades = [];
$gradeCounts = [];

try {
    $subjectStmt = $pdo->query("SELECT lr_subject, SUM(lr_qty) AS total_qty FROM lr_textbooks GROUP BY lr_subject HAVING total_qty > 0 ORDER BY total_qty DESC");
    $subjectData = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($subjectData as $row) {
        $subjects[] = $row['lr_subject'];
        $subjectCounts[] = (int)$row['total_qty'];
    }

    $gradeStmt = $pdo->query("SELECT grade_level, SUM(lr_qty) AS total_qty FROM lr_textbooks GROUP BY grade_level HAVING total_qty > 0 ORDER BY grade_level ASC");
    $gradeData = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($gradeData as $row) {
        $grades[] = $row['grade_level'];
        $gradeCounts[] = (int)$row['total_qty'];
    }
} catch (PDOException $e) {
    error_log('controllers/index/index_controller.php textbook chart query error: ' . $e->getMessage());
}