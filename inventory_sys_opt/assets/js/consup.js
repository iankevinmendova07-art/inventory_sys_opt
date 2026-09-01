console.log('consup.js loaded');

// Global error handler to surface uncaught errors
window.addEventListener('error', function (event) {
    console.error('Global JS error:', event.message, 'at', event.filename + ':' + event.lineno);
});

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize DataTable
    if ($.fn.DataTable) {
        $('#suppliesTable').DataTable({
            responsive: true,
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            order: [],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search supplies..."
            }
        });
    }

    // 2. Logout confirmation using SweetAlert2
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

   // 3. Add Supply Item AJAX submission
    const saveSupplyBtn = document.getElementById('saveSupplyBtn');
    if (saveSupplyBtn) {
        saveSupplyBtn.addEventListener('click', function () {
            const form = document.getElementById('addItemForm');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const itemCodeInput = document.getElementById('itemCode').value.trim();
            let codeExists = false;

            if ($.fn.DataTable.isDataTable('#suppliesTable')) {
                const table = $('#suppliesTable').DataTable();
                table.rows().every(function () {
                    const data = this.data();
                    if (data[0] && data[0].toString().trim() === itemCodeInput) {
                        codeExists = true;
                        return false;
                    }
                });
            }

            if (codeExists) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Duplicate Item Code',
                    text: 'An item with this code already exists in the table. Please use a unique item code.',
                    confirmButtonColor: '#0D3B66'
                }).then(() => {
                    const modalEl = document.getElementById('addItemModal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    }
                });
                return;
            }

            const formData = new FormData(form);

            fetch('controllers/supplies/consumable/add_supply.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text().then(text => ({ ok: response.ok, text })))
            .then(({ ok, text }) => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Server returned invalid response');
                }

                if (data.status === 'success') {
                    const modalEl = document.getElementById('addItemModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    form.reset();

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#0D3B66'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message || 'Server error',
                        confirmButtonColor: '#0D3B66'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: error.message || 'Something went wrong while processing your request.',
                    confirmButtonColor: '#0D3B66'
                });
            });
        });
    }

    // 4. Handle Edit Button Click
    $(document).on('click', '.edit-btn', function () {
        const supplyId = $(this).data('id');
        $(this).blur();

        fetch(`controllers/supplies/consumable/edit_supply.php?id=${supplyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const item = data.data;
                    document.getElementById('editId').value = item.id;
                    document.getElementById('editItemCode').value = item.supply_code;
                    document.getElementById('editItemName').value = item.supply_name;
                    document.getElementById('editUnit').value = item.supply_unit;
                    document.getElementById('editReference').value = item.reference || '';

                    const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
                    editModal.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#0D3B66'
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    });

    // 5. Handle Update Submission
    const updateSupplyBtn = document.getElementById('updateSupplyBtn');
    if (updateSupplyBtn) {
        updateSupplyBtn.addEventListener('click', function () {
            const form = document.getElementById('editItemForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);

            fetch('controllers/supplies/consumable/update_supply.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const modalEl = document.getElementById('editItemModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#0D3B66'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message,
                        confirmButtonColor: '#0D3B66'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Something went wrong while processing your request.',
                    confirmButtonColor: '#0D3B66'
                });
            });
        });
    }

    // 6. Handle Delete Button Click
    $(document).on('click', '.delete-btn', function () {
        const supplyId = $(this).data('id');

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
                formData.append('id', supplyId);

                fetch('controllers/supplies/consumable/delete_supply.php', {
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
                            confirmButtonColor: '#0D3B66'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                            confirmButtonColor: '#0D3B66'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Something went wrong while processing your request.',
                        confirmButtonColor: '#0D3B66'
                    });
                });
            }
        });
    });

    // 7. Add to Cart Auto-Fill Search & Readonly Toggle
    const cartCodeInput = document.getElementById('cartItemCode');

    function fetchItemDetails(value) {
        const idField = document.getElementById('cartSupplyId');
        const nameField = document.getElementById('cartItemName');
        const unitField = document.getElementById('cartUnit');
        const catField = document.getElementById('cartCategory');
        const qtyField = document.getElementById('cartQty');

        if (!value || value.trim().length === 0) {
            if (idField) idField.value = '';
            if (nameField) nameField.value = '';
            if (unitField) unitField.value = '';
            if (catField) catField.value = '';
            if (qtyField) {
                qtyField.value = '';
                qtyField.removeAttribute('data-max-qty');
                qtyField.setAttribute('readonly', true);
            }
            return;
        }

        fetch(`controllers/supplies/consumable/search_supply.php?q=${encodeURIComponent(value)}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data) {
                    if (idField) idField.value = data.data.id;
                    if (nameField) nameField.value = data.data.supply_name;
                    if (unitField) unitField.value = data.data.supply_unit;
                    if (catField) catField.value = data.data.supply_category;
                    if (qtyField) {
                        qtyField.value = data.data.supply_qty;
                        qtyField.dataset.maxQty = data.data.supply_qty;
                        qtyField.removeAttribute('readonly');
                    }
                } else {
                    if (idField) idField.value = '';
                    if (nameField) nameField.value = '';
                    if (unitField) unitField.value = '';
                    if (catField) catField.value = '';
                    if (qtyField) {
                        qtyField.value = '';
                        qtyField.removeAttribute('data-max-qty');
                        qtyField.setAttribute('readonly', true);
                    }
                }
            })
            .catch(error => console.error('Search error:', error));
    }

    if (cartCodeInput) {
        cartCodeInput.addEventListener('input', function() { 
            fetchItemDetails(this.value); 
        });
    }

    // 8. Add to Cart Quantity Validation
    const cartQtyInput = document.getElementById('cartQty');
    if (cartQtyInput) {
        cartQtyInput.addEventListener('input', function() {
            const maxQty = parseInt(this.dataset.maxQty, 10);
            const enteredQty = parseInt(this.value, 10);

            if (this.value !== "" && enteredQty <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Quantity',
                    text: 'Quantity cannot be zero or negative.',
                    confirmButtonColor: '#0D3B66'
                }).then(() => {
                    this.value = !isNaN(maxQty) ? maxQty : 1;
                });
                return;
            }

            if (!isNaN(maxQty) && !isNaN(enteredQty) && enteredQty > maxQty) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Insufficient Stock',
                    text: `You cannot exceed the available stock of ${maxQty}.`,
                    confirmButtonColor: '#0D3B66'
                }).then(() => {
                    this.value = maxQty;
                });
            }
        });
    }

    const addToCartModalEl = document.getElementById('addToCartModal');
    if (addToCartModalEl) {
        addToCartModalEl.addEventListener('show.bs.modal', function () {
            const cartForm = document.getElementById('cartForm');
            if (cartForm) cartForm.reset();

            const supplyIdField = document.getElementById('cartSupplyId');
            if (supplyIdField) supplyIdField.value = '';

            const qtyField = document.getElementById('cartQty');
            if (qtyField) {
                qtyField.value = '';
                qtyField.removeAttribute('data-max-qty');
                qtyField.setAttribute('readonly', true);
            }
        });
    }

    // 9. Add item to cart
    const addToCartBtn = document.getElementById('AddToCartBtn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function () {
            const form = document.getElementById('cartForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const supplyId = parseInt(document.getElementById('cartSupplyId').value, 10);
            const itemCode = document.getElementById('cartItemCode').value.trim();
            const itemName = document.getElementById('cartItemName').value.trim();
            const unit = document.getElementById('cartUnit').value.trim();
            const category = document.getElementById('cartCategory').value.trim();
            const qty = parseInt(document.getElementById('cartQty').value, 10);
            const maxQty = parseInt(document.getElementById('cartQty').dataset.maxQty, 10);

            if (!supplyId || !itemCode || !itemName || !unit || !category || !qty || qty <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Item',
                    text: 'Please enter a valid item code and quantity before adding to cart.',
                    confirmButtonColor: '#0D3B66'
                });
                return;
            }

            const existingCart = window.InventoryCart.readCart();
            const existingItem = existingCart.find(function (entry) {
                return entry.supplyId === supplyId;
            });
            const totalRequestedQty = (existingItem ? existingItem.qty : 0) + qty;

            if (!isNaN(maxQty) && totalRequestedQty > maxQty) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Insufficient Stock',
                    text: 'Total quantity in cart cannot exceed available stock of ' + maxQty + '.',
                    confirmButtonColor: '#0D3B66'
                });
                return;
            }

            window.InventoryCart.addItem({
                supplyId: supplyId,
                itemCode: itemCode,
                itemName: itemName,
                unit: unit,
                category: category,
                qty: qty,
                maxQty: maxQty
            });

            const modalEl = document.getElementById('addToCartModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            Swal.fire({
                icon: 'success',
                title: 'Added to Cart',
                text: itemName + ' (x' + qty + ') was added to your cart.',
                confirmButtonColor: '#0D3B66',
                timer: 1800,
                showConfirmButton: false
            });
        });
    }

    // 10. Cart modal interactions
    let cachedEmployees = [];

    function loadEmployees() {
        return fetch('controllers/cart/get_employees.php')
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    cachedEmployees = data.data || [];
                    return cachedEmployees;
                }
                throw new Error(data.message || 'Failed to load employees.');
            });
    }

    function refreshCartModal() {
        const cartItemsBody = document.getElementById('cartItemsBody');
        window.InventoryCart.renderCartItems(cartItemsBody);
        window.InventoryCart.renderRecipients(document.getElementById('recipientList'), cachedEmployees, getSelectedRecipients());
    }

    function getSelectedRecipients() {
        return Array.from(document.querySelectorAll('.recipient-checkbox:checked')).map(function (checkbox) {
            return checkbox.value;
        });
    }

    const cartToggleBtn = document.getElementById('cartToggleBtn');
    const viewCartModalEl = document.getElementById('viewCartModal');

    if (cartToggleBtn && viewCartModalEl) {
        cartToggleBtn.addEventListener('click', function () {
            loadEmployees()
                .then(function () {
                    refreshCartModal();
                    bootstrap.Modal.getOrCreateInstance(viewCartModalEl).show();
                })
                .catch(function (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message,
                        confirmButtonColor: '#0D3B66'
                    });
                });
        });
    }

    document.addEventListener('cartUpdated', function () {
        refreshCartModal();
    });

    document.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('.remove-cart-item');
        if (removeBtn) {
            const supplyId = parseInt(removeBtn.dataset.supplyId, 10);
            window.InventoryCart.removeItem(supplyId);
            refreshCartModal();
        }
    });

    const selectAllRecipientsBtn = document.getElementById('selectAllRecipientsBtn');
    if (selectAllRecipientsBtn) {
        selectAllRecipientsBtn.addEventListener('click', function () {
            document.querySelectorAll('.recipient-checkbox').forEach(function (checkbox) {
                checkbox.checked = true;
            });
        });
    }

    const clearRecipientsBtn = document.getElementById('clearRecipientsBtn');
    if (clearRecipientsBtn) {
        clearRecipientsBtn.addEventListener('click', function () {
            document.querySelectorAll('.recipient-checkbox').forEach(function (checkbox) {
                checkbox.checked = false;
            });
        });
    }

    const clearCartBtn = document.getElementById('clearCartBtn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'Clear cart?',
                text: 'This will remove all items from your cart.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#0D3B66',
                confirmButtonText: 'Yes, clear it'
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.InventoryCart.clearCart();
                    refreshCartModal();
                }
            });
        });
    }

    const releaseCartBtn = document.getElementById('releaseCartBtn');
    if (releaseCartBtn) {
        releaseCartBtn.addEventListener('click', function () {
            const cartItems = window.InventoryCart.readCart();
            const recipients = getSelectedRecipients();

            if (!cartItems.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Empty Cart',
                    text: 'Add items to your cart before releasing.',
                    confirmButtonColor: '#0D3B66'
                });
                return;
            }

            if (!recipients.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Recipients',
                    text: 'Please select at least one recipient.',
                    confirmButtonColor: '#0D3B66'
                });
                return;
            }

            Swal.fire({
                title: 'Release items?',
                html: 'Release <strong>' + cartItems.length + '</strong> item type(s) to <strong>' + recipients.length + '</strong> recipient(s)?<br><small class="text-muted">Each recipient receives the same quantities listed in the cart.</small>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0D3B66',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, release'
            }).then(function (result) {
                if (!result.isConfirmed) return;

                fetch('controllers/cart/process_release.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recipients: recipients,
                        items: cartItems
                    })
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.status === 'success') {
                        window.InventoryCart.clearCart();
                        const modal = bootstrap.Modal.getInstance(viewCartModalEl);
                        if (modal) modal.hide();

                        Swal.fire({
                            icon: 'success',
                            title: 'Released!',
                            text: data.message,
                            confirmButtonColor: '#0D3B66'
                        }).then(function () {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Release Failed',
                            text: data.message || 'Unable to release items.',
                            confirmButtonColor: '#0D3B66'
                        });
                    }
                })
                .catch(function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Something went wrong while releasing items.',
                        confirmButtonColor: '#0D3B66'
                    });
                });
            });
        });
    }

    if (window.InventoryCart) {
        window.InventoryCart.updateCartBadge();
    }
});

// Transaction Report Table & Print Functionality
$(document).ready(function() {
    let reportTable = null;
    let stockCardItemsTable = null;

    $('#stockCardModal').on('shown.bs.modal', function () {
        if (!stockCardItemsTable) {
            stockCardItemsTable = $('#stockCardItemsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: 'controllers/supplies/consumable/get_stock_card_items.php',
                order: [[0, 'asc']],
                columns: [
                    { data: 'item_name' }
                ],
                destroy: true,
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Search item names...'
                }
            });
        } else {
            stockCardItemsTable.ajax.reload();
        }
    });

    $('#stockCardItemsTable tbody').on('click', 'tr', function () {
        if (!stockCardItemsTable) return;

        const item = stockCardItemsTable.row(this).data();
        if (!item || !item.id) return;

        window.open(
            'controllers/supplies/consumable/print_stock_card.php?id=' + encodeURIComponent(item.id),
            '_blank'
        );
    });

    $('#requestReportModal').on('shown.bs.modal', function () {
        if (!reportTable) {
            reportTable = $('#transactionReportTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: 'controllers/supplies/consumable/get_transactions.php',
                order: [],
                columns: [
                    { data: 'trans_code' },
                    { data: 'name' },
                    { data: 'date' },
                    { data: 'action', orderable: false, searchable: false }
                ],
                destroy: true
            });
        } else {
            reportTable.ajax.reload();
        }
    });

    $(document).on('click', '.print-report-btn', function() {
        let transCode = $(this).data('trans-code');
        window.open('controllers/supplies/consumable/print_ris.php?trans_code=' + encodeURIComponent(transCode), '_blank');
    });

    // RSMI DataTable initialization
    let rsmiTable = null;

    $('#rsmiModal').on('shown.bs.modal', function () {
        if (!rsmiTable) {
            rsmiTable = $('#rsmiTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: 'controllers/supplies/consumable/get_rsmi_logs.php',
                order: [],
                columns: [
                    { data: 'trans_code' },
                    { data: 'emp_name' },
                    { data: 'supply_code' },
                    { data: 'supply_name' },
                    { data: 'supply_unit' },
                    { data: 'supply_qty', className: 'text-center' },
                    { data: 'date' }
                ],
                destroy: true,
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Search RSMI records...'
                }
            });
        } else {
            rsmiTable.ajax.reload();
        }
    });

    // Quantity Management Modal & Update Functionality
    let quantityTable = null;

    $('#updateQtyModal').on('shown.bs.modal', function () {
        if (!quantityTable) {
            quantityTable = $('#quantityTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: 'controllers/supplies/consumable/get_quantity_list.php',
                order: [],
                columns: [
                    { data: 'code' },
                    { data: 'name' },
                    { data: 'unit' },
                    { data: 'qty' },
                    { data: 'action', orderable: false, searchable: false }
                ],
                destroy: true
            });
        } else {
            quantityTable.ajax.reload();
        }
    });

    // Handle Quantity Update when modal closes to refresh main table if needed
    $('#updateQtyModal').on('hidden.bs.modal', function () {
        location.reload();
    });

    // Helper function to submit inline quantity updates
    function submitInlineQty(supplyId, supplyCode, supplyName) {
        const inputEl = document.getElementById('qty_input_' + supplyId);
        const referenceEl = document.getElementById('reference_input_' + supplyId);
        if (!inputEl || !referenceEl) return;

        const val = inputEl.value.trim();
        const reference = referenceEl.value.trim();
        if (val === '' || isNaN(val) || parseInt(val) < 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Input',
                text: 'Please enter a valid quantity into the input box.',
                confirmButtonColor: '#0D3B66'
            });
            return;
        }

        if (reference === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Reference Required',
                text: 'Please enter a reference before saving this stock-in transaction.',
                confirmButtonColor: '#0D3B66'
            });
            referenceEl.focus();
            return;
        }

        const newQty = parseInt(val);
        const formData = new FormData();
        formData.append('id', supplyId);
        formData.append('qty', newQty);
        formData.append('reference', reference);

        fetch('controllers/supplies/consumable/update_quantity.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Quantity Updated!',
                    text: `Updated "${supplyName}" quantity to ${newQty}.`,
                    timer: 1500,
                    showConfirmButton: false
                });

                if (quantityTable) {
                    quantityTable.ajax.reload(null, false);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to update quantity.',
                    confirmButtonColor: '#0D3B66'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Server Error',
                text: 'Something went wrong while updating quantity.',
                confirmButtonColor: '#0D3B66'
            });
        });
    }

    $(document).on('click', '.save-inline-qty-btn', function() {
        const supplyId = $(this).data('id');
        const supplyCode = $(this).data('code');
        const supplyName = $(this).data('name');
        submitInlineQty(supplyId, supplyCode, supplyName);
    });

    $(document).on('keypress', '.inline-qty-input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const supplyId = $(this).data('id');
            const supplyCode = $(this).data('code');
            const supplyName = $(this).data('name');
            submitInlineQty(supplyId, supplyCode, supplyName);
        }
    });

    $(document).on('keypress', '.inline-reference-input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const supplyId = $(this).closest('div').find('.inline-qty-input').data('id');
            const supplyCode = $(this).closest('div').find('.inline-qty-input').data('code');
            const supplyName = $(this).closest('div').find('.inline-qty-input').data('name');
            submitInlineQty(supplyId, supplyCode, supplyName);
        }
    });

    $(document).on('click', '.update-qty-btn', function() {
        const supplyId = $(this).data('id');
        const supplyCode = $(this).data('code');
        const supplyName = $(this).data('name');
        const currentQty = $(this).data('qty');

        Swal.fire({
            title: 'Update Quantity',
            html: '<p class="mb-2">Item: <strong>' + supplyCode + ' - ' + supplyName + '</strong></p><p class="text-muted small mb-0">Enter the new total quantity in stock:</p>',
            input: 'number',
            inputPlaceholder: currentQty ? currentQty.toString() : '0',
            inputValue: '',
            inputAttributes: {
                min: 0,
                step: 1,
                class: 'form-control text-center fw-bold fs-5 mt-2'
            },
            showCancelButton: true,
            confirmButtonColor: '#0D3B66',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Save Quantity',
            inputValidator: (value) => {
                if (value === '' || isNaN(value) || parseInt(value) < 0) {
                    return 'Please enter a valid non-negative quantity!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newQty = parseInt(result.value);

                const formData = new FormData();
                formData.append('id', supplyId);
                formData.append('qty', newQty);
                formData.append('reference', 'Stock replenishment');

                fetch('controllers/supplies/consumable/update_quantity.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        if (quantityTable) {
                            quantityTable.ajax.reload(null, false);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to update quantity.',
                            confirmButtonColor: '#0D3B66'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Something went wrong while updating quantity.',
                        confirmButtonColor: '#0D3B66'
                    });
                });
            }
        });
    });
});
function printRsmiReport() {
    const monthYear = document.getElementById('rsmiMonthYear').value;
    if (!monthYear) {
        alert('Please select a month and year first.');
        return;
    }
    // Opens print_rsmi_month.php with the selected month as a query parameter
    window.open('controllers/supplies/consumable/print_rsmi_month.php?month_year=' + monthYear, '_blank');
}
