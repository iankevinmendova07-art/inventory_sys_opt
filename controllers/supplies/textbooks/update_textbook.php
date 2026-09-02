<?php
session_start();
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

if (isset($_POST['update_textbook'])) {
    $id          = intval($_POST['id'] ?? 0);
    $lr_item     = trim($_POST['lr_item'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $lr_subject  = trim($_POST['lr_subject'] ?? '');
    $lr_qty      = intval($_POST['lr_qty'] ?? 0);
    $lr_unit     = trim($_POST['lr_unit'] ?? 'pc');
    $recipient   = trim($_POST['recipient'] ?? '');
    $condition   = trim($_POST['condition'] ?? 'Good');

    try {
        $stmt = $pdo->prepare("UPDATE lr_textbooks SET lr_item = ?, grade_level = ?, lr_subject = ?, lr_qty = ?, lr_unit = ?, recipient = ?, `condition` = ? WHERE id = ?");
        $stmt->execute([$lr_item, $grade_level, $lr_subject, $lr_qty, $lr_unit, $recipient, $condition, $id]);

        header("Location: ../../../textbooks.php?success=updated");
        exit();
    } catch (PDOException $e) {
        error_log('controllers/supplies/textbooks/update_textbook.php error: ' . $e->getMessage());
        die('A server error occurred while updating this record. Please try again.');
    }
} else {
    header("Location: ../../../textbooks.php");
    exit();
}
?>