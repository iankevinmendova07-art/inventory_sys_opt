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
    // This recovery flow resets the single administrator account.
    $adminId = $pdo->query('SELECT id FROM admin ORDER BY id LIMIT 1')->fetchColumn();
    if ($adminId === false) {
        json_error('No administrator account was found.');
    }

    $passwordHash = password_hash('11021992', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE admin SET username = :username, password = :password WHERE id = :id');
    $stmt->execute([
        'username' => 'admin',
        'password' => $passwordHash,
        'id' => $adminId,
    ]);

    json_success('Username: admin\nPassword: 11021992');
} catch (PDOException $e) {
    error_log('controllers/login/forgot_password_process.php DB error: ' . $e->getMessage());
    http_response_code(500);
    json_error('A database error occurred. Please try again.');
}
