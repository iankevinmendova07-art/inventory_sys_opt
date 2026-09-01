<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Note: Adjust the relative path to login.php depending on where your protected page is located!
    header("Location: login.php");
    exit();
}
?>