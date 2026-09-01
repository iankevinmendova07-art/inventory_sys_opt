<?php
// controllers/index/index_controller.php

require_once dirname(__DIR__, 2) . '/config/db.php';

// Get admin info from session
$adminName = isset($_SESSION['admin_name']) ? strtoupper($_SESSION['admin_name']) : 'ADMIN';
$adminRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator';

// 1. Consumable Supplies Breakdown (With Stock vs Out of Stock)
$totalConsumablesWithStock = 0;
$totalConsumablesOutStock = 0;
try {
    $stmtIn = $pdo->query("SELECT COUNT(*) FROM supplies WHERE supply_qty > 0");
    $totalConsumablesWithStock = (int)$stmtIn->fetchColumn();

    $stmtOut = $pdo->query("SELECT COUNT(*) FROM supplies WHERE supply_qty <= 0");
    $totalConsumablesOutStock = (int)$stmtOut->fetchColumn();
} catch (PDOException $e) {
    $totalConsumablesWithStock = 0;
    $totalConsumablesOutStock = 0;
}

// 2. Total Non-Consumable Supplies (nonconsumable table)
$totalNonConsumables = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM nonconsumable");
    $totalNonConsumables = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $totalNonConsumables = 0;
}

// 3. Total Transactions in transaction_log
$totalConsumableTransactions = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT trans_code) FROM transaction_log");
    $totalConsumableTransactions = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $totalConsumableTransactions = 0;
}

// 4. Total Transactions in nonconsumable
$totalNonConsumableTransactions = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT trans_code) FROM nonconsumable WHERE trans_code IS NOT NULL AND trans_code != ''");
    $totalNonConsumableTransactions = (int)$stmt->fetchColumn();
    if ($totalNonConsumableTransactions === 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM nonconsumable");
        $totalNonConsumableTransactions = (int)$stmt->fetchColumn();
    }
} catch (PDOException $e) {
    $totalNonConsumableTransactions = 0;
}

// 5. Total Employees / Active Personnel
$totalEmployees = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM employee");
    $totalEmployees = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $totalEmployees = 0;
}

// 6. Learning Resources SME Breakdown (lr_sme table: Science vs Math)
$scienceCount = 0;
$mathCount = 0;
try {
    $stmtSci = $pdo->query("SELECT COUNT(*) FROM lr_sme WHERE lr_type LIKE 'Science%'");
    $scienceCount = (int)$stmtSci->fetchColumn();

    $stmtMath = $pdo->query("SELECT COUNT(*) FROM lr_sme WHERE lr_type LIKE 'Math%'");
    $mathCount = (int)$stmtMath->fetchColumn();
} catch (PDOException $e) {
    $scienceCount = 0;
    $mathCount = 0;
}
try {
    // ... your existing queries ...

    // Textbooks Grouped by Subject
    $subjectStmt = $pdo->query("SELECT lr_subject, SUM(lr_qty) as total_qty FROM lr_textbooks GROUP BY lr_subject");
    $subjectData = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $subjects = [];
    $subjectCounts = [];
    foreach ($subjectData as $row) {
        $subjects[] = $row['lr_subject'];
        $subjectCounts[] = (int)$row['total_qty'];
    }

    // Textbooks Grouped by Grade Level
    $gradeStmt = $pdo->query("SELECT grade_level, SUM(lr_qty) as total_qty FROM lr_textbooks GROUP BY grade_level");
    $gradeData = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);

    $grades = [];
    $gradeCounts = [];
    foreach ($gradeData as $row) {
        $grades[] = $row['grade_level'];
        $gradeCounts[] = (int)$row['total_qty'];
    }

} catch (PDOException $e) {
    // Handle database error if needed
}
// Define all standard subjects used in your application
$allSubjects = [
    'Kindergarten', 'Language', 'Reading and Literacy', 'Filipino', 
    'English', 'Mathematics', 'Science', 'Araling Panlipunan', 
    'Makabansa', 'GMRC – Good Manners and Right Conduct', 
    'Edukasyong Pantahanan at Pangkabuhayan (EPP)', 'MAPEH'
];

// Define all standard grade levels
$allGrades = [
    'Kinder', 'Grade I', 'Grade II', 'Grade III', 
    'Grade IV', 'Grade V', 'Grade VI'
];

try {
    // 1. Textbooks Grouped by Subject (filtering out zero quantities)
    $subjectStmt = $pdo->query("SELECT lr_subject, SUM(lr_qty) as total_qty FROM lr_textbooks GROUP BY lr_subject HAVING total_qty > 0");
    $rawSubjectData = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $subjects = [];
    $subjectCounts = [];
    foreach ($rawSubjectData as $row) {
        $subjects[] = $row['lr_subject'];
        $subjectCounts[] = (int)$row['total_qty'];
    }

    // 2. Textbooks Grouped by Grade Level (filtering out zero quantities)
    $gradeStmt = $pdo->query("SELECT grade_level, SUM(lr_qty) as total_qty FROM lr_textbooks GROUP BY grade_level HAVING total_qty > 0");
    $rawGradeData = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);

    $grades = [];
    $gradeCounts = [];
    foreach ($rawGradeData as $row) {
        $grades[] = $row['grade_level'];
        $gradeCounts[] = (int)$row['total_qty'];
    }

} catch (PDOException $e) {
    // Handle error if needed
}