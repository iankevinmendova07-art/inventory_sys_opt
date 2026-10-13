<?php
require_once __DIR__ . '/includes/session_security.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

header('Cache-Control: no-store');

$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Initial administrator setup is available only on this computer.');
}

$error = '';
$adminExists = (bool)$pdo->query('SELECT 1 FROM admin LIMIT 1')->fetchColumn();

if (!$adminExists && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string)($_POST['csrf_token'] ?? '');
    if ($submittedToken === '' || !hash_equals(csrf_token(), $submittedToken)) {
        http_response_code(403);
        $error = 'The setup form expired. Reload the page and try again.';
    } else {
        $adminName = trim((string)($_POST['admin_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');

        if ($adminName === '' || strlen($adminName) > 100) {
            $error = 'Enter an administrator name of 1 to 100 characters.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $error = 'Username must be 3 to 50 characters using letters, numbers, dots, underscores, or hyphens.';
        } elseif (strlen($password) < 12 || strlen($password) > 72) {
            $error = 'Password must be between 12 and 72 bytes.';
        } elseif (!hash_equals($password, $passwordConfirmation)) {
            $error = 'The passwords do not match.';
        } else {
            try {
                $pdo->beginTransaction();
                $adminExists = (bool)$pdo->query('SELECT 1 FROM admin LIMIT 1')->fetchColumn();
                if ($adminExists) {
                    $pdo->rollBack();
                    $error = 'An administrator already exists. Initial setup is disabled.';
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO admin (admin_name, username, password, role) VALUES (?, ?, ?, 'Admin')"
                    );
                    $stmt->execute([$adminName, $username, password_hash($password, PASSWORD_DEFAULT)]);
                    $pdo->commit();
                    header('Location: login.php?setup=complete', true, 303);
                    exit();
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Initial administrator setup failed: ' . $e->getMessage());
                $error = 'The administrator account could not be created. Check the application log.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Administrator Setup</title>
    <style>
        :root { color-scheme: light; font-family: "Segoe UI", sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #eef2f5; color: #172b3a; }
        main { width: min(100% - 32px, 440px); box-sizing: border-box; padding: 32px; background: #fff; border: 1px solid #d4dde3; border-radius: 8px; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { color: #526575; line-height: 1.5; }
        label { display: block; margin: 18px 0 6px; font-weight: 600; }
        input { box-sizing: border-box; width: 100%; padding: 11px 12px; border: 1px solid #aebbc4; border-radius: 4px; font: inherit; }
        button, .button { display: inline-block; margin-top: 22px; padding: 11px 16px; border: 0; border-radius: 4px; background: #145b52; color: white; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .error { padding: 10px 12px; border-left: 4px solid #b42318; background: #fff1f0; color: #7a271a; }
        @media (max-width: 480px) { main { padding: 24px; } }
    </style>
</head>
<body>
<main>
    <?php if ($adminExists): ?>
        <h1>Setup is complete</h1>
        <p>An administrator account already exists. Initial setup is disabled.</p>
        <a class="button" href="login.php">Go to login</a>
    <?php else: ?>
        <h1>Create administrator</h1>
        <p>Set the first account for this local inventory system.</p>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <?php echo csrf_field(); ?>
            <label for="admin_name">Administrator name</label>
            <input id="admin_name" name="admin_name" value="Administrator" maxlength="100" required>
            <label for="username">Username</label>
            <input id="username" name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" required>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
            <button type="submit">Create account</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
