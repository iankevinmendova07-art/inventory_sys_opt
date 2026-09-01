<?php
require_once '../../config/db.php';

header('Content-Type: application/json');

if (isset($_GET['q'])) {
    $q = trim($_GET['q']);
    
    try {
        // BINARY forces case-sensitive exact match
        $stmt = $pdo->prepare("SELECT * FROM supplies WHERE BINARY supply_code = ? LIMIT 1");
        $stmt->execute([$q]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo json_encode(['status' => 'success', 'data' => $result]);
        } else {
            echo json_encode(['status' => 'not_found']);
        }
    } catch (PDOException $e) {
        error_log('controllers/cart/search_cart.php error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'A server error occurred. Please try again.']);
    }
}
?>