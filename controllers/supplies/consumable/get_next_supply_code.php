<?php
require_once dirname(__DIR__, 2) . '/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Invalid request method.');
}

try {
    // Item codes are stored as text, so compare numeric codes as numbers.
    $stmt = $pdo->query("SELECT supply_code FROM supplies WHERE TRIM(supply_code) REGEXP '^[0-9]+$'");
    $highestCode = 0;
    while (($code = $stmt->fetchColumn()) !== false) {
        $numericCode = (int) $code;
        if ($numericCode > $highestCode) {
            $highestCode = $numericCode;
        }
    }

    json_success('Next item code loaded.', ['itemCode' => (string) ($highestCode + 1)]);
} catch (PDOException $e) {
    error_log('controllers/supplies/consumable/get_next_supply_code.php DB error: ' . $e->getMessage());
    json_error('Unable to generate the next item code. Please try again.');
}
?>
