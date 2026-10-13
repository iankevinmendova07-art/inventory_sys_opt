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
                $findUnit = $pdo->prepare('SELECT id, unit_name FROM unit_measure WHERE id = ? LIMIT 1');
                $findUnit->execute([$id]);
            } else {
                $findUnit = $pdo->prepare('SELECT id, unit_name FROM unit_measure WHERE unit_name = ? LIMIT 1');
                $findUnit->execute([$id]);
            }
            $unit = $findUnit->fetch(PDO::FETCH_ASSOC);
            if (!$unit) {
                json_error('Unit of measure record not found.');
            }

            $references = $pdo->prepare(
                'SELECT 1 FROM supplies WHERE supply_unit = ?
                 UNION ALL SELECT 1 FROM stock_card WHERE item_unit = ?
                 UNION ALL SELECT 1 FROM lr_sme WHERE lr_unit = ?
                 UNION ALL SELECT 1 FROM lr_textbooks WHERE lr_unit = ?
                 UNION ALL SELECT 1 FROM nonconsumable WHERE unit_of_measure = ? LIMIT 1'
            );
            $references->execute(array_fill(0, 5, $unit['unit_name']));
            if ($references->fetchColumn()) {
                json_error('This unit is used by inventory records and cannot be deleted.');
            }

            $stmt = $pdo->prepare('DELETE FROM unit_measure WHERE id = ?');
            $stmt->execute([$unit['id']]);

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
