<?php
require_once __DIR__ . '/../config/db.php';

try {
    $empStmt = $pdo->query("SELECT emp_name, emp_position FROM employee ORDER BY emp_name ASC");
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($employees as $emp) {
        $name = htmlspecialchars($emp['emp_name']);
        $position = htmlspecialchars($emp['emp_position']);
        echo '<option value="' . $name . '">' . $name . ' (' . $position . ')</option>';
    }
} catch (PDOException $e) {
    // Handle exception
}
?>