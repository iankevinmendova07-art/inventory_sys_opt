<?php
if (ob_get_length()) { ob_clean(); }
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/auth/auth.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once '../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = trim($_POST['id'] ?? '');

        if (!empty($id)) {
            $findEmployee = $pdo->prepare('SELECT id, emp_name FROM employee WHERE id = ? OR emp_id = ? ORDER BY (id = ?) DESC LIMIT 1');
            $findEmployee->execute([$id, $id, $id]);
            $employee = $findEmployee->fetch(PDO::FETCH_ASSOC);
            if (!$employee) {
                json_error('Employee record not found.');
            }

            $references = $pdo->prepare(
                'SELECT 1 FROM transaction_log WHERE emp_name = ?
                 UNION ALL SELECT 1 FROM stock_card WHERE recepient = ?
                 UNION ALL SELECT 1 FROM lr_textbooks WHERE recipient = ?
                 UNION ALL SELECT 1 FROM nonconsumable WHERE recepient = ?
                 UNION ALL SELECT 1 FROM school_details WHERE head_name = ? OR custodian_name = ? LIMIT 1'
            );
            $references->execute(array_fill(0, 6, $employee['emp_name']));
            if ($references->fetchColumn()) {
                json_error('This employee is referenced by inventory history and cannot be deleted.');
            }

            $stmt = $pdo->prepare('DELETE FROM employee WHERE id = ?');
            $stmt->execute([$employee['id']]);

            echo json_encode(['status' => 'success']);
            exit();
        } else {
            json_error('Invalid ID provided.');
        }
    } else {
        json_error('Invalid request method.');
    }
} catch (PDOException $e) {
    error_log('controllers/Employee/process_delete_employee.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    exit();
}
