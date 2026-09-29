<?php
require_once dirname(__DIR__) . '/auth/auth.php';
require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/includes/json_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_error('Invalid request method.');
}

$enabled = $_POST['enabled'] ?? null;
if (!in_array($enabled, ['0', '1'], true)) {
    json_error('Invalid SMS notification setting.');
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO system_settings (setting_key, setting_value) VALUES ('sms_enabled', ?) 
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->execute([$enabled]);

    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
        header('Location: ../../setting.php?sms=' . ($enabled === '1' ? 'activated' : 'deactivated'), true, 303);
        exit();
    }

    json_success('SMS notifications ' . ($enabled === '1' ? 'activated.' : 'deactivated.'), [
        'enabled' => $enabled === '1'
    ]);
} catch (PDOException $e) {
    error_log('update_sms_status.php error: ' . $e->getMessage());
    json_error('Unable to update SMS notification settings. Please try again.');
}
