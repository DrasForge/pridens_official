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

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Product Management</div>
            <a href="merchant_products.html?id=${mId}&mode=Products" class="nav-item" data-page="merchant_products.html" data-mode="Products">
                <ion-icon name="cube-outline"></ion-icon>
                <span>My Products</span>
            </a>
            <a href="merchant_categories.html?id=${mId}&mode=Products" class="nav-item" data-page="merchant_categories.html" data-mode="Products">
                <ion-icon name="albums-outline"></ion-icon>
                <span>Product Categories</span>
            </a>
            <a href="merchant_inventory.html?id=${mId}&mode=Products" class="nav-item" data-page="merchant_inventory.html" data-mode="Products">
                <ion-icon name="list-outline"></ion-icon>
                <span>Stock Inventory</span>
            </a>
            <a href="merchant_product_editor.html?merchant_id=${mId}&category=Products" class="nav-item" data-page="merchant_product_editor.html" data-cat="Products">
                <ion-icon name="add-circle-outline"></ion-icon>
                <span>Add new Product</span>
            </a>
            <a href="merchant_orders.html?id=${mId}" class="nav-item" data-page="merchant_orders.html">
                <ion-icon name="receipt-outline"></ion-icon>
                <span>Order Management</span>
            </a>

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: #facc15; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Food Management</div>
            <a href="merchant_products.html?id=${mId}&mode=Foods" class="nav-item" data-page="merchant_products.html" data-mode="Foods">
                <ion-icon name="fast-food-outline"></ion-icon>
                <span>My Menu</span>
            </a>
            <a href="merchant_categories.html?id=${mId}&mode=Foods" class="nav-item" data-page="merchant_categories.html" data-mode="Foods">
                <ion-icon name="restaurant-outline"></ion-icon>
                <span>Menu Categories</span>
            </a>
            <a href="merchant_product_editor.html?merchant_id=${mId}&category=Foods" class="nav-item" data-page="merchant_product_editor.html" data-cat="Foods">
                <ion-icon name="add-circle-outline"></ion-icon>
                <span>Add new Dish</span>
            </a>
            <a href="merchant_food_orders.html?id=${mId}" class="nav-item" data-page="merchant_food_orders.html">
                <ion-icon name="restaurant-outline"></ion-icon>
                <span>Kitchen Orders</span>
            </a>

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: #10b981; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Spot Management</div>
            <a href="merchant_spots.html?id=${mId}" class="nav-item" data-page="merchant_spots.html">
                <ion-icon name="location-outline"></ion-icon>
                <span>My Spots</span>
            </a>

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: #3b82f6; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Service Management</div>
            <a href="merchant_services.html?id=${mId}" class="nav-item" data-page="merchant_services.html" data-page2="merchant_service_editor.html">
                <ion-icon name="briefcase-outline"></ion-icon>
                <span>My Services</span>
            </a>
            <a href="merchant_service_orders.html?id=${mId}" class="nav-item" data-page="merchant_service_orders.html">
                <ion-icon name="calendar-outline"></ion-icon>
                <span>Service Monitoring</span>
            </a>
            <a href="merchant_categories.html?id=${mId}&mode=Services" class="nav-item" data-page="merchant_categories.html" data-mode="Services">
                <ion-icon name="grid-outline"></ion-icon>
                <span>Service Categories</span>
            </a>

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Marketing centre</div>
            
            <a href="${marketingUrl}" class="nav-item" data-page="merchant_marketing.html" data-page2="merchant_promo_editor.html" data-page3="merchant_voucher_editor.html">
                <ion-icon name="flash-outline"></ion-icon>
                <span>Campaigns & Vouchers</span>
            </a>
            <a href="merchant_pvoucher_ledger.html?id=${mId}" class="nav-item" data-page="merchant_pvoucher_ledger.html">
                <ion-icon name="ticket-outline"></ion-icon>
                <span>P-Voucher Redemptions</span>
            </a>

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: #f43f5e; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Store Sales</div>
            <a href="merchant_pos.html?id=${mId}" class="nav-item" data-page="merchant_pos.html">
                <ion-icon name="calculator-outline"></ion-icon>
                <span>Merchant POS Terminal</span>
            </a>

            <div style="padding: 20px 24px 10px; font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Settings</div>
            
            <a href="merchant_profile_editor.html?id=${mId}" class="nav-item" data-page="merchant_profile_editor.html">
                <ion-icon name="person-circle-outline"></ion-icon>
                <span>Master Profile</span>
            </a>
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
        const curMode = urlParams.get('mode');
        const curCat = urlParams.get('category');

        const allLinks = sidebar.querySelectorAll('a.nav-item');
        allLinks.forEach(link => {
            const linkPage = link.getAttribute('data-page');
            const linkPage2 = link.getAttribute('data-page2');
            const linkMode = link.getAttribute('data-mode');
            const linkCat = link.getAttribute('data-cat');

            let isActive = false;
            if (linkPage === currentPage || (linkPage2 && linkPage2 === currentPage)) {
                isActive = true;
                if (linkMode && linkMode !== curMode) isActive = false;
                if (linkCat && linkCat !== curCat) isActive = false;
                // Default handling for merchant_products without mode
                if (currentPage === 'merchant_products.html' && !curMode && linkMode === 'Foods') isActive = false;
            }

            if (isActive) link.classList.add('active');
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
    
    // Export to global scope so independent pages can re-init if needed
    window.initSidebar = initSidebar;

})();
