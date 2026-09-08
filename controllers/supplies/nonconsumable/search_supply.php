<?php
require_once dirname(__DIR__, 2) . '/auth/auth.php';

header('Content-Type: application/json');

$response = ['success' => false, 'data' => null];

try {
    $dbPath = __DIR__ . '/../../../config/db.php';
    
    if (!file_exists($dbPath)) {
        throw new RuntimeException('Database configuration is unavailable.');
    }
    
    require_once $dbPath;

    if (!isset($pdo)) {
        throw new RuntimeException('Database connection is unavailable.');
    }

    if (isset($_GET['property_number'])) {
        $propertyNumber = trim($_GET['property_number']);
        $stmt = $pdo->prepare("SELECT * FROM nonconsumable WHERE BINARY property_number = ? LIMIT 1");
        $stmt->execute([$propertyNumber]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $response['success'] = true;
            $response['data'] = $item;
        }
    }
} catch (Exception $e) {
    $response['success'] = false;
    error_log('controllers/supplies/nonconsumable/search_supply.php error: ' . $e->getMessage());
    $response['error'] = 'A server error occurred.';
}

echo json_encode($response);
exit;
?>