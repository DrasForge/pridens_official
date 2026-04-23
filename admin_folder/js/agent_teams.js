document.addEventListener('DOMContentLoaded', () => {
    const apiBase = '../admin_API';
    const teamList = document.getElementById('team-list');
    const teamModal = document.getElementById('team-modal');
    const teamForm = document.getElementById('team-form');
    
    let teams = [];
    let eligibleLeaders = [];
    let eligibleParents = [];

    // ── Fetch & Render ────────────────────────────────────────────────────────
    const fetchTeams = async () => {
        teamList.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><div class="loader" style="margin: 0 auto; border-top-color: var(--primary-color);"></div></div>`;
        try {
            const res = await fetch(`${apiBase}/get_teams.php`);
            const data = await res.json();
            if (data.status === 'success') {
                teams = data.data;
                renderTeams();
                fetchPendingSetups(); // Check for agents needing teams
            }
        } catch (e) { console.error('Failed to load teams'); }
    };

    const fetchPendingSetups = async () => {
        try {
            const res = await fetch(`${apiBase}/get_team_setup_status.php`);
            const data = await res.json();
            if (data.status === 'success') {
                renderPendingSetups(data.data);
            }
        } catch (e) { console.error('Failed to load pending setups'); }
    };

    const renderPendingSetups = (pending) => {
        const section = document.getElementById('pending-setups');
        const list = document.getElementById('pending-list');
        
        if (pending.length === 0) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        list.innerHTML = pending.map(p => `
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 16px; border-radius: 12px; display: flex; flex-direction: column; gap: 8px;">
                <div style="font-size: 0.85rem; font-weight: 600; color: #fff;">${p.first_name} ${p.last_name}</div>
                <div style="font-size: 0.7rem; color: var(--text-dim); margin-top: -4px;">Promoted to ${p.rank_name}</div>
                <button class="btn-card-primary" onclick="initTeamSetup('${p.agent_id}', '${p.first_name} ${p.last_name}')" style="padding: 8px; font-size: 0.75rem; color:#fff; border-radius: 8px; border:none; cursor:pointer;">
                    Initialize Team
                </button>
            </div>
        `).join('');
    };

    window.initTeamSetup = async (agentId, name) => {
        await openCreateModal();
        document.getElementById('leader-select').value = agentId;
        document.getElementById('team-name').placeholder = `e.g. Team ${name.split(' ')[1]}`;
    };

    const renderTeams = () => {
        if (teams.length === 0) {
            teamList.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-dim);">No teams found. Click "Create New Team" to start.</div>`;
            return;
        }

        teamList.innerHTML = teams.map(t => {
            const logo = t.team_logo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(t.team_name) + '&background=1e293b&color=fff&size=128';
            return `
                <div class="team-card">
                    <div class="team-header">
                        <img src="${logo}" class="team-logo" alt="${t.team_name}">
                        <div class="team-info">
                            <h3>${t.team_name}</h3>
                            ${t.parent_team_name ? `<span class="team-parent">Under ${t.parent_team_name}</span>` : '<span class="team-parent" style="color:var(--text-dim)">Root Organization</span>'}
                        </div>
                    </div>

                    <div class="team-stats">
                        <div class="stat-box">
                            <label>Members</label>
                            <span>${t.member_count}</span>
                        </div>
                        <div class="stat-box">
                            <label>Perf Index</label>
                            <span>—</span>
                        </div>
                    </div>

                    <div class="leader-badge">
                        <ion-icon name="ribbon-outline"></ion-icon>
                        <div class="leader-details">
                            <span class="leader-name">${t.leader_name}</span>
                            <span class="leader-rank">${t.leader_rank}</span>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button class="btn-card" onclick="editTeam(${t.id})">
                            <ion-icon name="settings-outline"></ion-icon> Settings
                        </button>
                        <button class="btn-card" onclick="manageMembers(${t.id}, '${t.team_name}')">
                            <ion-icon name="people-outline"></ion-icon> Members
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    };

    // ── Eligibility & Dropdowns ───────────────────────────────────────────────
    const fetchEligibility = async (editId = null) => {
        try {
            const res = await fetch(`${apiBase}/get_team_eligibility.php`);
            const data = await res.json();
            if (data.status === 'success') {
                eligibleLeaders = data.leaders;
                eligibleParents = data.parent_teams;
                
                // Populate Leader dropdown
                const leaderSelect = document.getElementById('leader-select');
                leaderSelect.innerHTML = '<option value="">Select an eligible leader...</option>' + 
                    eligibleLeaders.map(l => `<option value="${l.agent_id}">${l.first_name} ${l.last_name} (${l.rank_name})</option>`).join('');

                // Populate Parent dropdown (excluding current team to prevent circularity)
                const parentSelect = document.getElementById('parent-select');
                parentSelect.innerHTML = '<option value="">Root Team (No Parent)</option>' + 
                    eligibleParents.filter(p => !editId || p.id != editId).map(p => `<option value="${p.id}">${p.team_name}</option>`).join('');
            }
        } catch (e) { console.error('Failed to load eligibility'); }
    };

    // ── Modal Handling ────────────────────────────────────────────────────────
    window.openCreateModal = async () => {
        document.getElementById('modal-title').innerText = 'Create New Team';
        teamForm.reset();
        document.getElementById('edit-id').value = '';
        document.getElementById('existing-logo').value = '';
        document.getElementById('logo-preview').innerHTML = `<ion-icon name="image-outline" style="font-size: 2rem; color: var(--text-dim);"></ion-icon>`;
        
        await fetchEligibility();
        teamModal.classList.add('open');
    };

    window.editTeam = async (id) => {
        const t = teams.find(team => team.id == id);
        if (!t) return;

        document.getElementById('modal-title').innerText = 'Edit Team Settings';
        document.getElementById('edit-id').value = t.id;
        document.getElementById('team-name').value = t.team_name;
        document.getElementById('team-goal').value = t.team_goal || '';
        document.getElementById('existing-logo').value = t.team_logo || '';
        
        if (t.team_logo) {
            document.getElementById('logo-preview').innerHTML = `<img src="${t.team_logo}" alt="Preview">`;
        }

        await fetchEligibility(id);

        // Add current leader back to the options if they are already leading
        const leaderSelect = document.getElementById('leader-select');
        if (!eligibleLeaders.find(l => l.agent_id === t.leader_id)) {
            const opt = document.createElement('option');
            opt.value = t.leader_id;
            opt.text = `${t.leader_name} (${t.leader_rank})`;
            opt.selected = true;
            leaderSelect.add(opt, 1);
        } else {
            leaderSelect.value = t.leader_id;
        }

        document.getElementById('parent-select').value = t.parent_team_id || '';
        teamModal.classList.add('open');
    };

    window.closeModal = () => { teamModal.classList.remove('open'); };

    window.previewLogo = (input) => {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('logo-preview').innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    };

    // ── Form Submission ───────────────────────────────────────────────────────
    teamForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(teamForm);
        
        try {
            const res = await fetch(`${apiBase}/save_team.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'success') {
                closeModal();
                fetchTeams();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) { alert('Failed to save team.'); }
    });

    // ── Member Management (Placeholder logic for now) ─────────────────────────
    window.manageMembers = (id, name) => {
        alert(`Member management for team "${name}" (ID: ${id}) would include adding/removing agents via a picker modal. This is the next stage of implementation.`);
    };

    fetchTeams();
});
