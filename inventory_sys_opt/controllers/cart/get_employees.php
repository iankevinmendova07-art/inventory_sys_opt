<?php
session_start();
require_once '../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';


if (!isset($_SESSION['admin_id'])) {
    json_error('Unauthorized.');
}

try {
    $stmt = $pdo->query("SELECT id, emp_id, emp_name, emp_position, emp_email FROM employee ORDER BY emp_name ASC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $employees]);
} catch (PDOException $e) {
    json_error('Failed to load employees.');
}
