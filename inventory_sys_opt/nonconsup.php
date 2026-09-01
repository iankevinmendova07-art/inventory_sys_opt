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
    <title>Non-Consumable Inventory - San Roque Elementary School</title>
        
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
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom Non-Consumable JS -->
    <script src="assets/js/nonconsup.js" defer></script>
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
                    <i class="bi bi-speedometer2 me-2"></i> Non-Consumable Supplies Overview
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
                    <p class="mb-0 text-white-50" style="font-size: 0.95rem;">Here is the summary of your non-consumable supplies inventory for San Roque Elementary School.</p>
                </div>
            </div>
            <!-- Quick Metrics Row (Inline Buttons) -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card stat-card px-4 py-3 shadow-sm" style="cursor: pointer; border-radius: 12px; border: 2px solid #0d6efd; min-height: 88px;" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">INVENTORY CONTROL</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Add and Release Item</h5>
                            </div>
                            <div class="fs-2 text-primary opacity-75 ms-3"><i class="bi bi-box-seam"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- RPCPPE Card configured to open PDF view in a new tab -->
                    <div class="card stat-card px-4 py-3 shadow-sm" style="cursor: pointer; border-radius: 12px; border: 2px solid #198754; min-height: 88px;" onclick="window.open('controllers/supplies/nonconsumable/generate_rpcppe_pdf.php', '_blank');">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">ANALYTICS</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">RPCPPE Report</h5>
                            </div>
                            <div class="fs-2 text-success opacity-75 ms-3"><i class="bi bi-file-earmark-text"></i></div>
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
                                    <th>Property Number</th>
                                    <th>Description</th>
                                    <th>Unit</th>
                                    <th>Category</th>
                                    <th>Unit Cost</th>
                                    <th>Quantity</th>
                                    <th>Date Added</th>
                                    <th>Recipient</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php include 'controllers/supplies/nonconsumable/display_supply.php'; ?>
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
                <h5 class="modal-title fw-bold" id="addItemModalLabel">Release Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    <div class="mb-3">
                        <label for="propertyNumber" class="form-label fw-semibold">Property Number</label>
                        <input type="text" class="form-control" id="propertyNumber" name="property_number" placeholder="Enter property number" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" name="description" placeholder="Enter item description" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="unitOfMeasure" class="form-label fw-semibold">Unit of Measure</label>
                        <select class="form-select" id="unitOfMeasure" name="unit_of_measure" required>
                            <option value="" selected disabled>Select unit of measure</option>
                            <?php include 'includes/partials/item_unit_options.php'; ?>
                        </select>
                    </div>
                    <!-- Add Form Category Select -->
                    <div class="mb-3">
                        <label for="category" class="form-label">Category / Item Type</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="" disabled selected>Select category...</option>
                            <option value="BUILDINGS">BUILDINGS</option>
                            <option value="SCHOOL BUILDINGS">SCHOOL BUILDINGS</option>
                            <option value="PARK, PLAZAS AND MONUMENTS">PARK, PLAZAS AND MONUMENTS</option>
                            <option value="POWER SUPPLY SYSTEMS">POWER SUPPLY SYSTEMS</option>
                            <option value="COMPUTER SOFTWARE">COMPUTER SOFTWARE</option>
                            <option value="OFFICE EQUIPMENT">OFFICE EQUIPMENT</option>
                            <option value="FURNITURE & FIXTURES">FURNITURE & FIXTURES</option>
                            <option value="COMM. EQUIPMENT">COMM. EQUIPMENT</option>
                            <option value="ICT EQUIPMENT">ICT EQUIPMENT</option>
                            <option value="MOTOR VEHICLES">MOTOR VEHICLES</option>
                            <option value="MEDICAL EQUIPMENT">MEDICAL EQUIPMENT</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="unitCost" class="form-label fw-semibold">Unit Cost</label>
                            <input type="text" inputmode="decimal" class="form-control money-input" id="unitCost" name="unit_cost" placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="totalCost" class="form-label fw-semibold">Total Cost</label>
                            <input type="text" inputmode="decimal" class="form-control money-input" id="totalCost" name="total_cost" placeholder="0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="qtyPropertyCard" class="form-label fw-semibold">Qty per Property Card</label>
                            <input type="number" class="form-control" id="qtyPropertyCard" name="qty_property_card" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="qtyPhysicalCount" class="form-label fw-semibold">Qty per Physical Count</label>
                            <input type="number" class="form-control" id="qtyPhysicalCount" name="qty_physical_count" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="shortageOverageQty" class="form-label fw-semibold">Shortage/Overage Qty</label>
                            <input type="number" class="form-control" id="shortageOverageQty" name="shortage_overage_qty" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="shortageOverageValue" class="form-label fw-semibold">Shortage/Overage Value</label>
                            <input type="number" step="0.01" class="form-control" id="shortageOverageValue" name="shortage_overage_value" value="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="remarks" class="form-label fw-semibold">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Enter remarks"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="cartRecipient" class="form-label">Recipient</label>
                        <select class="form-control" id="cartRecipient" name="recipient" required>
                            <option value="">Select Employee</option>
                            <?php include 'includes/employee_dropdown.php'; ?>
                        </select>
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
                <h5 class="modal-title fw-bold" id="editItemModalLabel">Edit Non-Consumable Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    <input type="hidden" id="editId" name="id">
                    <div class="mb-3">
                        <label for="editPropertyNumber" class="form-label fw-semibold">Property Number</label>
                        <input type="text" class="form-control" id="editPropertyNumber" name="property_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editUnitOfMeasure" class="form-label fw-semibold">Unit of Measure</label>
                        <select class="form-select" id="editUnitOfMeasure" name="unit_of_measure" required>
                            <option value="" disabled>Select unit of measure</option>
                            <?php include 'includes/partials/item_unit_options.php'; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editCategory" class="form-label fw-semibold">Category / Item Type</label>
                        <select class="form-select" id="editCategory" name="category" required>
                            <option value="" disabled selected>Select category...</option>
                            <option value="BUILDINGS">BUILDINGS</option>
                            <option value="SCHOOL BUILDINGS">SCHOOL BUILDINGS</option>
                            <option value="PARK, PLAZAS AND MONUMENTS">PARK, PLAZAS AND MONUMENTS</option>
                            <option value="POWER SUPPLY SYSTEMS">POWER SUPPLY SYSTEMS</option>
                            <option value="COMPUTER SOFTWARE">COMPUTER SOFTWARE</option>
                            <option value="OFFICE EQUIPMENT">OFFICE EQUIPMENT</option>
                            <option value="FURNITURE & FIXTURES">FURNITURE & FIXTURES</option>
                            <option value="COMM. EQUIPMENT">COMM. EQUIPMENT</option>
                            <option value="ICT EQUIPMENT">ICT EQUIPMENT</option>
                            <option value="MOTOR VEHICLES">MOTOR VEHICLES</option>
                            <option value="MEDICAL EQUIPMENT">MEDICAL EQUIPMENT</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editUnitCost" class="form-label fw-semibold">Unit Cost</label>
                            <input type="text" inputmode="decimal" class="form-control money-input" id="editUnitCost" name="unit_cost">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editTotalCost" class="form-label fw-semibold">Total Cost</label>
                            <input type="text" inputmode="decimal" class="form-control money-input" id="editTotalCost" name="total_cost">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editQtyPropertyCard" class="form-label fw-semibold">Qty per Property Card</label>
                            <input type="number" class="form-control" id="editQtyPropertyCard" name="qty_property_card" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editQtyPhysicalCount" class="form-label fw-semibold">Qty per Physical Count</label>
                            <input type="number" class="form-control" id="editQtyPhysicalCount" name="qty_physical_count">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editShortageOverageQty" class="form-label fw-semibold">Shortage/Overage Qty</label>
                            <input type="number" class="form-control" id="editShortageOverageQty" name="shortage_overage_qty">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editShortageOverageValue" class="form-label fw-semibold">Shortage/Overage Value</label>
                            <input type="number" step="0.01" class="form-control" id="editShortageOverageValue" name="shortage_overage_value">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editRemarks" class="form-label fw-semibold">Remarks</label>
                        <textarea class="form-control" id="editRemarks" name="remarks" rows="2"></textarea>
                    </div>
                    <!-- Inside Edit Item Modal -->
                    <div class="mb-3">
                        <label for="editRecipient" class="form-label">Recipient</label>
                        <select class="form-control" id="editRecipient" name="recipient" required>
                            <option value="">Select Employee</option>
                            <?php include 'includes/employee_dropdown.php'; ?>
                        </select>
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
</body>
</html>