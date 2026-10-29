<?php
require_once __DIR__ . '/includes/session_security.php';
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
    <title>PROJECT - IAN (Inventory and Assets Navigation)</title>
    <link rel="icon" type="image/png" sizes="512x512" href="assets/img/ian-taskbar-hd.png?v=20261029">
    <!-- Bootstrap 5 CSS -->
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <!-- FontAwesome CSS (Required for password eye icons) -->
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="assets/vendor/sweetalert2/sweetalert2.min.css">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="assets/vendor/datatables/dataTables.bootstrap5.min.css">
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
                <!-- Clickable Database Backup Card -->
                <div class="col-md-3">
                    <a href="#" class="text-decoration-none" id="btnBackupDb" data-bs-toggle="modal" data-bs-target="#databaseModal">
                        <div class="card stat-card p-3 border-start border-4 border-info shadow-sm h-100 d-flex flex-column justify-content-between clickable-card">
                            <div class="d-flex align-items-center mb-2">
                                <div class="flex-grow-1">
                                    <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;">System Maintenance</span>
                                    <h5 class="fw-bold text-dark mb-0">Database</h5>
                                </div>
                                <div class="fs-1 text-info opacity-50"><i class="bi bi-database-down"></i></div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3 border-start border-4 border-danger shadow-sm h-100 clickable-card" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#schoolDetailsModal" aria-controls="schoolDetailsModal" aria-label="Edit school details">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;">School Profile</span>
                                <h5 class="fw-bold text-dark mb-0">School Details</h5>
                            </div>
                            <div class="text-danger fs-2 opacity-50"><i class="bi bi-building"></i></div>
                        </div>
                    </div>
                </div>
            </div>
<div class="modal fade" id="databaseModal" tabindex="-1" aria-labelledby="databaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="databaseModalLabel"><i class="bi bi-database me-2 text-info"></i>Database Backup &amp; Import</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Download a copy of the current database or replace it with a SQL backup.</p>
                <div class="d-grid gap-2">
                    <a href="controllers/setting/backup_database.php" class="btn btn-primary" id="downloadDatabaseBackup" download><i class="bi bi-download me-2"></i>Back Up Database</a>
                    <form id="clearDatabaseForm" action="controllers/setting/clear_database_records.php" method="post">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-danger w-100" id="clearDatabaseButton"><i class="bi bi-trash3 me-2"></i>Delete Records</button>
                        <small class="form-text text-muted">Deletes all records except those in admin, school_details, employee, position, and unit_measure. Table structures are kept.</small>
                    </form>
                    <form id="importDatabaseForm" action="controllers/setting/import_database.php" method="post" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="file" class="form-control mb-2" id="databaseSqlFile" name="database_file" accept=".sql,application/sql" required>
                        <button type="submit" class="btn btn-outline-danger w-100" id="importDatabaseButton"><i class="bi bi-upload me-2"></i>Import Database</button>
                        <small class="form-text text-muted">Only .sql files are accepted. Importing replaces the current database contents.</small>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="schoolDetailsModal" tabindex="-1" aria-labelledby="schoolDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="controllers/setting/save_school_details.php" method="post">
                <?php echo csrf_field(); ?>
                <div class="modal-header text-white" style="background-color: #0D3B66;">
                    <h5 class="modal-title fw-bold" id="schoolDetailsModalLabel"><i class="bi bi-building me-2"></i>School Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="schoolRegion" class="form-label fw-semibold">Region</label>
                        <select class="form-select" id="schoolRegion" name="Region" required>
                            <option value="" disabled <?php echo empty($schoolDetails['Region']) ? 'selected' : ''; ?>>Select a region</option>
                            <?php foreach ($philippineRegions as $region): ?>
                                <option value="<?php echo htmlspecialchars($region, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($schoolDetails['Region'] ?? '') === $region ? 'selected' : ''; ?>><?php echo htmlspecialchars($region, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="schoolDivision" class="form-label fw-semibold">School Division</label>
                        <input type="text" class="form-control" id="schoolDivision" name="Division" maxlength="255" placeholder="e.g., Schools Division Of Catbalogan City" value="<?php echo htmlspecialchars($schoolDetails['Division'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="schoolType" class="form-label fw-semibold">School Type</label>
                        <select class="form-select" id="schoolType" name="type" required>
                            <option value="" disabled <?php echo empty($schoolDetails['type']) ? 'selected' : ''; ?>>Select a school type</option>
                            <?php foreach (['Elementary', 'Secondary'] as $schoolTypeOption): ?>
                                <option value="<?php echo $schoolTypeOption; ?>" <?php echo ($schoolDetails['type'] ?? '') === $schoolTypeOption ? 'selected' : ''; ?>><?php echo $schoolTypeOption; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="schoolName" class="form-label fw-semibold">School Name</label>
                        <input type="text" class="form-control" id="schoolName" name="school_name" maxlength="255" value="<?php echo htmlspecialchars($schoolDetails['school_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="schoolAddress" class="form-label fw-semibold">School Address</label>
                        <textarea class="form-control" id="schoolAddress" name="school_address" maxlength="255" rows="2"><?php echo htmlspecialchars($schoolDetails['school_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="headName" class="form-label fw-semibold">School Head Name</label>
                        <select class="form-select" id="headName" name="head_name" required>
                            <option value="" disabled <?php echo empty($schoolDetails['head_name']) ? 'selected' : ''; ?>>Select a school head</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo htmlspecialchars($employee['emp_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($schoolDetails['head_name'] ?? '') === ($employee['emp_name'] ?? null) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['emp_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="headPosition" class="form-label fw-semibold">School Head Position</label>
                        <select class="form-select" id="headPosition" name="head_position" required>
                            <option value="" disabled <?php echo empty($schoolDetails['head_position']) ? 'selected' : ''; ?>>Select a position</option>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo htmlspecialchars($position['position_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($schoolDetails['head_position'] ?? '') === ($position['position_name'] ?? null) ? 'selected' : ''; ?>><?php echo htmlspecialchars($position['position_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="custodianName" class="form-label fw-semibold">Property Custodian Name</label>
                        <select class="form-select" id="custodianName" name="custodian_name" required>
                            <option value="" disabled <?php echo empty($schoolDetails['custodian_name']) ? 'selected' : ''; ?>>Select a property custodian</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo htmlspecialchars($employee['emp_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($schoolDetails['custodian_name'] ?? '') === ($employee['emp_name'] ?? null) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['emp_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="custodianPosition" class="form-label fw-semibold">Property Custodian Position</label>
                        <select class="form-select" id="custodianPosition" name="custodian_position" required>
                            <option value="" disabled <?php echo empty($schoolDetails['custodian_position']) ? 'selected' : ''; ?>>Select a position</option>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo htmlspecialchars($position['position_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($schoolDetails['custodian_position'] ?? '') === ($position['position_name'] ?? null) ? 'selected' : ''; ?>><?php echo htmlspecialchars($position['position_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #0D3B66; border: none;"><i class="bi bi-save me-1"></i>Save Details</button>
                </div>
            </form>
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
                                            <th>Mobile No.</th>
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
                                                    <td><?php echo htmlspecialchars($emp['emp_phone'] ?? ''); ?></td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 edit-employee-btn" data-id="<?php echo htmlspecialchars($emp['id'] ?? $emp['emp_id']); ?>" data-name="<?php echo htmlspecialchars($emp['emp_name'] ?? ''); ?>" data-position="<?php echo htmlspecialchars($emp['emp_position'] ?? ''); ?>" data-emp-phone="<?php echo htmlspecialchars($emp['emp_phone'] ?? ''); ?>" title="Edit Employee">
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
                        <label for="employeePhone" class="form-label fw-semibold">Mobile No.</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold bg-primary text-white border-primary" style="letter-spacing:0.5px;">+63</span>
                            <input type="tel" class="form-control ph-phone-input" id="employeePhone" name="emp_phone_local"
                                required maxlength="10" placeholder="9XXXXXXXXX"
                                pattern="9[0-9]{9}"
                                title="Enter 10-digit Philippine mobile number starting with 9 (e.g. 9171234567)">
                            <input type="hidden" id="employeePhoneFull" name="emp_phone">
                        </div>
                        <div class="form-text text-muted">Enter the 10-digit number starting with <strong>9</strong> (e.g. <span style="color: #dc3545; font-weight: 600;">9171234567</span>)</div>
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
                        <label for="editEmployeePhone" class="form-label fw-semibold">Mobile No.</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold bg-primary text-white border-primary" style="letter-spacing:0.5px;">+63</span>
                            <input type="tel" class="form-control ph-phone-input" id="editEmployeePhone" name="emp_phone_local"
                                required maxlength="10" placeholder="9XXXXXXXXX"
                                pattern="9[0-9]{9}"
                                title="Enter 10-digit Philippine mobile number starting with 9 (e.g. 9171234567)">
                            <input type="hidden" id="editEmployeePhoneFull" name="emp_phone">
                        </div>
                        <div class="form-text text-muted">Enter the 10-digit number starting with <strong>9</strong> (e.g. <span style="color: #dc3545; font-weight: 600;">9171234567</span>)</div>
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
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="assets/vendor/sweetalert2/sweetalert2.min.js"></script>
<!-- jQuery (Required for DataTables) -->
<script src="assets/vendor/jquery/jquery.min.js"></script>
<!-- DataTables JS & Bootstrap 5 Integration -->
<script src="assets/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="assets/vendor/datatables/dataTables.bootstrap5.min.js"></script>
<script>
    window.schoolDetailsSaveStatus = <?php echo json_encode($schoolDetailsStatus); ?>;
</script>
<!-- External Custom Setting JS -->
<script src="assets/js/setting.js?v=11"></script>

</body>
</html>
