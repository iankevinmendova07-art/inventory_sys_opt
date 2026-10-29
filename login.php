<?php
require_once __DIR__ . '/includes/session_security.php';
require_once __DIR__ . '/includes/csrf.php';

if (isset($_SESSION['admin_id']) && !isset($_GET['success'])) {
    header("Location: index.php");
    exit();
}

// Prepare the admin name for the data attribute (converted to uppercase)
$adminNameUpper = isset($_SESSION['admin_name']) ? strtoupper($_SESSION['admin_name']) : 'ADMIN';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROJECT - IAN (Inventory and Assets Navigation)</title>
    <link rel="icon" type="image/png" sizes="512x512" href="assets/img/ian-taskbar-hd.png?v=20261029">
    
    <!-- Bootstrap 5 CSS -->
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="assets/vendor/sweetalert2/sweetalert2.min.css">

    <!-- Custom Branding CSS -->
    <link href="assets/css/login.css" rel="stylesheet">
</head>
<body data-admin-name="<?php echo htmlspecialchars($adminNameUpper, ENT_QUOTES, 'UTF-8'); ?>">

<div class="login-container">
    <div class="login-card text-center">
        
        <div class="logo-wrapper">
            <img src="assets/img/ian.png" alt="Project IAN logo">
        </div>

        <h1 class="school-title">PROJECT - IAN (Inventory and Assets Navigation)</h1>

        <form id="loginForm" action="controllers/login/login_process.php" method="POST">
            <?php echo csrf_field(); ?>
            <div class="mb-3 text-start">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus placeholder="Enter your username">
            </div>
            
            <div class="mb-4 text-start">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn btn-primary-custom w-100 mb-2">SECURE LOGIN</button>
        </form>

        <button type="button" class="btn btn-link p-0" id="forgotPasswordButton">Forgot password?</button>
        
        <div class="mt-3">
            <small class="text-muted" style="font-size: 0.75rem;">🔒 Authorized Administrative Access Only</small>
        </div>
    </div>
    
    <div class="footer-text">
        &copy; <?php echo date('Y'); ?> PROJECT - IAN (Inventory and Assets Navigation)
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="assets/vendor/sweetalert2/sweetalert2.min.js"></script>

<!-- External Custom Login JS -->
<script src="assets/js/login.js?v=2"></script>

</body>
</html>
