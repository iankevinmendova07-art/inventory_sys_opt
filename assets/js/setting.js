// assets/js/setting.js

document.addEventListener('DOMContentLoaded', function() {
    if (window.schoolDetailsSaveStatus) {
        const messages = {
            saved: { icon: 'success', title: 'Saved', text: 'School details have been saved successfully.' },
            invalid: { icon: 'error', title: 'Could not save', text: 'Complete the required school head and custodian fields and use no more than 255 characters per field.' },
            invalid_employee: { icon: 'error', title: 'Could not save', text: 'Select school head and property custodian names from the current employee list.' },
            invalid_position: { icon: 'error', title: 'Could not save', text: 'Select school head and property custodian positions from the current position list.' },
            error: { icon: 'error', title: 'Save failed', text: 'School details could not be saved. Check the school_details table and try again.' }
        };
        const alert = messages[window.schoolDetailsSaveStatus] || messages.error;
        Swal.fire({ ...alert, confirmButtonColor: '#0D3B66' });
    }

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
                    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'controllers/login/logout.php';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'csrf_token';
                    input.value = token;
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
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

    // -------------------------------------------------------
    // Philippine Phone Number Auto-Format (+63 prefix logic)
    // -------------------------------------------------------
    // For every .ph-phone-input visible field:
    //   - Allow only digits
    //   - Auto-strip leading +63 or 0 on paste (so user doesn't need to remove it)
    //   - Sync the sibling hidden field (emp_phone) with the full +63XXXXXXXXXX value in real time
    document.querySelectorAll('.ph-phone-input').forEach(function(input) {
        function getHiddenField(inp) {
            let sibling = inp.nextElementSibling;
            while (sibling) {
                if (sibling.tagName === 'INPUT' && sibling.type === 'hidden') return sibling;
                sibling = sibling.nextElementSibling;
            }
            return null;
        }

        function syncHidden(inp) {
            const hiddenField = getHiddenField(inp);
            if (hiddenField) {
                hiddenField.value = inp.value.length > 0 ? '+63' + inp.value : '';
            }
        }

        function cleanPhoneInput(val) {
            // Remove all non-digit characters
            val = val.replace(/\D/g, '');
            // Strip leading country code 63 (e.g. +639... or 639...)
            if (val.startsWith('63')) val = val.slice(2);
            // Strip leading 0 (e.g. 09...)
            if (val.startsWith('0')) val = val.slice(1);
            // Limit to 10 digits
            return val.slice(0, 10);
        }

        input.addEventListener('input', function() {
            this.value = cleanPhoneInput(this.value);
            syncHidden(this);
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            this.value = cleanPhoneInput(pasted);
            syncHidden(this);
        });

        input.addEventListener('keyup', function() {
            syncHidden(this);
        });
    });

    // Event delegation for Edit Employee Button click
    $(document).on('click', '.edit-employee-btn', function() {
        const id = $(this).data('id');
        const empId = $(this).closest('tr').find('td:eq(0)').text().trim();
        const name = $(this).data('name');
        const position = $(this).data('position');
        let empPhone = $(this).data('emp-phone') || '';

        // Strip the +63 prefix so only the 10-digit local number is shown in the input
        let localPhone = empPhone.replace(/\D/g, '');
        if (localPhone.startsWith('63')) localPhone = localPhone.slice(2);
        if (localPhone.startsWith('0')) localPhone = localPhone.slice(1);
        localPhone = localPhone.slice(0, 10);

        $('#editEmpDbId').val(id);
        $('#editEmployeeId').val(empId);
        $('#editEmployeeName').val(name);
        $('#editEmployeePosition').val(position);
        // Set visible local field and sync hidden full-number field
        $('#editEmployeePhone').val(localPhone);
        $('#editEmployeePhoneFull').val(localPhone.length > 0 ? '+63' + localPhone : '');

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

    const backupLink = document.getElementById('downloadDatabaseBackup');
    if (backupLink) {
        backupLink.addEventListener('click', async function(event) {
            event.preventDefault();
            if (backupLink.getAttribute('aria-disabled') === 'true') return;

            const originalContent = backupLink.innerHTML;
            backupLink.setAttribute('aria-disabled', 'true');
            backupLink.classList.add('disabled');
            backupLink.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Preparing backup...';

            try {
                const response = await fetch(backupLink.href, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { Accept: 'application/sql' }
                });
                const contentType = response.headers.get('Content-Type') || '';
                if (!response.ok || response.redirected || !contentType.toLowerCase().startsWith('application/sql')) {
                    throw new Error('The backup could not be generated. Refresh the page, sign in again if needed, and retry.');
                }

                const backup = await response.blob();
                const start = await backup.slice(0, 2).text();
                const end = await backup.slice(Math.max(0, backup.size - 100)).text();
                if (backup.size === 0 || start !== '--' || !end.includes('-- Backup completed successfully.')) {
                    throw new Error('The server returned an incomplete SQL backup. No file was downloaded.');
                }

                const disposition = response.headers.get('Content-Disposition') || '';
                const filenameMatch = disposition.match(/filename="?([^";]+)"?/i);
                const filename = filenameMatch && /^[\w.-]+\.sql$/i.test(filenameMatch[1])
                    ? filenameMatch[1]
                    : 'inventory_sys_backup.sql';
                const downloadUrl = URL.createObjectURL(backup);
                const download = document.createElement('a');
                download.href = downloadUrl;
                download.download = filename;
                document.body.appendChild(download);
                download.click();
                download.remove();
                window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);

                const modal = bootstrap.Modal.getInstance(document.getElementById('databaseModal'));
                if (modal) modal.hide();
                Swal.fire('Backup downloaded', filename, 'success');
            } catch (error) {
                Swal.fire('Backup failed', error.message || 'The database backup could not be downloaded.', 'error');
            } finally {
                backupLink.innerHTML = originalContent;
                backupLink.classList.remove('disabled');
                backupLink.removeAttribute('aria-disabled');
            }
        });
    }

    $('#importDatabaseForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const file = $('#databaseSqlFile')[0].files[0];
        if (!file || !/\.sql$/i.test(file.name)) {
            Swal.fire('SQL file required', 'Choose a file with the .sql extension.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Replace the current database?',
            text: 'This will run the uploaded SQL file against the current database and may replace its tables and data.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Import Database'
        }).then((result) => {
            if (!result.isConfirmed) return;
            const button = $('#importDatabaseButton');
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Importing...');
            $.ajax({
                url: form.action,
                method: 'POST',
                data: new FormData(form),
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(response) {
                if (response.status === 'success') {
                    Swal.fire('Import complete', response.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Import failed', response.message || 'The SQL file could not be imported.', 'error');
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'The SQL file could not be imported. Check that it is valid and compatible with this database.';
                Swal.fire('Import failed', message, 'error');
            }).always(function() {
                button.prop('disabled', false).html('<i class="bi bi-upload me-2"></i>Import Database');
            });
        });
    });

    $('#clearDatabaseForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Delete all records?',
            text: 'This permanently deletes records from every table except admin, school_details, employee, position, and unit_measure. Table structures will remain, but this action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Delete Records'
        }).then((result) => {
            if (!result.isConfirmed) return;
            const button = $('#clearDatabaseButton');
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...');
            $.ajax({
                url: form.action,
                method: 'POST',
                data: $(form).serialize(),
                dataType: 'json'
            }).done(function(response) {
                if (response.status === 'success') {
                    Swal.fire('Records deleted', response.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Delete failed', response.message || 'The records could not be deleted.', 'error');
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'The records could not be deleted. Some data may already have been removed.';
                Swal.fire('Delete failed', message, 'error');
            }).always(function() {
                button.prop('disabled', false).html('<i class="bi bi-trash3 me-2"></i>Delete Records');
            });
        });
    });
});
