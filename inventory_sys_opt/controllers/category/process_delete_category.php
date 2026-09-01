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
        $id = trim($_POST['id'] ?? '');

        if (!empty($id)) {
            if (is_numeric($id)) {
                $stmt = $pdo->prepare("DELETE FROM unit_measure WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM unit_measure WHERE unit_name = ?");
                $stmt->execute([$id]);
            }

            echo json_encode(['status' => 'success']);
            exit();
        } else {
            json_error('Invalid unit of measure identifier.');
        }
    } else {
        json_error('Invalid request method.');
    }
} catch (PDOException $e) {
    error_log('controllers/category/process_delete_category.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    exit();
}
