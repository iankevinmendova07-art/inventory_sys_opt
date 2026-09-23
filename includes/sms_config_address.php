<?php
/**
 * SMS gateway connection settings.
 * Update these addresses when the phone joins a different network.
 * Keep the username, password, and device ID in the root .env file.
 */

if (!function_exists('sms_gateway_config')) {
    function sms_gateway_config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        // Root .env is ignored by Git. Server environment variables take priority.
        $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
                    $value = substr($value, 1, -1);
                }

                if (in_array($key, ['SMS_GATEWAY_USERNAME', 'SMS_GATEWAY_PASSWORD', 'SMS_GATEWAY_DEVICE_ID', 'SMS_GATEWAY_ADDRESS_MODE'], true)
                    && getenv($key) === false) {
                    putenv($key . '=' . $value);
                }
            }
        }

        // Choose 'local' while the computer and phone share Wi-Fi; use 'public' remotely.
        $localAddress = 'http://192.168.101.7:8000';
        $publicAddress = 'http://103.224.94.20:8000';
        $mode = strtolower(trim((string)(getenv('SMS_GATEWAY_ADDRESS_MODE') ?: 'local')));
        $baseAddress = $mode === 'public' ? $publicAddress : $localAddress;

        $config = [
            'url' => rtrim($baseAddress, '/') . '/message',
            'username' => (string)(getenv('SMS_GATEWAY_USERNAME') ?: ''),
            'password' => (string)(getenv('SMS_GATEWAY_PASSWORD') ?: ''),
            // Available for gateway variants that require a target device ID.
            'device_id' => (string)(getenv('SMS_GATEWAY_DEVICE_ID') ?: ''),
        ];

        return $config;
    }
}
