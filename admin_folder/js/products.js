// admin_folder/js/products.js

document.addEventListener('DOMContentLoaded', () => {
    // === State ===
    let products = [];
    let isEditing = false;

    // === DOM Elements ===
    const productsTbody = document.getElementById('products-tbody');
    const productSearch = document.getElementById('product-search');
    const addProductBtn = document.getElementById('add-product-btn');
    const productModal = document.getElementById('product-modal');
    const closeModalBtn = document.getElementById('close-modal');
    const btnCancel = document.getElementById('btn-cancel');
    const productForm = document.getElementById('product-form');
    const modalTitle = document.getElementById('modal-title');
    
    // Form Inputs
    const productIdInput = document.getElementById('product-id');
    const skuInput = document.getElementById('sku');
    const nameInput = document.getElementById('product-name');
    const categoryInput = document.getElementById('category');
    const priceInput = document.getElementById('product-price');
    const basePriceInput = document.getElementById('product-base-price');
    const statusInput = document.getElementById('status');

    // === Initial Load ===
    fetchProducts();

    // === Event Listeners ===
    addProductBtn.addEventListener('click', () => showModal());
    closeModalBtn.addEventListener('click', hideModal);
    btnCancel.addEventListener('click', hideModal);
    
    productSearch.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        filterProducts(query);
    });

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveProduct();
    });

    // Close modal on outside click
    window.addEventListener('click', (e) => {
        if (e.target === productModal) hideModal();
    });

    // === Functions ===

    async function fetchProducts() {
        try {
            const res = await fetch('../admin_API/get_pos_products.php');
            const r = await res.json();
            if (r.success) {
                products = r.data;
                renderProducts(products);
            } else {
                showError('Failed to load products: ' + r.error);
            }
        } catch (e) {
            showError('Connection error while fetching products');
        }
    }

    function renderProducts(data) {
        if (data.length === 0) {
            productsTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">No products found.</td></tr>';
            return;
        }

        productsTbody.innerHTML = data.map(p => `
            <tr>
                <td style="font-family: monospace; font-weight: 600;">${p.sku}</td>
                <td style="font-weight: 600; color: white;">${p.name}</td>
                <td><span style="font-size: 12px; color: var(--text-muted);">${p.category}</span></td>
                <td style="font-weight: 600; color: #4ade80;">₱${parseFloat(p.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                <td>
                    <span class="status-badge status-${p.status.toLowerCase()}">${p.status}</span>
                </td>
                <td>
                    <div class="actions-cell">
                        <button class="btn-action view" onclick="editProduct(${p.id})" title="Edit Product">
                            <ion-icon name="create-outline"></ion-icon>
                        </button>
                        <button class="btn-action reject" onclick="deactivateProduct(${p.id})" title="Deactivate Product">
                            <ion-icon name="trash-outline"></ion-icon>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function filterProducts(query) {
        const filtered = products.filter(p => 
            p.sku.toLowerCase().includes(query) || 
            p.name.toLowerCase().includes(query) ||
            p.category.toLowerCase().includes(query)
        );
        renderProducts(filtered);
    }

    function showModal(product = null) {
        isEditing = !!product;
        modalTitle.textContent = isEditing ? 'Edit Product' : 'Add New Product';
        
        if (isEditing) {
            productIdInput.value = product.id;
            skuInput.value = product.sku;
            nameInput.value = product.name;
            categoryInput.value = product.category;
            priceInput.value = product.price;
            basePriceInput.value = product.base_price;
            statusInput.value = product.status;
            skuInput.readOnly = true; // SKU shouldn't change easily
        } else {
            productForm.reset();
            productIdInput.value = '';
            skuInput.readOnly = false;
            statusInput.value = 'Active';
        }

        productModal.style.display = 'flex';
    }

    function hideModal() {
        productModal.style.display = 'none';
    }

    async function saveProduct() {
        const payload = {
            id: productIdInput.value || null,
            sku: skuInput.value.trim(),
            name: nameInput.value.trim(),
            category: categoryInput.value,
            price: priceInput.value,
            base_price: basePriceInput.value,
            status: statusInput.value
        };

        const btnSave = document.getElementById('btn-save-product');
        const originalText = btnSave.innerHTML;
        btnSave.disabled = true;
        btnSave.innerHTML = '<div class="loader"></div>';

        try {
            const res = await fetch('../admin_API/save_pos_product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const r = await res.json();
            
            if (r.success) {
                hideModal();
                fetchProducts(); // Refresh list
            } else {
                alert('Error: ' + r.error);
            }
        } catch (e) {
            alert('Connection error while saving product');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = originalText;
        }
    }

    window.editProduct = (id) => {
        const product = products.find(p => p.id == id);
        if (product) showModal(product);
    };

    window.deactivateProduct = async (id) => {
        if (!confirm('Are you sure you want to deactivate this product? It will no longer appear in the POS.')) return;

        try {
            const res = await fetch('../admin_API/delete_pos_product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const r = await res.json();
            if (r.success) {
                fetchProducts();
            } else {
                alert('Error: ' + r.error);
            }
        } catch (e) {
            alert('Connection error while deactivating product');
        }
    };

    function showError(msg) {
        productsTbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--error-color);">${msg}</td></tr>`;
    }
});
