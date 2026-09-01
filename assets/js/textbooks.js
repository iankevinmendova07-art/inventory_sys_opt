document.addEventListener('DOMContentLoaded', function () {
    // Initialize DataTables safely
    if ($.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#suppliesTable')) {
            $('#suppliesTable').DataTable().destroy();
        }
        $('#suppliesTable').DataTable({
            responsive: true,
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            order: [],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search textbooks..."
            }
        });
    }

    // Open Edit Modal & Populate Data
    $(document).on('click', '.edit-btn', function () {
        $('#edit_id').val($(this).data('id'));
        $('#edit_lr_item').val($(this).data('item'));
        $('#edit_grade_level').val($(this).data('grade'));
        $('#edit_lr_subject').val($(this).data('subject'));
        $('#edit_lr_qty').val($(this).data('qty'));
        $('#edit_lr_unit').val($(this).data('unit'));
        $('#edit_recipient').val($(this).data('recipient'));

        $('#editTextbookModal').modal('show');
    });

    // Delete Button Click Handler with SweetAlert Confirmation
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

    // Success SweetAlert Notifications
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'added') {
        Swal.fire({
            icon: 'success',
            title: 'Successfully Added!',
            text: 'The textbook has been added to the inventory.',
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
document.addEventListener('DOMContentLoaded', function () {
    // Initialize DataTables safely
    if ($.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#suppliesTable')) {
            $('#suppliesTable').DataTable().destroy();
        }
        $('#suppliesTable').DataTable({
            responsive: true,
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            order: [],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search textbooks..."
            }
        });
    }

    // Open Edit Modal & Populate Data
    $(document).on('click', '.edit-btn', function () {
        $('#edit_id').val($(this).data('id'));
        $('#edit_lr_item').val($(this).data('item'));
        $('#edit_grade_level').val($(this).data('grade'));
        $('#edit_lr_subject').val($(this).data('subject'));
        $('#edit_lr_qty').val($(this).data('qty'));
        $('#edit_lr_unit').val($(this).data('unit'));
        $('#edit_recipient').val($(this).data('recipient'));

        $('#editTextbookModal').modal('show');
    });

    // Print Button Click Handler
    $(document).on('click', '.print-btn', function () {
        const textbookId = $(this).data('id');
        window.open('controllers/supplies/textbooks/print_textbook.php?id=' + textbookId, '_blank');
    });

    // Delete Button Click Handler with SweetAlert Confirmation
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
    // Success SweetAlert Notifications
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'added') {
        Swal.fire({
            icon: 'success',
            title: 'Successfully Added!',
            text: 'The textbook has been added to the inventory.',
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