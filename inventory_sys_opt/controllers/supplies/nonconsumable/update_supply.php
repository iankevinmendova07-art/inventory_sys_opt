<?php
// Ensure no prior output or whitespace exists before session start
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';

// Force JSON header

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                   = intval($_POST['id'] ?? 0);
    $description          = trim($_POST['description'] ?? $_POST['itemName'] ?? '');
    $item_type            = trim($_POST['item_type'] ?? $_POST['category'] ?? '');
    $property_number      = trim($_POST['property_number'] ?? $_POST['itemCode'] ?? '');
    $unit_of_measure      = trim($_POST['unit_of_measure'] ?? $_POST['unit'] ?? '');
    $unit_cost            = is_numeric($_POST['unit_cost'] ?? null) ? floatval($_POST['unit_cost']) : null;
    $total_cost           = is_numeric($_POST['total_cost'] ?? null) ? floatval($_POST['total_cost']) : null;
    $qty_property_card    = intval($_POST['qty_property_card'] ?? $_POST['qty'] ?? 0);
    $qty_physical_count   = intval($_POST['qty_physical_count'] ?? 0);
    $shortage_overage_qty = intval($_POST['shortage_overage_qty'] ?? 0);
    $shortage_overage_value = is_numeric($_POST['shortage_overage_value'] ?? null) ? floatval($_POST['shortage_overage_value']) : null;
    $remarks              = trim($_POST['remarks'] ?? '');
    $recipient            = trim($_POST['recipient'] ?? $_POST['recepient'] ?? '');

    if ($id <= 0 || empty($property_number) || empty($description) || empty($unit_of_measure)) {
        json_error('Please fill in required fields properly.');
    }

    try {
        $stmt = $pdo->prepare("UPDATE nonconsumable SET property_number = ?, description = ?, item_type = ?, unit_of_measure = ?, unit_cost = ?, total_cost = ?, qty_property_card = ?, qty_physical_count = ?, shortage_overage_qty = ?, shortage_overage_value = ?, remarks = ?, recepient = ? WHERE id = ?");
        $execute = $stmt->execute([$property_number, $description, $item_type, $unit_of_measure, $unit_cost, $total_cost, $qty_property_card, $qty_physical_count, $shortage_overage_qty, $shortage_overage_value, $remarks, $recipient, $id]);

        if ($execute) {
            json_success('Item updated successfully!');
        } else {
            json_error('Failed to update item.');
        }
    } catch (PDOException $e) {
        error_log('controllers/supplies/nonconsumable/update_supply.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
} else {
    json_error('Invalid request method.');
}
exit;