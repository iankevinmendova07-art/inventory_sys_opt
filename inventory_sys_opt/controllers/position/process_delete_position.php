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
                $stmt = $pdo->prepare("DELETE FROM position WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM position WHERE position_name = ?");
                $stmt->execute([$id]);
            }

            echo json_encode(['status' => 'success']);
            exit();
        } else {
            json_error('Invalid position identifier.');
        }
    } else {
        json_error('Invalid request method.');
    }
} catch (PDOException $e) {
    error_log('controllers/position/process_delete_position.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    exit();
}
