// admin_folder/js/marketplace-sidebar.js

(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const mId = urlParams.get('merchant_id') || urlParams.get('id') || urlParams.get('p_id'); 
    
    // We need the merchant ID to keep context in the links
    const baseUrl = `merchant_products.html?id=${mId}`;
    const editorUrl = `merchant_product_editor.html?merchant_id=${mId}`;
    const marketingUrl = `merchant_marketing.html?id=${mId}`;

    const sidebarHTML = `
        <div class="sidebar-header">
            <div class="logo-placeholder" style="background:#ee4d2d;">M</div>
            <h2>Marketplace</h2>
            <button class="mobile-toggle" id="close-sidebar">
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <a href="merchants.html" class="nav-item">
                <ion-icon name="arrow-back-outline"></ion-icon>
                <span>Back to Admin</span>
            </a>

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Seller center</div>

            <div style="padding: 10px 24px 5px; font-size: 0.85rem; color: #fff; font-weight: 700; display:flex; align-items:center; gap:8px;">
                <ion-icon name="bag-handle-outline" style="color:#6366f1;"></ion-icon>
                Products
            </div>
            
            <a href="${baseUrl}" class="nav-item" data-page="merchant_products.html" style="padding-left:44px;">
                <ion-icon name="cube-outline"></ion-icon>
                <span>My Products</span>
            </a>

            <a href="merchant_categories.html?id=${mId}" class="nav-item" data-page="merchant_categories.html" style="padding-left:44px;">
                <ion-icon name="albums-outline"></ion-icon>
                <span>My Categories</span>
            </a>

            <a href="merchant_inventory.html?id=${mId}" class="nav-item" data-page="merchant_inventory.html" style="padding-left:44px;">
                <ion-icon name="list-outline"></ion-icon>
                <span>Stock Inventory</span>
            </a>

            <a href="${editorUrl}" class="nav-item" data-page="merchant_product_editor.html" style="padding-left:44px;">
                <ion-icon name="add-circle-outline"></ion-icon>
                <span>Add new Product</span>
            </a>

            <a href="merchant_orders.html?id=${mId}" class="nav-item" data-page="merchant_orders.html" style="padding-left:44px;">
                <ion-icon name="receipt-outline"></ion-icon>
                <span>Order Management</span>
            </a>

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Marketing centre</div>
            
            <a href="${marketingUrl}" class="nav-item" data-page="merchant_marketing.html" data-page2="merchant_promo_editor.html" data-page3="merchant_voucher_editor.html">
                <ion-icon name="flash-outline"></ion-icon>
                <span>Campaigns & Vouchers</span>
            </a>

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Settings</div>
            
            <a href="merchant_shop_settings.html?id=${mId}" class="nav-item" data-page="merchant_shop_settings.html">
                <ion-icon name="settings-outline"></ion-icon>
                <span>Shop Settings</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="merchants.html" class="nav-item">
                <ion-icon name="exit-outline"></ion-icon>
                <span>Exit Manager</span>
            </a>
        </div>
    `;

    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        sidebar.innerHTML = sidebarHTML;

        // --- Active Link Detection ---
        const currentPath = window.location.pathname;
        const currentPage = currentPath.split('/').pop();

        const allLinks = sidebar.querySelectorAll('a.nav-item');
        allLinks.forEach(link => {
            const linkPage = link.getAttribute('data-page');
            const linkPage2 = link.getAttribute('data-page2');
            if (linkPage === currentPage || linkPage2 === currentPage) {
                link.classList.add('active');
            }
        });

        // Mobile Mobile Toggles (Close)
        const closeBtn = document.getElementById('close-sidebar');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => sidebar.classList.remove('open'));
        }
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }

    // Handle Open button
    window.addEventListener('load', () => {
        const openBtn = document.getElementById('open-sidebar');
        const sidebar = document.getElementById('sidebar');
        if (openBtn && sidebar) {
            openBtn.addEventListener('click', () => sidebar.classList.add('open'));
        }
    });

})();
