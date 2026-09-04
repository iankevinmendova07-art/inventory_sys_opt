console.log('nonconsup.js (optimized) loaded');

window.addEventListener('error', function (event) {
    console.error('Global JS error (nonconsup):', event.message, 'at', event.filename + ':' + event.lineno);
});

// Reusable Debounce Utility
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

const nonconsSearchCache = new Map();
let suppliesTable = null;

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Server-Side DataTable for Non-Consumable Supplies
    if ($.fn.DataTable && document.getElementById('suppliesTable')) {
        suppliesTable = $('#suppliesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
            ajax: {
                url: 'controllers/supplies/nonconsumable/get_nonconsumable_paginated.php',
                type: 'GET',
                error: function (xhr, error, thrown) {
                    console.error('Non-consumable DataTables Error:', thrown);
                }
            },
            columns: [
                { data: 'property_number', className: 'fw-semibold' },
                { data: 'description' },
                { data: 'unit_of_measure' },
                { data: 'item_type' },
                { data: 'formatted_unit_cost' },
                { 
                    data: 'qty_property_card',
                    render: function (data, type, row) {
                        const qty = parseInt(data, 10) || 0;
                        return type === 'display' ? '<span class="badge bg-secondary">' + qty + '</span>' : qty;
                    }
                },
                { data: 'created_at' },
                { data: 'recepient' },
                { data: 'remarks' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        const escapedDesc = (row.description || '').replace(/"/g, '&quot;');
                        const escapedPropNum = (row.property_number || '').replace(/"/g, '&quot;');
                        const escapedUnit = (row.unit_of_measure || '').replace(/"/g, '&quot;');
                        const escapedCategory = (row.item_type || '').replace(/"/g, '&quot;');
                        const escapedRemarks = (row.remarks || '').replace(/"/g, '&quot;');
                        const escapedRecipient = (row.recepient || '').replace(/"/g, '&quot;');

                        return '<div class="d-flex gap-1">' +
                            '<button class="btn btn-sm btn-primary edit-btn" ' +
                                'data-id="' + row.id + '" ' +
                                'data-property_number="' + escapedPropNum + '" ' +
                                'data-description="' + escapedDesc + '" ' +
                                'data-unit="' + escapedUnit + '" ' +
                                'data-category="' + escapedCategory + '" ' +
                                'data-unit_cost="' + row.unit_cost + '" ' +
                                'data-total_cost="' + row.total_cost + '" ' +
                                'data-qty_property_card="' + row.qty_property_card + '" ' +
                                'data-qty_physical_count="' + row.qty_physical_count + '" ' +
                                'data-shortage_overage_qty="' + row.shortage_overage_qty + '" ' +
                                'data-shortage_overage_value="' + row.shortage_overage_value + '" ' +
                                'data-remarks="' + escapedRemarks + '" ' +
                                'data-recipient="' + escapedRecipient + '" ' +
                                'title="Edit"><i class="bi bi-pencil-square"></i></button>' +
                            '<button class="btn btn-sm btn-danger delete-btn" data-id="' + row.id + '" title="Delete"><i class="bi bi-trash"></i></button>' +
                            '<button class="btn btn-sm btn-secondary print-btn" ' +
                                'data-id="' + row.id + '" ' +
                                'data-category="' + escapedCategory + '" ' +
                                'data-unit_cost="' + row.unit_cost + '" ' +
                                'data-total_cost="' + row.total_cost + '" ' +
                                'title="Print Item"><i class="bi bi-printer"></i></button>' +
                            '</div>';
                    }
                }
            ],
            order: [[5, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search items..."
            }
        });
    }

    // 2. Add Supply Button AJAX Submission (In-Place Reload)
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

            fetch('controllers/supplies/nonconsumable/add_supply.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const modalEl = document.getElementById('addItemModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    form.reset();

                    nonconsSearchCache.clear();

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
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Server error', confirmButtonColor: '#0D3B66' });
                }
            })
            .catch(error => { 
                console.error('Add non-consumable error:', error); 
                Swal.fire({ icon: 'error', title: 'Server Error', text: error.message || 'Something went wrong.', confirmButtonColor: '#0D3B66' }); 
            })
            .finally(() => {
                saveSupplyBtn.disabled = false;
                saveSupplyBtn.textContent = 'Save Item';
            });
        });
    }

    // 3. Edit Button Handler
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        let propertyNumber = $(this).data('property_number');
        let description = $(this).data('description');
        let unit = $(this).data('unit');
        let category = $(this).data('category');
        let unitCost = $(this).data('unit_cost');
        let totalCost = $(this).data('total_cost');
        let qtyCard = $(this).data('qty_property_card');
        let qtyCount = $(this).data('qty_physical_count');
        let shortageQty = $(this).data('shortage_overage_qty');
        let shortageVal = $(this).data('shortage_overage_value');
        let remarks = $(this).data('remarks');
        let recipient = $(this).data('recipient');

        $('#editId').val(id);
        $('#editPropertyNumber').val(propertyNumber);
        $('#editDescription').val(description);
        $('#editUnitOfMeasure').val(unit);
        $('#editCategory').val(category);
        $('#editUnitCost').val(unitCost);
        $('#editTotalCost').val(totalCost);
        $('#editQtyPropertyCard').val(qtyCard);
        $('#editQtyPhysicalCount').val(qtyCount);
        $('#editShortageOverageQty').val(shortageQty);
        $('#editShortageOverageValue').val(shortageVal);
        $('#editRemarks').val(remarks);
        $('#editRecipient').val(recipient);

        const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
        editModal.show();
    });

    // 4. Update Supply Button (In-Place Reload)
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

            fetch('controllers/supplies/nonconsumable/update_supply.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') { 
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editItemModal')); 
                    if (modal) modal.hide(); 

                    nonconsSearchCache.clear();

                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Success', 
                        text: data.message, 
                        timer: 1500,
                        showConfirmButton: false 
                    });

                    if (suppliesTable) {
                        suppliesTable.ajax.reload(null, false);
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#0D3B66' });
                }
            })
            .catch(err => { 
                console.error(err); 
                Swal.fire({ icon: 'error', title: 'Server Error' }); 
            })
            .finally(() => {
                updateSupplyBtn.disabled = false;
                updateSupplyBtn.textContent = 'Save Changes';
            });
        });
    }

    // 5. Delete Button Handler (In-Place Reload)
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
        }).then(result => {
            if (result.isConfirmed) {
                const formData = new FormData(); 
                formData.append('id', supplyId);

                fetch('controllers/supplies/nonconsumable/delete_supply.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => { 
                    if (data.status === 'success') { 
                        nonconsSearchCache.clear();
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message, timer: 1200, showConfirmButton: false }); 
                        if (suppliesTable) {
                            suppliesTable.ajax.reload(null, false);
                        }
                    } else { 
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message }); 
                    } 
                })
                .catch(err => { 
                    console.error(err); 
                    Swal.fire({ icon: 'error', title: 'Server Error' }); 
                });
            }
        });
    });
});

// 6. Debounced Search in Release / Cart Modals
$(document).ready(function() {
    $('#cartPropertyNumber').on('input', debounce(function() {
        let propertyNumber = $(this).val().trim();

        if (propertyNumber === '') {
            clearCartModalFields();
            return;
        }

        if (nonconsSearchCache.has(propertyNumber)) {
            populateCartNonconsFields(nonconsSearchCache.get(propertyNumber));
            return;
        }

        $.ajax({
            url: 'controllers/supplies/nonconsumable/search_supply.php',
            type: 'GET',
            data: { property_number: propertyNumber },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    nonconsSearchCache.set(propertyNumber, response.data);
                    populateCartNonconsFields(response.data);
                } else {
                    nonconsSearchCache.set(propertyNumber, null);
                    clearCartModalFields();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                clearCartModalFields();
            }
        });
    }, 300));

    function populateCartNonconsFields(item) {
        if (!item) {
            clearCartModalFields();
            return;
        }
        $('#cartDescription').val(item.description || '');
        $('#cartUnitOfMeasure').val(item.unit_of_measure || '');
        $('#cartCategory').val(item.category || '');
        $('#cartUnitCost').val(item.unit_cost || '');
        $('#cartTotalCost').val(item.total_cost || '');
        $('#cartQtyPropertyCard').val(item.qty_property_card || '0');
        $('#cartQtyPhysicalCount').val(item.qty_physical_count || '0');
        $('#cartShortageOverageQty').val(item.shortage_overage_qty || '0');
        $('#cartShortageOverageValue').val(item.shortage_overage_value || '0.00');
        $('#cartRemarks').val(item.remarks || '');
        $('#cartRecipient').val(item.recepient || item.emp_name || '');
    }

    function clearCartModalFields() {
        $('#cartDescription').val('');
        $('#cartUnitOfMeasure').val('');
        $('#cartCategory').val('');
        $('#cartUnitCost').val('');
        $('#cartTotalCost').val('');
        $('#cartQtyPropertyCard').val('0');
        $('#cartQtyPhysicalCount').val('0');
        $('#cartShortageOverageQty').val('0');
        $('#cartShortageOverageValue').val('0.00');
        $('#cartRemarks').val('');
        $('#cartRecipient').val('');
    }
});

// 7. Handle Print Button Click using event delegation
$(document).on('click', '.print-btn', function () {
    const supplyId = $(this).data('id');
    const category = $(this).data('category');
    const unitCost = parseFloat($(this).data('unit_cost') || 0);
    const totalCost = parseFloat($(this).data('total_cost') || 0);
    $(this).blur();

    const normalizedCategory = category ? category.trim().toUpperCase() : '';
    const highValueCategories = ['BUILDINGS', 'SCHOOL BUILDINGS', 'PARK, PLAZAS AND MONUMENTS'];
    const categoryMatch = highValueCategories.includes(normalizedCategory);
    const costCheck = unitCost >= 50000.00 || totalCost >= 50000.00;
    const usePropertyCard = categoryMatch || costCheck;

    if (usePropertyCard) {
        window.open(`controllers/supplies/nonconsumable/print_property_card.php?id=${supplyId}`, '_blank');
    } else {
        window.open(`controllers/supplies/nonconsumable/print_supply.php?id=${supplyId}`, '_blank');
    }
});

// 8. Money Input Formatting
(function () {
    function formatMoneyInput(el) {
        if (!el) return;
        var raw = el.value;
        var cleaned = raw.replace(/[^\d.]/g, '');
        var firstDot = cleaned.indexOf('.');
        var intPart, decPart;
        if (firstDot === -1) {
            intPart = cleaned;
            decPart = '';
        } else {
            intPart = cleaned.slice(0, firstDot);
            decPart = cleaned.slice(firstDot + 1).replace(/\./g, '').slice(0, 2);
        }
        intPart = intPart.replace(/^0+(?=\d)/, '');
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        el.value = firstDot === -1 ? intPart : (intPart + '.' + decPart);
    }

    function stripCommas(el) {
        if (el) el.value = el.value.replace(/,/g, '');
    }

    document.querySelectorAll('.money-input').forEach(function (el) {
        el.addEventListener('input', function () { formatMoneyInput(el); });
    });

    var editModal = document.getElementById('editItemModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function () {
            formatMoneyInput(document.getElementById('editUnitCost'));
            formatMoneyInput(document.getElementById('editTotalCost'));
        });
    }

    document.addEventListener('click', function (e) {
        var saveBtn = e.target.closest && e.target.closest('#saveSupplyBtn');
        var updateBtn = e.target.closest && e.target.closest('#updateSupplyBtn');
        if (saveBtn) {
            stripCommas(document.getElementById('unitCost'));
            stripCommas(document.getElementById('totalCost'));
        }
        if (updateBtn) {
            stripCommas(document.getElementById('editUnitCost'));
            stripCommas(document.getElementById('editTotalCost'));
        }
    }, true);
})();