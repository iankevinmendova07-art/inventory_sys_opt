<?php
// controllers/supplies/consumable/get_transactions.php
session_start();
require_once '../../../controllers/auth/auth.php'; 
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json');

try {
    // Grouping by trans_code ensures each transaction code only appears once
    $stmt = $pdo->prepare("SELECT trans_code, emp_name AS name, created_at AS transaction_date FROM transaction_log GROUP BY trans_code, emp_name, created_at ORDER BY MAX(id) DESC");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($logs as $row) {
        $data[] = [
            'trans_code' => htmlspecialchars($row['trans_code']),
            'name' => htmlspecialchars($row['name']),
            'date' => htmlspecialchars($row['transaction_date']),
            'action' => '<div class="text-center"><button type="button" class="btn btn-sm btn-outline-primary print-report-btn" data-trans-code="' . htmlspecialchars($row['trans_code']) . '" title="Print Report"><i class="bi bi-printer"></i></button></div>'
        ];
    }

    echo json_encode(['data' => $data]);
} catch (Exception $e) {
    error_log('controllers/supplies/consumable/get_transactions.php error: ' . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'A server error occurred. Please try again.']);
}
?>