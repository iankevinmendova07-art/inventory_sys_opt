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
            if (is_numeric($id)) {
                $findPosition = $pdo->prepare('SELECT id, position_name FROM position WHERE id = ? LIMIT 1');
                $findPosition->execute([$id]);
            } else {
                $findPosition = $pdo->prepare('SELECT id, position_name FROM position WHERE position_name = ? LIMIT 1');
                $findPosition->execute([$id]);
            }
            $position = $findPosition->fetch(PDO::FETCH_ASSOC);
            if (!$position) {
                json_error('Position record not found.');
            }

            $references = $pdo->prepare('SELECT 1 FROM employee WHERE emp_position = ? LIMIT 1');
            $references->execute([$position['position_name']]);
            if ($references->fetchColumn()) {
                json_error('This position is assigned to one or more employees and cannot be deleted.');
            }

            $stmt = $pdo->prepare('DELETE FROM position WHERE id = ?');
            $stmt->execute([$position['id']]);

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
