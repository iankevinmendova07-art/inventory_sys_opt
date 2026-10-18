<?php
// controllers/cart/process_release.php
require_once dirname(__DIR__) . '/auth/auth.php';
require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/includes/json_response.php';

if (!isset($_SESSION['admin_id'])) {
    json_error('Unauthorized access.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Invalid request method.', 405);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    json_error('Invalid request payload.');
}

$requestKey = strtolower(trim((string) ($payload['requestKey'] ?? '')));
if (!preg_match('/^[a-f0-9]{32}$/', $requestKey)) {
    json_error('Invalid or missing checkout request key. Refresh the page and try again.');
}
$requestFingerprint = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

// Use the authenticated PHP session for retry protection so this endpoint does
// not depend on an extra database table. PHP's session lock serializes requests
// from the same browser session while a release is being processed.
if (!isset($_SESSION['checkout_requests']) || !is_array($_SESSION['checkout_requests'])) {
    $_SESSION['checkout_requests'] = [];
}
$cachedRequest = $_SESSION['checkout_requests'][$requestKey] ?? null;
if (is_array($cachedRequest) && isset($cachedRequest['fingerprint'], $cachedRequest['response'])) {
    if (!hash_equals((string) $cachedRequest['fingerprint'], $requestFingerprint)) {
        http_response_code(409);
        json_error('This checkout request key was already used for different items.');
    }
    echo json_encode($cachedRequest['response']);
    exit;
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
    json_error('Please select at least one valid recipient.');
}

// 1. Normalize and aggregate items by supplyId
$normalizedItems = [];
foreach ($items as $item) {
    $supplyId = (int)($item['supplyId'] ?? 0);
    $rawQty = $item['qty'] ?? null;
    $qty = filter_var($rawQty, FILTER_VALIDATE_INT);
    $unit = trim($item['unit'] ?? 'PIECE');

    if ($supplyId <= 0 || $qty === false || $qty <= 0) {
        json_error('Invalid cart item detected.');
    }

    if (!isset($normalizedItems[$supplyId])) {
        $normalizedItems[$supplyId] = [
            'supplyId' => $supplyId,
            'itemCode' => trim($item['itemCode'] ?? ''),
            'itemName' => trim($item['itemName'] ?? ''),
            'unit'     => $unit,
            'qty'      => $qty
        ];
    } else {
        $normalizedItems[$supplyId]['qty'] += $qty;
    }
}

$supplyIds = array_keys($normalizedItems);
$releasedBy = $_SESSION['admin_name'] ?? 'Administrator';
$sequenceLockHeld = false;

try {
    $pdo->beginTransaction();

    // 2. Fetch all recipient records in ONE query
    $inRecipients = implode(',', array_fill(0, count($recipients), '?'));
    $stmtEmp = $pdo->prepare("SELECT emp_name, emp_phone FROM employee WHERE emp_name IN ($inRecipients)");
    $stmtEmp->execute($recipients);
    $employeeRows = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

    $employeeMap = [];
    foreach ($employeeRows as $emp) {
        $employeeMap[$emp['emp_name']] = $emp['emp_phone'] ?? '';
    }

    foreach ($recipients as $recipientName) {
        if (!isset($employeeMap[$recipientName])) {
            $pdo->rollBack();
            json_error("Recipient '{$recipientName}' is not registered in employee records.");
        }
    }

    // 3. Lock all required inventory rows in ONE query with FOR UPDATE
    $inSupplyIds = implode(',', array_fill(0, count($supplyIds), '?'));
    $stmtSupplies = $pdo->prepare("SELECT id, supply_code, supply_name, supply_unit, supply_qty FROM supplies WHERE id IN ($inSupplyIds) FOR UPDATE");
    $stmtSupplies->execute($supplyIds);
    $dbSupplies = $stmtSupplies->fetchAll(PDO::FETCH_ASSOC);

    $suppliesMap = [];
    foreach ($dbSupplies as $s) {
        $suppliesMap[(int)$s['id']] = $s;
    }

    // 4. Batch Stock Validation
    foreach ($normalizedItems as $supplyId => $item) {
        if (!isset($suppliesMap[$supplyId])) {
            $pdo->rollBack();
            json_error("Item ID {$supplyId} does not exist in inventory.");
        }

        $currentStock = (int)$suppliesMap[$supplyId]['supply_qty'];
        $totalRequired = $item['qty'] * $recipientCount;

        if ($currentStock < $totalRequired) {
            $pdo->rollBack();
            json_error("Insufficient stock for {$suppliesMap[$supplyId]['supply_name']}. Available: {$currentStock}, Needed: {$totalRequired} ({$item['qty']} x {$recipientCount} recipients).");
        }
    }

    // Serialize code allocation even when separate supply rows are released concurrently.
    $lockStmt = $pdo->query("SELECT GET_LOCK('inventory_sys_opt_transaction_code', 10)");
    if ((int) $lockStmt->fetchColumn() !== 1) {
        $pdo->rollBack();
        json_error('Transaction numbering is busy. Please retry the release.');
    }
    $sequenceLockHeld = true;

    // 5. Continue the transaction code sequence across months within the year.
    $yearPrefix = date('Y-');
    $yearMonth = date('Y-n-');
    $stmtLastCode = $pdo->prepare(
        "SELECT trans_code
         FROM transaction_log
         WHERE trans_code LIKE ?
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmtLastCode->execute([$yearPrefix . '%']);
    $lastCodeRow = $stmtLastCode->fetch(PDO::FETCH_ASSOC);
    $increment = 1;
    if ($lastCodeRow && !empty($lastCodeRow['trans_code'])) {
        if (preg_match('/-(\d+)$/', trim($lastCodeRow['trans_code']), $m)) {
            $increment = (int)$m[1] + 1;
        }
    }

    // 6. Prepared Statements for Batch Insertion & Stock Deduction
    $stmtInsertLog = $pdo->prepare(
        "INSERT INTO transaction_log (trans_code, supply_code, supply_name, supply_unit, supply_qty, emp_name, emp_email, release_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    $stmtInsertStockCard = $pdo->prepare(
        "INSERT INTO stock_card (supply_code, item_name, item_unit, transaction_date, transaction_type, qty, reference, recepient, created_at)
         VALUES (?, ?, ?, CURDATE(), 'OUT', ?, ?, ?, NOW())"
    );

    $stmtDeductStock = $pdo->prepare(
        "UPDATE supplies SET supply_qty = supply_qty - ? WHERE id = ? AND supply_qty >= ?"
    );

    $transCodes = [];

    // 7. Execute Transaction Logging
    foreach ($recipients as $recipientName) {
        $transCode = $yearMonth . str_pad((string)$increment++, 3, '0', STR_PAD_LEFT);
        $transCodes[] = $transCode;
        $employeePhone = $employeeMap[$recipientName];

        foreach ($normalizedItems as $supplyId => $item) {
            $dbItem = $suppliesMap[$supplyId];
            $unit = $item['unit'] ?: ($dbItem['supply_unit'] ?? 'PIECE');

            $stmtInsertLog->execute([
                $transCode,
                $dbItem['supply_code'],
                $dbItem['supply_name'],
                $unit,
                $item['qty'],
                $recipientName,
                '',
                $releasedBy
            ]);

            $stmtInsertStockCard->execute([
                $dbItem['supply_code'],
                $dbItem['supply_name'],
                $unit,
                $item['qty'],
                'RIS No. ' . $transCode,
                $recipientName
            ]);
        }
    }

    // 8. Atomic Quantity Deduction
    foreach ($normalizedItems as $supplyId => $item) {
        $totalDeduct = $item['qty'] * $recipientCount;
        $stmtDeductStock->execute([$totalDeduct, $supplyId, $totalDeduct]);

        if ($stmtDeductStock->rowCount() !== 1) {
            $pdo->rollBack();
            json_error("Concurrency conflict: Stock level changed during checkout for {$suppliesMap[$supplyId]['supply_name']}. Transaction aborted.");
        }
    }

    $checkoutResponse = [
        'status' => 'success',
        'message' => 'Successfully released ' . count($normalizedItems) . ' item type(s) to ' . $recipientCount . ' recipient(s).',
        'trans_codes' => $transCodes,
        'print_url' => 'controllers/supplies/consumable/print_ris.php?trans_codes=' . urlencode(implode(',', $transCodes))
    ];
    $pdo->commit();
    $_SESSION['checkout_requests'][$requestKey] = [
        'fingerprint' => $requestFingerprint,
        'response' => $checkoutResponse,
        'created_at' => time(),
    ];
    // Keep the session cache bounded; recent release retries remain idempotent.
    if (count($_SESSION['checkout_requests']) > 20) {
        uasort($_SESSION['checkout_requests'], static function ($a, $b) {
            return ((int) ($a['created_at'] ?? 0)) <=> ((int) ($b['created_at'] ?? 0));
        });
        $_SESSION['checkout_requests'] = array_slice($_SESSION['checkout_requests'], -20, null, true);
    }
    if ($sequenceLockHeld) {
        $pdo->query("SELECT RELEASE_LOCK('inventory_sys_opt_transaction_code')");
        $sequenceLockHeld = false;
    }

    echo json_encode($checkoutResponse);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($sequenceLockHeld) {
        try {
            $pdo->query("SELECT RELEASE_LOCK('inventory_sys_opt_transaction_code')");
        } catch (Throwable $releaseError) {
            error_log('Unable to release transaction-code lock: ' . $releaseError->getMessage());
        }
    }
    error_log('controllers/cart/process_release.php error: ' . $e->getMessage());
    json_error('Database failure occurred while processing release.');
}
