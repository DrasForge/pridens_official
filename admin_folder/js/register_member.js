// admin_folder/js/register_member.js

document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    const totalSteps = 5;
    const beneficiaries = [];

    const steps = document.querySelectorAll('.step-content');
    const progressSteps = document.querySelectorAll('.progress-step');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const btnSubmit = document.getElementById('btn-submit');

    // --- NAVIGATION ---
    function updateWizard() {
        steps.forEach(s => s.classList.remove('active'));
        progressSteps.forEach(ps => {
            const stepNum = parseInt(ps.dataset.step);
            ps.classList.remove('active', 'completed');
            if (stepNum === currentStep) ps.classList.add('active');
            if (stepNum < currentStep) ps.classList.add('completed');
        });

        document.getElementById(`step-${currentStep}`).classList.add('active');

        btnPrev.style.display = currentStep === 1 ? 'none' : 'block';
        if (currentStep === totalSteps) {
            btnNext.style.display = 'none';
            btnSubmit.style.display = 'block';
        } else {
            btnNext.style.display = 'block';
            btnSubmit.style.display = 'none';
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    btnNext.addEventListener('click', () => {
        if (validateStep(currentStep)) {
            currentStep++;
            updateWizard();
        }
    });

    btnPrev.addEventListener('click', () => {
        currentStep--;
        updateWizard();
    });

    function validateStep(step) {
        // Basic validation for now - check required fields
        const activeStep = document.getElementById(`step-${step}`);
        const inputs = activeStep.querySelectorAll('input[required], select[required]');
        let valid = true;

        inputs.forEach(i => {
            if (!i.value.trim()) {
                i.style.borderColor = '#ef4444';
                valid = false;
            } else {
                i.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            }
        });

        if (step === 1) {
            const si = document.getElementById('si_number').value.trim();
            const plan = document.getElementById('plan_type').value.trim();
            const feedback = document.getElementById('si-feedback');
            // If SI was typed but not verified yet, block
            if (si && !plan) {
                feedback.style.display = 'block';
                feedback.className = 'field-feedback error';
                feedback.innerText = '✗ Please verify the SI number before proceeding.';
                document.getElementById('si_number').style.borderColor = '#ef4444';
                valid = false;
            }
            // If no SI at all, warn but allow (pre-registration)
            if (!si && !plan) {
                if (!confirm('No Sales Invoice verified. Continue as pre-registration?')) {
                    valid = false;
                }
            }
        }

        return valid;
    }

    // --- SI VERIFICATION ---
    const btnVerifySI = document.getElementById('verify-si');
    btnVerifySI.addEventListener('click', async () => {
        const si = document.getElementById('si_number').value.trim();
        const feedback = document.getElementById('si-feedback');
        
        if (!si) { feedback.style.display='block'; feedback.className='field-feedback error'; feedback.innerText='Please enter a Sales Invoice number.'; return; }

        btnVerifySI.disabled = true;
        btnVerifySI.innerText = 'Checking...';
        feedback.style.display = 'none';

        try {
            const res = await fetch(`../admin_API/verify_si.php?si=${encodeURIComponent(si)}`);
            const r = await res.json();
            
            feedback.style.display = 'block';
            if (r.success) {
                feedback.className = 'field-feedback success';
                feedback.innerText = `✓ Valid — ${r.data.plan}`;

                // Store plan_id as hidden value for submission
                document.getElementById('plan_id').value = r.data.plan_id;
                document.getElementById('plan_type').value = r.data.plan;

                // Auto-fill referral from transaction if present
                if (r.data.agent_code) {
                    const agentInput = document.getElementById('agent_code');
                    agentInput.value = r.data.agent_code;
                    agentInput.readOnly = true;
                    agentInput.style.opacity = '0.7';
                    // Show agent name immediately
                    const nameDisplay = document.getElementById('agent_name');
                    const agentFeedback = document.getElementById('agent-feedback');
                    if (r.data.agent_name) {
                        nameDisplay.value = 'Referred by: ' + r.data.agent_name;
                        nameDisplay.style.display = 'block';
                        agentFeedback.style.display = 'block';
                        agentFeedback.className = 'field-feedback success';
                        agentFeedback.innerText = '✓ Agent verified from transaction';
                    } else {
                        verifyAgent(r.data.agent_code);
                    }
                }
            } else {
                feedback.className = 'field-feedback error';
                feedback.innerText = '✗ ' + r.error;
                document.getElementById('plan_id').value = '';
                document.getElementById('plan_type').value = '';
            }
        } catch (e) {
            console.error(e);
            feedback.style.display = 'block';
            feedback.className = 'field-feedback error';
            feedback.innerText = 'Connection error. Please try again.';
        } finally {
            btnVerifySI.disabled = false;
            btnVerifySI.innerText = 'Verify SI';
        }
    });

    // --- AGENT VERIFICATION ---
    const btnVerifyAgent = document.getElementById('verify-agent');
    btnVerifyAgent.addEventListener('click', async () => {
        const code = document.getElementById('agent_code').value.trim();
        const feedback = document.getElementById('agent-feedback');
        if (!code) {
            feedback.style.display = 'block';
            feedback.className = 'field-feedback error';
            feedback.innerText = 'Please enter an Agent Referral Code.';
            return;
        }
        btnVerifyAgent.disabled = true;
        btnVerifyAgent.innerText = '...';
        await verifyAgent(code);
        btnVerifyAgent.disabled = false;
        btnVerifyAgent.innerText = 'Check';
    });

    async function verifyAgent(code) {
        const feedback = document.getElementById('agent-feedback');
        const nameDisplay = document.getElementById('agent_name');
        feedback.style.display = 'none';
        try {
            const res = await fetch(`../admin_API/verify_agent.php?id=${encodeURIComponent(code)}`);
            const r = await res.json();
            feedback.style.display = 'block';
            if (r.success) {
                feedback.className = 'field-feedback success';
                feedback.innerText = '✓ Agent Verified';
                nameDisplay.value = 'Referred by: ' + r.full_name;
                nameDisplay.style.display = 'block';
            } else {
                feedback.className = 'field-feedback error';
                feedback.innerText = '✗ ' + (r.error || 'Agent not found or inactive.');
                nameDisplay.style.display = 'none';
            }
        } catch (e) {
            feedback.style.display = 'block';
            feedback.className = 'field-feedback error';
            feedback.innerText = 'Connection error. Please try again.';
        }
    }

    // --- CASCADING LOCATIONS ---
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');

    async function loadProvinces() {
        const res = await fetch('../admin_API/get_locations.php?type=provinces');
        const provinces = await res.json();
        provinceSelect.innerHTML = '<option value="">Select Province</option>' + 
            provinces.map(p => `<option value="${p}">${p}</option>`).join('');
    }

    provinceSelect.addEventListener('change', async () => {
        const prov = provinceSelect.value;
        if (!prov) {
            citySelect.disabled = true;
            return;
        }
        const res = await fetch(`../admin_API/get_locations.php?type=cities&province=${prov}`);
        const cities = await res.json();
        citySelect.innerHTML = '<option value="">Select City/Municipality</option>' + 
            cities.map(c => `<option value="${c}">${c}</option>`).join('');
        citySelect.disabled = false;
        barangaySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Select Barangay First</option>';
    });

    citySelect.addEventListener('change', async () => {
        const prov = provinceSelect.value;
        const city = citySelect.value;
        if (!city) {
            barangaySelect.disabled = true;
            return;
        }
        const res = await fetch(`../admin_API/get_locations.php?type=barangays&province=${prov}&city=${city}`);
        const brgys = await res.json();
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>' + 
            brgys.map(b => `<option value="${b}">${b}</option>`).join('');
        barangaySelect.disabled = false;
    });

    loadProvinces();

    // --- BENEFICIARIES ---
    const benList = document.getElementById('beneficiaries-list');
    const btnAddBen = document.getElementById('add-beneficiary');
    let benCount = 0;

    function addBeneficiary() {
        if (benCount >= 3) {
            alert('Maximum of 3 beneficiaries only.');
            return;
        }
        benCount++;
        const id = Date.now();
        const div = document.createElement('div');
        div.className = 'beneficiary-item fade-in';
        div.id = `ben-${id}`;
        div.innerHTML = `
            <button type="button" class="btn-remove-ben" onclick="removeBen(${id})"><ion-icon name="trash-outline"></ion-icon></button>
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="ben-name" required placeholder="e.g. John Doe Jr.">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" class="ben-dob" required>
                </div>
                <div class="form-group">
                    <label>Relation</label>
                    <select class="ben-relation" required>
                        <option value="Spouse">Spouse</option>
                        <option value="Child">Child</option>
                        <option value="Parent">Parent</option>
                        <option value="Sibling">Sibling</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contact (Optional)</label>
                    <input type="tel" class="ben-contact" placeholder="912...">
                </div>
            </div>
        `;
        benList.appendChild(div);
    }

    window.removeBen = (id) => {
        const el = document.getElementById(`ben-${id}`);
        if (el) {
            el.remove();
            benCount--;
        }
    };

    btnAddBen.addEventListener('click', addBeneficiary);
    // Add first beneficiary by default
    addBeneficiary();

    // --- FORM SUBMISSION ---
    btnSubmit.addEventListener('click', async () => {
        if (!validateStep(5)) return;

        const benData = [];
        document.querySelectorAll('.beneficiary-item').forEach(item => {
            benData.push({
                full_name: item.querySelector('.ben-name').value,
                date_of_birth: item.querySelector('.ben-dob').value,
                relation: item.querySelector('.ben-relation').value,
                contact_number: item.querySelector('.ben-contact').value
            });
        });

        const formData = {
            sales_invoice_number: document.getElementById('si_number').value,
            plan_id: document.getElementById('plan_id').value,
            referral_code: document.getElementById('agent_code').value,
            last_name: document.getElementById('last_name').value,
            first_name: document.getElementById('first_name').value,
            middle_name: document.getElementById('middle_name').value,
            suffix: document.getElementById('suffix').value,
            date_of_birth: document.getElementById('dob').value,
            place_of_birth: document.getElementById('place_of_birth').value,
            gender: document.getElementById('gender').value,
            civil_status: document.getElementById('civil_status').value,
            nationality: document.getElementById('nationality').value,
            occupation: document.getElementById('occupation').value,
            email: document.getElementById('email').value,
            contact_number: document.getElementById('country_code').value + document.getElementById('contact_number').value,
            source_of_income: document.getElementById('source_of_income').value,
            monthly_income: document.getElementById('monthly_income').value,
            address_house_street: document.getElementById('house_street').value,
            address_region: document.getElementById('province').value,
            address_city: document.getElementById('city').value,
            address_barangay: document.getElementById('barangay').value,
            address_zip_code: document.getElementById('zip_code').value,
            beneficiaries: benData
        };

        btnSubmit.innerText = 'Processing...';
        btnSubmit.disabled = true;

        try {
            const res = await fetch('../admin_API/register_subscriber.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const r = await res.json();
            if (r.success) {
                alert(`Member Registered Successfully! Account ID: ${r.account_id}`);
                window.location.href = 'accounts.html';
            } else {
                alert('Error: ' + r.error);
                btnSubmit.innerText = 'Finalize Registration';
                btnSubmit.disabled = false;
            }
        } catch (e) {
            console.error(e);
            alert('A network error occurred.');
            btnSubmit.disabled = false;
        }
    });
});
