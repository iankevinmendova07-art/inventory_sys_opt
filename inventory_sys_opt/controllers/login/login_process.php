<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';

// Set header to return JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        json_error('Please fill in all fields.');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['admin_name'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = $admin['role'];

            // Pass the uppercase name back to JavaScript for the greeting
            echo json_encode([
                'status' => 'success', 
                'admin_name' => strtoupper($admin['admin_name'])
            ]);
            exit();
        } else {
            json_error('Invalid username or password.');
        }

    } catch (PDOException $e) {
        json_error('A database error occurred.');
    }
} else {
    json_error('Invalid request method.');
}
?>