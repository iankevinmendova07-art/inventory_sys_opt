<?php
require_once dirname(__DIR__, 2) . '/auth/auth.php';
require_once '../../../config/db.php';
require_once __DIR__ . '/../../../includes/json_response.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM lr_textbooks WHERE id = ?");
        $stmt->execute([$id]);

        json_success('The textbook has been deleted.');
    } catch (PDOException $e) {
        error_log('controllers/supplies/textbooks/delete_textbook.php error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'A server error occurred while deleting this record.']);
    }
}