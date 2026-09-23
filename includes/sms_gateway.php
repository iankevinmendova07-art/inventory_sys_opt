<?php
require_once __DIR__ . '/sms_config_address.php';

function send_release_sms(array $phoneNumbers, string $message): array
{
    try {
        return attempt_release_sms($phoneNumbers, $message);
    } catch (Throwable $e) {
        error_log('SMS gateway error: ' . $e->getMessage());
        return [
            'enabled' => true,
            'sent' => false,
            'message' => 'SMS notification could not be sent.'
        ];
    }
}

function attempt_release_sms(array $phoneNumbers, string $message): array
{
    $gateway = sms_gateway_config();
    $endpoint = trim($gateway['url']);
    $username = $gateway['username'];
    $password = $gateway['password'];

    if ($endpoint === '' || $username === '' || $password === '') {
        return [
            'enabled' => false,
            'sent' => false,
            'message' => 'SMS gateway is not configured.'
        ];
    }

    $phoneNumbers = array_values(array_unique(array_filter(array_map('normalize_sms_phone', $phoneNumbers))));
    if (!$phoneNumbers) {
        return [
            'enabled' => true,
            'sent' => false,
            'message' => 'No recipient mobile numbers are available.'
        ];
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'textMessage' => ['text' => $message],
            'phoneNumbers' => $phoneNumbers
        ], JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERPWD => $username . ':' . $password,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $statusCode < 200 || $statusCode >= 300) {
        error_log('SMS gateway request failed: HTTP ' . $statusCode . ($curlError ? ' - ' . $curlError : ''));
        return [
            'enabled' => true,
            'sent' => false,
            'message' => $statusCode === 0 ? 'SMS gateway is unreachable. Check that the phone server is on the same network and running.' : 'SMS notification could not be sent.'
        ];
    }

    return [
        'enabled' => true,
        'sent' => true,
        'message' => 'SMS notification sent.'
    ];
}

function normalize_sms_phone(?string $phoneNumber): string
{
    $phoneNumber = preg_replace('/[^0-9+]/', '', trim((string)$phoneNumber));

    if ($phoneNumber === '') {
        return '';
    }

    if (preg_match('/^09\d{9}$/', $phoneNumber)) {
        return '+63' . substr($phoneNumber, 1);
    }

    if (preg_match('/^63\d{10}$/', $phoneNumber)) {
        return '+' . $phoneNumber;
    }

    return $phoneNumber;
}
