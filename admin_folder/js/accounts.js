document.addEventListener('DOMContentLoaded', () => {
    // Mobile Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const openBtn = document.getElementById('open-sidebar');
    const closeBtn = document.getElementById('close-sidebar');

    const toggleSidebar = () => {
        sidebar.classList.toggle('open');
    };

    if (openBtn && closeBtn) {
        openBtn.addEventListener('click', toggleSidebar);
        closeBtn.addEventListener('click', toggleSidebar);
    }

    // Generic Submenu Toggle logic
    document.querySelectorAll('.nav-item-has-children').forEach(toggle => {
        const submenu = toggle.nextElementSibling;
        if (submenu && submenu.classList.contains('sub-menu')) {
            toggle.addEventListener('click', () => {
                submenu.classList.toggle('open');
                const icon = toggle.querySelector('.chevron');
                if (icon) {
                    icon.style.transform = submenu.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            });
            // Ensure initial state styling for open submenu chevron
            const icon = toggle.querySelector('.chevron');
            if (icon && submenu.classList.contains('open')) {
                icon.style.transform = 'rotate(180deg)';
            }
        }
    });

    // Logout Functionality
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = 'index.html';
        });
    }

    // Accounts Table Logic
    const tabs = document.querySelectorAll('.tab');
    const tableBody = document.getElementById('accounts-table-body');
    const apiBaseUrl = '../admin_API';

    const fetchAccounts = async (status = 'All') => {
        try {
            // Show loader
            tableBody.innerHTML = `
                <tr class="loading-row">
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <div class="loader" style="margin: 0 auto; border-top-color: var(--primary-color);"></div>
                    </td>
                </tr>
            `;

            const response = await fetch(`${apiBaseUrl}/get_accounts.php?status=${status}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (response.status === 401) {
                window.location.href = 'index.html';
                return;
            }

            const data = await response.json();

            if (data.status === 'success') {
                renderTable(data.data);
            } else {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--error-color); padding: 20px;">${data.message || 'Error loading data'}</td></tr>`;
            }
        } catch (error) {
            console.error('Error fetching accounts:', error);
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">Database not initialized (Import schema.sql)</td></tr>`;
        }
    };

    const renderTable = (accounts) => {
        if (!accounts || accounts.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">No accounts found</td></tr>`;
            return;
        }

        tableBody.innerHTML = '';
        accounts.forEach(acc => {
            const tr = document.createElement('tr');
            
            // Format Status Badge
            let statusClass = 'badge-status ';
            if (acc.status === 'Active') statusClass += 'active';
            else if (acc.status === 'Pending') statusClass += 'pending';
            else if (acc.status === 'Rejected') statusClass += 'rejected';

            let statusHtml = `<span class="${statusClass}">${acc.status}</span>`;
            if (acc.approved_by_name) {
                statusHtml += `<div class="status-admin">by ${acc.approved_by_name}<br><small style="font-size: 0.75rem; color: var(--text-muted);">${acc.approved_at_formatted || ''}</small></div>`;
            }

            let insuranceClass = 'badge-status ';
            if (acc.insurance_status === 'Active') insuranceClass += 'active';
            else if (acc.insurance_status === 'Pending') insuranceClass += 'pending';
            else if (acc.insurance_status === 'N/A') insuranceClass += 'rejected';

            let insuranceHtml = `<span class="${insuranceClass}">${acc.insurance_status}</span>`;
            if (acc.insurance_approved_by_name) {
                insuranceHtml += `<div class="status-admin">by ${acc.insurance_approved_by_name}<br><small style="font-size: 0.75rem; color: var(--text-muted);">${acc.insurance_approved_at_formatted || ''}</small></div>`;
            }

            // Actions Buttons based on status
            let actionsHtml = '';
            if (acc.status === 'Pending') {
                actionsHtml += `
                    <button class="btn-action approve" title="Approve"><ion-icon name="checkmark-outline"></ion-icon></button>
                    <button class="btn-action reject" title="Reject"><ion-icon name="close-outline"></ion-icon></button>
                `;
            }
            actionsHtml += `<button class="btn-action view" title="View Details" onclick="window.location.href='view_subscriber.html?id=${acc.account_id}'"><ion-icon name="eye-outline"></ion-icon></button>`;

            tr.innerHTML = `
                <td>
                    <div class="subscriber-cell">
                        <div class="sub-avatar">${acc.avatar_initials}</div>
                        <div class="sub-info">
                            <span class="sub-name">${acc.first_name} ${acc.last_name}</span>
                            <span class="sub-email">${acc.email}</span>
                        </div>
                    </div>
                </td>
                <td class="account-id">${acc.account_id}</td>
                <td>${statusHtml}</td>
                <td>${insuranceHtml}</td>
                <td>${acc.joined_date}</td>
                <td class="actions-cell">${actionsHtml}</td>
            `;
            tableBody.appendChild(tr);
        });
    };

    // Handle Action Buttons (Event Delegation)
    tableBody.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-action');
        if (!btn) return;

        const tr = btn.closest('tr');
        if (!tr) return;
        
        const idCell = tr.querySelector('.account-id');
        if (!idCell) return;
        
        const accountId = idCell.innerText.trim();

        if (btn.classList.contains('approve')) {
            if (confirm(`Are you sure you want to APPROVE account ${accountId}?`)) {
                await updateStatus(accountId, 'Active');
            }
        } else if (btn.classList.contains('reject')) {
            if (confirm(`Are you sure you want to REJECT account ${accountId}?`)) {
                await updateStatus(accountId, 'Rejected');
            }
        }
    });

    const updateStatus = async (accountId, status) => {
        try {
            console.log('Sending update for:', accountId, 'to status:', status);
            const response = await fetch(`${apiBaseUrl}/update_subscriber_status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    account_id: accountId,
                    subscription_status: status
                })
            });

            const text = await response.text();
            console.log('Raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                alert('Server returned invalid JSON. Check console.');
                return;
            }

            if (data.status === 'success') {
                // Refresh typical view
                const activeTab = document.querySelector('.tab.active');
                fetchAccounts(activeTab ? activeTab.dataset.status : 'All');
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error updating status:', error);
            alert('Failed to update status. Check console for details.');
        }
    };

    // Tab Navigation
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active from all
            tabs.forEach(t => t.classList.remove('active'));
            // Add active to clicked
            tab.classList.add('active');
            // Fetch data
            fetchAccounts(tab.dataset.status);
        });
    });

    // Initial load
    fetchAccounts('All');
});
