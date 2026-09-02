<?php
session_start();
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

if (isset($_POST['save_textbook'])) {
    $lr_item     = trim($_POST['lr_item'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $lr_subject  = trim($_POST['lr_subject'] ?? '');
    $lr_qty      = intval($_POST['lr_qty'] ?? 0);
    $lr_unit     = trim($_POST['lr_unit'] ?? 'pc');
    $recipient   = trim($_POST['recipient'] ?? '');
    $condition   = trim($_POST['condition'] ?? 'Good');

    try {
        $stmt = $pdo->prepare("INSERT INTO lr_textbooks (lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient, `condition`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$lr_item, $grade_level, $lr_subject, $lr_qty, $lr_unit, $recipient, $condition]);

        header("Location: ../../../textbooks.php?success=added");
        exit();
    } catch (PDOException $e) {
        error_log('controllers/supplies/textbooks/insert_textbook.php error: ' . $e->getMessage());
        echo 'A server error occurred while saving this record. Please try again.';
    }
} else {
    header("Location: ../../../textbooks.php");
    exit();
}
?>