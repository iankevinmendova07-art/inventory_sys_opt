<?php
session_start();
// Protect the page using your auth check
require_once 'controllers/auth/auth.php';

// Get admin name for display
$adminName = isset($_SESSION['admin_name']) ? strtoupper($_SESSION['admin_name']) : 'ADMIN';
$adminRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator';
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
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom Dashboard Styling -->
    <link href="assets/css/consup.css" rel="stylesheet">
    <script src="assets/js/cart.js" defer></script>
    <script src="assets/js/consup.js" defer></script>
    <!-- jQuery (Must be loaded before scripts that use $)[cite: 1] -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
                    <i class="bi bi-speedometer2 me-2"></i> Consumable Supplies Overview
                </span>

                <div class="ms-auto d-flex align-items-center gap-3">
                    <div class="user-profile-badge">
                        <div class="user-avatar">
                            <?php echo substr($adminName, 0, 1); ?>
                        </div>
                        <div class="text-start">
                            <span class="d-block fw-bold text-dark" style="font-size: 0.85rem; line-height: 1.1;"><?php echo htmlspecialchars($adminName); ?></span>
                            <span class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($adminRole); ?></span>
                        </div>
                    </div>
                    <button type="button" class="cart-icon-btn" id="cartToggleBtn" title="View cart">
                        <i class="bi bi-cart3"></i>
                        <span class="cart-badge d-none" id="cartBadge">0</span>
                    </button>
                </div>
            </div>
        </nav>
        <!-- Main Body Container -->
        <div class="container-fluid p-4">
            <!-- Welcome Banner -->
            <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4" style="background: linear-gradient(135deg, #0D3B66 0%, #1a528a 100%); color: white; border-radius: 12px; padding: 1.5rem;">
                <div>
                    <h4 class="alert-heading fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($adminName); ?>!</h4>
                    <p class="mb-0 text-white-50" style="font-size: 0.95rem;">Here is the summary of your consumable supplies inventory for San Roque Elementary School.</p>
                </div>
            </div>
            <!-- Quick Metrics Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card px-4 py-3 shadow-sm" style="cursor: pointer; border-radius: 12px; border: 2px solid #0d6efd; min-height: 88px;" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">INVENTORY CONTROL</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Add Item</h5>
                            </div>
                            <div class="fs-2 text-primary opacity-75 ms-3"><i class="bi bi-box-seam"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card px-4 py-3 shadow-sm" style="cursor: pointer; border-radius: 12px; border: 2px solid #6f42c1; min-height: 88px;" data-bs-toggle="modal" data-bs-target="#updateQtyModal">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">STOCK MANAGEMENT</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Update Quantity</h5>
                            </div>
                            <div class="fs-2 opacity-75 ms-3" style="color: #6f42c1;"><i class="bi bi-boxes"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card px-4 py-3 shadow-sm" style="cursor: pointer; border-radius: 12px; border: 2px solid #198754; min-height: 88px;" data-bs-toggle="modal" data-bs-target="#requestReportModal">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">ANALYTICS</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Requisition and Issue Slip</h5>
                            </div>
                            <div class="fs-2 text-success opacity-75 ms-3"><i class="bi bi-file-earmark-text"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card px-4 py-3 shadow-sm" style="cursor: pointer; border-radius: 12px; border: 2px solid #0dcaf0; min-height: 88px;" data-bs-toggle="modal" data-bs-target="#stockCardModal">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">INVENTORY RECORDS</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Stock Card</h5>
                            </div>
                            <div class="fs-2 text-info opacity-75 ms-3"><i class="bi bi-card-list"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card px-4 py-3 shadow-sm" style="cursor: pointer; border-radius: 12px; border: 2px solid #fd7e14; min-height: 88px;" data-bs-toggle="modal" data-bs-target="#rsmiModal">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">ISSUANCE REPORT</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">RSMI</h5>
                            </div>
                            <div class="fs-2 opacity-75 ms-3" style="color: #fd7e14;"><i class="bi bi-file-earmark-ruled"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Card / DataTable -->
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="suppliesTable" class="table table-hover align-middle w-100">
                            <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Unit</th>
                                    <th>Reference</th>
                                    <th>Quantity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Hydrated dynamically via Server-Side DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="addItemModalLabel">Add Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                        <div class="mb-3">
                            <label for="itemCode" class="form-label fw-semibold">Item Code</label>
                            <input type="text" class="form-control" id="itemCode" name="itemCode" placeholder="Enter item code" required>
                        </div>
                        <div class="mb-3">
                            <label for="itemName" class="form-label fw-semibold">Item Name</label>
                            <input type="text" class="form-control" id="itemName" name="itemName" placeholder="Enter item name" required>
                        </div>
                        <div class="mb-3">
                            <label for="unit" class="form-label fw-semibold">Unit</label>
                            <select class="form-select" id="unit" name="unit" required>
                                <option value="" selected disabled>Select unit of measure</option>
                                <?php include 'includes/partials/item_unit_options.php'; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="qty" class="form-label fw-semibold">Quantity</label>
                            <input type="number" class="form-control" id="qty" name="qty" placeholder="Enter quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="reference" class="form-label fw-semibold">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference" placeholder="Enter reference" maxlength="100" required>
                        </div>
                    </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveSupplyBtn" class="btn btn-primary px-4" style="background-color: #0D3B66; border: none;">Add Item</button>
            </div>
        </div>
    </div>
</div>
<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="editItemModalLabel">Edit Supply</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    <input type="hidden" id="editId" name="id">
                    <div class="mb-3">
                        <label for="editItemCode" class="form-label fw-semibold">Item Code</label>
                        <input type="text" class="form-control" id="editItemCode" name="itemCode" required>
                    </div>
                    <div class="mb-3">
                        <label for="editItemName" class="form-label fw-semibold">Item Name</label>
                        <input type="text" class="form-control" id="editItemName" name="itemName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUnit" class="form-label fw-semibold">Unit</label>
                        <select class="form-select" id="editUnit" name="unit" required>
                            <option value="" disabled>Select unit of measure</option>
                            <?php include 'includes/partials/item_unit_options.php'; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editReference" class="form-label fw-semibold">Reference</label>
                        <input type="text" class="form-control" id="editReference" name="reference" maxlength="100" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="updateSupplyBtn" class="btn btn-primary px-4" style="background-color: #0D3B66; border: none;">Save Changes</button>
            </div>
        </div>
    </div>
</div>
<!-- Update Quantity Modal -->
<div class="modal fade" id="updateQtyModal" tabindex="-1" aria-labelledby="updateQtyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="updateQtyModalLabel"><i class="bi bi-boxes me-2"></i>Update Quantity / Stock Management</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Enter the new total quantity and its reference to record an incoming stock transaction.</p>
                <div class="table-responsive">
                    <table id="quantityTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Unit</th>
                                <th>Current Quantity</th>
                                <th class="text-center">New Quantity / Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loaded dynamically via DataTables AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Add to Cart Modal -->
<div class="modal fade" id="addToCartModal" tabindex="-1" aria-labelledby="addToCartModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="addToCartModalLabel">Add Item to Cart</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                  <form id="cartForm">
                        <input type="hidden" id="cartSupplyId" name="supplyId">
                        <input type="hidden" id="cartCategory" name="category" value="Consumable Supply">
                        <div class="mb-3">
                            <label for="cartItemCode" class="form-label fw-semibold">Item Code</label>
                            <input type="text" class="form-control" id="cartItemCode" name="itemCode" placeholder="Item code" required readonly>
                        </div>
                        <div class="mb-3">
                            <label for="cartItemName" class="form-label fw-semibold">Item Name</label>
                            <input type="text" class="form-control" id="cartItemName" name="itemName" placeholder="Enter item name" required readonly>
                        </div>
                        <div class="mb-3">
                            <label for="cartUnit" class="form-label fw-semibold">Unit</label>
                             <input type="text" class="form-control" id="cartUnit" name="unit" placeholder="Enter unit of measure" required readonly>
                        </div>
                        <div class="mb-3">
                            <label for="cartQty" class="form-label fw-semibold">Quantity</label>
                            <input type="number" class="form-control" id="cartQty" name="qty" placeholder="Enter quantity" required readonly>
                        </div>
                    </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" id="AddToCartBtn" class="btn btn-primary px-4" style="background-color: #0D3B66; border: none;">Add to Cart</button>
            </div>
        </div>
    </div>
</div>
<!-- View Cart Modal -->
<div class="modal fade" id="viewCartModal" tabindex="-1" aria-labelledby="viewCartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="viewCartModalLabel"><i class="bi bi-cart3 me-2"></i>Release Cart</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <h6 class="fw-bold text-secondary mb-3">Cart Items</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>Unit</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="cartItemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-secondary mb-0">Recipients</h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" id="selectAllRecipientsBtn">Select All</button>
                                <button type="button" class="btn btn-outline-secondary" id="clearRecipientsBtn">Clear</button>
                            </div>
                        </div>
                        <p class="text-muted small">Select one or more employees. Each recipient will receive all items in the cart.</p>
                        <div id="recipientList" class="recipient-list border rounded p-3"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" id="clearCartBtn">Clear Cart</button>
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" id="releaseCartBtn" class="btn btn-primary px-4" style="background-color: #0D3B66; border: none;">Release Items</button>
            </div>
        </div>
    </div>
</div>
<!-- Stock Card Modal -->
<div class="modal fade" id="stockCardModal" tabindex="-1" aria-labelledby="stockCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="stockCardModalLabel"><i class="bi bi-card-list me-2"></i>Stock Card Items</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Select an item below to view/print its individual stock card, or print all stock cards at once.</span>
                    <button type="button" class="btn btn-sm btn-primary px-3 btn-print-all-stock-cards" style="background-color: #0D3B66; border-color: #0D3B66;">
                        <i class="bi bi-printer me-1"></i> Print All Stock Cards
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="stockCardItemsTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                            <tr>
                                <th>Item Name</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary px-4 btn-print-all-stock-cards" style="background-color: #0D3B66; border-color: #0D3B66;">
                    <i class="bi bi-printer me-1"></i> Print All Stock Cards
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Request Report Modal -->
<div class="modal fade" id="requestReportModal" tabindex="-1" aria-labelledby="requestReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="requestReportModalLabel"><i class="bi bi-file-earmark-text me-2"></i>Transaction Report Logs</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="transactionReportTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                            <tr>
                                <th>Trans. Code</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data populated dynamically via AJAX/PHP -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- RSMI Modal -->
<div class="modal fade" id="rsmiModal" tabindex="-1" aria-labelledby="rsmiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="rsmiModalLabel"><i class="bi bi-file-earmark-ruled me-2"></i>Report of Supplies and Materials Issued (RSMI - Appendix 64)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <p class="text-muted small mb-0">Issued supplies record compiled from <strong>Transaction Logs</strong> formatted according to standard DepEd / COA Appendix 64.</p>
                    </div>
                    
                    <!-- Month and Year Dropdown Filter (Removed auto-submit) -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <select name="rsmi_month_year" id="rsmiMonthYear" class="form-select form-select-sm">
                                <option value="" selected disabled>Select Month & Year</option>
                                <?php
                                if (!isset($pdo)) {
                                    @include_once __DIR__ . '/config/db.php';
                                }

                                if (isset($pdo)) {
                                    try {
                                      $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym FROM transaction_log WHERE created_at IS NOT NULL AND TRIM(created_at) != '' GROUP BY ym ORDER BY ym DESC");
                                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        if ($rows) {
                                            foreach ($rows as $row) {
                                                if (!empty($row['ym'])) {
                                                    $ym = $row['ym'];
                                                    $timestamp = strtotime($ym . '-01');
                                                    $display = date('F Y', $timestamp);
                                                    
                                                    // Keeps selection if passed via query string
                                                    $selected = (isset($_GET['rsmi_month_year']) && $_GET['rsmi_month_year'] === $ym) ? 'selected' : '';
                                                    echo "<option value=\"{$ym}\" {$selected}>{$display}</option>";
                                                }
                                            }
                                        } else {
                                            echo "<option value=\"\" disabled>No transaction records found</option>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<option value=\"\" disabled>Query Error: " . htmlspecialchars($e->getMessage()) . "</option>";
                                    }
                                } else {
                                    echo "<option value=\"\" disabled>Database Connection Failed</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <button type="button" class="btn btn-primary fw-bold px-3 shadow-sm btn-sm" style="background-color: #0D3B66; border: none;" onclick="printRsmiReport()">
                            <i class="bi bi-printer me-1"></i> Print / Generate RSMI
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
