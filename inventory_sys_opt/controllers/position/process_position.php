<?php
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once '../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $positionName = trim($_POST['position'] ?? '');

        if (!empty($positionName)) {
            // Optional: Explicitly check if it already exists to be safe
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM position WHERE position_name = ?");
            $checkStmt->execute([$positionName]);
            if ($checkStmt->fetchColumn() > 0) {
                json_error('Position already added to the database.');
            }

            $stmt = $pdo->prepare("INSERT INTO position (position_name) VALUES (?)");
            $stmt->execute([$positionName]);

            echo json_encode(['status' => 'success']);
            exit();
        } else {
            json_error('Position field cannot be empty.');
        }
    } else {
        json_error('Invalid request method.');
    }
} catch (PDOException $e) {
    // Catch MySQL duplicate entry error code (1062) or general constraint violations
    if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
        json_error('Position already added to the database.');
    } else {
        error_log('controllers/position/process_position.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
    exit();
}