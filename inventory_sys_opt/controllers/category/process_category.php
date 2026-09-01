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
        $unitName = ucwords(strtolower(trim($_POST['unit_name'] ?? $_POST['category_name'] ?? '')));

        if (!empty($unitName)) {
            // Check if unit already exists in unit_measure table
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM unit_measure WHERE unit_name = ?");
            $checkStmt->execute([$unitName]);
            if ($checkStmt->fetchColumn() > 0) {
                json_error('Unit of measure already added to the database.');
            }

            $stmt = $pdo->prepare("INSERT INTO unit_measure (unit_name) VALUES (?)");
            $stmt->execute([$unitName]);

            echo json_encode(['status' => 'success']);
            exit();
        } else {
            json_error('Unit field cannot be empty.');
        }
    } else {
        json_error('Invalid request method.');
    }
} catch (PDOException $e) {
    if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
        json_error('Unit of measure already added to the database.');
    } else {
        error_log('controllers/category/process_category.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
    exit();
}