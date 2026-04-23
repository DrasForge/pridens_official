// registration_folder/js/registration.js
document.addEventListener('DOMContentLoaded', () => {
    const apiBase = 'api';
    let currentStep = 1;
    let registrationData = null;

    // Elements
    const wizardSteps = {
        1: document.getElementById('wizard-step-1'),
        2: document.getElementById('wizard-step-2'),
        3: document.getElementById('wizard-step-3')
    };
    const stepNodes = {
        1: document.getElementById('step-node-1'),
        2: document.getElementById('step-node-2'),
        3: document.getElementById('step-node-3')
    };

    const btnVerify = document.getElementById('btn-verify');
    const btnRegister = document.getElementById('btn-register');
    const btnBack = document.getElementById('btn-back');

    const inputs = {
        subId: document.getElementById('subscriber_id'),
        receipt: document.getElementById('receipt_number')
    };

    const displays = {
        name: document.getElementById('disp-name'),
        email: document.getElementById('disp-email'),
        phone: document.getElementById('disp-phone'),
        plan: document.getElementById('disp-plan'),
        resId: document.getElementById('res-agent-id'),
        resPass: document.getElementById('res-password')
    };

    const errors = {
        1: document.getElementById('error-step-1'),
        2: document.getElementById('error-step-2')
    };

    // --- Navigation Logic ---
    function goToStep(step) {
        // Hide all steps
        Object.values(wizardSteps).forEach(el => el.classList.remove('active'));
        // Show current step
        wizardSteps[step].classList.add('active');
        
        // Update nodes
        Object.keys(stepNodes).forEach(key => {
            if (key < step) {
                stepNodes[key].classList.add('completed');
                stepNodes[key].innerHTML = '<ion-icon name="checkmark"></ion-icon>';
            } else if (key == step) {
                stepNodes[key].classList.add('active');
                stepNodes[key].classList.remove('completed');
                stepNodes[key].innerText = key;
            } else {
                stepNodes[key].classList.remove('active', 'completed');
                stepNodes[key].innerText = key;
            }
        });
        currentStep = step;
    }

    // --- Step 1: Verification ---
    btnVerify.onclick = async () => {
        const subId = inputs.subId.value.trim();
        const receipt = inputs.receipt.value.trim();

        if (!subId || !receipt) {
            errors[1].innerText = "Please provide both Subscriber ID and Receipt No.";
            return;
        }

        errors[1].innerText = "";
        toggleLoading(btnVerify, true);

        try {
            const res = await fetch(`${apiBase}/check_eligibility.php?subscriber_id=${subId}&receipt_number=${receipt}`);
            const data = await res.json();

            if (data.status === 'success') {
                registrationData = data.subscriber;
                // Populate Step 2
                displays.name.innerText = `${registrationData.first_name} ${registrationData.last_name}`;
                displays.email.innerText = registrationData.email;
                displays.phone.innerText = registrationData.contact_number;
                // Plan info is a bit complex, but we'll show a default for now
                displays.plan.innerText = "Active Subscriber";

                goToStep(2);
            } else {
                errors[1].innerText = data.message;
            }
        } catch (e) {
            errors[1].innerText = "Connection error. Please try again.";
        } finally {
            toggleLoading(btnVerify, false);
        }
    };

    // --- Step 2: Registration ---
    btnRegister.onclick = async () => {
        toggleLoading(btnRegister, true);
        errors[2].innerText = "";

        try {
            const res = await fetch(`${apiBase}/register_agent.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    subscriber_id: inputs.subId.value.trim(),
                    receipt_number: inputs.receipt.value.trim()
                })
            });
            const data = await res.json();

            if (data.status === 'success') {
                displays.resId.innerText = data.credentials.agent_id;
                displays.resPass.innerText = data.credentials.password;
                goToStep(3);
            } else {
                errors[2].innerText = data.message;
            }
        } catch (e) {
            errors[2].innerText = "Failed to finalize registration.";
        } finally {
            toggleLoading(btnRegister, false);
        }
    };

    btnBack.onclick = () => goToStep(1);

    // --- Helpers ---
    function toggleLoading(btn, isLoading) {
        const text = btn.querySelector('.btn-text');
        const loader = btn.querySelector('.loader');
        if (isLoading) {
            text.style.display = 'none';
            loader.style.display = 'block';
            btn.disabled = true;
        } else {
            text.style.display = 'block';
            loader.style.display = 'none';
            btn.disabled = false;
        }
    }
});
