// assets/js/setting.js

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables via jQuery if available
    if (window.jQuery && $.fn.DataTable) {
        $('#staffTable').DataTable({
            "pageLength": 10,
            "responsive": true,
            "order": [],
            "columnDefs": [
                { "orderable": false, "targets": 4 } // Disable sorting on Action column
            ]
        });

        $('#categoryTable').DataTable({
            "pageLength": 10,
            "responsive": true,
            "order": [],
            "columnDefs": [
                { "orderable": false, "targets": 2 } // Disable sorting on Action column
            ]
        });

        $('#positionTable').DataTable({
            "pageLength": 10,
            "responsive": true,
            "order": [],
            "columnDefs": [
                { "orderable": false, "targets": 2 } // Disable sorting on Action column
            ]
        });

        // Adjust DataTables column width when tab changes
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });
    }

    // --- TAB PERSISTENCE LOGIC ---
    // Restore active tab from localStorage if present
    const savedActiveTab = localStorage.getItem('activeSettingTab');
    if (savedActiveTab) {
        const tabEl = document.getElementById(savedActiveTab);
        if (tabEl) {
            const tabInstance = bootstrap.Tab.getOrCreateInstance(tabEl);
            tabInstance.show();
        }
    }

    // Save active tab ID to localStorage when user clicks/changes tab
    const tabButtons = document.querySelectorAll('#settingsTab button[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function(event) {
            localStorage.setItem('activeSettingTab', event.target.id);
        });
    });

    // Also set active tab when clicking top metric shortcut cards
    document.querySelectorAll('[data-bs-target="#addEmployeeModal"]').forEach(el => {
        el.addEventListener('click', () => localStorage.setItem('activeSettingTab', 'staff-tab'));
    });
    document.querySelectorAll('[data-bs-target="#addCategoryModal"]').forEach(el => {
        el.addEventListener('click', () => localStorage.setItem('activeSettingTab', 'category-tab'));
    });
    document.querySelectorAll('[data-bs-target="#addPositionModal"]').forEach(el => {
        el.addEventListener('click', () => localStorage.setItem('activeSettingTab', 'position-tab'));
    });

    // Logout confirmation using SweetAlert2
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of the inventory system.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0D3B66',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Logout'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'controllers/login/logout.php';
                }
            });
        });
    }

    // Password Visibility Toggle Handler for Eye Icons
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            // Find the target input using data-target attribute or previous sibling input
            const targetId = this.getAttribute('data-target');
            const input = targetId ? document.getElementById(targetId) : this.previousElementSibling;
            
            if (input && input.tagName === 'INPUT') {
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash'); // Switch to crossed-out eye
                } else {
                    input.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye'); // Switch back to regular eye
                }
            }
        });
    });

    // Reusable function to handle AJAX form submissions
    function handleAjaxForm(formSelector, modalId, successMessage, successColor, shouldReload = false) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Client-side validation specifically for updateAdminForm password matching
            if (form.id === 'updateAdminForm') {
                const newPass = document.getElementById('adminPassword').value;
                const confirmPass = document.getElementById('confirmPassword').value;

                if (newPass !== confirmPass) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Password Mismatch',
                        text: 'The new password and confirmation password do not match!',
                        confirmButtonColor: '#0D3B66'
                    });
                    return; // Stop execution if passwords don't match
                }
            }

            const formData = new FormData(form);
            const actionUrl = form.getAttribute('action');

            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const modalEl = document.getElementById(modalId);
                    if (modalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                    }
                    form.reset();

                    Swal.fire({
                        title: 'Success!',
                        text: successMessage,
                        icon: 'success',
                        confirmButtonColor: successColor
                    }).then(() => {
                        if (shouldReload) {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Something went wrong.',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred.',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        });
    }

    // Initialize Add Employee Form (set to reload page so new record displays in table)
    handleAjaxForm(
        'form[action="controllers/Employee/process_employee.php"]', 
        'addEmployeeModal', 
        'New employee has been successfully added.', 
        '#0D3B66', 
        true
    );

    // Initialize Add Position Form (reloads page to update table & dropdown options)
    handleAjaxForm(
        'form[action="controllers/position/process_position.php"]', 
        'addPositionModal', 
        'New position has been successfully added.', 
        '#198754', 
        true
    );

    // Initialize Add Category Form (reloads page to update table)
    handleAjaxForm(
        'form[action="controllers/category/process_category.php"]', 
        'addCategoryModal', 
        'New unit of measure has been successfully added.', 
        '#ffc107', 
        true
    );

    // Initialize Update Admin Form via AJAX
    handleAjaxForm(
        'form[action="controllers/admin/process_admin.php"]', 
        'addAdminModal', 
        'Admin details have been successfully updated.', 
        '#0D3B66', 
        true
    );

    // Event delegation for Edit Employee Button click
    $(document).on('click', '.edit-employee-btn', function() {
        const id = $(this).data('id');
        const empId = $(this).closest('tr').find('td:eq(0)').text().trim();
        const name = $(this).data('name');
        const position = $(this).data('position');
        const email = $(this).data('email');

        $('#editEmpDbId').val(id);
        $('#editEmployeeId').val(empId);
        $('#editEmployeeName').val(name);
        $('#editEmployeePosition').val(position);
        $('#editEmployeeEmail').val(email);

        const editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
        editModal.show();
    });

    // Event delegation for Edit Category Button click
    $(document).on('click', '.edit-category-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#editCategoryId').val(id);
        $('#editCategoryOldName').val(name);
        $('#editCategoryName').val(name);

        const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        editModal.show();
    });

    // Event delegation for Edit Position Button click
    $(document).on('click', '.edit-position-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#editPositionId').val(id);
        $('#editPositionOldName').val(name);
        $('#editPositionInput').val(name);

        const editModal = new bootstrap.Modal(document.getElementById('editPositionModal'));
        editModal.show();
    });

    // Initialize Edit Employee Form Submission
    handleAjaxForm(
        'form[action="controllers/Employee/process_edit_employee.php"]', 
        'editEmployeeModal', 
        'Employee details have been successfully updated.', 
        '#0D3B66', 
        true
    );

    // Initialize Edit Category Form Submission
    handleAjaxForm(
        'form[action="controllers/category/process_edit_category.php"]', 
        'editCategoryModal', 
        'Unit of measure has been successfully updated.', 
        '#ffc107', 
        true
    );

    // Initialize Edit Position Form Submission
    handleAjaxForm(
        'form[action="controllers/position/process_edit_position.php"]', 
        'editPositionModal', 
        'Position has been successfully updated.', 
        '#198754', 
        true
    );

    // Reusable Delete Handler with SweetAlert2 confirmation
    function handleDelete(buttonSelector, endpointUrl, confirmMessage) {
        $(document).on('click', buttonSelector, function() {
            const id = $(this).data('id');
            if (!id) return;

            Swal.fire({
                title: 'Are you sure?',
                text: confirmMessage,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: endpointUrl,
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Record has be removed.',
                                    icon: 'success',
                                    confirmButtonColor: '#0D3B66'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message || 'Could not delete record.', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'An error occurred while deleting the record.', 'error');
                        }
                    });
                }
            });
        });
    }

    // Attach Delete Handlers
    handleDelete('.delete-employee-btn', 'controllers/Employee/process_delete_employee.php', 'This employee record will be deleted permanently.');
    handleDelete('.delete-category-btn', 'controllers/category/process_delete_category.php', 'This unit of measure will be deleted permanently.');
    handleDelete('.delete-position-btn', 'controllers/position/process_delete_position.php', 'This position will be deleted permanently.');
});