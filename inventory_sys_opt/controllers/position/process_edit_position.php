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
        $id           = trim($_POST['id'] ?? '');
        $oldPosition  = trim($_POST['old_position_name'] ?? '');
        $positionName = trim($_POST['position'] ?? '');

        if (!empty($positionName)) {
            if (!empty($id) && is_numeric($id)) {
                $stmt = $pdo->prepare("UPDATE position SET position_name = ? WHERE id = ?");
                $stmt->execute([$positionName, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE position SET position_name = ? WHERE position_name = ?");
                $stmt->execute([$positionName, $oldPosition ?: $id]);
            }

            // Also update any employees currently assigned to this old position name
            if (!empty($oldPosition)) {
                $stmtEmp = $pdo->prepare("UPDATE employee SET emp_position = ? WHERE emp_position = ?");
                $stmtEmp->execute([$positionName, $oldPosition]);
            }

            echo json_encode(['status' => 'success']);
            exit();
        } else {
            json_error('Position name cannot be empty.');
        }
    } else {
        json_error('Invalid request method.');
    }
} catch (PDOException $e) {
    if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
        json_error('Position already exists in database.');
    } else {
        error_log('controllers/position/process_edit_position.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
    exit();
}
