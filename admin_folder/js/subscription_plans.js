/* admin_folder/js/subscription_plans.js */

// Global storage for verified product IDs
const verifiedProducts = {
    skuOnboarding: null,
    skuMonthly: null,
    skuPostTerm: null
};

let globalRanks = [];
const MAX_BRACKETS = 4;
const MIN_BRACKETS = 2;

async function fetchRanks() {
    try {
        const res = await fetch('../admin_API/agent_api.php?action=get_agent_ranks');
        const json = await res.json();
        if (json.status === 'success') {
            globalRanks = json.data;
        }
    } catch (e) {
        console.error("Failed to fetch ranks", e);
    }
}

// --- Dynamic Row Management ---

function addResidualRow(rank = '', amount = '') {
    const container = document.getElementById('residualsList');
    if (!container) return;
    
    // Ensure we have at least one rank to select
    if (!globalRanks.length && !rank) {
        console.warn("No ranks available to add residual row");
    }

    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.style.gridTemplateColumns = '1fr 1fr auto';
    
    let options = globalRanks.map(r => `
        <option value="${r.rank_name}" ${rank === r.rank_name ? 'selected' : ''}>${r.rank_name}</option>
    `).join('');

    // If the rank provided isn't in current list (legacy/deleted), add it anyway to preserve data
    if (rank && !globalRanks.find(r => r.rank_name === rank)) {
        options = `<option value="${rank}" selected>${rank} (Legacy)</option>` + options;
    }

    row.innerHTML = `
        <select class="residual-rank">
            <option value="" disabled ${!rank ? 'selected' : ''}>Select Rank...</option>
            ${options}
        </select>
        <input type="number" class="residual-amount" placeholder="Amount (₱)" value="${amount}">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><ion-icon name="trash-outline"></ion-icon></button>
    `;
    container.appendChild(row);
}

function addRequirementRow(text = '') {
    const container = document.getElementById('requirementsList');
    if (!container) return;
    
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.style.gridTemplateColumns = '1fr auto';
    row.innerHTML = `
        <input type="text" class="requirement-name" placeholder="Document Name" value="${text}">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><ion-icon name="trash-outline"></ion-icon></button>
    `;
    container.appendChild(row);
}

function addBracketRow(from = '', to = '', bill = '') {
    const container = document.getElementById('bracketsList');
    if (!container) return;

    const currentCount = container.querySelectorAll('.bracket-card').length;
    if (currentCount >= MAX_BRACKETS) {
        alert(`Maximum of ${MAX_BRACKETS} brackets allowed.`);
        return;
    }
    
    const card = document.createElement('div');
    card.className = 'bracket-card';
    card.innerHTML = `
        <div class="form-grid" style="padding:0; grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-bottom:0;">
            <div class="form-group">
                <label>Approved From (day)</label>
                <input type="number" class="bracket-from" placeholder="e.g. 1" value="${from}" min="1" max="31">
            </div>
            <div class="form-group">
                <label>To (day)</label>
                <input type="number" class="bracket-to" placeholder="e.g. 14" value="${to}" min="1" max="31">
            </div>
            <div class="form-group">
                <label>Bill on (day of month)</label>
                <input type="number" class="bracket-bill" placeholder="e.g. 14" value="${bill}" min="1" max="31">
            </div>
        </div>
        <div class="bracket-summary">Approved day <span class="sum-from">...</span>&ndash;<span class="sum-to">...</span> &rarr; billed on the <b class="sum-bill">...</b> of every month</div>
        <button type="button" class="btn-remove" title="Remove Bracket"><ion-icon name="close-outline"></ion-icon></button>
    `;

    // Attach logic
    const fromInp = card.querySelector('.bracket-from');
    const toInp = card.querySelector('.bracket-to');
    const billInp = card.querySelector('.bracket-bill');
    const btnRem = card.querySelector('.btn-remove');

    const updateSum = () => {
        card.querySelector('.sum-from').innerText = fromInp.value || '...';
        card.querySelector('.sum-to').innerText = toInp.value || '...';
        card.querySelector('.sum-bill').innerText = billInp.value ? (billInp.value + (['11','12','13'].includes(billInp.value) ? 'th' : (billInp.value.endsWith('1') ? 'st' : (billInp.value.endsWith('2') ? 'nd' : (billInp.value.endsWith('3') ? 'rd' : 'th'))))) : '...';
    };

    [fromInp, toInp, billInp].forEach(inp => inp.addEventListener('input', updateSum));
    btnRem.addEventListener('click', () => {
        card.remove();
        updateBracketCounter();
    });

    container.appendChild(card);
    updateSum();
    updateBracketCounter();
}

function updateBracketCounter() {
    const list = document.getElementById('bracketsList');
    const counter = document.getElementById('bracketCounter');
    const addBtn = document.getElementById('addBracketBtn');
    if (!list || !counter) return;

    const count = list.querySelectorAll('.bracket-card').length;
    counter.innerText = `${count} of ${MAX_BRACKETS} brackets`;
    
    if (addBtn) {
        if (count >= MAX_BRACKETS) {
            addBtn.style.opacity = '0.5';
            addBtn.style.pointerEvents = 'none';
        } else {
            addBtn.style.opacity = '1';
            addBtn.style.pointerEvents = 'all';
        }
    }
}

// --- SKU Verification ---

async function verifySKU(inputId, feedbackId) {
    const input = document.getElementById(inputId);
    const feedback = document.getElementById(feedbackId);
    const sku = input.value;
    
    if (!sku) return;
    
    feedback.innerHTML = '<span style="color:var(--text-muted)">Verifying...</span>';
    try {
        const res = await fetch(`../admin_API/verify_sku.php?sku=${encodeURIComponent(sku)}`);
        const data = await res.json();
        
        if (data.success) {
            feedback.className = 'sku-feedback valid';
            feedback.innerHTML = `<ion-icon name="checkmark-circle"></ion-icon> ${data.product.name} - ₱${parseFloat(data.product.price).toLocaleString()}`;
            verifiedProducts[inputId] = data.product.id;

            // Autofill name and price fields
            const prefix = inputId.replace('sku', '');
            const nameId = prefix.charAt(0).toLowerCase() + prefix.slice(1) + 'Name';
            const priceId = prefix.charAt(0).toLowerCase() + prefix.slice(1) + 'Price';
            
            if (document.getElementById(nameId)) document.getElementById(nameId).value = data.product.name;
            if (document.getElementById(priceId)) document.getElementById(priceId).value = parseFloat(data.product.price).toFixed(2);
        } else {
            feedback.className = 'sku-feedback invalid';
            feedback.innerHTML = `<ion-icon name="close-circle"></ion-icon> SKU not found`;
            verifiedProducts[inputId] = null;
        }
    } catch (e) {
        feedback.innerHTML = 'Error communicating with server';
        verifiedProducts[inputId] = null;
    }
}

// --- Wizard Navigation ---
let currentWizardStep = 1;
const totalWizardSteps = 7;

function jumpToStep(n) {
    if (n === currentWizardStep) return;
    if (n > currentWizardStep) {
        // Validate all steps between current and target
        for (let i = currentWizardStep; i < n; i++) {
            if (!validateWizardStep(i)) return;
        }
    }
    const diff = n - currentWizardStep;
    changeStep(diff);
}

function changeStep(n) {
    if (n > 0 && !validateWizardStep(currentWizardStep)) return;

    const steps = document.querySelectorAll('.wizard-step');
    const tabs = document.querySelectorAll('.tab-item');
    
    steps[currentWizardStep-1].classList.remove('active');
    tabs[currentWizardStep-1].classList.remove('active');

    currentWizardStep += n;
    
    steps[currentWizardStep-1].classList.add('active');
    tabs[currentWizardStep-1].classList.add('active');

    // Update buttons
    document.getElementById('prevBtn').disabled = currentWizardStep === 1;
    document.getElementById('nextBtn').style.display = currentWizardStep === totalWizardSteps ? 'none' : 'flex';
    document.getElementById('submitBtn').style.display = currentWizardStep === totalWizardSteps ? 'flex' : 'none';

    if (currentWizardStep === totalWizardSteps) generateWizardSummary();
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateWizardStep(step) {
    const stepEl = document.getElementById(`step${step}`);
    if (!stepEl) return true;

    // Check required fields
    const inputs = stepEl.querySelectorAll('[required]');
    for (let input of inputs) {
        if (!input.value) {
            alert('Please fill in all required fields.');
            input.focus();
            return false;
        }
    }

    // Step 2 specific: SKU Verification
    if (step === 2) {
        const hasInsurance = document.querySelector('input[name="has_insurance"]:checked').value === '1';
        if (!verifiedProducts.skuOnboarding) {
            alert('Please verify the Onboarding SKU before proceeding.');
            return false;
        }
        if (hasInsurance && !verifiedProducts.skuMonthly) {
            alert('Please verify the Monthly SKU before proceeding.');
            return false;
        }
    }

    // Step 7 specific: Brackets count
    if (step === 7) {
        const count = document.querySelectorAll('#bracketsList .bracket-card').length;
        if (count < MIN_BRACKETS || count > MAX_BRACKETS) {
            alert(`Please define between ${MIN_BRACKETS} and ${MAX_BRACKETS} billing brackets.`);
            return false;
        }

        // Check if all fields are filled
        const bracketInps = document.querySelectorAll('#bracketsList input');
        for (let inp of bracketInps) {
            if (!inp.value) {
                alert('All bracket fields (From, To, Bill On) must be filled.');
                inp.focus();
                return false;
            }
        }
    }

    return true;
}

function toggleInsuranceMode() {
    const hasInsurance = document.querySelector('input[name="has_insurance"]:checked').value === '1';
    
    // Step 1 fields
    document.querySelectorAll('.ins-field').forEach(el => {
        el.style.display = hasInsurance ? 'flex' : 'none';
    });

    // Step 2 sections
    const monthlySku = document.getElementById('monthly-sku-section');
    const postTerm = document.getElementById('post-term-section');
    if (monthlySku) monthlySku.style.display = hasInsurance ? 'block' : 'none';
    if (postTerm) postTerm.style.display = hasInsurance ? 'block' : 'none';

    // Step 3: Commission Levels 2-5
    const otc25 = document.getElementById('otc-levels-2-5');
    if (otc25) otc25.style.display = hasInsurance ? 'contents' : 'none';

    // Step 4: Allocations
    const tab4Text = document.getElementById('tab-4-text');
    if (tab4Text) tab4Text.innerText = hasInsurance ? 'Monthly Alloc.' : 'Subscription Alloc.';
    
    const collectionFee = document.getElementById('monthly-collection-fee-group');
    if (collectionFee) collectionFee.style.display = hasInsurance ? 'grid' : 'none';

    // Tabs 5 & 6
    const tab5 = document.getElementById('tab-5');
    const tab6 = document.getElementById('tab-6');
    if (tab5) tab5.style.display = hasInsurance ? 'flex' : 'none';
    if (tab6) tab6.style.display = hasInsurance ? 'flex' : 'none';
}

// --- Specific Wizard UI Logic ---

function switchFinTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.fin-pane').forEach(pane => pane.style.display = 'none');
    
    event.currentTarget.classList.add('active');
    document.getElementById('fin-' + tabId).style.display = 'block';
}

function togglePostTerm() {
    // Legacy - sections are now always visible or handled by SKU presence
}

function updateCardPreview() {
    const bgColor = document.getElementById('themeBg').value;
    const txtColor = document.getElementById('themeTxt').value;
    const pattern = document.getElementById('themePattern').value;
    const opacity = document.getElementById('themeOpacity').value;
    const planName = document.getElementById('planName').value || 'Emerald Pro Max';

    document.getElementById('themeBgHex').value = bgColor.toUpperCase();
    document.getElementById('themeTxtHex').value = txtColor.toUpperCase();
    document.getElementById('opaVal').innerText = opacity + '%';

    const card = document.getElementById('cardPreview');
    const patternOverlay = document.getElementById('cardPattern');
    const prevName = document.getElementById('prevPlanName');

    card.style.backgroundColor = bgColor;
    card.style.color = txtColor;
    prevName.innerText = planName;
    patternOverlay.style.opacity = opacity / 100;

    // Pattern background logic
    let bgImage = 'none';
    switch(pattern) {
        case 'waves':
            bgImage = 'url("data:image/svg+xml,%3Csvg width=\'100%25\' height=\'100%25\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M0 100 Q 250 50 500 100 T 1000 100\' fill=\'none\' stroke=\'white\' stroke-width=\'2\' opacity=\'0.2\'/%3E%3C/svg%3E")';
            break;
        case 'geometric':
            bgImage = 'repeating-linear-gradient(45deg, rgba(255,255,255,0.1) 0px, rgba(255,255,255,0.1) 2px, transparent 2px, transparent 10px)';
            break;
        case 'dots':
            bgImage = 'radial-gradient(rgba(255,255,255,0.2) 1px, transparent 1px)';
            patternOverlay.style.backgroundSize = '20px 20px';
            break;
    }
    patternOverlay.style.backgroundImage = bgImage;
}

function generateWizardSummary() {
    const summary = document.getElementById('summaryContent');
    if (!summary) return;

    summary.innerHTML = `
        <div class="review-section">
            <div class="section-header"><ion-icon name="information-circle"></ion-icon><h3>Plan Info</h3></div>
            <div class="review-grid">
                <span class="review-label">Plan Name:</span> <span class="review-value">${document.getElementById('planName').value}</span>
                <span class="review-label">Coverage:</span> <span class="review-value">${document.getElementById('coverage').value}</span>
                <span class="review-label">Terms:</span> <span class="review-value">${document.getElementById('terms').value}</span>
                <span class="review-label">Plan Term:</span> <span class="review-value">${document.getElementById('subTerm').value} Months</span>
                <span class="review-label">Quota Weight:</span> <span class="review-value">${document.getElementById('quotaWeight').value}x</span>
            </div>
        </div>
        <div class="review-section">
            <div class="section-header"><ion-icon name="card"></ion-icon><h3>Financials</h3></div>
            <div class="review-grid">
                <span class="review-label">Onboarding SKU:</span> <span class="review-value">${document.getElementById('skuOnboarding')?.value} (Verified)</span>
                <span class="review-label">Monthly SKU:</span> <span class="review-value">${document.getElementById('skuMonthly')?.value} (Verified)</span>
                <span class="review-label">L1 Commission:</span> <span class="review-value">₱${parseFloat(document.getElementById('otcL1')?.value || 0).toLocaleString()}</span>
                <span class="review-label">Monthly Reward:</span> <span class="review-value">₱${parseFloat(document.getElementById('monthlyPv')?.value || 0).toLocaleString()}</span>
            </div>
        </div>
        <div class="review-section">
            <div class="section-header"><ion-icon name="calendar"></ion-icon><h3>Billing Brackets</h3></div>
            <div class="review-grid">
                ${Array.from(document.querySelectorAll('#bracketsList .bracket-card')).map((card, i) => {
                    const from = card.querySelector('.bracket-from').value;
                    const to = card.querySelector('.bracket-to').value;
                    const bill = card.querySelector('.bracket-bill').value;
                    return `<span class="review-label">Bracket ${i+1}:</span> <span class="review-value">Approved ${from}-${to} &rarr; Bill on ${bill}</span>`;
                }).join('')}
            </div>
        </div>
    `;
}

// --- Initialization & Edit Mode Detection ---

document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const planId = urlParams.get('id');

    // Fetch dynamic ranks first
    await fetchRanks();

    // Add card design listeners
    ['themeBg', 'themeTxt', 'themePattern', 'themeOpacity', 'planName'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updateCardPreview);
    });

    // Insurance toggle listeners
    document.querySelectorAll('input[name="has_insurance"]').forEach(radio => {
        radio.addEventListener('change', toggleInsuranceMode);
    });

    if (planId) {
        document.querySelector('.welcome-banner h1').innerText = 'Edit Subscription Plan';
        document.querySelector('.welcome-banner p').innerText = 'Update the existing subscription package details.';
        await loadPlanDetails(planId);
    } else {
        // Default rows for Create mode
        if (globalRanks.length > 0) {
            addResidualRow(globalRanks[0].rank_name, 150);
        } else {
            addResidualRow('', 150);
        }
        addRequirementRow('Death Certificate');
        addRequirementRow('Valid Government ID');
        addBracketRow(1, 31, 30);
        updateCardPreview();
    }
});

async function loadPlanDetails(id) {
    const safeSet = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    const safeCheck = (id, checked) => { const el = document.getElementById(id); if (el) el.checked = checked; };

    try {
        const response = await fetch(`../admin_API/get_plan_details.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const plan = data.plan;
            
            // Step 1: Basic Info
            safeSet('planName', plan.plan_name);
            safeSet('coverage', plan.insurance_coverage);
            safeSet('terms', plan.payment_terms);
            safeSet('subTerm', plan.subscription_term_months);
            safeSet('insTerm', plan.insurance_term_months);
            safeSet('contPeriod', plan.contestability_period_days || 0);
            safeSet('quotaWeight', plan.quota_weight);
            
            const benRadio = document.querySelector(`input[name="requires_beneficiaries"][value="${plan.requires_beneficiaries}"]`);
            if (benRadio) benRadio.checked = true;

            const insRadio = document.querySelector(`input[name="has_insurance"][value="${plan.has_insurance}"]`);
            if (insRadio) insRadio.checked = true;
            toggleInsuranceMode();

            // Step 2: Fees (Auto-verify)
            if (document.getElementById('skuOnboarding')) document.getElementById('skuOnboarding').value = plan.onboarding_sku || '';
            if (document.getElementById('skuMonthly')) document.getElementById('skuMonthly').value = plan.monthly_sku || '';
            
            if (plan.post_term_sku_ref || plan.post_term_sku) {
                const skuVal = plan.post_term_sku_ref || plan.post_term_sku;
                if (document.getElementById('skuPostTerm')) document.getElementById('skuPostTerm').value = skuVal;
                if (document.getElementById('postTermCycle')) document.getElementById('postTermCycle').value = plan.post_term_payment_cycle;
                if (document.getElementById('postTermPrice')) document.getElementById('postTermPrice').value = plan.post_term_fee || 0;
            }

            // Trigger verification
            if (document.getElementById('skuOnboarding')) await verifySKU('skuOnboarding', 'onboardingFeedback');
            if (document.getElementById('skuMonthly')) await verifySKU('skuMonthly', 'monthlyFeedback');
            if (document.getElementById('skuPostTerm') && document.getElementById('skuPostTerm').value) await verifySKU('skuPostTerm', 'postTermFeedback');

            // Step 3: Financials
            const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
            setVal('otcL1', plan.otc_l1);
            setVal('otcL2', plan.otc_l2);
            setVal('otcL3', plan.otc_l3);
            setVal('otcL4', plan.otc_l4);
            setVal('otcL5', plan.otc_l5);

            setVal('monthlyPv', plan.monthly_pvoucher);
            setVal('monthlyMerchant', plan.monthly_merchant_handling);
            setVal('monthlyPoints', plan.monthly_points_rewards);
            setVal('monthlyCollection', plan.monthly_collection_fee);
            setVal('climbsPremium', plan.climbs_insurance_premium);

            if (document.getElementById('resL1')) document.getElementById('resL1').value = plan.residual_l1;
            if (document.getElementById('resL2')) document.getElementById('resL2').value = plan.residual_l2;
            if (document.getElementById('resL3')) document.getElementById('resL3').value = plan.residual_l3; 
            if (document.getElementById('resL4')) document.getElementById('resL4').value = plan.residual_l4;

            // Residuals
            const residualsList = document.getElementById('residualsList');
            residualsList.innerHTML = '';
            plan.position_residuals.forEach(r => addResidualRow(r.rank, r.amount));

            // Step 5: Insurance
            const setBenefit = (name, amtId, contId) => {
                const benefit = plan.insurance_benefits.find(b => b.name === name);
                if (benefit) {
                    document.getElementById(amtId).value = benefit.amount;
                    if (contId) document.getElementById(contId).checked = parseInt(benefit.contestability) === 1;
                }
            };
            setBenefit('Accidental Benefit', 'amt_acc_death', 'cont_acc_death');
            setBenefit('Life Benefit', 'amt_life', 'cont_life');
            setBenefit('Burial Expense', 'amt_burial', null);

            // Requirements
            const reqList = document.getElementById('requirementsList');
            reqList.innerHTML = '';
            plan.claim_requirements.forEach(req => addRequirementRow(req));

            // Brackets
            const bracList = document.getElementById('bracketsList');
            bracList.innerHTML = '';
            plan.billing_brackets.forEach(b => addBracketRow(b.from, b.to, b.bill_on));

            // Step 5: Card Design
            if (plan.card_theme) {
                safeSet('themeBg', plan.card_theme.bgColor || '#6366f1');
                safeSet('themeTxt', plan.card_theme.txtColor || '#ffffff');
                safeSet('themePattern', plan.card_theme.pattern || 'waves');
                safeSet('themeOpacity', plan.card_theme.opacity || 15);
                if (typeof updateCardPreview === 'function') updateCardPreview();
            }

        } else {
            alert('Error loading plan: ' + data.message);
        }
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

// --- Form Submission ---

document.getElementById('createPlanForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const urlParams = new URLSearchParams(window.location.search);
    const planId = urlParams.get('id');
    const submitBtn = document.getElementById('submitBtn');
    
    const payload = {
        id: planId || null,
        plan_name: document.getElementById('planName').value,
        has_insurance: document.querySelector('input[name="has_insurance"]:checked').value === '1',
        insurance_coverage: document.getElementById('coverage').value,
        payment_terms: document.getElementById('terms').value,
        subscription_term: parseInt(document.getElementById('subTerm').value),
        insurance_term: parseInt(document.getElementById('insTerm').value),
        contestability_period_days: parseInt(document.getElementById('contPeriod').value || 0),
        quota_weight: parseFloat(document.getElementById('quotaWeight').value),
        requires_beneficiaries: document.querySelector('input[name="requires_beneficiaries"]:checked').value === '1',
        onboarding_product_id: verifiedProducts.skuOnboarding,
        monthly_product_id: verifiedProducts.skuMonthly,
        
        // Advanced Fields
        post_term_enabled: document.getElementById('skuPostTerm')?.value ? true : false,
        post_term_product_id: verifiedProducts.skuPostTerm,
        post_term_sku: document.getElementById('skuPostTerm')?.value || null,
        post_term_fee: parseFloat(document.getElementById('postTermPrice')?.value || 0),
        post_term_payment_cycle: document.getElementById('postTermCycle')?.value || 'Annually',

        otc_l1: parseFloat(document.getElementById('otcL1').value || 0),
        otc_l2: parseFloat(document.getElementById('otcL2').value || 0),
        otc_l3: parseFloat(document.getElementById('otcL3').value || 0),
        otc_l4: parseFloat(document.getElementById('otcL4').value || 0),
        otc_l5: parseFloat(document.getElementById('otcL5').value || 0),

        monthly_pvoucher: parseFloat(document.getElementById('monthlyPv').value || 0),
        monthly_merchant_handling: parseFloat(document.getElementById('monthlyMerchant').value || 0),
        monthly_points_rewards: parseFloat(document.getElementById('monthlyPoints').value || 0),
        monthly_collection_fee: parseFloat(document.getElementById('monthlyCollection').value || 0),
        climbs_insurance_premium: parseFloat(document.getElementById('climbsPremium').value || 0),

        residual_l1: parseFloat(document.getElementById('resL1').value || 0),
        residual_l2: parseFloat(document.getElementById('resL2').value || 0),
        residual_l3: parseFloat(document.getElementById('resL3').value || 0),
        residual_l4: parseFloat(document.getElementById('resL4').value || 0),

        card_theme: {
            bgColor: document.getElementById('themeBg')?.value || '#6366f1',
            txtColor: document.getElementById('themeTxt')?.value || '#ffffff',
            pattern: document.getElementById('themePattern')?.value || 'waves',
            opacity: parseInt(document.getElementById('themeOpacity')?.value || 15)
        },
        
        position_residuals: Array.from(document.querySelectorAll('#residualsList .dynamic-row')).map(row => ({
            rank: row.querySelector('.residual-rank').value,
            amount: parseFloat(row.querySelector('.residual-amount').value || 0)
        })),
        
        insurance_benefits: [
            { 
                name: 'Accidental Benefit', 
                amount: parseFloat(document.getElementById('amt_acc_death').value || 0), 
                contestability: document.getElementById('cont_acc_death').checked 
            },
            { 
                name: 'Life Benefit', 
                amount: parseFloat(document.getElementById('amt_life').value || 0), 
                contestability: document.getElementById('cont_life').checked 
            },
            { 
                name: 'Burial Expense', 
                amount: parseFloat(document.getElementById('amt_burial').value || 0), 
                contestability: false 
            }
        ],
        
        claim_requirements: Array.from(document.querySelectorAll('.requirement-name'))
            .map(input => input.value)
            .filter(v => v !== ''),
            
        billing_brackets: Array.from(document.querySelectorAll('#bracketsList .bracket-card')).map(card => ({
            from: parseInt(card.querySelector('.bracket-from').value),
            to: parseInt(card.querySelector('.bracket-to').value),
            bill_on: parseInt(card.querySelector('.bracket-bill').value)
        }))
    };

    const apiUrl = planId ? '../admin_API/update_subscription_plan.php' : '../admin_API/save_subscription_plan.php';

    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="loader" style="width:20px; height:20px; margin-right:10px;"></div> Saving...';

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (result.success) {
            alert(planId ? 'Subscription Plan updated successfully!' : 'Subscription Plan created successfully!');
            window.location.href = 'subscription_plans.html';
        } else {
            alert('Error: ' + result.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Subscription Plan <ion-icon name="rocket-outline"></ion-icon>';
        }
    } catch (error) {
        console.error('Submit error:', error);
        alert('A server error occurred.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Subscription Plan <ion-icon name="rocket-outline"></ion-icon>';
    }
});
