document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize DataTables safely
    if ($.fn.DataTable) {
        const table = document.querySelector('#suppliesTable');
        if (table && !$.fn.DataTable.isDataTable(table)) {
            $(table).DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                order: [],
                ajax: 'controllers/supplies/textbooks/get_textbooks_paginated.php',
                columns: [
                    { data: 'lr_item', render: $.fn.dataTable.render.text() },
                    { data: 'grade_level', render: $.fn.dataTable.render.text() },
                    { data: 'lr_subject', render: $.fn.dataTable.render.text() },
                    { data: 'lr_qty', render: $.fn.dataTable.render.text() },
                    { data: 'lr_unit', render: $.fn.dataTable.render.text() },
                    { data: 'recipient', render: $.fn.dataTable.render.text() },
                    { data: 'condition', render: $.fn.dataTable.render.text() },
                    { data: 'created_at', render: $.fn.dataTable.render.text() },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            if (type !== 'display') return '';
                            const attr = value => $('<div>').text(value == null ? '' : String(value)).html().replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            return '<button class="btn btn-sm btn-primary edit-btn" data-id="' + Number(row.id) + '" data-item="' + attr(row.lr_item) + '" data-grade="' + attr(row.grade_level) + '" data-subject="' + attr(row.lr_subject) + '" data-qty="' + attr(row.lr_qty) + '" data-unit="' + attr(row.lr_unit) + '" data-recipient="' + attr(row.recipient) + '" data-condition="' + attr(row.condition) + '"><i class="bi bi-pencil-square"></i></button> ' +
                                '<button class="btn btn-sm btn-danger delete-btn" data-id="' + Number(row.id) + '"><i class="bi bi-trash"></i></button> ' +
                                '<button class="btn btn-sm btn-secondary print-btn" data-id="' + Number(row.id) + '" title="Print"><i class="bi bi-printer"></i></button>';
                        }
                    }
                ],
                language: { search: '_INPUT_', searchPlaceholder: 'Search textbooks...' }
            });
        }
    }

    // 2. Focus input automatically when Add Modal opens
    const addModalEl = document.getElementById('addTextbookModal');
    if (addModalEl) {
        addModalEl.addEventListener('shown.bs.modal', function () {
            const itemInput = document.getElementById('lr_item');
            if (itemInput) {
                itemInput.focus();
            }
        });
    }

    // 3. Open Edit Modal & Populate Data
    $(document).on('click', '.edit-btn', function () {
        $('#edit_id').val($(this).data('id'));
        $('#edit_lr_item').val($(this).data('item'));
        $('#edit_grade_level').val($(this).data('grade'));
        $('#edit_lr_subject').val($(this).data('subject'));
        $('#edit_lr_qty').val($(this).data('qty'));
        $('#edit_lr_unit').val($(this).data('unit'));
        $('#edit_recipient').val($(this).data('recipient'));
        $('#edit_condition').val($(this).attr('data-condition'));

        const editModalEl = document.getElementById('editTextbookModal');
        if (editModalEl) {
            bootstrap.Modal.getOrCreateInstance(editModalEl).show();
        }
    });

    const editModalEl = document.getElementById('editTextbookModal');
    if (editModalEl) {
        editModalEl.addEventListener('shown.bs.modal', function () {
            const editItemInput = document.getElementById('edit_lr_item');
            if (editItemInput) {
                editItemInput.focus();
            }
        });
    }

    // 4. Print Button Click Handler
    $(document).on('click', '.print-btn', function () {
        const textbookId = $(this).data('id');
        window.open('controllers/supplies/textbooks/print_textbook.php?id=' + encodeURIComponent(textbookId), '_blank');
    });

    // 5. Delete Button Click Handler with SweetAlert Confirmation
    $(document).on('click', '.delete-btn', function () {
        const textbookId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#0D3B66',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', textbookId);

                fetch('controllers/supplies/textbooks/delete_textbook.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Something went wrong while deleting.'
                    });
                });
            }
        });
    });

    // 6. Global safeguard for modal backdrop cleanup
    $(document).on('hidden.bs.modal', function () {
        setTimeout(function () {
            if ($('.modal.show').length === 0) {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
            }
        }, 150);
    });

    // 7. Success SweetAlert Notifications
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'added') {
        Swal.fire({
            icon: 'success',
            title: 'Successfully Added!',
            html: '<p class="mb-0">The textbook has been added to the inventory.</p>',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    } else if (urlParams.get('success') === 'updated') {
        Swal.fire({
            icon: 'success',
            title: 'Successfully Updated!',
            text: 'The textbook details have been updated.',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
});
