<?php
if (ob_get_length()) { ob_clean(); }
header('Content-Type: application/json; charset=utf-8');
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once '../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id       = trim($_POST['id'] ?? '');
        $empId    = trim($_POST['employee_id'] ?? '');
        $empName  = ucwords(strtolower(trim($_POST['name'] ?? '')));
        $empPos   = trim($_POST['position'] ?? '');
        $empEmail = trim($_POST['email'] ?? '');

        if (!empty($empId) && !empty($empName) && !empty($empPos) && !empty($empEmail)) {
            if (!empty($id)) {
                $stmt = $pdo->prepare("UPDATE employee SET emp_id = ?, emp_name = ?, emp_position = ?, emp_email = ? WHERE id = ? OR emp_id = ?");
                $stmt->execute([$empId, $empName, $empPos, $empEmail, $id, $empId]);
            } else {
                $stmt = $pdo->prepare("UPDATE employee SET emp_name = ?, emp_position = ?, emp_email = ? WHERE emp_id = ?");
                $stmt->execute([$empName, $empPos, $empEmail, $empId]);
            }

            echo json_encode(['status' => 'success']);
            exit();
        } else {
            json_error('Please fill in all required fields.');
        }
    } else {
        json_error('Invalid request method.');
    }
} catch (PDOException $e) {
    error_log('controllers/Employee/process_edit_employee.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    exit();
}
