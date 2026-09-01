<?php
// controllers/cart/process_release.php
session_start();
require_once '../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';


if (!isset($_SESSION['admin_id'])) {
    json_error('Unauthorized.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Invalid request method.');
}

$payload = json_decode(file_get_contents('php://input'), true);

if (!is_array($payload)) {
    json_error('Invalid request payload.');
}

$recipients = $payload['recipients'] ?? [];
$items = $payload['items'] ?? [];

if (empty($recipients) || !is_array($recipients)) {
    json_error('Please select at least one recipient.');
}

if (empty($items) || !is_array($items)) {
    json_error('Your cart is empty.');
}

$recipients = array_values(array_unique(array_filter(array_map('trim', $recipients))));
$recipientCount = count($recipients);

if ($recipientCount === 0) {
    json_error('Please select at least one recipient.');
}

$normalizedItems = [];
foreach ($items as $item) {
    $supplyId = intval($item['supplyId'] ?? 0);
    $qty = intval($item['qty'] ?? 0);
    $unit = trim($item['unit'] ?? 'PIECE');

    if ($supplyId <= 0 || $qty <= 0) {
        json_error('Invalid cart item detected.');
    }

    if (!isset($normalizedItems[$supplyId])) {
        $normalizedItems[$supplyId] = [
            'supplyId' => $supplyId,
            'itemCode' => trim($item['itemCode'] ?? ''),
            'itemName' => trim($item['itemName'] ?? ''),
            'unit' => $unit,
            'qty' => $qty
        ];
    } else {
        $normalizedItems[$supplyId]['qty'] += $qty;
    }
}

/**
 * Generate trans_code in YEAR-MONTH-INCREMENT format (e.g. 2026-8-001, 2026-8-002).
 */
function generateTransactionCode(PDO $pdo): string
{
    $year = date('Y');
    $month = (int) date('n');
    $prefix = $year . '-' . $month . '-';

    $stmt = $pdo->prepare("SELECT trans_code FROM transaction_log WHERE trans_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastRow && !empty($lastRow['trans_code'])) {
        $parts = explode('-', $lastRow['trans_code']);
        $lastIncrement = intval(end($parts));
        $newIncrement = $lastIncrement + 1;
    } else {
        $newIncrement = 1;
    }

    return $prefix . str_pad((string) $newIncrement, 3, '0', STR_PAD_LEFT);
}

try {
    $pdo->beginTransaction();

    $releasedBy = $_SESSION['admin_name'] ?? 'Admin';

    foreach ($normalizedItems as $supplyId => $item) {
        $stmt = $pdo->prepare("SELECT id, supply_code, supply_name, supply_qty FROM supplies WHERE id = ? FOR UPDATE");
        $stmt->execute([$supplyId]);
        $supply = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$supply) {
            $pdo->rollBack();
            json_error('One or more items no longer exist in inventory.');
        }

        $totalNeeded = $item['qty'] * $recipientCount;

        if ((int) $supply['supply_qty'] < $totalNeeded) {
            $pdo->rollBack();
            echo json_encode([
                'status' => 'error',
                'message' => 'Insufficient stock for ' . $supply['supply_name'] . '. Available: ' . $supply['supply_qty'] . ', needed: ' . $totalNeeded . ' (' . $item['qty'] . ' x ' . $recipientCount . ' recipients).'
            ]);
            exit;
        }
    }

    $employeeStmt = $pdo->prepare("SELECT emp_name, emp_email FROM employee WHERE emp_name = ? LIMIT 1");

    // Updated statement to include supply_unit
    $insertLog = $pdo->prepare(
        "INSERT INTO transaction_log (trans_code, supply_code, supply_name, supply_unit, supply_qty, emp_name, emp_email, release_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    $insertStockCard = $pdo->prepare(
        "INSERT INTO stock_card (supply_code, item_name, item_unit, transaction_date, transaction_type, qty, reference, recepient, created_at)
         VALUES (?, ?, ?, CURDATE(), 'OUT', ?, ?, ?, NOW())"
    );

    $updateStock = $pdo->prepare(
        "UPDATE supplies SET supply_qty = supply_qty - ? WHERE id = ? AND supply_qty >= ?"
    );

    $verifyStock = $pdo->prepare("SELECT supply_name, supply_qty FROM supplies WHERE id = ?");

    $transCodes = [];

    foreach ($recipients as $recipientName) {
        $transCode = generateTransactionCode($pdo);
        $transCodes[] = $transCode;

        $employeeStmt->execute([$recipientName]);
        $employee = $employeeStmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Recipient "' . $recipientName . '" was not found in the employee records.']);
            exit;
        }

        foreach ($normalizedItems as $supplyId => $item) {
            $stmt = $pdo->prepare("SELECT supply_code, supply_name, supply_unit FROM supplies WHERE id = ?");
            $stmt->execute([$supplyId]);
            $supply = $stmt->fetch(PDO::FETCH_ASSOC);

            // Use cart unit, or fallback to database unit if available
            $supplyUnit = $item['unit'] ?? ($supply['supply_unit'] ?? 'PIECE');

            $insertLog->execute([
                $transCode,
                $supply['supply_code'],
                $supply['supply_name'],
                $supplyUnit, // <-- Safely recorded into transaction_log
                $item['qty'],
                $employee['emp_name'],
                $employee['emp_email'],
                $releasedBy
            ]);

            // Record the same release in the stock card for this recipient.
            $insertStockCard->execute([
                $supply['supply_code'],
                $supply['supply_name'],
                $supplyUnit,
                $item['qty'],
                'RIS No. ' . $transCode,
                $employee['emp_name']
            ]);
        }
    }

    foreach ($normalizedItems as $supplyId => $item) {
        $totalDeduct = $item['qty'] * $recipientCount;
        $updateStock->execute([$totalDeduct, $supplyId, $totalDeduct]);

        if ($updateStock->rowCount() !== 1) {
            $verifyStock->execute([$supplyId]);
            $current = $verifyStock->fetch(PDO::FETCH_ASSOC);
            $pdo->rollBack();
            echo json_encode([
                'status' => 'error',
                'message' => 'Release blocked for ' . ($current['supply_name'] ?? 'item') . '. Available stock is ' . ($current['supply_qty'] ?? 0) . ', but ' . $totalDeduct . ' is required. Quantity cannot go below zero.'
            ]);
            exit;
        }

        $verifyStock->execute([$supplyId]);
        $current = $verifyStock->fetch(PDO::FETCH_ASSOC);

        if (!$current || (int) $current['supply_qty'] < 0) {
            $pdo->rollBack();
            echo json_encode([
                'status' => 'error',
                'message' => 'Release blocked: stock level for ' . ($current['supply_name'] ?? 'item') . ' would fall below zero.'
            ]);
            exit;
        }
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Successfully released ' . count($normalizedItems) . ' item type(s) to ' . $recipientCount . ' recipient(s). Transaction codes: ' . implode(', ', $transCodes) . '.'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_error('Database error while processing release.');
}
