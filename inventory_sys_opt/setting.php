<?php
session_start();
// Protect the page using your auth check
require_once 'controllers/auth/auth.php';
// Include settings controller logic from controllers/setting
require_once 'controllers/setting/setting_controller.php';
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
    <!-- FontAwesome CSS (Required for password eye icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom Dashboard Styling -->
    <link href="assets/css/index.css" rel="stylesheet">
    <style>
        .clickable-card {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .clickable-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }
        .custom-tabs .nav-link {
            border-radius: 8px 8px 0 0;
            padding: 10px 20px;
        }
        .custom-tabs .nav-link.active {
            background-color: #ffffff;
            border-bottom-color: transparent;
        }
    </style>
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
                    <i class="bi bi-speedometer2 me-2"></i> Settings Overview
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
                    <h4 class="alert-heading fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($adminName); ?></h4>
                    <p class="mb-0 text-white-50" style="font-size: 0.95rem;">Manage Staff, Units of Measure, and Positions via interactive DataTables below.</p>
                </div>
            </div>
            <!-- Quick Metrics Row -->
            <div class="row g-4 mb-4">
                <!-- Clickable Staff Management Card -->
                <div class="col-md-3">
                    <div class="card stat-card p-3 border-start border-4 border-primary shadow-sm h-100 d-flex flex-column justify-content-between clickable-card" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-grow-1">
                                <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;">Personnel Control</span>
                                <h5 class="fw-bold text-dark mb-0">Staff Management</h5>
                            </div>
                            <div class="fs-1 text-primary opacity-50"><i class="bi bi-person-badge"></i></div>
                        </div>
                    </div>
                </div>
                <!-- Clickable Unit of Measure Card -->
                <div class="col-md-3">
                    <div class="card stat-card p-3 border-start border-4 border-warning shadow-sm h-100 d-flex flex-column justify-content-between clickable-card" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-grow-1">
                                <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;">Inventory Setup</span>
                                <h5 class="fw-bold text-dark mb-0">Unit of Measure</h5>
                            </div>
                            <div class="fs-1 text-warning opacity-50"><i class="bi bi-tags"></i></div>
                        </div>
                    </div>
                </div>
                <!-- Clickable Position Management Card -->
                <div class="col-md-3">
                    <div class="card stat-card p-3 border-start border-4 border-success shadow-sm h-100 d-flex flex-column justify-content-between clickable-card" data-bs-toggle="modal" data-bs-target="#addPositionModal">
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-grow-1">
                                <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;">Organizational Setup</span>
                                <h5 class="fw-bold text-dark mb-0">Position Management</h5>
                            </div>
                            <div class="fs-1 text-success opacity-50"><i class="bi bi-briefcase"></i></div>
                        </div>
                    </div>
                </div>
                <!-- Clickable Add Admin Card -->
                <div class="col-md-3">
                    <div class="card stat-card p-3 border-start border-4 border-dark shadow-sm h-100 d-flex flex-column justify-content-between clickable-card" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-grow-1">
                                <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;">Access Control</span>
                                <h5 class="fw-bold text-dark mb-0">Admin Management</h5>
                            </div>
                            <div class="fs-1 text-dark opacity-50"><i class="bi bi-shield-lock-fill"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Nav Tabs for DataTables -->
            <ul class="nav nav-tabs custom-tabs mb-3" id="settingsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-dark" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff-panel" type="button" role="tab" aria-controls="staff-panel" aria-selected="true">
                        <i class="bi bi-people-fill me-2 text-primary"></i> Staff Management
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-dark" id="category-tab" data-bs-toggle="tab" data-bs-target="#category-panel" type="button" role="tab" aria-controls="category-panel" aria-selected="false">
                        <i class="bi bi-tags-fill me-2 text-warning"></i> Unit of Measure
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-dark" id="position-tab" data-bs-toggle="tab" data-bs-target="#position-panel" type="button" role="tab" aria-controls="position-panel" aria-selected="false">
                        <i class="bi bi-briefcase-fill me-2 text-success"></i> Position Management
                    </button>
                </li>
            </ul>
            <!-- Tab Content containing 3 DataTables -->
            <div class="tab-content" id="settingsTabContent">
                <!-- 1. Staff Management DataTable Panel -->
                <div class="tab-pane fade show active" id="staff-panel" role="tabpanel" aria-labelledby="staff-tab">
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-badge me-2 text-primary"></i> Staff / Employee Directory</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="staffTable" class="table table-striped table-hover align-middle w-100">
                                    <thead class="table-light text-uppercase text-muted" style="font-size: 0.8rem;">
                                        <tr>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Email</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($employees)): ?>
                                            <?php foreach ($employees as $emp): ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($emp['emp_id'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($emp['emp_name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($emp['emp_position'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($emp['emp_email'] ?? ''); ?></td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 edit-employee-btn" data-id="<?php echo htmlspecialchars($emp['id'] ?? $emp['emp_id']); ?>" data-name="<?php echo htmlspecialchars($emp['emp_name'] ?? ''); ?>" data-position="<?php echo htmlspecialchars($emp['emp_position'] ?? ''); ?>" data-email="<?php echo htmlspecialchars($emp['emp_email'] ?? ''); ?>" title="Edit Employee">
                                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-employee-btn" data-id="<?php echo htmlspecialchars($emp['id'] ?? $emp['emp_id']); ?>" title="Delete Employee">
                                                            <i class="bi bi-trash me-1"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 2. Unit of Measure DataTable Panel -->
                <div class="tab-pane fade" id="category-panel" role="tabpanel" aria-labelledby="category-tab">
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-tags me-2 text-warning"></i> Unit of Measure List</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="categoryTable" class="table table-striped table-hover align-middle w-100">
                                    <thead class="table-light text-uppercase text-muted" style="font-size: 0.8rem;">
                                        <tr>
                                            <th>#</th>
                                            <th>Unit Name</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($categories)): ?>
                                            <?php $index = 1; foreach ($categories as $cat): ?>
                                                <tr>
                                                    <td><?php echo $index++; ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($cat['unit_name'] ?? $cat['category_name'] ?? ''); ?></td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 edit-category-btn" data-id="<?php echo htmlspecialchars($cat['id'] ?? $cat['unit_name'] ?? $cat['category_name']); ?>" data-name="<?php echo htmlspecialchars($cat['unit_name'] ?? $cat['category_name'] ?? ''); ?>" title="Edit Unit of Measure">
                                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-category-btn" data-id="<?php echo htmlspecialchars($cat['id'] ?? $cat['unit_name'] ?? $cat['category_name']); ?>" title="Delete Unit of Measure">
                                                            <i class="bi bi-trash me-1"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 3. Position Management DataTable Panel -->
                <div class="tab-pane fade" id="position-panel" role="tabpanel" aria-labelledby="position-tab">
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-briefcase me-2 text-success"></i> Organizational Position List</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="positionTable" class="table table-striped table-hover align-middle w-100">
                                    <thead class="table-light text-uppercase text-muted" style="font-size: 0.8rem;">
                                        <tr>
                                            <th>#</th>
                                            <th>Position Name</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($positions)): ?>
                                            <?php $index = 1; foreach ($positions as $pos): ?>
                                                <tr>
                                                    <td><?php echo $index++; ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($pos['position_name'] ?? ''); ?></td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 edit-position-btn" data-id="<?php echo htmlspecialchars($pos['id'] ?? $pos['position_name']); ?>" data-name="<?php echo htmlspecialchars($pos['position_name'] ?? ''); ?>" title="Edit Position">
                                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-position-btn" data-id="<?php echo htmlspecialchars($pos['id'] ?? $pos['position_name']); ?>" title="Delete Position">
                                                            <i class="bi bi-trash me-1"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>      
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="controllers/Employee/process_employee.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="addEmployeeModalLabel">
                        <i class="bi bi-person-plus-fill me-2"></i> Add Employee
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="employeeId" class="form-label fw-semibold">Employee ID No.</label>
                        <input type="text" class="form-control" id="employeeId" name="employee_id" required placeholder="Enter Employee ID">
                    </div>
                    <div class="mb-3">
                        <label for="employeeName" class="form-label fw-semibold">Name (Firstname, Middle Initial and Lastname)</label>
                        <input type="text" class="form-control" id="employeeName" name="name" required placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label for="employeePosition" class="form-label fw-semibold">Position</label>
                        <select class="form-select" id="employeePosition" name="position" required>
                            <option value="" disabled selected>Select Position</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?php echo htmlspecialchars($pos['position_name']); ?>">
                                    <?php echo htmlspecialchars($pos['position_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="employeeEmail" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="employeeEmail" name="email" required placeholder="Enter email address">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold shadow-sm" style="background-color: #0D3B66; border: none; font-size: 1rem;">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Position Modal -->
<div class="modal fade" id="addPositionModal" tabindex="-1" aria-labelledby="addPositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="controllers/position/process_position.php" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="addPositionModalLabel">
                        <i class="bi bi-briefcase-fill me-2"></i> Add Position Field
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="positionName" class="form-label fw-semibold">Position</label>
                        <input type="text" class="form-control" id="positionName" name="position" required placeholder="Enter position name">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow-sm" style="font-size: 1rem;">Save Position</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Unit of Measure Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="controllers/category/process_category.php" method="POST">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold" id="addCategoryModalLabel">
                        <i class="bi bi-tags-fill me-2"></i> Add Unit of Measure
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label fw-semibold">Unit Name</label>
                        <input type="text" class="form-control" id="categoryName" name="unit_name" required placeholder="Enter unit name (e.g. Ream, Box, Piece)">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-lg px-5 fw-bold shadow-sm text-dark" style="font-size: 1rem;">Save Unit of Measure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="controllers/Employee/process_edit_employee.php" method="POST">
                <input type="hidden" id="editEmpDbId" name="id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="editEmployeeModalLabel">
                        <i class="bi bi-pencil-square me-2"></i> Edit Employee Information
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="editEmployeeId" class="form-label fw-semibold">Employee ID No.</label>
                        <input type="text" class="form-control" id="editEmployeeId" name="employee_id" required placeholder="Enter Employee ID">
                    </div>
                    <div class="mb-3">
                        <label for="editEmployeeName" class="form-label fw-semibold">Name (Firstname, Middle Initial and Lastname)</label>
                        <input type="text" class="form-control" id="editEmployeeName" name="name" required placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label for="editEmployeePosition" class="form-label fw-semibold">Position</label>
                        <select class="form-select" id="editEmployeePosition" name="position" required>
                            <option value="" disabled>Select Position</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?php echo htmlspecialchars($pos['position_name']); ?>">
                                    <?php echo htmlspecialchars($pos['position_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editEmployeeEmail" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="editEmployeeEmail" name="email" required placeholder="Enter email address">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold shadow-sm" style="background-color: #0D3B66; border: none; font-size: 1rem;">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Unit of Measure Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="controllers/category/process_edit_category.php" method="POST">
                <input type="hidden" id="editCategoryId" name="id">
                <input type="hidden" id="editCategoryOldName" name="old_category_name">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold" id="editCategoryModalLabel">
                        <i class="bi bi-pencil-square me-2"></i> Edit Unit of Measure
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label fw-semibold">Unit Name</label>
                        <input type="text" class="form-control" id="editCategoryName" name="unit_name" required placeholder="Enter unit name">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-lg px-5 fw-bold shadow-sm text-dark" style="font-size: 1rem;">Update Unit of Measure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Position Modal -->
<div class="modal fade" id="editPositionModal" tabindex="-1" aria-labelledby="editPositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="controllers/position/process_edit_position.php" method="POST">
                <input type="hidden" id="editPositionId" name="id">
                <input type="hidden" id="editPositionOldName" name="old_position_name">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="editPositionModalLabel">
                        <i class="bi bi-pencil-square me-2"></i> Edit Position
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="editPositionInput" class="form-label fw-semibold">Position Name</label>
                        <input type="text" class="form-control" id="editPositionInput" name="position" required placeholder="Enter position name">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow-sm" style="font-size: 1rem;">Update Position</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="updateAdminForm" action="controllers/admin/process_admin.php" method="POST">
                <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($adminData['id'] ?? 1); ?>">
                <div class="modal-header text-white" style="background-color: #0D3B66;">
                    <h5 class="modal-title fw-bold" id="addAdminModalLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i> Update Admin Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="adminFullName" class="form-label fw-semibold">Full Name (Admin Name)</label>
                        <input type="text" class="form-control" id="adminFullName" name="admin_name" value="<?php echo htmlspecialchars($adminData['admin_name'] ?? ''); ?>" required placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label for="adminUsername" class="form-label fw-semibold">Username</label>
                        <input type="text" class="form-control" id="adminUsername" name="username" value="<?php echo htmlspecialchars($adminData['username'] ?? ''); ?>" required placeholder="Enter login username">
                    </div>
                    <!-- Old Password -->
                    <div class="mb-3">
                        <label for="oldPassword" class="form-label">Old Password (Required)</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="oldPassword" name="old_password" placeholder="Enter current password" required>
                            <span class="input-group-text" style="cursor: pointer;">
                                <i class="fas fa-eye toggle-password" data-target="oldPassword"></i>
                            </span>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="adminPassword" class="form-label">New Password (Optional)</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="adminPassword" name="new_password" placeholder="Enter new password if changing">
                            <span class="input-group-text" style="cursor: pointer;">
                                <i class="fas fa-eye toggle-password" data-target="adminPassword"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Re-enter new password">
                            <span class="input-group-text" style="cursor: pointer;">
                                <i class="fas fa-eye toggle-password" data-target="confirmPassword"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold shadow-sm" style="background-color: #0D3B66; border: none; font-size: 1rem;">Update Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- jQuery (Required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS & Bootstrap 5 Integration -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- External Custom Setting JS -->
<script src="assets/js/setting.js"></script>

</body>
</html>