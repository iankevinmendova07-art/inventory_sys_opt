<?php
session_start();
// Protect the page using your auth check
require_once 'controllers/auth/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inventory_sys/config/db.php';

// Get admin name for display
$adminName = isset($_SESSION['admin_name']) ? strtoupper($_SESSION['admin_name']) : 'ADMIN';
$adminRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator';

// Fetch teachers from employee table
$teachers = [];
try {
    $teacherStmt = $pdo->query("SELECT * FROM employee WHERE emp_position LIKE '%teacher%' ORDER BY emp_name ASC");
    $teachers = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error gracefully if needed
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Textbook Inventory - San Roque Elementary School</title>
        
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom Dashboard Styling -->
    <link href="assets/css/textbooks.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Shared logout handler -->
    <script src="assets/js/index.js" defer></script>
    <!-- Custom textbooks JS -->
    <script src="assets/js/textbooks.js" defer></script>
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
                    <i class="bi bi-speedometer2 me-2"></i> Textbook Overview
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
                    <div class="card stat-card px-4 py-3 shadow-sm" style="cursor: pointer; border-radius: 12px; border: 2px solid #0d6efd; min-height: 88px;" data-bs-toggle="modal" data-bs-target="#addTextbookModal">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">TEXTBOOK INVENTORY CONTROL</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Add Textbooks</h5>
                            </div>
                            <div class="fs-2 text-primary opacity-75 ms-3"><i class="bi bi-box-seam"></i></div>
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
                                    <th>Item Name</th>
                                    <th>Grade Level</th>
                                    <th>Subject</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Recipient</th>
                                    <th>Date Added</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php include 'controllers/supplies/textbooks/display_textbook.php'; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Textbook Modal -->
<div class="modal fade" id="addTextbookModal" tabindex="-1" aria-labelledby="addTextbookModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="addTextbookModalLabel">Add New Textbook</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="controllers/supplies/textbooks/insert_textbook.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="lr_item" class="form-label fw-semibold">Item Name (lr_item)</label>
                        <input type="text" class="form-control" id="lr_item" name="lr_item" placeholder="Enter textbook name" required>
                    </div>
                    <div class="mb-3">
                        <label for="grade_level" class="form-label fw-semibold">Grade Level</label>
                        <select class="form-select" id="grade_level" name="grade_level" required>
                            <option value="" disabled selected>Select grade level...</option>
                            <option value="Kinder">Kinder</option>
                            <option value="Grade I">Grade I</option>
                            <option value="Grade II">Grade II</option>
                            <option value="Grade III">Grade III</option>
                            <option value="Grade IV">Grade IV</option>
                            <option value="Grade V">Grade V</option>
                            <option value="Grade VI">Grade VI</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="lr_subject" class="form-label fw-semibold">Subject</label>
                        <select class="form-select" id="lr_subject" name="lr_subject" required>
                            <option value="" disabled selected>Select subject...</option>
                            <option value="Kindergarten">Kindergarten</option>
                            <option value="Language">Language</option>
                            <option value="Reading and Literacy">Reading and Literacy</option>
                            <option value="Filipino">Filipino</option>
                            <option value="English">English</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Science">Science</option>
                            <option value="Araling Panlipunan">Araling Panlipunan</option>
                            <option value="Makabansa">Makabansa</option>
                            <option value="GMRC – Good Manners and Right Conduct">GMRC – Good Manners and Right Conduct</option>
                            <option value="Edukasyong Pantahanan at Pangkabuhayan (EPP)">Edukasyong Pantahanan at Pangkabuhayan (EPP)</option>
                            <option value="MAPEH">MAPEH</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="lr_qty" class="form-label fw-semibold">Quantity</label>
                            <input type="number" class="form-control" id="lr_qty" name="lr_qty" value="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lr_unit" class="form-label fw-semibold">Unit</label>
                            <input type="text" class="form-control" id="lr_unit" name="lr_unit" value="pc" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="recipient" class="form-label fw-semibold">Recipient</label>
                        <select class="form-select" id="recipient" name="recipient" required>
                            <option value="" disabled selected>Select teacher recipient...</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo htmlspecialchars($teacher['emp_name']); ?>">
                                    <?php echo htmlspecialchars($teacher['emp_name'] . ' (' . $teacher['emp_position'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_textbook" class="btn btn-primary px-4" style="background-color: #0D3B66; border: none;">Add Textbook</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Textbook Modal -->
<div class="modal fade" id="editTextbookModal" tabindex="-1" aria-labelledby="editTextbookModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="editTextbookModalLabel">Edit Textbook</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="controllers/supplies/textbooks/update_textbook.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_lr_item" class="form-label fw-semibold">Item Name</label>
                        <input type="text" class="form-control" id="edit_lr_item" name="lr_item" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_grade_level" class="form-label fw-semibold">Grade Level</label>
                        <select class="form-select" id="edit_grade_level" name="grade_level" required>
                            <option value="Kinder">Kinder</option>
                            <option value="Grade I">Grade I</option>
                            <option value="Grade II">Grade II</option>
                            <option value="Grade III">Grade III</option>
                            <option value="Grade IV">Grade IV</option>
                            <option value="Grade V">Grade V</option>
                            <option value="Grade VI">Grade VI</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_lr_subject" class="form-label fw-semibold">Subject</label>
                        <select class="form-select" id="edit_lr_subject" name="lr_subject" required>
                            <option value="Kindergarten">Kindergarten</option>
                            <option value="Language">Language</option>
                            <option value="Reading and Literacy">Reading and Literacy</option>
                            <option value="Filipino">Filipino</option>
                            <option value="English">English</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Science">Science</option>
                            <option value="Araling Panlipunan">Araling Panlipunan</option>
                            <option value="Makabansa">Makabansa</option>
                            <option value="GMRC – Good Manners and Right Conduct">GMRC – Good Manners and Right Conduct</option>
                            <option value="Edukasyong Pantahanan at Pangkabuhayan (EPP)">Edukasyong Pantahanan at Pangkabuhayan (EPP)</option>
                            <option value="MAPEH">MAPEH</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_lr_qty" class="form-label fw-semibold">Quantity</label>
                            <input type="number" class="form-control" id="edit_lr_qty" name="lr_qty" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_lr_unit" class="form-label fw-semibold">Unit</label>
                            <input type="text" class="form-control" id="edit_lr_unit" name="lr_unit" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_recipient" class="form-label fw-semibold">Recipient</label>
                        <select class="form-select" id="edit_recipient" name="recipient" required>
                            <option value="" disabled selected>Select teacher recipient...</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo htmlspecialchars($teacher['emp_name']); ?>">
                                    <?php echo htmlspecialchars($teacher['emp_name'] . ' (' . $teacher['emp_position'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_textbook" class="btn btn-primary px-4" style="background-color: #0D3B66; border: none;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>