// js/insurance_approvals.js
document.addEventListener('DOMContentLoaded', () => {
    const apiBaseUrl = '../admin_API';
    let currentTab = 'Pending';
    let selectedAccountId = null;

    // Elements
    const stats = {
        pending: document.getElementById('statPending'),
        ready: document.getElementById('statReady'),
        active: document.getElementById('statActive')
    };
    const approvalsBody = document.getElementById('approvalsBody');
    const tableTitle = document.getElementById('tableTitle');
    const tableHead = document.getElementById('tableHead');
    const docInput = document.getElementById('documentInput');

    const btnTabPending = document.getElementById('btnTabPending');
    const btnTabApproved = document.getElementById('btnTabApproved');

    async function loadData() {
        try {
            const res = await fetch(`${apiBaseUrl}/get_insurance_approvals.php`);
            const data = await res.json();

            if (data.status === 'success') {
                updateStats(data.stats);
                renderTable(currentTab === 'Pending' ? data.pending_list : data.approved_list);
            }
        } catch (e) {
            console.error('Error loading data:', e);
        }
    }

    function updateStats(s) {
        stats.pending.innerText = s.pending;
        stats.ready.innerText = s.ready;
        stats.active.innerText = s.active;
    }

    function renderTable(list) {
        approvalsBody.innerHTML = '';
        
        if (currentTab === 'Pending') {
            tableTitle.innerText = 'Pending Insurance Approvals';
            tableHead.innerHTML = `
                <th class="col-subscriber">Subscriber</th>
                <th class="col-member">Member ID</th>
                <th class="col-plan">Plan</th>
                <th class="col-receipt">Receipt #</th>
                <th class="col-doc" style="text-align:center;">Document</th>
                <th class="col-actions">Actions</th>
            `;

            list.forEach(row => {
                const tr = document.createElement('tr');
                const isReady = row.insurance_ready_for_approval == 1;
                
                tr.innerHTML = `
                    <td>
                        <div style="font-weight:600;">${row.first_name} ${row.last_name}</div>
                        <div style="font-size:12px; color:var(--text-muted);">${row.email}</div>
                    </td>
                    <td>${row.account_id}</td>
                    <td>${row.plan_name}</td>
                    <td>${row.sales_invoice_number}</td>
                    <td>
                        ${row.subscription_status !== 'Active' ? `
                            <div style="font-size:11px; color:#f87171; text-align:center;">
                                <ion-icon name="warning-outline"></ion-icon> Subscriber Pending
                            </div>
                        ` : (isReady ? `
                            <a href="${apiBaseUrl}/${row.insurance_document_path}" target="_blank" class="doc-link">
                                <ion-icon name="document-text-outline"></ion-icon> PDF Document
                            </a>
                            <div class="upload-info"><ion-icon name="lock-closed-outline"></ion-icon> Locked</div>
                        ` : `
                            <label class="upload-label" onclick="window.prepareUpload('${row.account_id}')">
                                <ion-icon name="cloud-upload-outline" style="font-size: 20px;"></ion-icon>
                                Click to Upload Doc
                            </label>
                        `)}
                    </td>
                    <td>
                        <div style="width: 100%;">
                            ${row.subscription_status !== 'Active' ? `
                                <button class="btn-approve" style="opacity: 0.5; cursor: not-allowed;" disabled>
                                    <ion-icon name="lock-closed-outline"></ion-icon> Locked
                                </button>
                                <div style="font-size:11px; color:#f87171; margin-top:4px; text-align:center;">
                                    Subscriber account must be Active
                                </div>
                            ` : (isReady ? `
                                <input type="text" class="policy-input" placeholder="Policy Number *" id="policy_${row.account_id}">
                                <button class="btn-approve ready" onclick="window.approvePolicy('${row.account_id}')">
                                    <ion-icon name="shield-checkmark-outline"></ion-icon> Approve
                                </button>
                            ` : `
                                <button class="btn-approve" style="opacity: 0.5; cursor: not-allowed;" disabled>
                                    <ion-icon name="shield-outline"></ion-icon> Approve
                                </button>
                                <div style="font-size:11px; color:#fbbf24; margin-top:4px; text-align:center;">
                                    <ion-icon name="arrow-up-outline"></ion-icon> Upload doc first
                                </div>
                            `)}
                        </div>
                    </td>
                `;
                approvalsBody.appendChild(tr);
            });
        } else {
            tableTitle.innerText = 'Approved Policies';
            tableHead.innerHTML = `
                <th class="col-subscriber">Subscriber</th>
                <th class="col-member">Member ID</th>
                <th class="col-plan">Plan</th>
                <th class="col-receipt">Policy Number</th>
                <th class="col-doc" style="text-align:center;">Document</th>
                <th class="col-actions">Approved Date</th>
            `;

            list.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <div style="font-weight:600;">${row.first_name} ${row.last_name}</div>
                        <div style="font-size:12px; color:var(--text-muted);">${row.email}</div>
                    </td>
                    <td>${row.account_id}</td>
                    <td>${row.plan_name}</td>
                    <td>
                        <div style="color:#4ade80; font-weight:700;">${row.insurance_policy_number}</div>
                        ${row.insurance_approved_by_name ? `<div style="font-size:10px; color:var(--text-muted); margin-top:2px;">by ${row.insurance_approved_by_name}<br><span>${row.insurance_approved_at_formatted || ''}</span></div>` : ''}
                    </td>
                    <td>
                        <a href="${apiBaseUrl}/${row.insurance_document_path}" target="_blank" class="doc-link">
                            <ion-icon name="document-text-outline"></ion-icon> View Doc
                        </a>
                    </td>
                    <td>${row.joined_date}</td>
                `;
                approvalsBody.appendChild(tr);
            });
        }

        if (list.length === 0) {
            approvalsBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">No records found.</td></tr>`;
        }
    }

    // Tab Switching
    btnTabPending.onclick = () => {
        currentTab = 'Pending';
        btnTabPending.classList.add('active');
        btnTabApproved.classList.remove('active');
        loadData();
    };

    btnTabApproved.onclick = () => {
        currentTab = 'Approved';
        btnTabApproved.classList.add('active');
        btnTabPending.classList.remove('active');
        loadData();
    };

    // Upload Preparation
    window.prepareUpload = (id) => {
        selectedAccountId = id;
        docInput.click();
    };

    docInput.onchange = async () => {
        if (!docInput.files.length || !selectedAccountId) return;

        const formData = new FormData();
        formData.append('account_id', selectedAccountId);
        formData.append('document', docInput.files[0]);

        try {
            const res = await fetch(`${apiBaseUrl}/upload_insurance_doc.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'success') {
                loadData();
            } else {
                alert(data.message || 'Upload failed');
            }
        } catch (e) {
            console.error(e);
            alert('Error uploading document');
        }
    };

    // Approval Logic
    window.approvePolicy = async (id) => {
        const policyInput = document.getElementById(`policy_${id}`);
        const policyNumber = policyInput.value.trim();

        if (!policyNumber) {
            alert('Policy Number is required');
            return;
        }

        if (!confirm('Are you sure you want to approve this policy?')) return;

        try {
            const res = await fetch(`${apiBaseUrl}/approve_insurance_policy.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ account_id: id, policy_number: policyNumber })
            });
            const data = await res.json();
            if (data.status === 'success') {
                alert('Policy approved successfully!');
                loadData();
            } else {
                alert(data.message || 'Approval failed');
            }
        } catch (e) {
            console.error(e);
            alert('Error approving policy');
        }
    };

    // Initial Load
    loadData();
});
