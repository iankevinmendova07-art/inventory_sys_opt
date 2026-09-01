<?php
// controllers/supplies/consumable/get_rsmi_logs.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM transaction_log ORDER BY id DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($logs as $row) {
        $data[] = [
            'trans_code' => htmlspecialchars($row['trans_code']),
            'emp_name' => htmlspecialchars($row['emp_name']),
            'supply_code' => htmlspecialchars($row['supply_code']),
            'supply_name' => htmlspecialchars($row['supply_name']),
            'supply_unit' => htmlspecialchars($row['supply_unit']),
            'supply_qty' => htmlspecialchars($row['supply_qty']),
            'date' => htmlspecialchars(date('M d, Y', strtotime($row['created_at'])))
        ];
    }

    echo json_encode(['data' => $data]);
} catch (Exception $e) {
    error_log('controllers/supplies/consumable/get_rsmi_logs.php error: ' . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'A server error occurred. Please try again.']);
}
?>
