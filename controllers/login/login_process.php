<?php
require_once __DIR__ . '/../../includes/session_security.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';
require_once __DIR__ . '/../../includes/login_security.php';

// Set header to return JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        json_error('Please fill in all fields.');
    }

    $clientIp = get_client_ip();

    // Check rate limit and lockout BEFORE performing authentication
    $lockout = check_login_lockout($pdo, $username, $clientIp);
    if ($lockout['locked']) {
        json_error($lockout['message'], [
            'locked_out' => true,
            'retry_after' => $lockout['remaining_seconds']
        ]);
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            // Authentication SUCCESS: clear accumulated failed attempts for this user and IP
            clear_login_attempts($pdo, $username, $clientIp);

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
            // Mitigation for timing attacks when admin does not exist
            if (!$admin) {
                password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            }

            // Authentication FAILED: record the attempt
            $result = record_failed_login($pdo, $username, $clientIp);
            if ($result['locked']) {
                json_error($result['message'], [
                    'locked_out' => true,
                    'retry_after' => $result['remaining_seconds']
                ]);
            } else {
                $rem = $result['remaining_attempts'];
                $word = $rem === 1 ? 'attempt' : 'attempts';
                json_error("Invalid username or password. ({$rem} {$word} remaining before lockout)", [
                    'remaining_attempts' => $rem
                ]);
            }
        }

    } catch (PDOException $e) {
        error_log('controllers/login/login_process.php DB error: ' . $e->getMessage());
        json_error('A database error occurred.');
    }
} else {
    json_error('Invalid request method.');
}
?>
