<?php
session_start();

// Require the centralized database connection file
require_once '../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';

// Set header to return JSON format for AJAX

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empId   = trim($_POST['employee_id'] ?? '');
    $empName = ucwords(strtolower(trim($_POST['name'] ?? '')));
    $empPos  = trim($_POST['position'] ?? '');
    $empEmail = trim($_POST['email'] ?? '');

    if (!empty($empId) && !empty($empName) && !empty($empPos) && !empty($empEmail)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO employee (emp_id, emp_name, emp_position, emp_email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$empId, $empName, $empPos, $empEmail]);

            echo json_encode(['status' => 'success']);
            exit();
        } catch (PDOException $e) {
            json_error('Database error or duplicate ID.');
        }
    } else {
        json_error('Please fill in all fields.');
    }
} else {
    json_error('Invalid request method.');
}