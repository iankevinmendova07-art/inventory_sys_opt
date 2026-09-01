<?php
session_start();
require_once '../../auth/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inventory_sys/config/db.php';

if (isset($_POST['save_textbook'])) {
    $lr_item     = $_POST['lr_item'];
    $grade_level = $_POST['grade_level'];
    $lr_subject  = $_POST['lr_subject'];
    $lr_qty      = $_POST['lr_qty'];
    $lr_unit     = $_POST['lr_unit'];
    $recipient   = $_POST['recipient'];

    try {
        $stmt = $pdo->prepare("INSERT INTO lr_textbooks (lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$lr_item, $grade_level, $lr_subject, $lr_qty, $lr_unit, $recipient]);

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