<?php
// controllers/supplies/consumable/update_quantity.php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = intval($_POST['id'] ?? 0);
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : -1;
    $reference = trim($_POST['reference'] ?? '');

    if ($id <= 0 || $qty < 0 || $reference === '') {
        json_error('A valid ID, quantity, and reference are required.');
    }

    try {
        $pdo->beginTransaction();

        // Lock the supply row so its quantity cannot change while this stock-in is saved.
        $supplyStmt = $pdo->prepare("SELECT supply_code, supply_name, supply_unit, supply_qty FROM supplies WHERE id = ? FOR UPDATE");
        $supplyStmt->execute([$id]);
        $supply = $supplyStmt->fetch(PDO::FETCH_ASSOC);

        if (!$supply) {
            $pdo->rollBack();
            json_error('Supply item was not found.');
        }

        $currentQty = (int)$supply['supply_qty'];
        if ($qty <= $currentQty) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'For an IN transaction, the new quantity must be greater than the current quantity (' . $currentQty . ').']);
            exit;
        }

        $addedQty = $qty - $currentQty;

        $updateStmt = $pdo->prepare("UPDATE supplies SET supply_qty = ? WHERE id = ?");
        $updateStmt->execute([$qty, $id]);

        $stockCardStmt = $pdo->prepare(
            "INSERT INTO stock_card (supply_code, item_name, item_unit, transaction_date, transaction_type, qty, reference, recepient, created_at)
             VALUES (?, ?, ?, CURDATE(), 'IN', ?, ?, 'Administrative Officer II', NOW())"
        );
        $stockCardStmt->execute([
            $supply['supply_code'],
            $supply['supply_name'],
            $supply['supply_unit'],
            $addedQty,
            $reference
        ]);

        $pdo->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'Quantity updated successfully. The added quantity has been recorded as IN in the stock card.'
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('controllers/supplies/consumable/update_quantity.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
} else {
    json_error('Invalid request method.');
}
?>
