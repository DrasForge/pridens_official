document.addEventListener('DOMContentLoaded', () => {
    // ── Sidebar ──────────────────────────────────────────────────────────────
    const sidebar  = document.getElementById('sidebar');
    const openBtn  = document.getElementById('open-sidebar');
    const closeBtn = document.getElementById('close-sidebar');
    if (openBtn && closeBtn) {
        openBtn.addEventListener('click',  () => sidebar.classList.toggle('open'));
        closeBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
    document.querySelectorAll('.nav-item-has-children').forEach(toggle => {
        const sub = toggle.nextElementSibling;
        if (sub?.classList.contains('sub-menu')) {
            toggle.addEventListener('click', () => {
                sub.classList.toggle('open');
                const chevron = toggle.querySelector('.chevron');
                if (chevron) chevron.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0)';
            });
            const chevron = toggle.querySelector('.chevron');
            if (chevron && sub.classList.contains('open')) chevron.style.transform = 'rotate(180deg)';
        }
    });
    document.getElementById('logout-btn')?.addEventListener('click', e => {
        e.preventDefault();
        window.location.href = 'index.html';
    });

    // ── State ─────────────────────────────────────────────────────────────────
    const apiBase  = '../admin_API';
    const tbody    = document.getElementById('agents-table-body');
    let currentStatus = 'All';
    let currentSearch = '';

    // ── Fetch & Render Table ──────────────────────────────────────────────────
    const fetchAgents = async () => {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;"><div class="loader" style="margin:0 auto;border-top-color:var(--primary-color);"></div></td></tr>`;
        try {
            const url = `${apiBase}/get_agents.php?status=${currentStatus}&search=${encodeURIComponent(currentSearch)}`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (res.status === 401) { window.location.href = 'index.html'; return; }
            const data = await res.json();
            if (data.status === 'success') renderTable(data.data);
            else showError(data.message || 'Error loading agents');
        } catch (e) {
            showError('Database not reachable');
        }
    };

    const showError = (msg) => {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-dim);">${msg}</td></tr>`;
    };

    const renderTable = (agents) => {
        if (!agents.length) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:60px;color:var(--text-dim);">
                <ion-icon name="people-outline" style="font-size:40px;display:block;margin:0 auto 12px;"></ion-icon>No agents found.</td></tr>`;
            return;
        }
        tbody.innerHTML = agents.map(a => {
            const initials = ((a.first_name?.[0] || '') + (a.last_name?.[0] || '')).toUpperCase();
            const isActive = a.status === 'Active';
            const statusBadge = isActive
                ? `<span class="badge-status active">Active</span>`
                : `<span class="badge-status rejected">Inactive</span>`;
            return `
            <tr>
                <td>
                    <div class="agent-info">
                        <div class="agent-avatar">${initials}</div>
                        <div>
                            <div class="agent-name">${a.first_name} ${a.last_name}</div>
                            <div class="agent-id">${a.email}</div>
                        </div>
                    </div>
                </td>
                <td><code style="font-size:0.82rem;color:var(--accent-blue);">${a.agent_id}</code></td>
                <td>
                    <div class="position-badge">${a.agent_position || 'Sales Agent'}</div>
                    <div style="font-size:0.72rem;color:var(--text-dim);margin-top:3px;">${a.rank_name || '—'}</div>
                </td>
                <td>${statusBadge}</td>
                <td style="color:var(--text-dim);font-size:0.85rem;">${a.registered_date || '—'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-action btn-secondary btn-sm" onclick="openPanel('${a.agent_id}')" title="View Details">
                            <ion-icon name="eye-outline"></ion-icon>
                        </button>
                        <button class="btn-action ${isActive ? 'btn-danger' : 'btn-success'} btn-sm" 
                            onclick="toggleStatus('${a.agent_id}', '${isActive ? 'Inactive' : 'Active'}')" 
                            title="${isActive ? 'Deactivate' : 'Activate'}">
                            <ion-icon name="${isActive ? 'ban-outline' : 'checkmark-circle-outline'}"></ion-icon>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    };

    // ── Tabs ──────────────────────────────────────────────────────────────────
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentStatus = tab.dataset.status;
            fetchAgents();
        });
    });

    // ── Search ────────────────────────────────────────────────────────────────
    document.getElementById('agent-search').addEventListener('keydown', e => {
        if (e.key === 'Enter') { currentSearch = e.target.value.trim(); fetchAgents(); }
    });
    document.getElementById('btn-search').addEventListener('click', () => {
        currentSearch = document.getElementById('agent-search').value.trim();
        fetchAgents();
    });

    // ── Detail Panel Logic ────────────────────────────────────────────────────
    const panel = document.getElementById('agent-detail-panel');
    const overlay = document.getElementById('panel-overlay');
    const panelBody = document.getElementById('panel-body');
    let currentAgent = null;
    let isEditing = false;
    let ranks = [];

    // Fetch ranks once for dropdowns
    const fetchRanks = async () => {
        try {
            const res = await fetch(`${apiBase}/get_ranks.php`);
            const r = await res.json();
            if (r.success) ranks = r.ranks;
        } catch (e) { console.error('Failed to load ranks'); }
    };
    fetchRanks();

    const closePanel = () => { 
        panel.classList.remove('open'); 
        overlay.classList.remove('show'); 
        isEditing = false;
    };
    document.getElementById('close-panel').addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);

    window.openPanel = async (agentId) => {
        panel.classList.add('open');
        overlay.classList.add('show');
        isEditing = false;
        panelBody.innerHTML = `<div style="text-align:center;padding:60px;"><div class="loader" style="margin:0 auto;border-top-color:var(--primary-color);"></div></div>`;
        
        try {
            const res = await fetch(`${apiBase}/get_agent_detail.php?agent_id=${agentId}`);
            const data = await res.json();
            if (data.status !== 'success') {
                panelBody.innerHTML = `<p style="padding:24px;color:var(--text-dim);">${data.message}</p>`;
                return;
            }
            currentAgent = data.data;
            renderViewingMode();
        } catch (e) {
            panelBody.innerHTML = `<p style="padding:24px;color:var(--text-dim);">Error loading agent details.</p>`;
        }
    };

    const renderViewingMode = () => {
        clearPanelFooter();
        const a = currentAgent;
        const initials = ((a.first_name?.[0] || '') + (a.last_name?.[0] || '')).toUpperCase();
        const isActive = a.status === 'Active';

        panelBody.innerHTML = `
            <div class="agent-hero">
                <div class="agent-hero-avatar">${initials}</div>
                <div>
                    <div class="agent-hero-name">${a.first_name} ${a.middle_name || ''} ${a.last_name}</div>
                    <div class="agent-hero-id">${a.agent_id}</div>
                    <div class="agent-hero-pos"><span class="position-badge">${a.agent_position || 'Sales Agent'}</span></div>
                </div>
            </div>
            
            <div class="panel-actions">
                <button class="btn-action btn-secondary" onclick="switchToEditMode()">
                    <ion-icon name="create-outline"></ion-icon> Edit Profile
                </button>
                <button class="btn-action ${isActive ? 'btn-danger' : 'btn-success'}" onclick="toggleStatus('${a.agent_id}', '${isActive ? 'Inactive' : 'Active'}')">
                    <ion-icon name="${isActive ? 'ban-outline' : 'checkmark-circle-outline'}"></ion-icon>
                    ${isActive ? 'Deactivate' : 'Activate'}
                </button>
            </div>

            <div class="detail-section">
                <h4>Personal Information</h4>
                <div class="detail-grid">
                    <div class="detail-item"><label>Email</label><span>${a.email || '—'}</span></div>
                    <div class="detail-item"><label>Phone</label><span>${a.phone || '—'}</span></div>
                    <div class="detail-item"><label>Gender</label><span>${a.gender || '—'}</span></div>
                    <div class="detail-item"><label>Birthday</label><span>${a.birthdate_formatted || '—'}</span></div>
                    <div class="detail-item"><label>Marital Status</label><span>${a.marital_status || '—'}</span></div>
                    <div class="detail-item"><label>Occupation</label><span>${a.occupation || '—'}</span></div>
                </div>
            </div>

            <div class="detail-section">
                <h4>Professional & Location</h4>
                <div class="detail-grid">
                    <div class="detail-item"><label>Rank</label><span>${a.rank_name || 'Sales Agent'}</span></div>
                    <div class="detail-item"><label>Status</label><span style="color:${isActive ? '#4ade80' : '#f87171'}">${a.status}</span></div>
                    <div class="detail-item full"><label>Address</label><span>${[a.address, a.barangay, a.city, a.province].filter(Boolean).join(', ') || '—'}</span></div>
                    <div class="detail-item"><label>Registered</label><span>${a.registered_date || '—'}</span></div>
                </div>
            </div>
        `;
    };

    window.switchToEditMode = () => {
        isEditing = true;
        const a = currentAgent;
        const rankOptions = ranks.map(r => `<option value="${r.id}" ${r.id == a.rank_id ? 'selected' : ''}>${r.rank_name}</option>`).join('');

        panelBody.innerHTML = `
            <div class="agent-hero" style="background: rgba(99,102,241,0.05);">
                <div class="agent-hero-avatar">${(a.first_name[0] + a.last_name[0]).toUpperCase()}</div>
                <div>
                    <div class="agent-hero-name" style="color: var(--accent-blue);">Editing Agent Profile</div>
                    <div class="agent-hero-id">${a.agent_id}</div>
                </div>
            </div>

            <div class="detail-section editing">
                <h4>Full Name</h4>
                <div class="detail-grid">
                    <div class="detail-item"><label>First Name</label><input type="text" id="edit-first-name" class="edit-input" value="${a.first_name}"></div>
                    <div class="detail-item"><label>Middle Name</label><input type="text" id="edit-middle-name" class="edit-input" value="${a.middle_name || ''}"></div>
                    <div class="detail-item full"><label>Last Name</label><input type="text" id="edit-last-name" class="edit-input" value="${a.last_name}"></div>
                </div>
            </div>

            <div class="detail-section">
                <h4>Contact & Personal</h4>
                <div class="detail-grid">
                    <div class="detail-item"><label>Email</label><input type="email" id="edit-email" class="edit-input" value="${a.email}"></div>
                    <div class="detail-item"><label>Phone</label><input type="text" id="edit-phone" class="edit-input" value="${a.phone || ''}"></div>
                    <div class="detail-item"><label>Gender</label>
                        <select id="edit-gender" class="edit-input">
                            <option value="Male" ${a.gender === 'Male' ? 'selected' : ''}>Male</option>
                            <option value="Female" ${a.gender === 'Female' ? 'selected' : ''}>Female</option>
                        </select>
                    </div>
                    <div class="detail-item"><label>Civil Status</label>
                        <select id="edit-marital" class="edit-input">
                            <option value="Single" ${a.marital_status === 'Single' ? 'selected' : ''}>Single</option>
                            <option value="Married" ${a.marital_status === 'Married' ? 'selected' : ''}>Married</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h4>Company Info</h4>
                <div class="detail-grid">
                    <div class="detail-item"><label>Rank</label>
                        <select id="edit-rank" class="edit-input">${rankOptions}</select>
                    </div>
                    <div class="detail-item"><label>Status</label>
                        <select id="edit-status" class="edit-input">
                            <option value="Active" ${a.status === 'Active' ? 'selected' : ''}>Active</option>
                            <option value="Inactive" ${a.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h4>Location</h4>
                <div class="detail-grid">
                    <div class="detail-item full"><label>Street Address</label><input type="text" id="edit-address" class="edit-input" value="${a.address || ''}"></div>
                    <div class="detail-item"><label>Barangay</label><input type="text" id="edit-barangay" class="edit-input" value="${a.barangay || ''}"></div>
                    <div class="detail-item"><label>City</label><input type="text" id="edit-city" class="edit-input" value="${a.city || ''}"></div>
                    <div class="detail-item full"><label>Province</label><input type="text" id="edit-province" class="edit-input" value="${a.province || ''}"></div>
                </div>
            </div>
        `;

        // Add fixed footer at the bottom of the PANEL, not the scroll body
        const footer = document.createElement('div');
        footer.className = 'save-footer';
        footer.innerHTML = `
            <button class="btn-action btn-primary" onclick="saveAgentDetails()" id="btn-save-agent">Save Changes</button>
            <button class="btn-action btn-secondary" onclick="renderViewingMode()">Cancel</button>
        `;
        
        // Remove existing footer if any
        const oldFooter = panel.querySelector('.save-footer');
        if (oldFooter) oldFooter.remove();
        panel.appendChild(footer);
    };

    const clearPanelFooter = () => {
        const oldFooter = panel.querySelector('.save-footer');
        if (oldFooter) oldFooter.remove();
    };

    window.saveAgentDetails = async () => {
        const btn = document.getElementById('btn-save-agent');
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Saving...';

        const payload = {
            agent_id: currentAgent.agent_id,
            first_name: document.getElementById('edit-first-name').value.trim(),
            middle_name: document.getElementById('edit-middle-name').value.trim(),
            last_name: document.getElementById('edit-last-name').value.trim(),
            email: document.getElementById('edit-email').value.trim(),
            phone: document.getElementById('edit-phone').value.trim(),
            gender: document.getElementById('edit-gender').value,
            marital_status: document.getElementById('edit-marital').value,
            rank_id: document.getElementById('edit-rank').value,
            status: document.getElementById('edit-status').value,
            address: document.getElementById('edit-address').value.trim(),
            barangay: document.getElementById('edit-barangay').value.trim(),
            city: document.getElementById('edit-city').value.trim(),
            province: document.getElementById('edit-province').value.trim()
        };

        try {
            const res = await fetch(`${apiBase}/update_agent_details.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                closePanel();
                fetchAgents();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            alert('Connection error while saving.');
        } finally {
            btn.disabled = false;
            btn.innerText = originalText;
        }
    };

    window.toggleStatus = async (agentId, newStatus) => {
        if (!confirm(`Are you sure you want to set this agent to "${newStatus}"?`)) return;
        try {
            const res = await fetch(`${apiBase}/update_agent_status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ agent_id: agentId, status: newStatus })
            });
            const data = await res.json();
            if (data.status === 'success') { 
                fetchAgents();
                if (panel.classList.contains('open')) {
                    // Refresh agent data in panel
                    const r = await fetch(`${apiBase}/get_agent_detail.php?agent_id=${agentId}`);
                    const d = await r.json();
                    if (d.status === 'success') {
                        currentAgent = d.data;
                        renderViewingMode();
                    }
                }
            }
            else alert('Error: ' + data.message);
        } catch (e) { alert('Network error.'); }
    };

    fetchAgents();
});
