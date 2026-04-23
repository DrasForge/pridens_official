// admin_folder/js/agent_applications.js
const apiBase = '../admin_API';

document.addEventListener('DOMContentLoaded', () => {
    fetchPromotions();
    fetchHistory();
});

window.switchThemeTab = (tab) => {
    if (tab === 'pending') {
        document.getElementById('tab-pending').classList.add('active');
        document.getElementById('tab-pending').style.borderBottomColor = 'var(--accent-blue)';
        document.getElementById('tab-pending').style.color = '#fff';
        document.getElementById('tab-history').classList.remove('active');
        document.getElementById('tab-history').style.borderBottomColor = 'transparent';
        document.getElementById('tab-history').style.color = 'var(--text-dim)';
        document.getElementById('container-pending').style.display = 'block';
        document.getElementById('container-history').style.display = 'none';
        fetchPromotions();
    } else {
        document.getElementById('tab-history').classList.add('active');
        document.getElementById('tab-history').style.borderBottomColor = 'var(--accent-blue)';
        document.getElementById('tab-history').style.color = '#fff';
        document.getElementById('tab-pending').classList.remove('active');
        document.getElementById('tab-pending').style.borderBottomColor = 'transparent';
        document.getElementById('tab-pending').style.color = 'var(--text-dim)';
        document.getElementById('container-history').style.display = 'block';
        document.getElementById('container-pending').style.display = 'none';
        fetchHistory();
    }
};

async function fetchPromotions() {
    const list = document.getElementById('promoList');
    list.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;"><div class="loader" style="margin: 0 auto; border-top-color: var(--primary-color);"></div></td></tr>';

    try {
        const res = await fetch(`${apiBase}/get_pending_promotions.php`);
        const data = await res.json();

        if (data.status === 'success') {
            renderPromotions(data.data);
        } else {
            list.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">Error: ${data.message}</td></tr>`;
        }
    } catch (e) {
        list.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">Network Error</td></tr>`;
    }
}

function renderPromotions(promos) {
    const list = document.getElementById('promoList');
    if (!promos || promos.length === 0) {
        list.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-dim);">No pending promotions at this time.</td></tr>`;
        return;
    }

    list.innerHTML = promos.map(p => {
        const dateRequested = new Date(p.requested_at).toLocaleDateString();
        const agentSince = new Date(p.agent_since).toLocaleDateString();
        return `
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 16px 24px;">
                    <div style="font-weight: 600; color: #fff;">${p.first_name} ${p.last_name}</div>
                    <div style="font-size: 0.75rem; color: var(--text-dim);">${p.agent_id}</div>
                </td>
                <td style="padding: 16px 24px; color: var(--text-dim);">${agentSince}</td>
                <td style="padding: 16px 24px;">
                    <span style="background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; color: #94a3b8;">${p.current_rank}</span>
                </td>
                <td style="padding: 16px 24px;">
                    <span style="background: rgba(99,102,241,0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; color: #818cf8; border: 1px solid rgba(99,102,241,0.2);">
                        ${p.target_rank}
                    </span>
                </td>
                <td style="padding: 16px 24px; color: var(--text-dim);">${dateRequested}</td>
                <td style="padding: 16px 24px; text-align: center;">
                    <div style="display: flex; gap: 8px; justify-content: center;">
                        <button onclick="processPromotion(${p.promotion_id}, 'approve')" style="background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s;">Approve</button>
                        <button onclick="processPromotion(${p.promotion_id}, 'reject')" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s;">Reject</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

window.processPromotion = async (promotionId, action) => {
    const confirmMsg = action === 'approve' ? 'Approve this promotion?' : 'Reject this promotion?';
    if (!confirm(confirmMsg)) return;

    try {
        const res = await fetch(`${apiBase}/process_promotion_approval.php`, {
            method: 'POST',
            body: JSON.stringify({ promotion_id: promotionId, action: action })
        });
        const data = await res.json();
        
        if (data.status === 'success') {
            fetchPromotions();
            fetchHistory();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) {
        alert('Network Error');
    }
};

async function fetchHistory() {
    const list = document.getElementById('historyList');
    list.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 40px;"><div class="loader" style="margin: 0 auto; border-top-color: var(--primary-color);"></div></td></tr>';

    try {
        const res = await fetch(`${apiBase}/get_approved_promotions.php`);
        const data = await res.json();

        if (data.status === 'success') {
            renderHistory(data.data);
        } else {
            list.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 40px; color: #ef4444;">Error: ${data.message}</td></tr>`;
        }
    } catch (e) {
        list.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 40px; color: #ef4444;">Network Error</td></tr>`;
    }
}

function renderHistory(promos) {
    const list = document.getElementById('historyList');
    if (!promos || promos.length === 0) {
        list.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-dim);">No approval history found.</td></tr>`;
        return;
    }

    list.innerHTML = promos.map(p => {
        const approvedOn = new Date(p.approved_at).toLocaleDateString();
        return `
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 16px 24px;">
                    <div style="font-weight: 600; color: #fff;">${p.first_name} ${p.last_name}</div>
                    <div style="font-size: 0.75rem; color: var(--text-dim);">${p.agent_id}</div>
                </td>
                <td style="padding: 16px 24px;">
                    <span style="background: rgba(99,102,241,0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; color: #818cf8; border: 1px solid rgba(99,102,241,0.2);">
                        ${p.target_rank}
                    </span>
                </td>
                <td style="padding: 16px 24px; color: var(--text-dim);">${approvedOn}</td>
                <td style="padding: 16px 24px; text-align: center;">
                    <a href="print_agency_docs.html?agent_id=${p.agent_id}&rank=${encodeURIComponent(p.target_rank)}&date=${encodeURIComponent(approvedOn)}&name=${encodeURIComponent(p.first_name + ' ' + p.last_name)}" target="_blank" class="btn-primary" style="padding: 6px 16px; font-size: 0.8rem; text-decoration: none; border-radius: 6px;">
                        <ion-icon name="print-outline" style="margin-right: 4px;"></ion-icon> Print Agency
                    </a>
                </td>
            </tr>
        `;
    }).join('');
}
