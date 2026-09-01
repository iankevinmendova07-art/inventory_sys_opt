<?php
session_start();

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
    <title>Login - San Roque Elementary School Inventory System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom Branding CSS -->
    <link href="assets/css/login.css" rel="stylesheet">
</head>
<body data-admin-name="<?php echo htmlspecialchars($adminNameUpper); ?>">

<div class="login-container">
    <div class="login-card text-center">
        
        <div class="logo-wrapper">
            <img src="assets/img/san_roque.png" alt="San Roque ES Logo">
        </div>

        <h1 class="school-title">SAN ROQUE ELEMENTARY SCHOOL</h1>
        <p class="system-subtitle">PROJECT IAN <br>Inventory and Asset Navigator </p>

        <form id="loginForm" action="controllers/login/login_process.php" method="POST">
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
        
        <div class="mt-3">
            <small class="text-muted" style="font-size: 0.75rem;">🔒 Authorized Administrative Access Only</small>
        </div>
    </div>
    
    <div class="footer-text">
        &copy; <?php echo date('Y'); ?> San Roque Elementary School<br>Catbalogan City
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- External Custom Login JS -->
<script src="assets/js/login.js"></script>

</body>
</html>