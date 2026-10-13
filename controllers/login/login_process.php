<?php
require_once __DIR__ . '/../../includes/session_security.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';

// Set header to return JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        json_error('Please fill in all fields.');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['admin_name'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['last_activity'] = time();

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
