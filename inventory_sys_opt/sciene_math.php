<?php
session_start();
// Protect the page using your auth check
require_once 'controllers/auth/auth.php';

// Get admin name for display
$adminName = isset($_SESSION['admin_name']) ? strtoupper($_SESSION['admin_name']) : 'ADMIN';
$adminRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator';

// Fetch Science and Math data separately
require_once 'config/db.php';

$scienceItems = [];
$mathItems    = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM lr_sme WHERE lr_type = ? ORDER BY CAST(lr_code AS UNSIGNED) ASC");
    $stmt->execute(['Science']);
    $scienceItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt->execute(['Math']);
    $mathItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // silently fail; tables will show empty
}

function renderRows(array $items): void {
    if (empty($items)) {
        echo '<tr><td colspan="5" class="text-center text-muted py-3">No items found.</td></tr>';
        return;
    }
    foreach ($items as $row) {
        echo '<tr>
            <td>' . htmlspecialchars($row['lr_code']) . '</td>
            <td>' . htmlspecialchars($row['lr_item']) . '</td>
            <td>' . htmlspecialchars($row['lr_qty']) . '</td>
            <td>' . htmlspecialchars($row['lr_unit'] ?? '') . '</td>
            <td>
                <button class="btn btn-sm btn-primary edit-btn me-1"
                    data-id="'       . $row['id'] . '"
                    data-lr_code="'  . htmlspecialchars($row['lr_code'],         ENT_QUOTES) . '"
                    data-lr_item="'  . htmlspecialchars($row['lr_item'],         ENT_QUOTES) . '"
                    data-lr_qty="'   . htmlspecialchars($row['lr_qty'],          ENT_QUOTES) . '"
                    data-lr_unit="'  . htmlspecialchars($row['lr_unit'] ?? '',   ENT_QUOTES) . '"
                    data-lr_type="'  . htmlspecialchars($row['lr_type'] ?? '',   ENT_QUOTES) . '">
                    <i class="bi bi-pencil-square"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row['id'] . '">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </td>
        </tr>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Science &amp; Math Equipment - San Roque Elementary School</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom Styling -->
    <link href="assets/css/consup.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Shared logout handler -->
    <script src="assets/js/index.js" defer></script>

    <style>
        /* Tab styling matching setting.php */
        .custom-tabs {
            border-bottom: 2px solid #dee2e6;
        }
        .custom-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            padding: 0.65rem 1.25rem;
            transition: border-color 0.2s, color 0.2s;
        }
        .custom-tabs .nav-link:hover {
            border-bottom-color: #adb5bd;
        }
        .custom-tabs .nav-link.active {
            border-bottom: 3px solid #0D3B66;
            color: #0D3B66 !important;
            background: transparent;
        }
        #scienceTable th,
        #mathTable th {
            font-size: 0.9rem !important;
            font-weight: 700 !important;
        }
        #scienceTable td,
        #mathTable td {
            font-weight: normal !important;
        }
    </style>
</head>
<body>

<div class="wrapper d-flex">
    <?php include_once 'includes/nav.php'; ?>

    <div id="content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-top">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h6 text-secondary fw-bold">
                    <i class="bi bi-journal-bookmark me-2"></i> Science &amp; Math Equipment Overview
                </span>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <div class="user-profile-badge">
                        <div class="user-avatar"><?php echo substr($adminName, 0, 1); ?></div>
                        <div class="text-start">
                            <span class="d-block fw-bold text-dark" style="font-size: 0.85rem; line-height: 1.1;"><?php echo htmlspecialchars($adminName); ?></span>
                            <span class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($adminRole); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Body -->
        <div class="container-fluid p-4">

            <!-- Welcome Banner -->
            <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4"
                 style="background: linear-gradient(135deg, #0D3B66 0%, #1a528a 100%); color: white; border-radius: 12px; padding: 1.5rem;">
                <div>
                    <h4 class="alert-heading fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($adminName); ?>!</h4>
                    <p class="mb-0 text-white-50" style="font-size: 0.95rem;">
                        Here is the summary of Science &amp; Math Equipment inventory for San Roque Elementary School.
                    </p>
                </div>
            </div>

            <!-- Quick Action Cards -->
            <div class="row g-3 mb-4">
                <!-- Add Item -->
                <div class="col-md-3">
                    <div class="card stat-card px-4 py-3 shadow-sm"
                         style="cursor: pointer; border-radius: 12px; border: 2px solid #0d6efd; min-height: 88px;"
                         data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">INVENTORY CONTROL</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Add Item</h5>
                            </div>
                            <div class="fs-2 text-primary opacity-75 ms-3"><i class="bi bi-box-seam"></i></div>
                        </div>
                    </div>
                </div>
                <!-- Inventory Report -->
                <div class="col-md-3">
                    <div class="card stat-card px-4 py-3 shadow-sm"
                         style="cursor: pointer; border-radius: 12px; border: 2px solid #198754; min-height: 88px;"
                         data-bs-toggle="modal" data-bs-target="#reportModal">
                        <div class="d-flex align-items-center h-100">
                            <div class="flex-grow-1">
                                <span class="d-block text-uppercase text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">REPORTS</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Inventory Report</h5>
                            </div>
                            <div class="fs-2 text-success opacity-75 ms-3"><i class="bi bi-file-earmark-pdf"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Card with Nav Tabs -->
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">

                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs custom-tabs mb-3" id="smeTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-dark" id="science-tab"
                                    data-bs-toggle="tab" data-bs-target="#science-panel"
                                    type="button" role="tab" aria-controls="science-panel" aria-selected="true">
                                <i class="bi bi-journal-medical me-2 text-danger"></i> Science
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark" id="math-tab"
                                    data-bs-toggle="tab" data-bs-target="#math-panel"
                                    type="button" role="tab" aria-controls="math-panel" aria-selected="false">
                                <i class="bi bi-calculator me-2 text-primary"></i> Math
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="smeTabContent">

                        <!-- Science Panel -->
                        <div class="tab-pane fade show active" id="science-panel" role="tabpanel" aria-labelledby="science-tab">
                            <div class="table-responsive">
                                <table id="scienceTable" class="table table-hover align-middle w-100">
                                    <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php renderRows($scienceItems); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Math Panel -->
                        <div class="tab-pane fade" id="math-panel" role="tabpanel" aria-labelledby="math-tab">
                            <div class="table-responsive">
                                <table id="mathTable" class="table table-hover align-middle w-100">
                                    <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php renderRows($mathItems); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div><!-- /tab-content -->
                </div>
            </div>

        </div>
    </div>
</div>
<!-- ── Add Item Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-labelledby="addEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="addEquipmentModalLabel"><i class="bi bi-box-seam me-2"></i>Add Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addEquipmentForm">
                    <div class="mb-3">
                        <label for="lr_code" class="form-label fw-semibold">Item Code</label>
                        <input type="text" class="form-control" id="lr_code" name="lr_code" placeholder="Enter item code" required>
                    </div>
                    <div class="mb-3">
                        <label for="lr_item" class="form-label fw-semibold">Item Name</label>
                        <input type="text" class="form-control" id="lr_item" name="lr_item" placeholder="Enter item name" required>
                    </div>
                    <div class="mb-3">
                        <label for="lr_quantity" class="form-label fw-semibold">Quantity</label>
                        <input type="number" class="form-control" id="lr_quantity" name="lr_quantity" placeholder="Enter quantity" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="lr_unit" class="form-label fw-semibold">Unit</label>
                        <input type="text" class="form-control" id="lr_unit" name="lr_unit" placeholder="e.g. pc, set, unit">
                    </div>
                    <div class="mb-3">
                        <label for="lr_type" class="form-label fw-semibold">Type</label>
                        <select class="form-select" id="lr_type" name="lr_type" required>
                            <option value="" selected disabled>Select type</option>
                            <option value="Science">Science</option>
                            <option value="Math">Math</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveEquipmentBtn" class="btn btn-primary px-4" style="background-color: #0D3B66; border: none;">Add Item</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Inventory Report Modal ───────────────────────────────────────────── -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="reportModalLabel">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Generate Inventory Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Select which equipment list to export as PDF.</p>
                <div class="d-grid gap-2">
                    <a href="controllers/supplies/science_math/print_lr_report.php?type=Science"
                       target="_blank"
                       class="btn btn-outline-danger fw-semibold">
                        <i class="bi bi-journal-medical me-2"></i> Science Equipment
                    </a>
                    <a href="controllers/supplies/science_math/print_lr_report.php?type=Math"
                       target="_blank"
                       class="btn btn-outline-primary fw-semibold">
                        <i class="bi bi-calculator me-2"></i> Math Equipment
                    </a>
                    <a href="controllers/supplies/science_math/print_lr_report.php?type=All"
                       target="_blank"
                       class="btn fw-semibold text-white" style="background-color: #0D3B66; border: none;">
                        <i class="bi bi-list-ul me-2"></i> All Equipment (Science &amp; Math)
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Edit Item Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="editEquipmentModal" tabindex="-1" aria-labelledby="editEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #0D3B66;">
                <h5 class="modal-title fw-bold" id="editEquipmentModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editEquipmentForm">
                    <input type="hidden" id="editId" name="id">
                    <div class="mb-3">
                        <label for="editLrCode" class="form-label fw-semibold">Item Code</label>
                        <input type="text" class="form-control" id="editLrCode" name="lr_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="editLrItem" class="form-label fw-semibold">Item Name</label>
                        <input type="text" class="form-control" id="editLrItem" name="lr_item" required>
                    </div>
                    <div class="mb-3">
                        <label for="editLrQuantity" class="form-label fw-semibold">Quantity</label>
                        <input type="number" class="form-control" id="editLrQuantity" name="lr_quantity" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="editLrUnit" class="form-label fw-semibold">Unit</label>
                        <input type="text" class="form-control" id="editLrUnit" name="lr_unit" placeholder="e.g. pc, set, unit">
                    </div>
                    <div class="mb-3">
                        <label for="editLrType" class="form-label fw-semibold">Type</label>
                        <select class="form-select" id="editLrType" name="lr_type" required>
                            <option value="" disabled>Select type</option>
                            <option value="Science">Science</option>
                            <option value="Math">Math</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="updateEquipmentBtn" class="btn btn-primary px-4" style="background-color: #0D3B66; border: none;">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // DataTable config helper
    function initTable(id) {
        if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#' + id)) {
            $('#' + id).DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                order: [],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Search...'
                }
            });
        }
    }

    // Init Science table right away (active tab)
    initTable('scienceTable');

    // Init Math table when its tab is first shown (DataTables needs visible container)
    document.getElementById('math-tab').addEventListener('shown.bs.tab', function () {
        initTable('mathTable');
    });

    // ── Add Item ────────────────────────────────────────────────────────────
    document.getElementById('saveEquipmentBtn').addEventListener('click', function () {
        const form = document.getElementById('addEquipmentForm');
        if (!form.checkValidity()) { form.reportValidity(); return; }
        fetch('controllers/supplies/science_math/add_sme.php', { method: 'POST', body: new FormData(form) })
        .then(r => r.json()).then(data => {
            if (data.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('addEquipmentModal')).hide();
                form.reset();
                Swal.fire({ icon: 'success', title: 'Success!', text: data.message, confirmButtonColor: '#0D3B66' }).then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#0D3B66' });
            }
        }).catch(() => Swal.fire({ icon: 'error', title: 'Server Error', confirmButtonColor: '#0D3B66' }));
    });

    // ── Open Edit Modal ─────────────────────────────────────────────────────
    $(document).on('click', '.edit-btn', function () {
        $('#editId').val($(this).data('id'));
        $('#editLrCode').val($(this).data('lr_code'));
        $('#editLrItem').val($(this).data('lr_item'));
        $('#editLrQuantity').val($(this).data('lr_qty'));
        $('#editLrUnit').val($(this).data('lr_unit'));
        $('#editLrType').val($(this).data('lr_type'));
        $('#editEquipmentModal').modal('show');
    });

    // ── Save Edit ───────────────────────────────────────────────────────────
    document.getElementById('updateEquipmentBtn').addEventListener('click', function () {
        const form = document.getElementById('editEquipmentForm');
        if (!form.checkValidity()) { form.reportValidity(); return; }
        fetch('controllers/supplies/science_math/update_sme.php', { method: 'POST', body: new FormData(form) })
        .then(r => r.json()).then(data => {
            if (data.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('editEquipmentModal')).hide();
                Swal.fire({ icon: 'success', title: 'Updated!', text: data.message, confirmButtonColor: '#0D3B66' }).then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#0D3B66' });
            }
        }).catch(() => Swal.fire({ icon: 'error', title: 'Server Error', confirmButtonColor: '#0D3B66' }));
    });

    // ── Delete ──────────────────────────────────────────────────────────────
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?', text: "You won't be able to revert this!",
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', cancelButtonColor: '#0D3B66',
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                const fd = new FormData(); fd.append('id', id);
                fetch('controllers/supplies/science_math/delete_sme.php', { method: 'POST', body: fd })
                .then(r => r.json()).then(data => {
                    if (data.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message, confirmButtonColor: '#0D3B66' }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                }).catch(() => Swal.fire({ icon: 'error', title: 'Server Error' }));
            }
        });
    });
});
</script>

</body>
</html>
