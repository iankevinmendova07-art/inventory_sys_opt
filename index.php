<?php
require_once __DIR__ . '/includes/session_security.php';
require_once __DIR__ . '/controllers/auth/auth.php';
require_once __DIR__ . '/controllers/index/index_controller.php';
$e = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$initial = strtoupper(substr(trim((string)$adminName), 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Project IAN</title>
    <link rel="icon" type="image/png" sizes="512x512" href="assets/img/ian-taskbar-hd.png?v=20261029">
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/vendor/sweetalert2/sweetalert2.min.css">
    <link href="assets/css/index.css" rel="stylesheet">
    <link href="assets/css/navigation.css" rel="stylesheet">
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="assets/vendor/chartjs/chart.umd.min.js"></script>
    <script src="assets/js/index.js" defer></script>
</head>
<body>
<div class="wrapper d-flex">
    <?php include_once __DIR__ . '/includes/nav.php'; ?>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <main id="content">
        <nav class="navbar navbar-top" aria-label="Top navigation">
            <div class="container-fluid px-3 px-lg-4">
                <button class="btn sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle navigation" aria-expanded="false"><i class="bi bi-list"></i></button>
                <div class="page-heading"><span class="eyebrow">INVENTORY & ASSET MANAGEMENT</span><h1>Dashboard</h1></div>
                <div class="ms-auto user-profile-badge" aria-label="Signed in as <?php echo $e($adminName); ?>, <?php echo $e($adminRole); ?>">
                    <span class="user-avatar"><?php echo $e($initial); ?></span>
                    <span class="profile-copy"><strong><?php echo $e($adminName); ?></strong><small><?php echo $e($adminRole); ?></small></span>
                </div>
            </div>
        </nav>
        <div class="dashboard-content">
            <section class="welcome-panel">
                <div><span class="welcome-kicker">YOUR INVENTORY AT A GLANCE</span><h2>Welcome back, <?php echo $e($adminName); ?></h2><p>Here’s what’s happening across your school’s inventory and learning resources.</p></div>
                <div class="welcome-date"><i class="bi bi-calendar3"></i><span><?php echo $e(date('l, F j, Y')); ?></span></div>
            </section>

            <section aria-labelledby="overviewTitle" class="mb-4">
                <div class="section-heading"><div><h2 id="overviewTitle">Inventory overview</h2><p>Current records across your system</p></div></div>
                <div class="row g-3">
                    <?php
                    $metrics = [
                        ['Consumables in stock', $totalConsumablesWithStock, 'bi-box-seam', 'blue', 'Items with available quantity'],
                        ['Out of stock', $totalConsumablesOutStock, 'bi-exclamation-triangle', 'red', 'Consumable items needing attention'],
                        ['Non-consumables', $totalNonConsumables, 'bi-pc-display', 'amber', 'Property records'],
                        ['Consumable releases', $totalConsumableTransactions, 'bi-receipt', 'green', 'Unique release transactions'],
                        ['Non-consumable records', $totalNonConsumableTransactions, 'bi-journal-check', 'teal', 'Recorded transactions'],
                        ['Personnel', $totalEmployees, 'bi-people', 'violet', 'Registered staff'],
                    ];
                    foreach ($metrics as [$label, $value, $icon, $tone, $note]): ?>
                    <div class="col-6 col-xl-4 col-xxl-2"><article class="metric-card">
                        <div class="metric-top"><span><?php echo $e($label); ?></span><span class="metric-icon tone-<?php echo $e($tone); ?>"><i class="bi <?php echo $e($icon); ?>"></i></span></div>
                        <strong class="metric-value"><?php echo number_format((int)$value); ?></strong><span class="metric-note"><?php echo $e($note); ?></span>
                    </article></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section aria-label="Analytics" class="row g-3 mb-4">
                <div class="col-lg-7"><article class="panel h-100"><div class="panel-heading"><div><h2>Supply stock status</h2><p>Item records by current stock level</p></div><span class="panel-icon"><i class="bi bi-bar-chart-line"></i></span></div><div class="chart-frame"><canvas id="suppliesColumnChart" data-with-stock="<?php echo (int)$totalConsumablesWithStock; ?>" data-out-stock="<?php echo (int)$totalConsumablesOutStock; ?>" data-non-consumable="<?php echo (int)$totalNonConsumables; ?>" aria-label="Supply stock status chart"></canvas></div></article></div>
                <div class="col-lg-5"><article class="panel h-100"><div class="panel-heading"><div><h2>Transactions</h2><p>Recorded transaction totals by module</p></div><span class="panel-icon"><i class="bi bi-arrow-left-right"></i></span></div><div class="chart-frame"><canvas id="transactionsColumnChart" data-consumable="<?php echo (int)$totalConsumableTransactions; ?>" data-non-consumable="<?php echo (int)$totalNonConsumableTransactions; ?>" aria-label="Transactions chart"></canvas></div></article></div>
                <div class="col-lg-5"><article class="panel h-100"><div class="panel-heading"><div><h2>Science &amp; math equipment</h2><p>Equipment records by learning area</p></div><span class="panel-icon"><i class="bi bi-flask"></i></span></div><div class="chart-frame chart-frame-short"><canvas id="lrSmeColumnChart" data-science="<?php echo (int)$scienceCount; ?>" data-math="<?php echo (int)$mathCount; ?>" aria-label="Science and math equipment chart"></canvas></div></article></div>
                <div class="col-lg-7"><article class="panel h-100"><div class="panel-heading"><div><h2>Textbooks by subject</h2><p>Total recorded quantity</p></div><span class="panel-icon"><i class="bi bi-book"></i></span></div><div class="chart-frame chart-frame-short"><canvas id="textbooksSubjectChart" data-labels="<?php echo $e(json_encode($subjects, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)); ?>" data-values="<?php echo $e(json_encode($subjectCounts)); ?>" aria-label="Textbooks by subject chart"></canvas></div></article></div>
                <div class="col-lg-7"><article class="panel h-100"><div class="panel-heading"><div><h2>Textbooks by grade</h2><p>Total recorded quantity</p></div><span class="panel-icon"><i class="bi bi-layers"></i></span></div><div class="chart-frame chart-frame-short"><canvas id="textbooksGradeChart" data-labels="<?php echo $e(json_encode($grades, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)); ?>" data-values="<?php echo $e(json_encode($gradeCounts)); ?>" aria-label="Textbooks by grade chart"></canvas></div></article></div>
                <div class="col-lg-5"><article class="panel h-100"><div class="panel-heading"><div><h2>Quick actions</h2><p>Go directly to a system module</p></div><span class="panel-icon"><i class="bi bi-lightning-charge"></i></span></div><div class="quick-actions">
                    <a href="consup.php"><span class="action-icon tone-blue"><i class="bi bi-box-seam"></i></span><span><strong>Consumable supplies</strong><small>Manage stock and releases</small></span><i class="bi bi-arrow-up-right"></i></a>
                    <a href="nonconsup.php"><span class="action-icon tone-amber"><i class="bi bi-pc-display"></i></span><span><strong>Non-consumable supplies</strong><small>Manage property records</small></span><i class="bi bi-arrow-up-right"></i></a>
                    <a href="sciene_math.php"><span class="action-icon tone-green"><i class="bi bi-flask"></i></span><span><strong>Science &amp; math equipment</strong><small>Browse learning resources</small></span><i class="bi bi-arrow-up-right"></i></a>
                    <a href="textbooks.php"><span class="action-icon tone-violet"><i class="bi bi-book"></i></span><span><strong>Textbooks</strong><small>Browse textbook records</small></span><i class="bi bi-arrow-up-right"></i></a>
                    <a href="setting.php"><span class="action-icon tone-teal"><i class="bi bi-gear"></i></span><span><strong>System settings</strong><small>Manage school information</small></span><i class="bi bi-arrow-up-right"></i></a>
                </div></article></div>
                <div class="col-12"><article class="panel"><div class="panel-heading"><div><h2>Recent consumable releases</h2><p>Latest transactions recorded in the inventory</p></div><a class="text-link" href="consup.php">View supplies <i class="bi bi-arrow-right"></i></a></div>
                    <?php if ($recentTransactions): ?><div class="table-responsive"><table class="table activity-table mb-0"><thead><tr><th>Transaction</th><th>Recipient</th><th>Items</th><th>Date</th><th class="text-end">Module</th></tr></thead><tbody>
                    <?php foreach ($recentTransactions as $transaction): ?><tr><td><span class="transaction-code"><?php echo $e($transaction['trans_code']); ?></span></td><td><?php echo $e($transaction['emp_name']); ?></td><td><?php echo number_format((int)$transaction['item_count']); ?> line<?php echo (int)$transaction['item_count'] === 1 ? '' : 's'; ?></td><td><?php echo $e($transaction['created_at'] ? date('M j, Y · g:i A', strtotime($transaction['created_at'])) : '—'); ?></td><td class="text-end"><span class="status-pill"><span></span>Consumable release</span></td></tr><?php endforeach; ?>
                    </tbody></table></div><?php else: ?><div class="empty-state"><span><i class="bi bi-inbox"></i></span><strong>No releases yet</strong><p>Completed consumable transactions will appear here.</p></div><?php endif; ?>
                </article></div>
            </section>
            <footer class="dashboard-footer">Project IAN <span>·</span> Inventory and Assets Navigation</footer>
        </div>
    </main>
</div>
</body>
</html>
