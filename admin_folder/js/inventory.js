// admin_folder/js/inventory.js

document.addEventListener('DOMContentLoaded', () => {
    // === State ===
    let products = [];

    // === DOM Elements ===
    const inventoryTbody = document.getElementById('inventory-tbody');
    const inventorySearch = document.getElementById('inventory-search');
    const refreshBtn = document.getElementById('refresh-inventory');
    const lowStockBadge = document.getElementById('low-stock-count');
    
    // Modal Elements
    const stockModal = document.getElementById('stock-modal');
    const closeModalBtn = document.getElementById('close-modal');
    const btnCancel = document.getElementById('btn-cancel');
    const stockForm = document.getElementById('stock-form');
    const modalProductName = document.getElementById('modal-product-name');
    const modalCurrentStock = document.getElementById('modal-current-stock');
    const modalProductId = document.getElementById('modal-product-id');
    const stockChangeInput = document.getElementById('stock-change');
    const adjustmentTypeSelect = document.getElementById('adjustment-type');
    const notesInput = document.getElementById('notes');

    // === Initial Load ===
    fetchInventory();

    // === Event Listeners ===
    refreshBtn.addEventListener('click', fetchInventory);
    closeModalBtn.addEventListener('click', hideModal);
    btnCancel.addEventListener('click', hideModal);
    
    inventorySearch.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        filterInventory(query);
    });

    stockForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await updateStock();
    });

    // Close modal on outside click
    window.addEventListener('click', (e) => {
        if (e.target === stockModal) hideModal();
    });

    // === Functions ===

    async function fetchInventory() {
        try {
            const res = await fetch('../admin_API/get_inventory.php');
            const r = await res.json();
            if (r.success) {
                products = r.data;
                renderInventory(products);
                updateLowStockBadge();
            } else {
                showError('Failed to load inventory: ' + r.error);
            }
        } catch (e) {
            showError('Connection error while fetching inventory');
        }
    }

    function renderInventory(data) {
        if (data.length === 0) {
            inventoryTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">No products found in inventory.</td></tr>';
            return;
        }

        inventoryTbody.innerHTML = data.map(p => {
            const stock = parseInt(p.stock_quantity);
            const threshold = parseInt(p.low_stock_threshold);
            
            let statusClass = 'badge-good';
            let statusText = 'Healthy';
            let stockClass = 'stock-healthy';

            if (stock <= 0) {
                statusClass = 'badge-out';
                statusText = 'Out of Stock';
                stockClass = 'stock-none';
            } else if (stock <= threshold) {
                statusClass = 'badge-low';
                statusText = 'Low Stock';
                stockClass = 'stock-low';
            }

            return `
                <tr>
                    <td style="font-family: monospace;">${p.sku}</td>
                    <td>
                        <div style="font-weight: 600; color: white;">${p.name}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">${p.category}</div>
                    </td>
                    <td>
                        <span class="stock-value ${stockClass}">${stock}</span>
                        <span style="font-size: 12px; color: var(--text-muted); margin-left: 4px;">units</span>
                    </td>
                    <td style="color: var(--text-muted);">${threshold}</td>
                    <td>
                        <span class="inventory-badge ${statusClass}">
                            <ion-icon name="${statusText === 'Healthy' ? 'checkmark-circle' : (statusText === 'Low Stock' ? 'alert-circle' : 'close-circle')}"></ion-icon>
                            ${statusText}
                        </span>
                    </td>
                    <td>
                        <button class="btn-stock-adjust" onclick="openAdjustModal(${p.id})">
                            <ion-icon name="add-outline"></ion-icon> Update Stock
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function filterInventory(query) {
        const filtered = products.filter(p => 
            p.sku.toLowerCase().includes(query) || 
            p.name.toLowerCase().includes(query)
        );
        renderInventory(filtered);
    }

    function updateLowStockBadge() {
        const lowStockCount = products.filter(p => parseInt(p.stock_quantity) <= parseInt(p.low_stock_threshold)).length;
        lowStockBadge.textContent = lowStockCount;
        lowStockBadge.style.display = lowStockCount > 0 ? 'block' : 'none';
        
        // Add pulse animation if low stock
        if (lowStockCount > 0) {
            lowStockBadge.parentElement.classList.add('pulse');
        } else {
            lowStockBadge.parentElement.classList.remove('pulse');
        }
    }

    window.openAdjustModal = (id) => {
        const product = products.find(p => p.id == id);
        if (!product) return;

        modalProductId.value = product.id;
        modalProductName.textContent = product.name;
        modalCurrentStock.textContent = `Current: ${product.stock_quantity} units`;
        stockChangeInput.value = '';
        notesInput.value = '';
        adjustmentTypeSelect.value = 'Restock';
        
        stockModal.style.display = 'flex';
        stockChangeInput.focus();
    };

    function hideModal() {
        stockModal.style.display = 'none';
    }

    async function updateStock() {
        const payload = {
            product_id: modalProductId.value,
            change_amount: stockChangeInput.value,
            action_type: adjustmentTypeSelect.value,
            notes: notesInput.value,
            admin_id: 1 // TODO: Get from session
        };

        const btnSave = document.getElementById('btn-save-stock');
        const originalText = btnSave.innerHTML;
        btnSave.disabled = true;
        btnSave.innerHTML = '<div class="loader"></div>';

        try {
            const res = await fetch('../admin_API/update_stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const r = await res.json();
            
            if (r.success) {
                hideModal();
                fetchInventory(); // Refresh list
            } else {
                alert('Error: ' + r.error);
            }
        } catch (e) {
            alert('Connection error while updating stock');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = originalText;
        }
    }

    function showError(msg) {
        inventoryTbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--error-color);">${msg}</td></tr>`;
    }
});
