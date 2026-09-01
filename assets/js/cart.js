(function () {
    const CART_STORAGE_KEY = 'inventory_consumable_cart';

    function readCart() {
        try {
            const stored = localStorage.getItem(CART_STORAGE_KEY);
            const parsed = stored ? JSON.parse(stored) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            console.error('Failed to read cart:', error);
            return [];
        }
    }

    function writeCart(items) {
        localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
        updateCartBadge(items.length);
        document.dispatchEvent(new CustomEvent('cartUpdated', { detail: { items } }));
    }

    function updateCartBadge(count) {
        const badge = document.getElementById('cartBadge');
        if (!badge) return;

        badge.textContent = count;
        badge.classList.toggle('d-none', count === 0);
    }

    function getCartCount() {
        return readCart().length;
    }

    function addItem(item) {
        const cart = readCart();
        const existingIndex = cart.findIndex(function (entry) {
            return entry.supplyId === item.supplyId;
        });

        if (existingIndex >= 0) {
            cart[existingIndex].qty += item.qty;
            cart[existingIndex].maxQty = item.maxQty;
        } else {
            cart.push(item);
        }

        writeCart(cart);
        return cart;
    }

    function removeItem(supplyId) {
        const cart = readCart().filter(function (entry) {
            return entry.supplyId !== supplyId;
        });
        writeCart(cart);
        return cart;
    }

    function clearCart() {
        writeCart([]);
        return [];
    }

    function renderCartItems(container) {
        if (!container) return;

        const cart = readCart();
        container.innerHTML = '';

        if (cart.length === 0) {
            container.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Your cart is empty.</td></tr>';
            return;
        }

        cart.forEach(function (item) {
            const row = document.createElement('tr');
            row.innerHTML =
                '<td class="fw-semibold">' + escapeHtml(item.itemCode) + '</td>' +
                '<td>' + escapeHtml(item.itemName) + '</td>' +
                '<td>' + escapeHtml(item.unit) + '</td>' +
                '<td class="text-center fw-bold">' + item.qty + '</td>' +
                '<td class="text-end">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-cart-item" data-supply-id="' + item.supplyId + '">' +
                        '<i class="bi bi-trash"></i>' +
                    '</button>' +
                '</td>';
            container.appendChild(row);
        });
    }

    function renderRecipients(container, employees, selectedNames) {
        if (!container) return;

        container.innerHTML = '';

        if (!employees.length) {
            container.innerHTML = '<p class="text-muted mb-0">No employees found. Add employees in Settings first.</p>';
            return;
        }

        employees.forEach(function (employee) {
            const isChecked = selectedNames.indexOf(employee.emp_name) >= 0;
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check recipient-check mb-2';
            wrapper.innerHTML =
                '<input class="form-check-input recipient-checkbox" type="checkbox" value="' + escapeHtml(employee.emp_name) + '" id="recipient-' + employee.id + '"' + (isChecked ? ' checked' : '') + '>' +
                '<label class="form-check-label" for="recipient-' + employee.id + '">' +
                    escapeHtml(employee.emp_name) + ' <span class="text-muted">(' + escapeHtml(employee.emp_position) + ')</span>' +
                '</label>';
            container.appendChild(wrapper);
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    window.InventoryCart = {
        readCart: readCart,
        addItem: addItem,
        removeItem: removeItem,
        clearCart: clearCart,
        renderCartItems: renderCartItems,
        renderRecipients: renderRecipients,
        updateCartBadge: function () {
            updateCartBadge(getCartCount());
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        updateCartBadge(getCartCount());
    });
})();
