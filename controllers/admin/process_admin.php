<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminId = $_POST['admin_id'] ?? 1;
    $adminName = trim($_POST['admin_name']);
    $username = trim($_POST['username']);
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Check if new passwords match if a new password is provided
    if (!empty($newPassword) && $newPassword !== $confirmPassword) {
        json_error('New passwords do not match!');
    }

    try {
        // 1. Fetch current admin record
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            json_error('Admin record not found.');
        }

        // 2. Verify old password matches DB
        if (!password_verify($oldPassword, $admin['password'])) {
            json_error('Incorrect old password! Update failed.');
        }

        // 3. Hash new password if supplied, otherwise retain old hash
        $passwordToSave = !empty($newPassword) ? password_hash($newPassword, PASSWORD_DEFAULT) : $admin['password'];

        // 4. Update Database
        $updateStmt = $pdo->prepare("UPDATE admin SET admin_name = ?, username = ?, password = ? WHERE id = ?");
        $updateStmt->execute([$adminName, $username, $passwordToSave, $adminId]);

        $_SESSION['admin_name'] = $adminName;
        
        json_success('Admin details updated successfully!');

    } catch (PDOException $e) {
        error_log('controllers/admin/process_admin.php DB error: ' . $e->getMessage());
        json_error('A database error occurred. Please try again.');
        exit();
    }
} else {
    json_error('Invalid request method.');
}