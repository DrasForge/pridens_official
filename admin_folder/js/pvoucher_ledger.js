document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const openBtn = document.getElementById('open-sidebar');
    const closeBtn = document.getElementById('close-sidebar');
    const tableBody = document.getElementById('ledger-table-body');
    const searchInput = document.getElementById('ledger-search');
    const apiBaseUrl = '../admin_API';

    // Sidebar Toggles
    if (openBtn && closeBtn) {
        openBtn.onclick = () => sidebar.classList.add('open');
        closeBtn.onclick = () => sidebar.classList.remove('open');
    }

    // Generic Submenu Toggle logic
    document.querySelectorAll('.nav-item-has-children').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const submenu = toggle.nextElementSibling;
            submenu.classList.toggle('open');
            const icon = toggle.querySelector('.chevron');
            if (icon) {
                icon.style.transform = submenu.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        });
    });

    const fetchLedger = async (search = '') => {
        try {
            tableBody.innerHTML = `
                <tr class="loading-row">
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <div class="loader" style="margin: 0 auto; border-top-color: var(--primary-color);"></div>
                    </td>
                </tr>
            `;

            const response = await fetch(`${apiBaseUrl}/get_pvoucher_ledger.php?search=${encodeURIComponent(search)}`, {
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
            console.error('Error:', error);
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">Failed to fetch ledger data</td></tr>`;
        }
    };

    const renderTable = (entries) => {
        if (!entries || entries.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">No ledger entries found</td></tr>`;
            return;
        }

        tableBody.innerHTML = '';
        entries.forEach(entry => {
            const tr = document.createElement('tr');
            
            const typeClass = entry.type === 'Credit' ? 'text-success' : 'text-danger';
            const typeIcon = entry.type === 'Credit' ? 'add-circle-outline' : 'remove-circle-outline';

            tr.innerHTML = `
                <td style="font-size: 0.9rem; color: var(--text-muted);">${entry.date_formatted}</td>
                <td>
                    <div style="font-weight: 600;">${entry.first_name} ${entry.last_name}</div>
                </td>
                <td class="account-id">${entry.account_id}</td>
                <td style="font-weight: 700;">${parseFloat(entry.amount).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</td>
                <td>
                    <span class="badge-status ${entry.type.toLowerCase()}" style="display: flex; align-items: center; gap: 4px; width: fit-content;">
                        <ion-icon name="${typeIcon}"></ion-icon>
                        ${entry.type}
                    </span>
                </td>
                <td style="color: var(--text-muted); font-size: 0.9rem;">${entry.description}</td>
            `;
            tableBody.appendChild(tr);
        });
    };

    // Debounce search
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchLedger(e.target.value);
        }, 500);
    });

    // Initial Load
    fetchLedger();

    // Logout
    document.getElementById('logout-btn').onclick = (e) => {
        e.preventDefault();
        window.location.href = 'index.html';
    };
});
