<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = intval($_POST['id'] ?? 0);
    $lr_code     = trim($_POST['lr_code'] ?? '');
    $lr_item     = trim($_POST['lr_item'] ?? '');
    $lr_quantity = intval($_POST['lr_quantity'] ?? 0);
    $lr_unit     = trim($_POST['lr_unit'] ?? '');
    $lr_type     = trim($_POST['lr_type'] ?? '');

    if ($id <= 0 || empty($lr_code) || empty($lr_item) || empty($lr_type)) {
        json_error('Please fill in all required fields.');
    }

    try {
        $stmt = $pdo->prepare("UPDATE lr_sme SET lr_code = ?, lr_item = ?, lr_qty = ?, lr_unit = ?, lr_type = ? WHERE id = ?");
        $execute = $stmt->execute([$lr_code, $lr_item, $lr_quantity, $lr_unit, $lr_type, $id]);

        if ($execute) {
            json_success('Item updated successfully!');
        } else {
            json_error('Failed to update item.');
        }
    } catch (PDOException $e) {
        error_log('controllers/supplies/science_math/update_sme.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
} else {
    json_error('Invalid request method.');
}
?>

