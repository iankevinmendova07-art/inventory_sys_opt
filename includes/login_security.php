<?php
/** Persistent login throttling for both account names and client IP addresses. */

if (!function_exists('get_client_ip')) {
    function get_client_ip(): string
    {
        // Do not trust forwarded headers; this app's bundled server is loopback-only.
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }
}

if (!function_exists('login_attempt_bucket_hashes')) {
    function login_attempt_bucket_hashes(string $username, string $clientIp): array
    {
        $buckets = [];
        $normalizedUsername = strtolower(trim($username));
        if ($normalizedUsername !== '') {
            $buckets[] = hash('sha256', "username\0" . $normalizedUsername);
        }
        $normalizedIp = trim($clientIp);
        if ($normalizedIp !== '') {
            $buckets[] = hash('sha256', "ip\0" . $normalizedIp);
        }
        sort($buckets, SORT_STRING);
        return array_values(array_unique($buckets));
    }
}

if (!function_exists('check_login_lockout')) {
    function check_login_lockout(PDO $pdo, string $username, string $clientIp): array
    {
        $hashes = login_attempt_bucket_hashes($username, $clientIp);
        if (!$hashes) {
            return ['locked' => false, 'remaining_seconds' => 0, 'message' => ''];
        }

        $placeholders = implode(',', array_fill(0, count($hashes), '?'));
        $statement = $pdo->prepare(
            "SELECT MAX(TIMESTAMPDIFF(SECOND, NOW(), locked_until)) AS remaining_seconds
             FROM login_attempts
             WHERE bucket_hash IN ($placeholders) AND locked_until > NOW()"
        );
        $statement->execute($hashes);
        $remaining = max(0, (int) $statement->fetchColumn());
        if ($remaining === 0) {
            return ['locked' => false, 'remaining_seconds' => 0, 'message' => ''];
        }

        $minutes = (int) ceil($remaining / 60);
        return [
            'locked' => true,
            'remaining_seconds' => $remaining,
            'message' => "Too many failed login attempts. Try again in about {$minutes} minute(s).",
        ];
    }
}

if (!function_exists('record_failed_login')) {
    function record_failed_login(PDO $pdo, string $username, string $clientIp): array
    {
        // Five failures within 15 minutes lock that username/IP bucket for 15 minutes.
        $limit = 5;
        $hashes = login_attempt_bucket_hashes($username, $clientIp);
        if (!$hashes) {
            return ['locked' => false, 'remaining_attempts' => $limit, 'remaining_seconds' => 0];
        }

        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                'INSERT IGNORE INTO login_attempts (bucket_hash, failures, window_started, locked_until)
                 VALUES (?, 0, NOW(), NULL)'
            );
            $select = $pdo->prepare(
                'SELECT failures, (TIMESTAMPDIFF(SECOND, window_started, NOW()) >= 900) AS window_expired,
                        (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked,
                        GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), locked_until)) AS remaining_seconds
                 FROM login_attempts WHERE bucket_hash = ? FOR UPDATE'
            );
            $update = $pdo->prepare(
                'UPDATE login_attempts
                 SET failures = ?, window_started = IF(?, NOW(), window_started),
                     locked_until = IF(? >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)
                 WHERE bucket_hash = ?'
            );

            $leastRemaining = $limit;
            $maxRetry = 0;
            foreach ($hashes as $hash) {
                $insert->execute([$hash]);
                $select->execute([$hash]);
                $row = $select->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    throw new RuntimeException('Login throttling bucket could not be read.');
                }

                if ((int) $row['is_locked'] === 1) {
                    $maxRetry = max($maxRetry, (int) $row['remaining_seconds']);
                    $leastRemaining = 0;
                    continue;
                }

                $expired = (int) $row['window_expired'] === 1;
                $failures = $expired ? 1 : ((int) $row['failures'] + 1);
                if ($failures >= $limit) {
                    $maxRetry = max($maxRetry, 900);
                }
                $update->execute([$failures, $expired ? 1 : 0, $failures, $hash]);
                $leastRemaining = min($leastRemaining, max(0, $limit - $failures));
            }
            $pdo->commit();
            $locked = $leastRemaining === 0 && $maxRetry > 0;
            return [
                'locked' => $locked,
                'remaining_attempts' => $leastRemaining,
                'remaining_seconds' => $maxRetry,
            ];
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }
}

if (!function_exists('clear_login_attempts')) {
    function clear_login_attempts(PDO $pdo, string $username, string $clientIp): void
    {
        $hashes = login_attempt_bucket_hashes($username, $clientIp);
        if (!$hashes) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($hashes), '?'));
        $statement = $pdo->prepare("DELETE FROM login_attempts WHERE bucket_hash IN ($placeholders)");
        $statement->execute($hashes);
    }
}
