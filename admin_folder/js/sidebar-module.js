// admin_folder/js/sidebar-module.js

(function() {
    const sidebarHTML = `
        <div class="sidebar-header">
            <div class="logo-placeholder">A</div>
            <h2>Admin Panel</h2>
            <button class="mobile-toggle" id="close-sidebar">
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.html" class="nav-item" data-page="dashboard.html">
                <ion-icon name="home-outline"></ion-icon>
                <span>Dashboard</span>
            </a>
            
            <div class="nav-group">
                <div class="nav-item nav-item-has-children" id="subscribers-menu-toggle">
                    <div class="nav-item-icon-text">
                        <ion-icon name="people-outline"></ion-icon>
                        <span>Subscribers</span>
                    </div>
                    <ion-icon name="chevron-down-outline" class="chevron"></ion-icon>
                </div>
                <ul class="sub-menu" id="subscribers-submenu">
                    <li><a href="accounts.html" class="sub-item" data-page="accounts.html">Accounts</a></li>
                    <li><a href="insurance_approvals.html" class="sub-item" data-page="insurance_approvals.html">Insurance</a></li>
                    <li><a href="subscription_plans.html" class="sub-item" data-page="subscription_plans.html">Subscription Plans</a></li>
                    <li><a href="pvoucher_ledger.html" class="sub-item" data-page="pvoucher_ledger.html">P-Voucher Ledger</a></li>
                </ul>
            </div>
            
            <div class="nav-group">
                <div class="nav-item nav-item-has-children" id="pos-menu-toggle">
                    <div class="nav-item-icon-text">
                        <ion-icon name="cart-outline"></ion-icon>
                        <span>POS Management</span>
                    </div>
                    <ion-icon name="chevron-down-outline" class="chevron"></ion-icon>
                </div>
                <ul class="sub-menu" id="pos-submenu">
                    <li><a href="../point_of_sale_folder/index.html" class="sub-item" target="_blank">POS Terminal</a></li>
                    <li><a href="products.html" class="sub-item" data-page="products.html">Products</a></li>
                    <li><a href="inventory.html" class="sub-item" data-page="inventory.html">Inventory</a></li>
                    <li><a href="transactions.html" class="sub-item" data-page="transactions.html">Transactions</a></li>
                    <li><a href="reports.html" class="sub-item" data-page="reports.html">Reports</a></li>
                </ul>
            </div>
            
            <div class="nav-group">
                <div class="nav-item nav-item-has-children" id="agents-menu-toggle">
                    <div class="nav-item-icon-text">
                        <ion-icon name="briefcase-outline"></ion-icon>
                        <span>Agents</span>
                    </div>
                    <ion-icon name="chevron-down-outline" class="chevron"></ion-icon>
                </div>
                <ul class="sub-menu" id="agents-submenu">
                    <li><a href="agent_accounts.html" class="sub-item" data-page="agent_accounts.html">Manage Accounts</a></li>
                    <li><a href="../registration_folder/index.php" class="sub-item">Register Agent</a></li>
                    <li><a href="agent_ranks.html" class="sub-item" data-page="agent_ranks.html">Ranks & Qualifications</a></li>
                    <li><a href="agent_teams.html" class="sub-item" data-page="agent_teams.html">Manage Teams</a></li>
                    <li><a href="agent_applications.html" class="sub-item" data-page="agent_applications.html">Applications</a></li>
                    <li><a href="agency_documents_settings.html" class="sub-item" data-page="agency_documents_settings.html">Agency Documents</a></li>
                    <li><a href="agent_app_forms.html" class="sub-item" data-page="agent_app_forms.html">App Forms</a></li>
                    <li><a href="agent_commissions.html" class="sub-item" data-page="agent_commissions.html">Commissions</a></li>
                    <li><a href="agent_encashments.html" class="sub-item" data-page="agent_encashments.html">Encashments</a></li>
                </ul>
            </div>

            <div class="nav-group">
                <div class="nav-item nav-item-has-children" id="merchants-menu-toggle">
                    <div class="nav-item-icon-text">
                        <ion-icon name="storefront-outline"></ion-icon>
                        <span>Merchants</span>
                    </div>
                    <ion-icon name="chevron-down-outline" class="chevron"></ion-icon>
                </div>
                <ul class="sub-menu" id="merchants-submenu">
                    <li><a href="merchants.html" class="sub-item" data-page="merchants.html">All Merchants</a></li>
                    <li><a href="register_merchant.html" class="sub-item" data-page="register_merchant.html">Register Merchant</a></li>
                </ul>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="#" class="nav-item" id="logout-btn">
                <ion-icon name="log-out-outline"></ion-icon>
                <span>Logout</span>
            </a>
        </div>
    `;

    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        sidebar.innerHTML = sidebarHTML;

        // --- Active Link Detection ---
        const currentPath = window.location.pathname;
        const currentPage = currentPath.split('/').pop() || 'dashboard.html';

        const allLinks = sidebar.querySelectorAll('a.nav-item, a.sub-item');
        allLinks.forEach(link => {
            const linkPage = link.getAttribute('data-page');
            if (linkPage === currentPage) {
                link.classList.add('active');
                
                // If it's a sub-item, open its parent menu and highlight parent
                if (link.classList.contains('sub-item')) {
                    const parentMenu = link.closest('.sub-menu');
                    const parentToggle = parentMenu.previousElementSibling;
                    
                    parentMenu.classList.add('open');
                    if (parentToggle) {
                        parentToggle.classList.add('active');
                        const chevron = parentToggle.querySelector('.chevron');
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                    }
                }
            }
        });

        // --- Event Listeners ---

        // Submenu Toggles
        sidebar.querySelectorAll('.nav-item-has-children').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const submenu = toggle.nextElementSibling;
                submenu.classList.toggle('open');
                const chevron = toggle.querySelector('.chevron');
                if (chevron) {
                    chevron.style.transform = submenu.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            });
        });

        // Mobile Mobile Toggles (Close)
        const closeBtn = document.getElementById('close-sidebar');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => sidebar.classList.remove('open'));
        }

        // Logout
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'index.html';
            });
        }
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }

    // Also handle the Open button (usually outside the sidebar)
    window.addEventListener('load', () => {
        const openBtn = document.getElementById('open-sidebar');
        const sidebar = document.getElementById('sidebar');
        if (openBtn && sidebar) {
            openBtn.addEventListener('click', () => sidebar.classList.add('open'));
        }
    });

})();
