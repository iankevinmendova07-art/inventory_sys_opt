console.log('nonconsup.js loaded');

window.addEventListener('error', function (event) {
    console.error('Global JS error (nonconsup):', event.message, 'at', event.filename + ':' + event.lineno);
});

document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable) {
        $('#suppliesTable').DataTable({
            responsive: true,
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            order: [[5, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search items..."
            }
        });
    }

    const saveSupplyBtn = document.getElementById('saveSupplyBtn');
    if (saveSupplyBtn) {
        saveSupplyBtn.addEventListener('click', function () {
            const form = document.getElementById('addItemForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const formData = new FormData(form);

            fetch('controllers/supplies/nonconsumable/add_supply.php', { method: 'POST', body: formData })
            .then(response => response.text().then(text => ({ ok: response.ok, text })))
            .then(({ ok, text }) => {
                let data;
                try { data = JSON.parse(text); } catch (e) { console.error('Noncons add returned non-JSON:', text); throw e; }
                if (data.status === 'success') {
                    const modalEl = document.getElementById('addItemModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    form.reset();
                    Swal.fire({ icon: 'success', title: 'Success!', text: data.message, confirmButtonColor: '#0D3B66' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Server error', confirmButtonColor: '#0D3B66' });
                }
            })
            .catch(error => { console.error('Add non-consumable error:', error); Swal.fire({ icon: 'error', title: 'Server Error', text: error.message || 'Something went wrong.', confirmButtonColor: '#0D3B66' }); });
        });
    }

  // Example snippet inside your nonconsup.js edit button click handler
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
    let recipient = $(this).data('recipient'); // Database recipient value

    // Populate the modal fields
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
    
    // Set the selected value for the recipient dropdown
    $('#editRecipient').val(recipient);

    // Show the modal
    $('#editItemModal').modal('show');
});

    const updateSupplyBtn = document.getElementById('updateSupplyBtn');
    if (updateSupplyBtn) {
        updateSupplyBtn.addEventListener('click', function () {
            const form = document.getElementById('editItemForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const formData = new FormData(form);
            fetch('controllers/supplies/nonconsumable/update_supply.php', { method: 'POST', body: formData })
            .then(r => r.json()).then(data => {
                if (data.status === 'success') { const modal = bootstrap.Modal.getInstance(document.getElementById('editItemModal')); if (modal) modal.hide(); Swal.fire({ icon: 'success', title: 'Success', text: data.message, confirmButtonColor: '#0D3B66' }).then(()=> location.reload()); }
                else Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#0D3B66' });
            }).catch(err=>{ console.error(err); Swal.fire({ icon: 'error', title: 'Server Error' }); });
        });
    }

    $(document).on('click', '.delete-btn', function () {
        const supplyId = $(this).data('id');
        Swal.fire({ title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#0D3B66', confirmButtonText: 'Yes, delete it!' })
        .then(result => {
            if (result.isConfirmed) {
                const formData = new FormData(); formData.append('id', supplyId);
                fetch('controllers/supplies/nonconsumable/delete_supply.php', { method: 'POST', body: formData })
                .then(r=>r.json()).then(data=>{ if (data.status==='success') { Swal.fire({ icon:'success', title:'Deleted!', text:data.message, confirmButtonColor:'#0D3B66'}).then(()=>location.reload()); } else Swal.fire({ icon:'error', title:'Error', text:data.message }); })
                .catch(err=>{ console.error(err); Swal.fire({ icon:'error', title:'Server Error' }); });
            }
        });
    });
});

$(document).ready(function() {
    $('#cartPropertyNumber').on('input', function() {
        let propertyNumber = $(this).val().trim();
        console.log("Searching for property number:", propertyNumber);

        if (propertyNumber === '') {
            clearCartModalFields();
            return;
        }

        $.ajax({
            url: 'controllers/supplies/nonconsumable/search_supply.php',
            type: 'GET',
            data: { property_number: propertyNumber },
            dataType: 'json',
            success: function(response) {
                console.log("AJAX Response:", response);
                if (response.success && response.data) {
                    let item = response.data;
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
                } else {
                    clearCartModalFields();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr.responseText);
                clearCartModalFields();
            }
        });
    });

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
// Handle Print Button Click using event delegation for DataTables
$(document).on('click', '.print-btn', function () {
    const supplyId = $(this).data('id');
    const category = $(this).data('category');
    const unitCost = parseFloat($(this).data('unit_cost') || 0);
    const totalCost = parseFloat($(this).data('total_cost') || 0);
    $(this).blur();

    // Debug logging
    console.log('Print button clicked:', { supplyId, category, unitCost, totalCost });

    // Normalize category to handle case sensitivity and whitespace
    const normalizedCategory = category ? category.trim().toUpperCase() : '';
    
    // Check if item is high-value infrastructure item by category
    const highValueCategories = ['BUILDINGS', 'SCHOOL BUILDINGS', 'PARK, PLAZAS AND MONUMENTS'];
    const categoryMatch = highValueCategories.includes(normalizedCategory);
    
    // Check if item is high-value by cost (Unit Cost OR Total Cost >= 50,000)
    const costCheck = unitCost >= 50000.00 || totalCost >= 50000.00;
    
    // Use Property Card if category matches OR if either cost is >= 50,000
    const usePropertyCard = categoryMatch || costCheck;

    console.log('Normalized category:', normalizedCategory);
    console.log('Category match:', categoryMatch);
    console.log('Cost check:', costCheck, '(unitCost:', unitCost, ', totalCost:', totalCost, ')');
    console.log('Use Property Card:', usePropertyCard);

    // Open appropriate print format based on category and cost
    if (usePropertyCard) {
        console.log('Opening PROPERTY CARD format');
        window.open(`controllers/supplies/nonconsumable/print_property_card.php?id=${supplyId}`, '_blank');
    } else {
        console.log('Opening ICS format');
        window.open(`controllers/supplies/nonconsumable/print_supply.php?id=${supplyId}`, '_blank');
    }
});
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

    // Reformat Edit modal fields once they're populated and shown
    var editModal = document.getElementById('editItemModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function () {
            formatMoneyInput(document.getElementById('editUnitCost'));
            formatMoneyInput(document.getElementById('editTotalCost'));
        });
    }

    // Strip commas before the existing Add/Update click handlers (in nonconsup.js)
    // read these values, using the capture phase so this always runs first.
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