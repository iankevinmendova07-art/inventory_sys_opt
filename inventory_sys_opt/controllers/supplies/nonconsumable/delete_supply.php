<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        json_error('Invalid item ID.');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM nonconsumable WHERE id = ?");
        $execute = $stmt->execute([$id]);

        if ($execute) {
            json_success('Item deleted successfully!');
        } else {
            json_error('Failed to delete the item.');
        }
    } catch (PDOException $e) {
        error_log('controllers/supplies/nonconsumable/delete_supply.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
} else {
    json_error('Invalid request method.');
}
?>
