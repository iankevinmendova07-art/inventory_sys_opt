<?php
/** Send the saved school-details record to the configured Google Apps Script endpoint. */
if (!function_exists('school_sync_config')) {
    function school_sync_config(string $key): string
    {
        $value = getenv($key);
        if ($value !== false && trim($value) !== '') {
            return trim($value);
        }

        // This project keeps local settings in its ignored root .env file.
        foreach (['.env', 'env'] as $envFile) {
            $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $envFile;
            if (!is_readable($envPath)) {
                continue;
            }
            foreach (file($envPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=\s*(.*?)\s*$/', $line, $matches)) {
                    $value = trim($matches[1]);
                    if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                        $value = substr($value, 1, -1);
                    }
                    return trim($value);
                }
            }
        }
        return '';
    }
}

if (!function_exists('sync_school_details_to_sheet')) {
    function sync_school_details_to_sheet(array $values): string
    {
        $url = school_sync_config('SCHOOL_DETAILS_WEBHOOK_URL');
        $secret = school_sync_config('SCHOOL_DETAILS_WEBHOOK_SECRET');
        if ($url === '' || $secret === '') {
            return 'not_configured';
        }

        $parts = parse_url($url);
        if (!$parts || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
            || strtolower((string)($parts['host'] ?? '')) !== 'script.google.com'
            || !preg_match('#^/macros/s/[A-Za-z0-9_-]+/exec$#', (string)($parts['path'] ?? ''))) {
            error_log('School details sheet sync is misconfigured: expected a Google Apps Script /exec URL.');
            return 'failed';
        }

        $payload = [
            'timestamp' => time(),
            'data' => [
                'Region' => (string)$values['Region'],
                'School Division' => (string)$values['Division'],
                'Office/School Type' => (string)$values['type'],
                'School Name' => (string)$values['school_name'],
                'School ID' => (string)($values['school_id'] ?? ''),
                'School Address' => (string)$values['school_address'],
                'School Head/Supply Officer' => (string)$values['head_name'],
                'Property Custodian Name' => (string)$values['custodian_name'],
            ],
        ];
        try {
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $envelope = json_encode([
                'payload' => base64_encode($payloadJson),
                'signature' => hash_hmac('sha256', $payloadJson, $secret),
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            error_log('School details sheet sync could not encode its payload.');
            return 'failed';
        }

        // The bundled Windows PHP build has no built-in CA bundle. Prefer the
        // deployed runtime copy, then the repository copy used by Laragon.
        $caBundle = null;
        foreach ([
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'cacert.pem',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR . 'runtime-overlay' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'cacert.pem',
        ] as $candidateBundle) {
            if (is_readable($candidateBundle)) {
                $caBundle = $candidateBundle;
                break;
            }
        }

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            $curlOptions = [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $envelope,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'Project-IAN-School-Details-Sync/1.0',
            ];
            if ($caBundle !== null) {
                $curlOptions[CURLOPT_CAINFO] = $caBundle;
            }
            curl_setopt_array($handle, $curlOptions);
            $response = curl_exec($handle);
            $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $contentType = (string)curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
            $error = curl_error($handle);
            curl_close($handle);
        } else {
            $sslOptions = ['verify_peer' => true, 'verify_peer_name' => true];
            if ($caBundle !== null) {
                $sslOptions['cafile'] = $caBundle;
            }
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'timeout' => 12,
                    'follow_location' => 1,
                    'max_redirects' => 3,
                    'header' => "Content-Type: application/json\r\nAccept: application/json\r\nUser-Agent: Project-IAN-School-Details-Sync/1.0\r\n",
                    'content' => $envelope,
                    'ignore_errors' => true,
                ],
                'ssl' => $sslOptions,
            ]);
            $response = @file_get_contents($url, false, $context);
            $status = 0;
            $contentType = '';
            foreach (($http_response_header ?? []) as $header) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $match)) {
                    $status = (int)$match[1];
                } elseif (stripos($header, 'Content-Type:') === 0) {
                    $contentType = trim(substr($header, strlen('Content-Type:')));
                }
            }
            $error = $response === false ? (error_get_last()['message'] ?? 'HTTP request failed') : '';
        }

        $result = is_string($response) ? json_decode($response, true) : null;
        $expectedSchoolId = trim((string)($values['school_id'] ?? ''));
        if ($status >= 200 && $status < 300 && is_array($result) && ($result['ok'] ?? false) === true) {
            if (($result['schemaVersion'] ?? null) === 2
                && trim((string)($result['schoolId'] ?? '')) === $expectedSchoolId) {
                return 'synced';
            }
            $diagnostic = 'receiver is outdated or did not confirm the saved School ID; redeploy the latest Apps Script version';
            error_log('School details sheet sync failed (HTTP ' . $status . '): ' . $diagnostic);
            return 'failed';
        }
        if ($error !== '') {
            $diagnostic = $error;
        } elseif (is_array($result) && isset($result['error'])) {
            $diagnostic = 'endpoint response: ' . preg_replace('/[^A-Za-z0-9_ -]/', '', (string)$result['error']);
        } elseif (!is_array($result)) {
            $diagnostic = 'non-JSON endpoint response' . ($contentType !== '' ? ' (' . $contentType . ')' : '');
        } else {
            $diagnostic = 'endpoint did not confirm success';
        }
        error_log('School details sheet sync failed (HTTP ' . $status . '): ' . $diagnostic);
        return 'failed';
    }
}
?>
