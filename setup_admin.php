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

$philippineRegions = [
    'NCR - National Capital Region',
    'CAR - Cordillera Administrative Region',
    'Region I - Ilocos Region',
    'Region II - Cagayan Valley',
    'Region III - Central Luzon',
    'Region IV-A - CALABARZON',
    'MIMAROPA Region',
    'Region V - Bicol Region',
    'Region VI - Western Visayas',
    'Region VII - Central Visayas',
    'Region VIII - Eastern Visayas',
    'Region IX - Zamboanga Peninsula',
    'Region X - Northern Mindanao',
    'Region XI - Davao Region',
    'Region XII - SOCCSKSARGEN',
    'Region XIII - Caraga',
    'BARMM - Bangsamoro Autonomous Region in Muslim Mindanao'
];

$error = '';
$adminExists = (bool)$pdo->query('SELECT 1 FROM admin LIMIT 1')->fetchColumn();
$editSchoolDetails = $adminExists && (($_GET['edit'] ?? '') === '1');

// Check if school_details already has a record
$schoolDetails = null;
try {
    $stmtSD = $pdo->query("SELECT * FROM school_details ORDER BY id ASC LIMIT 1");
    if ($stmtSD) {
        $schoolDetails = $stmtSD->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // If table doesn't exist yet, it will be handled or created
}

$divisionValue = (string)($_POST['division'] ?? $schoolDetails['Division'] ?? '');
if ($divisionValue === '0') {
    $divisionValue = '';
}

if ((!$adminExists || $editSchoolDetails) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = (string)($_POST['csrf_token'] ?? '');
    if ($submittedToken === '' || !hash_equals(csrf_token(), $submittedToken)) {
        http_response_code(403);
        $error = 'The setup form expired. Reload the page and try again.';
    } else {
        $adminName = 'Administrator';
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');
        $region = trim((string)($_POST['region'] ?? ''));
        $division = trim((string)($_POST['division'] ?? ''));
        $schoolName = trim((string)($_POST['school_name'] ?? ''));
        $schoolName = function_exists('mb_strtoupper')
            ? mb_strtoupper($schoolName, 'UTF-8')
            : strtoupper($schoolName);
        $schoolAddress = trim((string)($_POST['school_address'] ?? ''));

        if (!$editSchoolDetails && !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $error = 'Username must be 3 to 50 characters using letters, numbers, dots, underscores, or hyphens.';
        } elseif (!$editSchoolDetails && (strlen($password) < 6 || strlen($password) > 72)) {
            $error = 'Password must be between 6 and 72 bytes.';
        } elseif (!$editSchoolDetails && !hash_equals($password, $passwordConfirmation)) {
            $error = 'The passwords do not match.';
        } elseif ($region === '' || !in_array($region, $philippineRegions, true)) {
            $error = 'Please select a valid Philippine Region.';
        } elseif ($division === '' || strlen($division) > 255) {
            $error = 'Enter a division name between 1 and 255 characters.';
        } elseif ($schoolName === '' || strlen($schoolName) > 255) {
            $error = 'Enter a school name between 1 and 255 characters.';
        } elseif ($schoolAddress === '' || strlen($schoolAddress) > 255) {
            $error = 'Enter a school address between 1 and 255 characters.';
        } else {
            try {
                $pdo->beginTransaction();
                $adminExists = (bool)$pdo->query('SELECT 1 FROM admin LIMIT 1')->fetchColumn();
                if ($adminExists && !$editSchoolDetails) {
                    $pdo->rollBack();
                    $error = 'An administrator already exists. Initial setup is disabled.';
                } else {
                    if (!$editSchoolDetails) {
                        $stmt = $pdo->prepare(
                            "INSERT INTO admin (admin_name, username, password, role) VALUES (?, ?, ?, 'Admin')"
                        );
                        $stmt->execute([$adminName, $username, password_hash($password, PASSWORD_DEFAULT)]);
                    }

                    // Save or update the school information in school_details
                    $existingSD = $pdo->query("SELECT id FROM school_details ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    if ($existingSD) {
                        $updateSD = $pdo->prepare("UPDATE school_details SET Region = ?, Division = ?, school_name = ?, school_address = ? WHERE id = ?");
                        $updateSD->execute([$region, $division, $schoolName, $schoolAddress, $existingSD['id']]);
                    } else {
                        $insertSD = $pdo->prepare("INSERT INTO school_details (Region, Division, school_name, school_address, head_name, head_position, custodian_name, custodian_position) VALUES (?, ?, ?, ?, '', '', '', '')");
                        $insertSD->execute([$region, $division, $schoolName, $schoolAddress]);
                    }

                    $pdo->commit();
                    header($editSchoolDetails ? 'Location: setup_admin.php?edit=1&saved=1' : 'Location: login.php?setup=complete', true, 303);
                    exit();
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Initial administrator setup failed: ' . $e->getMessage());
                $error = 'The setup could not be completed. Check the application log.';
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
        main { width: min(100% - 32px, 480px); box-sizing: border-box; padding: 32px; background: #fff; border: 1px solid #d4dde3; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        h1 { margin: 0 0 8px; font-size: 24px; color: #0D3B66; }
        p { color: #526575; line-height: 1.5; margin-top: 0; }
        .section-title { margin: 20px 0 6px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7c8a; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        label { display: block; margin: 14px 0 6px; font-weight: 600; font-size: 14px; }
        input, select { box-sizing: border-box; width: 100%; padding: 10px 12px; border: 1px solid #aebbc4; border-radius: 4px; font: inherit; background-color: #fff; }
        input:focus, select:focus { outline: none; border-color: #145b52; box-shadow: 0 0 0 3px rgba(20, 91, 82, 0.15); }
        button, .button { display: inline-block; width: 100%; margin-top: 24px; padding: 12px 16px; border: 0; border-radius: 4px; background: #145b52; color: white; font: inherit; font-weight: 600; text-align: center; text-decoration: none; cursor: pointer; transition: background-color 0.2s; }
        button:hover, .button:hover { background-color: #0e433c; }
        .centered-button { display: block; width: fit-content; min-width: 180px; margin: 24px auto 0; }
        .edit-link { display: block; margin-top: 16px; color: #145b52; text-align: center; }
        .error { padding: 10px 12px; border-left: 4px solid #b42318; background: #fff1f0; color: #7a271a; border-radius: 2px; font-size: 14px; margin-bottom: 16px; }
        .success { padding: 10px 12px; border-left: 4px solid #14804a; background: #eefbf3; color: #155d37; border-radius: 2px; font-size: 14px; margin-bottom: 16px; }
        @media (max-width: 480px) { main { padding: 24px; } }
    </style>
</head>
<body>
<main>
    <?php if ($adminExists && !$editSchoolDetails): ?>
        <h1>Setup is complete</h1>
        <p>An administrator account already exists. Initial setup is disabled.</p>
        <a class="button centered-button" href="login.php">Go to login</a>
        <a class="edit-link" href="setup_admin.php?edit=1">Edit school details</a>
    <?php else: ?>
        <h1><?php echo $editSchoolDetails ? 'Edit School Details' : 'Initial System Setup'; ?></h1>
        <p><?php echo $editSchoolDetails ? 'Update your school information.' : 'Set up the administrator account and school jurisdiction.'; ?></p>
        <?php if (isset($_GET['saved'])): ?>
            <p class="success" role="status">School details saved.</p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <?php echo csrf_field(); ?>

            <?php if (!$editSchoolDetails): ?>
                <label for="username">Username</label>
                <input id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" required>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" minlength="6" maxlength="72" autocomplete="new-password" required>
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="6" maxlength="72" autocomplete="new-password" required>
            <?php endif; ?>

            <div class="section-title" style="margin-top: 24px;">School Jurisdiction</div>
            <label for="region">Region</label>
            <select id="region" name="region" required>
                <option value="" disabled <?php echo empty($_POST['region'] ?? $schoolDetails['Region'] ?? '') ? 'selected' : ''; ?>>Select a Region</option>
                <?php
                $selectedRegion = $_POST['region'] ?? $schoolDetails['Region'] ?? 'Region VIII - Eastern Visayas';
                foreach ($philippineRegions as $r):
                ?>
                    <option value="<?php echo htmlspecialchars($r, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedRegion === $r ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="division">School Division</label>
            <input id="division" name="division" type="text" maxlength="255" placeholder="e.g. Catbalogan City or Schools Division of Catbalogan City" value="<?php echo htmlspecialchars($divisionValue, ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="school_name">School Name</label>
            <input id="school_name" name="school_name" type="text" maxlength="255" style="text-transform: uppercase;" value="<?php echo htmlspecialchars($_POST['school_name'] ?? $schoolDetails['school_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" oninput="this.value = this.value.toLocaleUpperCase();" required>

            <label for="school_address">Address</label>
            <input id="school_address" name="school_address" type="text" maxlength="255" value="<?php echo htmlspecialchars($_POST['school_address'] ?? $schoolDetails['school_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <button type="submit"><?php echo $editSchoolDetails ? 'Save School Details' : 'Complete Setup'; ?></button>
            <?php if ($editSchoolDetails): ?>
                <a class="edit-link" href="setup_admin.php">Back</a>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
