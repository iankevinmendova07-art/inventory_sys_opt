<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supply_code = trim($_POST['itemCode'] ?? '');
    $supply_name = trim($_POST['itemName'] ?? '');
    $supply_qty  = intval($_POST['qty'] ?? 0);
    $supply_unit = trim($_POST['unit'] ?? '');
    $reference   = trim($_POST['reference'] ?? '');

    if (empty($supply_code) || empty($supply_name) || $supply_qty <= 0 || empty($supply_unit) || empty($reference)) {
        json_error('Please fill in all required fields properly.');
    }

    $normalizeValue = function (string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[()\[\]{}_\-_.]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    };

    try {
        $existingItemsStmt = $pdo->query("SELECT supply_code, supply_name FROM supplies");
        while ($existingItem = $existingItemsStmt->fetch(PDO::FETCH_ASSOC)) {
            $existingCode = (string)($existingItem['supply_code'] ?? '');
            $existingName = (string)($existingItem['supply_name'] ?? '');

            if ($normalizeValue($existingCode) === $normalizeValue($supply_code)) {
                json_error('An item with this Item Code already exists.');
            }

            if ($normalizeValue($existingName) === $normalizeValue($supply_name)) {
                json_error('An item with this Item Name already exists.');
            }
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO supplies (supply_code, supply_name, supply_qty, supply_unit, reference) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$supply_code, $supply_name, $supply_qty, $supply_unit, $reference]);

        $stockCardStmt = $pdo->prepare(
            "INSERT INTO stock_card (supply_code, item_name, item_unit, transaction_date, transaction_type, qty, reference, recepient, created_at)
             VALUES (?, ?, ?, CURDATE(), 'IN', ?, ?, 'Administrative Officer II', NOW())"
        );
        $stockCardStmt->execute([
            $supply_code,
            $supply_name,
            $supply_unit,
            $supply_qty,
            $reference
        ]);

        $pdo->commit();

        json_success('Supply item added and recorded in the stock card successfully!');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('controllers/supplies/consumable/add_supply.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
} else {
    json_error('Invalid request method.');
}
?>
