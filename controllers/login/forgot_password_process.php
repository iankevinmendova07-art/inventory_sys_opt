<?php
require_once __DIR__ . '/../../includes/session_security.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/json_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_error('Invalid request method.');
}

require_csrf();

$recoveryPin = (string) ($_POST['recovery_pin'] ?? '');

if ($recoveryPin === '') {
    json_error('Please enter the recovery PIN.');
}

if (!hash_equals('11021992', $recoveryPin)) {
    json_error('Invalid username or recovery PIN.');
}

try {
    $adminId = $pdo->query('SELECT id FROM admin ORDER BY id LIMIT 1')->fetchColumn();
    if ($adminId === false) {
        json_error('No administrator account was found.');
    }

    $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE admin SET password = :password WHERE id = :id');
    $stmt->execute([
        'password' => $passwordHash,
        'id' => $adminId,
    ]);

    json_success('The admin password has been reset to 123456. You can now log in.');
} catch (PDOException $e) {
    error_log('controllers/login/forgot_password_process.php DB error: ' . $e->getMessage());
    http_response_code(500);
    json_error('A database error occurred. Please try again.');
}
