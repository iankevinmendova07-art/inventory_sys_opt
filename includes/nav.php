<?php
require_once __DIR__ . '/../config/db.php';

$schoolName = '';
try {
    $schoolName = trim((string) $pdo->query(
        'SELECT school_name FROM school_details ORDER BY id ASC LIMIT 1'
    )->fetchColumn());
} catch (PDOException $e) {
    error_log('includes/nav.php school name lookup failed: ' . $e->getMessage());
}

$csrfMetaToken = function_exists('csrf_token') ? csrf_token() : '';
$adminId = (string) ($_SESSION['admin_id'] ?? '');
// Get the current page filename
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if current page belongs to supplies submenus to keep the parent active/expanded
$isSuppliesActive = ($currentPage == 'consup.php' || $currentPage == 'non-consumable.php' || $currentPage == 'nonconsup.php');

// Check if current page belongs to learning resources submenus
$isLrActive = ($currentPage == 'lr.php' || $currentPage == 'science_math_eq.php' || $currentPage == 'sciene_math.php' || $currentPage == 'textbooks.php');
?>
<?php if ($csrfMetaToken !== ''): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfMetaToken, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
<meta name="admin-id" content="<?php echo htmlspecialchars($adminId, ENT_QUOTES, 'UTF-8'); ?>">

<!-- Sidebar Navigation -->
<nav id="sidebar">
    <div class="sidebar-header">
        <img src="assets/img/ian.png" alt="Project IAN logo" class="sidebar-logo">
        <?php if ($schoolName !== ''): ?>
            <p class="sidebar-school-name" style="font-size: 0.95rem;"><?php echo htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <h3 style="font-size: 0.75rem;">PROJECT - IAN (Inventory and Assets Navigation)</h3>
    </div>

    <ul class="list-unstyled components">
        <li class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
            <a href="index.php"><i class="bi bi-box-seam"></i> Home</a>
        </li>

        <!-- Supplies Main Menu with Toggle Submenus -->
        <li class="<?php echo $isSuppliesActive ? 'active' : ''; ?>">
            <a href="#suppliesSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $isSuppliesActive ? 'true' : 'false'; ?>" class="dropdown-toggle">
                <i class="bi bi-collection"></i> Supplies
            </a>
            <ul class="collapse list-unstyled <?php echo $isSuppliesActive ? 'show' : ''; ?>" id="suppliesSubmenu">
                <li class="<?php echo ($currentPage == 'consup.php') ? 'active' : ''; ?>">
                    <a href="consup.php"><i class="bi bi-arrow-return-right"></i> Consumable Supplies</a>
                </li>
                <li class="<?php echo ($currentPage == 'nonconsup.php' || $currentPage == 'non-consumable.php') ? 'active' : ''; ?>">
                    <a href="nonconsup.php"><i class="bi bi-arrow-return-right"></i> Non-Consumable Supplies</a>
                </li>
            </ul>
        </li>

        <!-- Learning Resources Main Menu with Science & Math and TextBooks sublinks -->
        <li class="<?php echo $isLrActive ? 'active' : ''; ?>">
            <a href="#lrSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $isLrActive ? 'true' : 'false'; ?>" class="dropdown-toggle">
                <i class="bi bi-journal-bookmark"></i> Learning Resources
            </a>
            <ul class="collapse list-unstyled <?php echo $isLrActive ? 'show' : ''; ?>" id="lrSubmenu">
                <li class="<?php echo ($currentPage == 'sciene_math.php' || $currentPage == 'science_math_eq.php') ? 'active' : ''; ?>">
                    <a href="sciene_math.php"><i class="bi bi-arrow-return-right"></i> Science and Math Equipment</a>
                </li>
                <li class="<?php echo ($currentPage == 'textbooks.php') ? 'active' : ''; ?>">
                    <a href="textbooks.php"><i class="bi bi-arrow-return-right"></i> TextBooks</a>
                </li>
            </ul>
        </li>

        <li class="<?php echo ($currentPage == 'setting.php') ? 'active' : ''; ?>">
            <a href="setting.php"><i class="bi bi-gear"></i> Settings</a>
        </li>
        <li class="mt-4">
            <a href="#" id="logoutBtn" class="text-danger"><i class="bi bi-box-arrow-right text-danger"></i> Logout</a>
        </li>
    </ul>
</nav>
