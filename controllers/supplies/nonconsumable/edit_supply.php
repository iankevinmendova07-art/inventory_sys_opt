<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';


if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM nonconsumable WHERE id = ?");
        $stmt->execute([$id]);
        $supply = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($supply) {
            echo json_encode(['status' => 'success', 'data' => $supply]);
        } else {
            json_error('Item not found.');
        }
    } catch (PDOException $e) {
        error_log('controllers/supplies/nonconsumable/edit_supply.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
    }
} else {
    json_error('Invalid request.');
}
?>
