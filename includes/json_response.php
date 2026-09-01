<?php
/**
 * json_response.php
 *
 * Tiny shared helper so every CRUD controller doesn't repeat the same
 * `header(...); echo json_encode([...]); exit();` boilerplate. Include
 * this once, then call json_success()/json_error() instead.
 */

if (!function_exists('json_success')) {
    function json_success(string $message, array $extra = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['status' => 'success', 'message' => $message], $extra));
        exit();
    }
}

if (!function_exists('json_error')) {
    function json_error(string $message, array $extra = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['status' => 'error', 'message' => $message], $extra));
        exit();
    }
}
