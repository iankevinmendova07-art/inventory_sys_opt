<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description           = trim($_POST['description'] ?? $_POST['itemName'] ?? '');
    $item_type             = trim($_POST['item_type'] ?? $_POST['category'] ?? '');
    $property_number       = trim($_POST['property_number'] ?? $_POST['itemCode'] ?? '');
    $unit_of_measure       = trim($_POST['unit_of_measure'] ?? $_POST['unit'] ?? '');
    $unit_cost             = is_numeric($_POST['unit_cost'] ?? null) ? floatval($_POST['unit_cost']) : null;
    $total_cost            = is_numeric($_POST['total_cost'] ?? null) ? floatval($_POST['total_cost']) : null;
    $qty_property_card     = intval($_POST['qty_property_card'] ?? $_POST['qty'] ?? 0);
    $qty_physical_count    = intval($_POST['qty_physical_count'] ?? 0);
    $shortage_overage_qty  = intval($_POST['shortage_overage_qty'] ?? 0);
    $shortage_overage_value= is_numeric($_POST['shortage_overage_value'] ?? null) ? floatval($_POST['shortage_overage_value']) : null;
    $remarks               = trim($_POST['remarks'] ?? '');
    $recipient             = trim($_POST['recipient'] ?? $_POST['recepient'] ?? '');

    if (empty($property_number) || empty($description) || empty($unit_of_measure)) {
        json_error('Please fill in required fields: property number, description, and unit of measure.');
    }

    try {
        // 1. Generate the Year - Month - Increment trans_code
        $currentYearMonth = date('Y-m'); // e.g., "2026-08"
        
        // Find the latest trans_code matching the current year and month
        $stmtSeq = $pdo->prepare("SELECT trans_code FROM nonconsumable WHERE trans_code LIKE ? ORDER BY id DESC LIMIT 1");
        $stmtSeq->execute([$currentYearMonth . '-%']);
        $lastRow = $stmtSeq->fetch(PDO::FETCH_ASSOC);

        if ($lastRow && !empty($lastRow['trans_code'])) {
            // Extract the increment portion from the last code (e.g., "2026-08-0005" -> "0005")
            $parts = explode('-', $lastRow['trans_code']);
            $lastIncrement = intval(end($parts));
            $newIncrement = $lastIncrement + 1;
        } else {
            // Start at 1 if no records exist for this month yet
            $newIncrement = 1;
        }

        // Format the new transaction code with a 4-digit zero-padded increment (e.g., 2026-08-0001)
        $trans_code = $currentYearMonth . '-' . str_pad($newIncrement, 4, '0', STR_PAD_LEFT);

        // 2. Insert into database including trans_code
        $stmt = $pdo->prepare("INSERT INTO nonconsumable (trans_code, property_number, description, item_type, unit_of_measure, unit_cost, total_cost, qty_property_card, qty_physical_count, shortage_overage_qty, shortage_overage_value, remarks, recepient, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $execute = $stmt->execute([$trans_code, $property_number, $description, $item_type, $unit_of_measure, $unit_cost, $total_cost, $qty_property_card, $qty_physical_count, $shortage_overage_qty, $shortage_overage_value, $remarks, $recipient]);

        if ($execute) {
            echo json_encode(['status' => 'success', 'message' => 'Item added successfully with code: ' . $trans_code]);
        } else {
            json_error('Failed to add item to the database.');
        }
    } catch (PDOException $e) {
        error_log('controllers/supplies/nonconsumable/add_supply.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
} else {
    json_error('Invalid request method.');
}
exit;