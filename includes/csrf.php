<?php
/**
 * Session-backed CSRF protection for authenticated state-changing requests.
 */

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' .
            htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_request_is_same_origin')) {
    function csrf_request_is_same_origin(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $source = $origin !== '' ? $origin : $referer;

        if ($source === '' || empty($_SERVER['HTTP_HOST'])) {
            return false;
        }

        $sourceParts = parse_url($source);
        if (!$sourceParts || empty($sourceParts['host'])) {
            return false;
        }

        $sourceHost = strtolower($sourceParts['host']);
        $requestHost = strtolower((string)$_SERVER['HTTP_HOST']);
        $sourcePort = isset($sourceParts['port']) ? ':' . $sourceParts['port'] : '';
        $requestPort = '';

        if (strpos($requestHost, ':') !== false) {
            [$requestHost, $requestPortValue] = explode(':', $requestHost, 2);
            $requestPort = ':' . $requestPortValue;
        }

        return $sourceHost === $requestHost && ($sourcePort === '' || $sourcePort === $requestPort);
    }
}

if (!function_exists('require_csrf')) {
    function require_csrf(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        $sessionToken = csrf_token();

        if ($submittedToken !== '' && hash_equals($sessionToken, (string)$submittedToken)) {
            return;
        }

        if (csrf_request_is_same_origin()) {
            return;
        }

        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing CSRF token.']);
        exit();
    }
}
