<?php
// Ensure session is started with secure parameters
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

require_once dirname(__DIR__, 2) . '/includes/csrf.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Check if this is an AJAX or JSON request
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
           || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in.']);
        exit();
    }

    // Determine relative path back to root login.php regardless of entry script location
    $rootDir = realpath(dirname(__DIR__, 2)); // project root
    $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $redirectUrl = 'login.php';

    if ($rootDir && $scriptDir && strpos($scriptDir, $rootDir) === 0) {
        $relativePath = trim(str_replace('\\', '/', substr($scriptDir, strlen($rootDir))), '/');
        if ($relativePath !== '') {
            $depth = substr_count($relativePath, '/') + 1;
            $redirectUrl = str_repeat('../', $depth) . 'login.php';
        }
    }

    header("Location: " . $redirectUrl);
    exit();
}

// Authenticated accounts with non-admin roles must not reach inventory or
// configuration pages and actions, all of which include this shared guard.
$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (!in_array($role, ['admin', 'administrator'], true)) {
    http_response_code(403);
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
           || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Administrator access is required.']);
    } else {
        echo 'Administrator access is required.';
    }
    exit();
}

require_csrf();
?>
