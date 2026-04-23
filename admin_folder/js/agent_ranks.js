// c:\Pridens_trading_co\admin_folder\js\agent_ranks.js

const TAG = '[AgentRanks]';
const API = '../admin_api/agent_api.php';

let ranks = [];
let dragEl = null;
let currentRankId = null;

document.addEventListener('DOMContentLoaded', () => {
    loadRanks();
});

// --- CORE: Load & Render ---
async function loadRanks() {
    try {
        const res = await fetch(`${API}?action=get_agent_ranks`);
        const json = await res.json();
        if (json.status === 'success') {
            ranks = json.data;
            renderRankList();
        } else {
            showPageError(json.message);
        }
    } catch (e) {
        showPageError("Failed to fetch ranks: " + e.message);
    }
}

function renderRankList() {
    const list = document.getElementById('rankList');
    if (!ranks.length) {
        list.innerHTML = `<div class="text-center text-muted" style="padding:40px;">No ranks defined yet. Click "Add Rank" to start.</div>`;
        return;
    }

    list.innerHTML = ranks.map((rank, index) => `
        <div class="rank-row" draggable="true" ondragstart="dragStart(event)" ondragover="dragOver(event)" ondragend="dragEnd(event)" data-id="${rank.id}">
            <div class="rank-order-badge">${index + 1}</div>
            <div class="rank-info-main">
                <div class="rank-title-group">
                    <h4>${rank.rank_name}</h4>
                    <span class="rank-type-tag ${rank.promotion_type}">${rank.promotion_type}</span>
                    ${rank.is_team_leader == 1 ? '<span class="rank-type-tag" style="background:rgba(139, 92, 246, 0.1); color:#a78bfa; border:1px solid rgba(139, 92, 246, 0.2);">TEAM LEADER</span>' : ''}
                </div>
                <div class="rank-stats-row">
                    <div class="stat-item"><ion-icon name="shield-checkmark-outline"></ion-icon> ${rank.qualification_count || 0} Rules</div>
                   
                </div>
            </div>
            <div class="rank-actions-group">
                <button class="btn-icon-advanced accent-blue" title="Promotion Rules" onclick="openQualifications(${rank.id}, '${rank.rank_name}')">
                    <ion-icon name="settings-outline"></ion-icon>
                </button>
                <button class="btn-icon-advanced accent-pink" title="Incentives" onclick="openIncentives(${rank.id}, '${rank.rank_name}')">
                    <ion-icon name="cash-outline"></ion-icon>
                </button>
                <button class="btn-icon-advanced accent-orange" title="Clusters" onclick="openClusters(${rank.id}, '${rank.rank_name}')">
                    <ion-icon name="git-network-outline"></ion-icon>
                </button>
                <button class="btn-icon-advanced" title="ID Card Theme" onclick="openCardTheme(${rank.id}, '${rank.rank_name}')">
                    <ion-icon name="color-palette-outline"></ion-icon>
                </button>
                <button class="btn-icon-advanced" title="Edit Rank" onclick="openEditRank(${rank.id})">
                    <ion-icon name="create-outline"></ion-icon>
                </button>
                <button class="btn-icon-advanced danger" title="Delete" onclick="deleteRank(${rank.id})">
                    <ion-icon name="trash-outline"></ion-icon>
                </button>
            </div>
        </div>
    `).join('');
}

// --- DRAG & DROP ---
function dragStart(e) {
    dragEl = e.target.closest('.rank-row');
    dragEl.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function dragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const target = e.target.closest('.rank-row');
    if (target && target !== dragEl) {
        const rect = target.getBoundingClientRect();
        const next = (e.clientY - rect.top) > (rect.height / 2);
        target.parentNode.insertBefore(dragEl, next ? target.nextSibling : target);
    }
}

async function dragEnd(e) {
    dragEl.classList.remove('dragging');
    const rows = [...document.querySelectorAll('.rank-row')];
    const newOrder = rows.map(r => r.dataset.id);
    
    // Optimistic UI update
    const oldRanks = [...ranks];
    ranks = newOrder.map(id => oldRanks.find(r => r.id == id));
    renderRankList();

    try {
        await fetch(`${API}?action=reorder_agent_ranks`, {
            method: 'POST',
            body: JSON.stringify({ order: newOrder })
        });
    } catch (err) {
        console.error("Reorder failed", err);
        loadRanks(); // Rollback
    }
}

// --- MODALS BASE ---
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// --- RANK CRUD ---
function openAddRank() {
    document.getElementById('rmTitle').innerText = "Add New Rank";
    document.getElementById('rmId').value = "";
    document.getElementById('rmName').value = "";
    document.getElementById('rmType').value = "auto";
    document.getElementById('rmIsTeamLeader').checked = false;
    openModal('rankModal');
}

function openEditRank(id) {
    const rank = ranks.find(r => r.id == id);
    if (!rank) return;
    document.getElementById('rmTitle').innerText = "Edit Rank";
    document.getElementById('rmId').value = rank.id;
    document.getElementById('rmName').value = rank.rank_name;
    document.getElementById('rmType').value = rank.promotion_type;
    document.getElementById('rmIsTeamLeader').checked = rank.is_team_leader == 1;
    openModal('rankModal');
}

async function saveRank() {
    const id = document.getElementById('rmId').value;
    const name = document.getElementById('rmName').value;
    const type = document.getElementById('rmType').value;
    const isTeamLeader = document.getElementById('rmIsTeamLeader').checked ? 1 : 0;

    if (!name) { alert("Please enter a name"); return; }

    const formData = new FormData();
    if (id) formData.append('id', id);
    formData.append('name', name);
    formData.append('promotion_type', type);
    formData.append('is_team_leader', isTeamLeader);

    const action = id ? 'update_agent_rank' : 'create_agent_rank';
    try {
        const res = await fetch(`${API}?action=${action}`, { method: 'POST', body: formData });
        const json = await res.json();
        if (json.status === 'success') {
            closeModal('rankModal');
            loadRanks();
        } else alert(json.message);
    } catch (e) { alert(e.message); }
}

async function deleteRank(id) {
    if (!confirm("Are you sure you want to delete this rank?")) return;
    const formData = new FormData();
    formData.append('id', id);
    try {
        const res = await fetch(`${API}?action=delete_agent_rank`, { method: 'POST', body: formData });
        const json = await res.json();
        if (json.status === 'success') loadRanks();
        else alert(json.message);
    } catch (e) { alert(e.message); }
}

// --- QUALIFICATIONS ---
async function openQualifications(id, name) {
    currentRankId = id;
    document.getElementById('qmRankName').innerText = name;
    document.getElementById('qmRankId').value = id;
    
    // Load Plans & Existing Quals
    const [pRes, qRes] = await Promise.all([
        fetch(`${API}?action=get_subscription_plans`),
        fetch(`${API}?action=get_rank_qualifications&rank_id=${id}`)
    ]);
    
    const plans = (await pRes.json()).data || [];
    const quals = (await qRes.json()).data || [];
    
    // Render Plans Section
    const planBox = document.getElementById('qmPlanRows');
    planBox.innerHTML = plans.map(p => {
        const q = quals.find(x => x.qualification_type === 'min_referrals_per_plan' && x.plan_id == p.id);
        return `
            <div class="qual-item-premium">
                <span class="qual-badge-mini" style="background:#4ade80; color:#064e3b;">Plan</span>
                <span class="qual-name-text">${p.name}</span>
                <div class="qual-input-wrap">
                    <input type="number" class="plan-req" data-plan-id="${p.id}" value="${q ? q.value : 0}" min="0">
                    <span>Directs</span>
                </div>
            </div>
        `;
    }).join('');

    // Render Rank Hierarchy Section
    const rankBox = document.getElementById('qmRankRows');
    rankBox.innerHTML = ranks.map(r => {
        if (r.id == id) return ''; // Skip self
        const q = quals.find(x => x.qualification_type === 'min_subordinates_by_rank' && x.target_rank_id == r.id);
        return `
            <div class="qual-item-premium">
                <span class="qual-badge-mini" style="background:#60a5fa; color:#1e3a8a;">Rank</span>
                <span class="qual-name-text">${r.rank_name}</span>
                <div class="qual-input-wrap">
                    <input type="number" class="rank-req" data-target-id="${r.id}" value="${q ? q.value : 0}" min="0">
                    <span>Total</span>
                </div>
            </div>
        `;
    }).join('');

    // Render General
    const m = quals.find(x => x.qualification_type === 'min_months_active');
    document.getElementById('qmMinMonths').value = m ? m.value : 0;
    
    // Monthly Sales Quota
    const sQuote = quals.find(x => x.qualification_type === 'min_sales_per_month');
    document.getElementById('qmMinSalesPerMonth').value = sQuote ? sQuote.value : 0;
    
    const app = quals.find(x => x.qualification_type === 'requires_approval');
    document.getElementById('qmRequiresApproval').checked = app && app.value == 1;

    openModal('qualModal');
}

async function saveQualifications() {
    const rankId = document.getElementById('qmRankId').value;
    const quals = [];
    
    // Collect Plan Requirements
    document.querySelectorAll('.plan-req').forEach(el => {
        if (el.value > 0) quals.push({ qualification_type: 'min_referrals_per_plan', value: el.value, plan_id: el.dataset.planId });
    });
    
    // Collect Rank Requirements
    document.querySelectorAll('.rank-req').forEach(el => {
        if (el.value > 0) quals.push({ qualification_type: 'min_subordinates_by_rank', value: el.value, target_rank_id: el.dataset.targetId });
    });
    
    // General
    const minMonths = document.getElementById('qmMinMonths').value;
    if (minMonths > 0) quals.push({ qualification_type: 'min_months_active', value: minMonths });

    const minSales = document.getElementById('qmMinSalesPerMonth').value;
    if (minSales > 0) quals.push({ qualification_type: 'min_sales_per_month', value: minSales });
    
    if (document.getElementById('qmRequiresApproval').checked) quals.push({ qualification_type: 'requires_approval', value: 1 });

    try {
        const res = await fetch(`${API}?action=save_rank_qualifications`, {
            method: 'POST',
            body: JSON.stringify({ rank_id: rankId, qualifications: quals })
        });
        const json = await res.json();
        if (json.status === 'success') {
            closeModal('qualModal');
            loadRanks(); // refresh badge count
        } else alert(json.message);
    } catch (e) { alert(e.message); }
}

// --- ID CARD THEME ---
function openCardTheme(id, name) {
    currentRankId = id;
    document.getElementById('ctRankName').innerText = name;
    document.getElementById('ctPreviewPos').innerText = name;
    document.getElementById('ctRankId').value = id;
    
    fetch(`${API}?action=get_card_theme&rank_id=${id}`)
        .then(r => r.json())
        .then(json => {
            if (json.status === 'success' && json.data && json.data.theme_data) {
                const theme = JSON.parse(json.data.theme_data);
                document.getElementById('ctGradStart').value = theme.gradStart || '#0f172a';
                document.getElementById('ctGradMid').value = theme.gradMid || '#1e293b';
                document.getElementById('ctGradEnd').value = theme.gradEnd || '#020617';
                document.getElementById('ctText').value = theme.text || '#ffffff';
                document.getElementById('ctAccent').value = theme.accent || '#6366f1';
                document.getElementById('ctLabel').value = theme.label || '#94a3b8';
                document.getElementById('ctPatternType').value = theme.pattern || 'circles';
            }
            updateCardPreview();
        });
        
    openModal('cardThemeModal');
}

function updateCardPreview() {
    const card = document.getElementById('cardPreview');
    const gStart = document.getElementById('ctGradStart').value;
    const gMid = document.getElementById('ctGradMid').value;
    const gEnd = document.getElementById('ctGradEnd').value;
    const textColor = document.getElementById('ctText').value;
    const accentColor = document.getElementById('ctAccent').value;
    const labelColor = document.getElementById('ctLabel').value;
    const pattern = document.getElementById('ctPatternType').value;

    card.style.background = `linear-gradient(135deg, ${gStart}, ${gMid}, ${gEnd})`;
    card.style.color = textColor;
    
    const rankVal = document.getElementById('ctPreviewPos');
    rankVal.style.color = accentColor;
    rankVal.style.borderTopColor = `${accentColor}33`;
    
    document.querySelector('.card-logo').style.color = accentColor;
    document.querySelector('.card-status-badge').style.color = accentColor;
    document.querySelector('.card-status-badge').style.background = `${accentColor}22`;
    
    document.querySelector('.card-id-val').style.color = labelColor;
    document.querySelector('.card-team-val').style.color = labelColor;
    
    renderPattern(pattern, accentColor);
}

function renderPattern(type, color) {
    const canvas = document.getElementById('patternCanvas');
    const ctx = canvas.getContext('2d');
    canvas.width = 320;
    canvas.height = 202;
    ctx.clearRect(0,0,320,202);
    
    if (type === 'none') return;
    
    ctx.globalAlpha = 0.15;
    ctx.strokeStyle = color;
    ctx.fillStyle = color;
    
    if (type === 'circles') {
        for(let i=0; i<8; i++){
            ctx.beginPath();
            ctx.arc(Math.random()*320, Math.random()*202, 20 + Math.random()*60, 0, Math.PI*2);
            ctx.stroke();
        }
    } else if (type === 'grid') {
        ctx.beginPath();
        for(let x=0; x<320; x+=15){ ctx.moveTo(x,0); ctx.lineTo(x,202); }
        for(let y=0; y<202; y+=15){ ctx.moveTo(0,y); ctx.lineTo(320,y); }
        ctx.stroke();
    } else if (type === 'waves') {
        ctx.beginPath();
        for(let i=0; i<3; i++){
            ctx.moveTo(0, 100 + i*20);
            for(let x=0; x<320; x++){ ctx.lineTo(x, 100 + i*20 + Math.sin(x/40)*30); }
        }
        ctx.stroke();
    }
}

async function saveCardTheme() {
    const rankId = document.getElementById('ctRankId').value;
    const theme = {
        gradStart: document.getElementById('ctGradStart').value,
        gradMid: document.getElementById('ctGradMid').value,
        gradEnd: document.getElementById('ctGradEnd').value,
        text: document.getElementById('ctText').value,
        accent: document.getElementById('ctAccent').value,
        label: document.getElementById('ctLabel').value,
        pattern: document.getElementById('ctPatternType').value
    };
    
    try {
        const res = await fetch(`${API}?action=save_card_theme`, {
            method: 'POST',
            body: JSON.stringify({ rank_id: rankId, theme_data: theme })
        });
        const json = await res.json();
        if (json.status === 'success') closeModal('cardThemeModal');
        else alert(json.message);
    } catch (e) { alert(e.message); }
}

// --- CLUSTERS (Dynamic Rows) ---
let currentClusters = [];

async function openClusters(id, name) {
    currentRankId = id;
    document.getElementById('cmRankName').innerText = name;
    
    const [cRes, pRes] = await Promise.all([
        fetch(`${API}?action=get_cluster_config&rank_id=${id}`),
        fetch(`${API}?action=get_subscription_plans`)
    ]);
    
    currentClusters = (await cRes.json()).data || [];
    window.currentPlans = (await pRes.json()).data || [];
    
    renderClusterRows();
    openModal('clusterModal');
}

function renderClusterRows() {
    const container = document.getElementById('clusterRows');
    container.innerHTML = currentClusters.map((c, index) => `
        <div class="cluster-card" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 15px; margin-bottom: 15px;">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: start; margin-bottom: 15px;">
                <div>
                    <label style="font-size: 10px; font-weight: 700; color: #9ca3af; display: block; margin-bottom: 6px; text-transform: uppercase;">Subordinate Position</label>
                    <select class="cluster-subrank input-premium" data-index="${index}" style="width: 100%;">
                        <option value="">— Select Rank —</option>
                        ${ranks.map(r => `<option value="${r.id}" ${c.subordinate_rank_id == r.id ? 'selected' : ''}>${r.rank_name}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label style="font-size: 10px; font-weight: 700; color: #9ca3af; display: block; margin-bottom: 6px; text-transform: uppercase;">Min. Groups</label>
                    <input type="number" class="cluster-groups input-premium" value="${c.min_groups}" style="width: 100%;">
                </div>
                <div>
                    <label style="font-size: 10px; font-weight: 700; color: #9ca3af; display: block; margin-bottom: 6px; text-transform: uppercase;">Members/Group</label>
                    <input type="number" class="cluster-members input-premium" value="${c.min_members_per_group}" style="width: 100%;">
                </div>
                <div style="padding-top: 22px;">
                    <button class="btn-icon-advanced danger" onclick="removeClusterRow(${index})" style="background: transparent; border: none; font-size: 16px;"><ion-icon name="trash"></ion-icon></button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr 1.5fr; gap: 15px; align-items: start; margin-bottom: 15px;">
                <div>
                    <label style="font-size: 10px; font-weight: 700; color: #9ca3af; display: block; margin-bottom: 6px; text-transform: uppercase;">Subscription Plan</label>
                    <select class="cluster-plan input-premium" style="width: 100%;">
                        <option value="">— All Subscription Plans —</option>
                        ${(window.currentPlans || []).map(p => `<option value="${p.id}" ${c.plan_id == p.id ? 'selected' : ''}>${p.name}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label style="font-size: 10px; font-weight: 700; color: #9ca3af; display: block; margin-bottom: 6px; text-transform: uppercase;">Group Sales Quota</label>
                    <input type="number" class="cluster-quota input-premium" value="${c.group_quota || 0}" style="width: 100%;">
                </div>
                <div>
                    <label style="font-size: 10px; font-weight: 700; color: #9ca3af; display: block; margin-bottom: 6px; text-transform: uppercase;">Incentive Value</label>
                    <div style="display: flex;">
                        <span style="background: rgba(255,255,255,0.03); border: 1px solid #334155; border-right: none; padding: 8px 12px; border-radius: 6px 0 0 6px; color: #9ca3af;">₱</span>
                        <input type="number" class="cluster-incentive input-premium" value="${c.group_incentive || 0}" style="width: 100%; border-radius: 0 6px 6px 0;">
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 15px; align-items: center;">
                <div style="display: flex; gap: 10px;">
                    <div>
                        <label style="font-size: 10px; font-weight: 700; color: #9ca3af; display: block; margin-bottom: 6px; text-transform: uppercase;">Start Day</label>
                        <input type="number" class="cluster-start input-premium" value="${c.start_day || 1}" style="width: 70px;">
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 700; color: #9ca3af; display: block; margin-bottom: 6px; text-transform: uppercase;">End Day</label>
                        <input type="number" class="cluster-end input-premium" value="${c.end_day || 31}" style="width: 70px;">
                    </div>
                </div>
                
                <div style="flex: 1; display: flex; align-items: center; justify-content: flex-start; gap: 20px; background: rgba(0,0,0,0.2); padding: 12px 20px; border-radius: 6px; height: 42px; margin-top: 18px;">
                    <label style="font-size: 12px; color: #e2e8f0; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" class="cluster-multi" ${c.allow_multipayout == 1 ? 'checked' : ''}> Multi-Payout
                    </label>
                    <label style="font-size: 12px; color: #e2e8f0; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" class="cluster-ismember" ${c.is_group_count == 1 ? 'checked' : ''}> Count as Group
                    </label>
                </div>
            </div>
        </div>
    `).join('');
}

function addClusterRow() {
    currentClusters.push({ subordinate_rank_id: ranks[0]?.id || '', plan_id: null, min_groups:1, min_members_per_group:1, group_quota:0, group_incentive:0, start_day:1, end_day:31, allow_multipayout:0, is_group_count:1 });
    renderClusterRows();
}

function removeClusterRow(idx) {
    currentClusters.splice(idx, 1);
    renderClusterRows();
}

async function saveClusterConfig() {
    const list = document.querySelectorAll('#clusterRows .cluster-card');
    const data = [];
    list.forEach((row, i) => {
        data.push({
            subordinate_rank_id: row.querySelector('.cluster-subrank').value,
            plan_id: row.querySelector('.cluster-plan').value || null,
            min_groups: row.querySelector('.cluster-groups').value,
            min_members_per_group: row.querySelector('.cluster-members').value,
            group_quota: row.querySelector('.cluster-quota').value,
            group_incentive: row.querySelector('.cluster-incentive').value,
            start_day: row.querySelector('.cluster-start').value,
            end_day: row.querySelector('.cluster-end').value,
            allow_multipayout: row.querySelector('.cluster-multi').checked ? 1 : 0,
            is_group_count: row.querySelector('.cluster-ismember').checked ? 1 : 0
        });
    });

    try {
        const res = await fetch(`${API}?action=save_cluster_config`, {
            method: 'POST',
            body: JSON.stringify({ rank_id: currentRankId, clusters: data })
        });
        const json = await res.json();
        if (json.status === 'success') closeModal('clusterModal');
    } catch (e) { alert(e.message); }
}

// --- INCENTIVES (Achievement Tiers) ---
let currentIncentivePlans = [];
let achievementTiers = [];

async function openIncentives(id, name) {
    currentRankId = id;
    document.getElementById('imRankId').value = id;
    document.getElementById('imRankNameActual').value = name;
    
    const [pRes, iRes, tRes] = await Promise.all([
        fetch(`${API}?action=get_subscription_plans`),
        fetch(`${API}?action=get_incentive_config&rank_id=${id}`),
        fetch(`${API}?action=get_incentive_tiers&rank_name=${encodeURIComponent(name)}`)
    ]);
    
    const plans = (await pRes.json()).data || [];
    const configs = (await iRes.json()).data || [];
    achievementTiers = (await tRes.json()).data || [];
    
    // Achievement Tiers
    renderTierRows();
    
    // Plan Brackets
    const planBox = document.getElementById('imPlanRows');
    
    // Set global toggles and dates based on the first plan (they are shared config for the rank)
    if (configs.length > 0) {
        document.getElementById('imCountDownline').checked = configs[0].count_downline == 1;
        document.getElementById('imAllowMulti').checked = configs[0].allow_multipayout == 1;
        document.getElementById('imStartDay').value = configs[0].start_day || 1;
        document.getElementById('imEndDay').value = configs[0].end_day || 31;
    } else {
        document.getElementById('imCountDownline').checked = true;
        document.getElementById('imAllowMulti').checked = false;
        document.getElementById('imStartDay').value = 1;
        document.getElementById('imEndDay').value = 31;
    }

    planBox.innerHTML = plans.map(p => {
        const c = configs.find(x => x.plan_id == p.id) || { monthly_quota:0, base_incentive:0, quota_50:0, incentive_50:0, quota_10:0, incentive_10:0 };
        return `
            <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 12px 0; align-items: center;">
                <div>
                    <div style="font-weight: 700; color: #facc15; font-size: 14px;">${p.name}</div>
                    <div style="font-size: 11px; color: #9ca3af;">Subscription</div>
                </div>
                
                <!-- 100% Bracket -->
                <div>
                    <label style="font-size: 9px; color: #6b7280; display: block; margin-bottom: 4px;">QUOTA / INC</label>
                    <div style="display: flex; gap: 5px;">
                        <input type="number" class="inc-qty input-premium" data-plan-id="${p.id}" value="${c.monthly_quota}" placeholder="Q" style="width: 100%; padding: 6px; text-align: center;">
                        <input type="number" class="inc-base input-premium" value="${c.base_incentive}" placeholder="₱" style="width: 100%; padding: 6px; text-align: center;">
                    </div>
                </div>

                <!-- 50% Bracket -->
                <div>
                    <label style="font-size: 9px; color: #6b7280; display: block; margin-bottom: 4px;">QUOTA / INC</label>
                    <div style="display: flex; gap: 5px;">
                        <input type="number" class="inc-50-qty input-premium" value="${c.quota_50 || 0}" placeholder="Q" style="width: 100%; padding: 6px; text-align: center;">
                        <input type="number" class="inc-50-inc input-premium" value="${c.incentive_50 || 0}" placeholder="₱" style="width: 100%; padding: 6px; text-align: center;">
                    </div>
                </div>

                <!-- 10% Bracket -->
                <div>
                    <label style="font-size: 9px; color: #6b7280; display: block; margin-bottom: 4px;">QUOTA / INC</label>
                    <div style="display: flex; gap: 5px;">
                        <input type="number" class="inc-10-qty input-premium" value="${c.quota_10 || 0}" placeholder="Q" style="width: 100%; padding: 6px; text-align: center;">
                        <input type="number" class="inc-10-inc input-premium" value="${c.incentive_10 || 0}" placeholder="₱" style="width: 100%; padding: 6px; text-align: center;">
                    </div>
                </div>
            </div>
        `;
    }).join('');

    openModal('incModal');
}

function renderTierRows() {
    const box = document.getElementById('imTierRows');
    box.innerHTML = `<h4>Multi-Tier Achievement Tiers</h4>` + achievementTiers.map((t, idx) => `
        <div class="qual-item-premium" style="margin-bottom:8px;">
            <div class="qual-input-wrap">
                <span>Quota:</span><input type="number" class="tier-quota input-premium" value="${t.quota}" style="width:100px;">
                <span>Incentive: ₱</span><input type="number" class="tier-inc input-premium" value="${t.incentive}" style="width:120px;">
                <button onclick="achievementTiers.splice(${idx},1); renderTierRows();" class="btn-icon-advanced danger"><ion-icon name="trash-outline"></ion-icon></button>
            </div>
        </div>
    `).join('');
}

function addTierRow() {
    achievementTiers.push({ quota:0, incentive:0, is_shared:0 });
    renderTierRows();
}

async function saveIncentiveConfig() {
    const rankId = document.getElementById('imRankId').value;
    const rankName = document.getElementById('imRankNameActual').value;
    
    // Tiers
    const tierData = [];
    document.querySelectorAll('.tier-quota').forEach((el, i) => {
        tierData.push({ quota: el.value, incentive: document.querySelectorAll('.tier-inc')[i].value });
    });
    
    // Plans
    const planData = [];
    document.querySelectorAll('.inc-qty').forEach((el, i) => {
        planData.push({
            plan_id: el.dataset.planId,
            monthly_quota: el.value,
            base_incentive: document.querySelectorAll('.inc-base')[i].value,
            quota_50: document.querySelectorAll('.inc-50-qty')[i].value,
            incentive_50: document.querySelectorAll('.inc-50-inc')[i].value,
            quota_10: document.querySelectorAll('.inc-10-qty')[i].value,
            incentive_10: document.querySelectorAll('.inc-10-inc')[i].value
        });
    });

    const countDownline = document.getElementById('imCountDownline').checked ? 1 : 0;
    const allowMulti = document.getElementById('imAllowMulti').checked ? 1 : 0;
    const startDay = document.getElementById('imStartDay').value;
    const endDay = document.getElementById('imEndDay').value;

    try {
        await fetch(`${API}?action=save_incentive_tiers`, {
            method: 'POST',
            body: JSON.stringify({ rank_name: rankName, tiers: tierData })
        });
        await fetch(`${API}?action=save_incentive_config`, {
            method: 'POST',
            body: JSON.stringify({ 
                rank_id: rankId, 
                incentives: planData, 
                allow_multipayout: allowMulti, 
                count_downline: countDownline,
                start_day: startDay,
                end_day: endDay
            })
        });
        closeModal('incModal');
    } catch (e) { alert(e.message); }
}

function showPageError(msg) {
    const b = document.getElementById('pageBanner');
    b.innerText = msg;
    b.style.display = 'block';
}
