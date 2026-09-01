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
        $id          = trim($_POST['id'] ?? '');
        $oldUnitName = trim($_POST['old_category_name'] ?? $_POST['old_unit_name'] ?? '');
        $unitName    = ucwords(strtolower(trim($_POST['unit_name'] ?? $_POST['category_name'] ?? '')));

        if (!empty($unitName)) {
            if (!empty($id) && is_numeric($id)) {
                $stmt = $pdo->prepare("UPDATE unit_measure SET unit_name = ? WHERE id = ?");
                $stmt->execute([$unitName, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE unit_measure SET unit_name = ? WHERE unit_name = ?");
                $stmt->execute([$unitName, $oldUnitName ?: $id]);
            }

            echo json_encode(['status' => 'success']);
            exit();
        } else {
            json_error('Unit name cannot be empty.');
        }
    } else {
        json_error('Invalid request method.');
    }
} catch (PDOException $e) {
    if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
        json_error('Unit of measure already exists in database.');
    } else {
        error_log('controllers/category/process_edit_category.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
    exit();
}
