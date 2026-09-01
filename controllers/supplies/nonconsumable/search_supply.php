<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$response = ['success' => false, 'data' => null, 'debug' => ''];

try {
    // Exactly three levels up: nonconsumable -> supplies -> controllers -> inventory_sys root -> config/database.php
    $dbPath = __DIR__ . '/../../../config/db.php';
    
    if (!file_exists($dbPath)) {
        throw new Exception("Database config file not found at: " . $dbPath);
    }
    
    require_once $dbPath;

    if (!isset($pdo)) {
        throw new Exception("PDO connection variable not set in database.php");
    }

    if (isset($_GET['property_number'])) {
        $propertyNumber = trim($_GET['property_number']);
        $response['debug_query_val'] = $propertyNumber;

        $stmt = $pdo->prepare("SELECT * FROM nonconsumable WHERE BINARY property_number = ? LIMIT 1");
        $stmt->execute([$propertyNumber]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $response['success'] = true;
            $response['data'] = $item;
        } else {
            $response['debug'] = "No record found for property number: " . $propertyNumber;
        }
    } else {
        $response['debug'] = "No property_number parameter provided in GET request.";
    }
} catch (Exception $e) {
    $response['success'] = false;
    error_log('controllers/supplies/nonconsumable/search_supply.php error: ' . $e->getMessage());
    $response['error'] = 'A server error occurred.';
}

echo json_encode($response);
exit;
?>