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
        // Track attempts by normalized username and client IP. The username bucket
        // prevents distributed guessing; the IP bucket limits rapid spraying.
        $clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $buckets = [
            hash('sha256', 'user:' . strtolower($username)) => 5,
            hash('sha256', 'ip:' . $clientIp) => 20,
        ];
        ksort($buckets, SORT_STRING);

        $pdo->beginTransaction();
        $createBucket = $pdo->prepare(
            'INSERT IGNORE INTO login_attempts (bucket_hash, failures, window_started, locked_until) VALUES (?, 0, NOW(), NULL)'
        );
        $readBucket = $pdo->prepare(
            'SELECT failures, window_started, locked_until, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM login_attempts WHERE bucket_hash = ? FOR UPDATE'
        );
        $bucketRows = [];
        foreach ($buckets as $bucketHash => $limit) {
            $createBucket->execute([$bucketHash]);
            $readBucket->execute([$bucketHash]);
            $bucketRows[$bucketHash] = $readBucket->fetch(PDO::FETCH_ASSOC);
        }

        $locked = false;
        foreach ($bucketRows as $row) {
            if ((int) $row['is_locked'] === 1) {
                $locked = true;
                break;
            }
        }
        if ($locked) {
            $pdo->commit();
            json_error('Too many login attempts. Please try again in 15 minutes.');
        }

        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $clearUserBucket = $pdo->prepare('DELETE FROM login_attempts WHERE bucket_hash = ?');
            $clearUserBucket->execute([hash('sha256', 'user:' . strtolower($username))]);
            $pdo->commit();
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
            $updateBucket = $pdo->prepare(
                'UPDATE login_attempts SET locked_until = IF(IF(window_started < DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, failures + 1) >= ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL), failures = IF(window_started < DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, failures + 1), window_started = IF(window_started < DATE_SUB(NOW(), INTERVAL 15 MINUTE), NOW(), window_started) WHERE bucket_hash = ?'
            );
            foreach ($buckets as $bucketHash => $limit) {
                $updateBucket->execute([$limit, $bucketHash]);
            }
            $pdo->commit();
            json_error('Invalid username or password.');
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_error('A database error occurred.');
    }
} else {
    json_error('Invalid request method.');
}
?>
