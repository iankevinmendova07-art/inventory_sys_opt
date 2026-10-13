<?php
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($requestPath)) {
    http_response_code(400);
    exit;
}

$decodedPath = rawurldecode($requestPath);
$fullPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $decodedPath), DIRECTORY_SEPARATOR));
$rootPath = realpath(__DIR__);

if ($fullPath === false || $rootPath === false) {
    return false;
}

$rootPrefix = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if ($fullPath !== $rootPath && strpos($fullPath, $rootPrefix) !== 0) {
    http_response_code(404);
    exit;
}

$relativePath = str_replace('\\', '/', ltrim(substr($fullPath, strlen($rootPath)), DIRECTORY_SEPARATOR));
$segments = $relativePath === '' ? [] : explode('/', $relativePath);
$blockedDirectories = ['config', 'includes', 'tools'];
$blockedExtensions = ['bak', 'config', 'env', 'ini', 'json', 'log', 'lock', 'md', 'sh', 'sql'];

if (
    ($segments && in_array(strtolower($segments[0]), $blockedDirectories, true)) ||
    array_filter($segments, static fn($segment) => $segment !== '' && $segment[0] === '.') ||
    in_array(strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)), $blockedExtensions, true)
) {
    http_response_code(404);
    exit;
}

return false;