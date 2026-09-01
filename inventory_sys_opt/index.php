<?php
session_start();
// Protect the page using your auth check
require_once 'controllers/auth/auth.php';
// Include dashboard controller logic from controllers/index
require_once 'controllers/index/index_controller.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supply Inventory Management System - San Roque Elementary School</title>
        
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom Dashboard Styling -->
    <link href="assets/css/index.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Dashboard JS Logic -->
    <script src="assets/js/index.js" defer></script>
</head>
<body>

<div class="wrapper d-flex">
    <!-- Include Sidebar Navigation Component -->
    <?php include_once 'includes/nav.php'; ?>

    <!-- Page Content Wrapper -->
    <div id="content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-top">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h6 text-secondary fw-bold">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard Overview
                </span>

                <div class="ms-auto d-flex align-items-center">
                    <div class="user-profile-badge">
                        <div class="user-avatar">
                            <?php echo substr($adminName, 0, 1); ?>
                        </div>
                        <div class="text-start">
                            <span class="d-block fw-bold text-dark" style="font-size: 0.85rem; line-height: 1.1;"><?php echo htmlspecialchars($adminName); ?></span>
                            <span class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($adminRole); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Main Body Container -->
        <div class="container-fluid p-4">
            <!-- Welcome Banner -->
            <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4" style="background: linear-gradient(135deg, #0D3B66 0%, #1a528a 100%); color: white; border-radius: 12px; padding: 1.5rem;">
                <div>
                    <h4 class="alert-heading fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($adminName); ?>!</h4>
                    <p class="mb-0 text-white-50" style="font-size: 0.9rem;">Overview of inventory supplies, stock status, transactions, and registered personnel.</p>
                </div>
            </div>

            <!-- Dashboard Metric Stat Cards -->
            <div class="row g-3 mb-4">
                <!-- Consumable Supplies (With Stock) -->
                <div class="col-md-4 col-xl-2">
                    <div class="card bg-white shadow-sm h-100" style="border: 1.5px solid #0d6efd; border-radius: 10px;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Consumable Item</span>
                                    <h3 class="fw-bold my-1 text-dark"><?php echo number_format($totalConsumablesWithStock); ?></h3>
                                    <span class="small text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>With Stock</span>
                                </div>
                                <div class="p-2 rounded-3 d-flex align-items-center justify-content-center" style="border: 1.5px solid rgba(13, 110, 253, 0.3); color: #0d6efd;">
                                    <i class="bi bi-box-seam fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consumable Supplies (Without Stock) -->
                <div class="col-md-4 col-xl-2">
                    <div class="card bg-white shadow-sm h-100" style="border: 1.5px solid #dc3545; border-radius: 10px;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Consumable Item</span>
                                    <h3 class="fw-bold my-1 text-dark"><?php echo number_format($totalConsumablesOutStock); ?></h3>
                                    <span class="small text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Without Stock</span>
                                </div>
                                <div class="p-2 rounded-3 d-flex align-items-center justify-content-center" style="border: 1.5px solid rgba(220, 53, 69, 0.3); color: #dc3545;">
                                    <i class="bi bi-exclamation-triangle fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Non-Consumable Supplies -->
                <div class="col-md-4 col-xl-2">
                    <div class="card bg-white shadow-sm h-100" style="border: 1.5px solid #ffc107; border-radius: 10px;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Non-Consumable Item</span>
                                    <h3 class="fw-bold my-1 text-dark"><?php echo number_format($totalNonConsumables); ?></h3>
                                    <span class="small text-muted">Supplies</span>
                                </div>
                                <div class="p-2 rounded-3 d-flex align-items-center justify-content-center" style="border: 1.5px solid rgba(255, 193, 7, 0.4); color: #d4a713;">
                                    <i class="bi bi-pc-display fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consumable Transactions -->
                <div class="col-md-4 col-xl-2">
                    <div class="card bg-white shadow-sm h-100" style="border: 1.5px solid #198754; border-radius: 10px;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Consumable Trans.</span>
                                    <h3 class="fw-bold my-1 text-dark"><?php echo number_format($totalConsumableTransactions); ?></h3>
                                    <span class="small text-muted">Releases</span>
                                </div>
                                <div class="p-2 rounded-3 d-flex align-items-center justify-content-center" style="border: 1.5px solid rgba(25, 135, 84, 0.3); color: #198754;">
                                    <i class="bi bi-receipt fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Non-Consumable Transactions -->
                <div class="col-md-4 col-xl-2">
                    <div class="card bg-white shadow-sm h-100" style="border: 1.5px solid #0dcaf0; border-radius: 10px;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Non-Consumable Trans.</span>
                                    <h3 class="fw-bold my-1 text-dark"><?php echo number_format($totalNonConsumableTransactions); ?></h3>
                                    <span class="small text-muted">Transactions</span>
                                </div>
                                <div class="p-2 rounded-3 d-flex align-items-center justify-content-center" style="border: 1.5px solid rgba(13, 202, 240, 0.4); color: #0dcaf0;">
                                    <i class="bi bi-journal-check fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Total Personnel Card -->
                <div class="col-md-4 col-xl-2">
                    <div class="card bg-white shadow-sm h-100" style="border: 1.5px solid #6f42c1; border-radius: 10px;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Personnel</span>
                                    <h3 class="fw-bold my-1 text-dark"><?php echo number_format($totalEmployees); ?></h3>
                                    <span class="small text-muted">Active Staff</span>
                                </div>
                                <div class="p-2 rounded-3 d-flex align-items-center justify-content-center" style="border: 1.5px solid rgba(111, 66, 193, 0.3); color: #6f42c1;">
                                    <i class="bi bi-people-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- First Row of Charts (3 items) -->
            <div class="row g-4 mb-4">
                <!-- Column Chart 1: Total Supplies Breakdown -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 border-bottom-0 d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;"><i class="bi bi-bar-chart-fill me-2 text-primary"></i> Total Supplies</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 320px; position: relative;">
                                <canvas id="suppliesColumnChart" 
                                    data-with-stock="<?php echo $totalConsumablesWithStock; ?>" 
                                    data-out-stock="<?php echo $totalConsumablesOutStock; ?>" 
                                    data-non-consumable="<?php echo $totalNonConsumables; ?>">
                                </canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column Chart 2: Total Transactions -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 border-bottom-0 d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;"><i class="bi bi-bar-chart-line-fill me-2 text-success"></i> Total Transactions</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 320px; position: relative;">
                                <canvas id="transactionsColumnChart" 
                                    data-consumable="<?php echo $totalConsumableTransactions; ?>" 
                                    data-non-consumable="<?php echo $totalNonConsumableTransactions; ?>">
                                </canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column Chart 3: Learning Resources (LR SME) -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 border-bottom-0 d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;"><i class="bi bi-book-half me-2 text-warning"></i> Science and Math Equipment Breakdown</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 320px; position: relative;">
                                <canvas id="lrSmeColumnChart" 
                                    data-science="<?php echo $scienceCount; ?>" 
                                    data-math="<?php echo $mathCount; ?>">
                                </canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row of Charts (Textbooks analytics) -->
            <div class="row g-4 mb-4">
                <!-- Textbooks by Subject Chart Card -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 border-bottom-0 d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;"><i class="bi bi-journal-bookmark-fill me-2 text-info"></i> Textbooks by Subject</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 320px; position: relative;">
                                <canvas id="textbooksSubjectChart" 
                                    data-labels='<?php echo json_encode($subjects); ?>' 
                                    data-values='<?php echo json_encode($subjectCounts); ?>'>
                                </canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Textbooks by Grade Level Chart Card -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 border-bottom-0 d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;"><i class="bi bi-layers-fill me-2 text-warning"></i> Textbooks by Grade Level</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 320px; position: relative;">
                                <canvas id="textbooksGradeChart" 
                                    data-labels='<?php echo json_encode($grades); ?>' 
                                    data-values='<?php echo json_encode($gradeCounts); ?>'>
                                </canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
