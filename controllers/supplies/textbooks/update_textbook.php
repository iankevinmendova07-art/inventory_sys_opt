<?php
session_start();
require_once '../../../config/db.php';

if (isset($_POST['update_textbook'])) {
    $id = $_POST['id'];
    $lr_item = $_POST['lr_item'];
    $grade_level = $_POST['grade_level'];
    $lr_subject = $_POST['lr_subject'];
    $lr_qty = $_POST['lr_qty'];
    $lr_unit = $_POST['lr_unit'];
    $recipient = $_POST['recipient'];

    try {
        $stmt = $pdo->prepare("UPDATE lr_textbooks SET lr_item = ?, grade_level = ?, lr_subject = ?, lr_qty = ?, lr_unit = ?, recipient = ? WHERE id = ?");
        $stmt->execute([$lr_item, $grade_level, $lr_subject, $lr_qty, $lr_unit, $recipient, $id]);

        header("Location: ../../../textbooks.php?success=updated");
        exit();
    } catch (PDOException $e) {
        error_log('controllers/supplies/textbooks/update_textbook.php error: ' . $e->getMessage());
        die('A server error occurred while updating this record. Please try again.');
    }
}
?>