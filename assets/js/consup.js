console.log('consup.js (optimized) loaded');

// Global error handler
window.addEventListener('error', function (event) {
    console.error('Global JS error:', event.message, 'at', event.filename + ':' + event.lineno);
});

// Reusable Utilities
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

const itemSearchCache = new Map();
let cachedEmployees = null;
let suppliesTable = null;

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Server-Side DataTable for Main Supplies Table
    if ($.fn.DataTable && document.getElementById('suppliesTable')) {
        suppliesTable = $('#suppliesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
            ajax: {
                url: 'controllers/supplies/consumable/get_supplies_paginated.php',
                type: 'GET',
                error: function (xhr, error, thrown) {
                    console.error('Supplies DataTables Error:', thrown);
                }
            },
            columns: [
                { data: 'supply_code', className: 'fw-semibold' },
                { data: 'supply_name' },
                { data: 'supply_unit' },
                { data: 'reference' },
                { 
                    data: 'supply_qty',
                    render: function (data, type, row) {
                        const qty = parseInt(data, 10);
                        if (type === 'display') {
                            if (qty === 0) {
                                return '<span class="badge bg-danger">0 (Out of Stock)</span>';
                            } else if (qty <= 5) {
                                return '<span class="badge bg-warning text-dark">' + qty + ' (Low Stock)</span>';
                            }
                            return '<span class="badge bg-success">' + qty + '</span>';
                        }
                        return qty;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        const qty = parseInt(row.supply_qty, 10);
                        const code = (row.supply_code || '').replace(/"/g, '&quot;');
                        const name = (row.supply_name || '').replace(/"/g, '&quot;');
                        const unit = (row.supply_unit || '').replace(/"/g, '&quot;');
                        const category = 'Consumable Supply';

                        let cartBtn = '';
                        if (qty > 0) {
                            cartBtn = '<button class="btn btn-sm btn-warning add-to-cart-btn text-dark" ' +
                                'data-id="' + row.id + '" ' +
                                'data-code="' + code + '" ' +
                                'data-name="' + name + '" ' +
                                'data-unit="' + unit + '" ' +
                                'data-category="' + category + '" ' +
                                'data-qty="' + qty + '" ' +
                                'title="Add to Cart"><i class="bi bi-cart-plus"></i></button>';
                        }

                        return '<div class="d-flex gap-1">' +
                            '<button class="btn btn-sm btn-primary edit-btn" data-id="' + row.id + '" title="Edit"><i class="bi bi-pencil-square"></i></button>' +
                            '<button class="btn btn-sm btn-danger delete-btn" data-id="' + row.id + '" title="Delete"><i class="bi bi-trash"></i></button>' +
                            cartBtn +
                            '</div>';
                    }
                }
            ],
            order: [[4, 'desc']],
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

    // 3. Add Supply Item AJAX submission (In-Place Reload)
    const saveSupplyBtn = document.getElementById('saveSupplyBtn');
    if (saveSupplyBtn) {
        saveSupplyBtn.addEventListener('click', function () {
            const form = document.getElementById('addItemForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            saveSupplyBtn.disabled = true;
            saveSupplyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            const formData = new FormData(form);

            fetch('controllers/supplies/consumable/add_supply.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const modalEl = document.getElementById('addItemModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    form.reset();
                    itemSearchCache.clear();

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    if (suppliesTable) {
                        suppliesTable.ajax.reload(null, false);
                    }
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Notice',
                        text: data.message || 'Server returned an error',
                        confirmButtonColor: '#0D3B66'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: error.message || 'Something went wrong.',
                    confirmButtonColor: '#0D3B66'
                });
            })
            .finally(() => {
                saveSupplyBtn.disabled = false;
                saveSupplyBtn.textContent = 'Add Item';
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
            .catch(error => console.error('Error fetching item for edit:', error));
    });

    // 5. Handle Update Submission (In-Place Reload)
    const updateSupplyBtn = document.getElementById('updateSupplyBtn');
    if (updateSupplyBtn) {
        updateSupplyBtn.addEventListener('click', function () {
            const form = document.getElementById('editItemForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            updateSupplyBtn.disabled = true;
            updateSupplyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

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
                    if (modal) modal.hide();

                    itemSearchCache.clear();

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    if (suppliesTable) {
                        suppliesTable.ajax.reload(null, false);
                    }
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
            })
            .finally(() => {
                updateSupplyBtn.disabled = false;
                updateSupplyBtn.textContent = 'Save Changes';
            });
        });
    }

    // 6. Handle Delete Button Click (In-Place Reload)
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
                        itemSearchCache.clear();

                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            timer: 1200,
                            showConfirmButton: false
                        });

                        if (suppliesTable) {
                            suppliesTable.ajax.reload(null, false);
                        }
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

    // 7. Add to Cart Debounced Auto-Fill Search & Readonly Toggle
    const cartCodeInput = document.getElementById('cartItemCode');

    function fetchItemDetails(value) {
        const idField = document.getElementById('cartSupplyId');
        const nameField = document.getElementById('cartItemName');
        const unitField = document.getElementById('cartUnit');
        const catField = document.getElementById('cartCategory');
        const qtyField = document.getElementById('cartQty');

        const trimmed = (value || '').trim();
        if (!trimmed) {
            if (idField) idField.value = '';
            if (nameField) nameField.value = '';
            if (unitField) unitField.value = '';
            if (catField) catField.value = '';
            if (qtyField) {
                qtyField.value = '';
                qtyField.removeAttribute('data-max-qty');
                qtyField.setAttribute('readonly', 'true');
            }
            return;
        }

        if (itemSearchCache.has(trimmed)) {
            populateCartItemFields(itemSearchCache.get(trimmed));
            return;
        }

        fetch(`controllers/supplies/consumable/search_supply.php?q=${encodeURIComponent(trimmed)}`)
            .then(response => response.json())
            .then(data => {
                const item = (data.status === 'success' && data.data) ? data.data : null;
                itemSearchCache.set(trimmed, item);
                populateCartItemFields(item);
            })
            .catch(error => console.error('Search error:', error));
    }

    function populateCartItemFields(item) {
        const idField = document.getElementById('cartSupplyId');
        const nameField = document.getElementById('cartItemName');
        const unitField = document.getElementById('cartUnit');
        const catField = document.getElementById('cartCategory');
        const qtyField = document.getElementById('cartQty');

        if (item) {
            if (idField) idField.value = item.id;
            if (nameField) nameField.value = item.supply_name;
            if (unitField) unitField.value = item.supply_unit;
            if (catField) catField.value = item.supply_category || 'Consumable Supply';
            if (qtyField) {
                qtyField.value = 1;
                qtyField.dataset.maxQty = item.supply_qty;
                qtyField.setAttribute('max', item.supply_qty);
                qtyField.setAttribute('min', 1);
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
                qtyField.setAttribute('readonly', 'true');
            }
        }
    }

    if (cartCodeInput) {
        cartCodeInput.addEventListener('input', debounce(function() { 
            fetchItemDetails(this.value); 
        }, 300));
    }

    // Handle Table Row "Add to Cart" Button Click
    $(document).on('click', '.add-to-cart-btn', function () {
        const supplyId = $(this).data('id');
        const itemCode = $(this).data('code') || '';
        const itemName = $(this).data('name') || '';
        const unit = $(this).data('unit') || '';
        const category = $(this).data('category') || 'Consumable Supply';
        const qty = parseInt($(this).data('qty'), 10) || 0;

        if (qty <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Out of Stock',
                text: `"${itemName}" is currently out of stock and cannot be added to the cart.`,
                confirmButtonColor: '#0D3B66'
            });
            return;
        }

        const idField = document.getElementById('cartSupplyId');
        const codeField = document.getElementById('cartItemCode');
        const nameField = document.getElementById('cartItemName');
        const unitField = document.getElementById('cartUnit');
        const catField = document.getElementById('cartCategory');
        const qtyField = document.getElementById('cartQty');

        if (idField) idField.value = supplyId;
        if (codeField) {
            codeField.value = itemCode;
            codeField.setAttribute('readonly', 'true');
        }
        if (nameField) {
            nameField.value = itemName;
            nameField.setAttribute('readonly', 'true');
        }
        if (unitField) {
            unitField.value = unit;
            unitField.setAttribute('readonly', 'true');
        }
        if (catField) {
            catField.value = category;
            catField.setAttribute('readonly', 'true');
        }
        if (qtyField) {
            qtyField.value = 1;
            qtyField.dataset.maxQty = qty;
            qtyField.setAttribute('max', qty);
            qtyField.setAttribute('min', 1);
            qtyField.removeAttribute('readonly');
        }

        const modalEl = document.getElementById('addToCartModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

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
                    this.value = !isNaN(maxQty) ? Math.min(1, maxQty) : 1;
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
        addToCartModalEl.addEventListener('shown.bs.modal', function () {
            const qtyField = document.getElementById('cartQty');
            if (qtyField) {
                qtyField.focus();
                qtyField.select();
            }
        });
        addToCartModalEl.addEventListener('hidden.bs.modal', function () {
            const cartForm = document.getElementById('cartForm');
            if (cartForm) cartForm.reset();

            const supplyIdField = document.getElementById('cartSupplyId');
            if (supplyIdField) supplyIdField.value = '';

            const qtyField = document.getElementById('cartQty');
            if (qtyField) {
                qtyField.value = '';
                qtyField.removeAttribute('data-max-qty');
                qtyField.setAttribute('readonly', 'true');
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

            const supplyIdField = document.getElementById('cartSupplyId');
            const itemCodeField = document.getElementById('cartItemCode');
            const itemNameField = document.getElementById('cartItemName');
            const unitField = document.getElementById('cartUnit');
            const catField = document.getElementById('cartCategory');
            const qtyField = document.getElementById('cartQty');

            const supplyId = parseInt(supplyIdField ? supplyIdField.value : '0', 10);
            const itemCode = itemCodeField ? itemCodeField.value.trim() : '';
            const itemName = itemNameField ? itemNameField.value.trim() : '';
            const unit = unitField ? unitField.value.trim() : '';
            const category = (catField && catField.value ? catField.value.trim() : '') || 'Consumable Supply';
            const qty = parseInt(qtyField ? qtyField.value : '0', 10);
            const maxQty = parseInt(qtyField && qtyField.dataset.maxQty ? qtyField.dataset.maxQty : '0', 10);

            if (!supplyId || !itemCode || !itemName || !unit || !qty || qty <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Item',
                    text: 'Please enter a valid item code and quantity before adding to cart.',
                    confirmButtonColor: '#0D3B66'
                });
                return;
            }

            const existingCart = window.InventoryCart ? window.InventoryCart.readCart() : [];
            const existingItem = existingCart.find(entry => entry.supplyId === supplyId);
            const totalRequestedQty = (existingItem ? existingItem.qty : 0) + qty;

            if (!isNaN(maxQty) && maxQty > 0 && totalRequestedQty > maxQty) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Insufficient Stock',
                    text: 'Total quantity in cart cannot exceed available stock of ' + maxQty + '.',
                    confirmButtonColor: '#0D3B66'
                });
                return;
            }

            if (window.InventoryCart) {
                window.InventoryCart.addItem({
                    supplyId: supplyId,
                    itemCode: itemCode,
                    itemName: itemName,
                    unit: unit,
                    category: category,
                    qty: qty,
                    maxQty: maxQty || qty
                });
            }

            const modalEl = document.getElementById('addToCartModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            Swal.fire({
                icon: 'success',
                title: 'Added to Cart',
                text: itemName + ' (x' + qty + ') was added to your cart.',
                confirmButtonColor: '#0D3B66',
                timer: 1500,
                showConfirmButton: false
            });
        });
    }

    // 10. Cart modal interactions with Employee Caching
    function loadEmployees() {
        if (cachedEmployees !== null) {
            return Promise.resolve(cachedEmployees);
        }
        return fetch('controllers/cart/get_employees.php')
            .then(response => response.json())
            .then(data => {
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
        window.InventoryCart.renderRecipients(document.getElementById('recipientList'), cachedEmployees || [], getSelectedRecipients());
    }

    function getSelectedRecipients() {
        return Array.from(document.querySelectorAll('.recipient-checkbox:checked')).map(cb => cb.value);
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
            document.querySelectorAll('.recipient-checkbox').forEach(cb => { cb.checked = true; });
        });
    }

    const clearRecipientsBtn = document.getElementById('clearRecipientsBtn');
    if (clearRecipientsBtn) {
        clearRecipientsBtn.addEventListener('click', function () {
            document.querySelectorAll('.recipient-checkbox').forEach(cb => { cb.checked = false; });
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

                releaseCartBtn.disabled = true;
                releaseCartBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Releasing...';

                fetch('controllers/cart/process_release.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recipients: recipients,
                        items: cartItems
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.InventoryCart.clearCart();
                        itemSearchCache.clear();
                        
                        const modal = bootstrap.Modal.getInstance(viewCartModalEl);
                        if (modal) modal.hide();
                        
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });

                        const printUrl = data.print_url || ('controllers/supplies/consumable/print_ris.php?trans_codes=' + encodeURIComponent((data.trans_codes || []).join(',')));

                        Swal.fire({
                            icon: 'success',
                            title: 'Items Released!',
                            html: '<p class="mb-0">' + data.message + '</p>',
                            showCancelButton: true,
                            confirmButtonColor: '#0D3B66',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="bi bi-printer me-1"></i> Print / View RIS',
                            cancelButtonText: 'Done',
                            allowOutsideClick: false
                        }).then(function (res) {
                            if (res.isConfirmed) {
                                window.open(printUrl, '_blank');
                            }
                            if (suppliesTable) {
                                suppliesTable.ajax.reload(null, false);
                            }
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
                })
                .finally(() => {
                    releaseCartBtn.disabled = false;
                    releaseCartBtn.innerHTML = '<i class="bi bi-send me-1"></i> Release';
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

    $(document).on('click', '.btn-print-all-stock-cards', function () {
        window.open('controllers/supplies/consumable/print_stock_card.php?all=1', '_blank');
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

    // When modal closes, reload main supplies table in-place without page reload
    $('#updateQtyModal').on('hidden.bs.modal', function () {
        if (suppliesTable) {
            suppliesTable.ajax.reload(null, false);
        }
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
                itemSearchCache.clear();

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
                if (suppliesTable) {
                    suppliesTable.ajax.reload(null, false);
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

    // Global safeguard to ensure modal backdrops never freeze the screen
    $(document).on('hidden.bs.modal', function () {
        setTimeout(function () {
            if ($('.modal.show').length === 0) {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
            }
        }, 150);
    });
});

function printRsmiReport() {
    const monthYear = document.getElementById('rsmiMonthYear').value;
    if (!monthYear) {
        alert('Please select a month and year first.');
        return;
    }
    window.open('controllers/supplies/consumable/print_rsmi_month.php?month_year=' + monthYear, '_blank');
}
