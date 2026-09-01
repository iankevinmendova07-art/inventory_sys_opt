<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = intval($_POST['id'] ?? 0);
    $supply_code     = trim($_POST['itemCode'] ?? '');
    $supply_name     = trim($_POST['itemName'] ?? '');
    $supply_unit     = trim($_POST['unit'] ?? '');
    $supply_category = trim($_POST['category'] ?? '');
    $reference       = trim($_POST['reference'] ?? '');

    if ($id <= 0 || empty($supply_code) || empty($supply_name) || empty($supply_category) || empty($supply_unit) || empty($reference)) {
        json_error('Please fill in all required fields properly.');
    }

    try {
        $stmt = $pdo->prepare("UPDATE supplies SET supply_code = ?, supply_name = ?, supply_category = ?, supply_unit = ?, reference = ? WHERE id = ?");
        $execute = $stmt->execute([$supply_code, $supply_name, $supply_category, $supply_unit, $reference, $id]);

        if ($execute) {
            json_success('Supply item updated successfully!');
        } else {
            json_error('Failed to update supply item.');
        }
    } catch (PDOException $e) {
        error_log('controllers/supplies/consumable/update_supply.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
} else {
    json_error('Invalid request method.');
}
?>
