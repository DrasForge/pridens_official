document.addEventListener('DOMContentLoaded', function () {
    const btnVerify = document.getElementById('btn-verify');
    const subscriberInput = document.getElementById('subscriber_id');
    const msg = document.getElementById('verify-msg');

    const btnVerifyPromo = document.getElementById('btn-verify-promo');
    const promoAgentInput = document.getElementById('promo_agent_code');
    const promoMsg = document.getElementById('promo-verify-msg');

    if (btnVerify) {
        btnVerify.addEventListener('click', async function () {
            const subId = subscriberInput.value.trim();
            if (!subId) {
                msg.textContent = 'Please enter a Subscriber ID.';
                msg.style.color = 'red';
                return;
            }

            btnVerify.disabled = true;
            btnVerify.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            msg.textContent = '';

            try {
                const formData = new FormData();
                formData.append('subscriber_id', subId);

                const response = await fetch('api/api.php?action=check_subscriber', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    msg.textContent = 'Subscriber Verified!';
                    msg.style.color = 'green';
                    const data = result.data;
                    setVal('firstname', data.firstname);
                    setVal('middlename', data.middlename);
                    setVal('lastname', data.lastname);
                    setVal('birthdate', data.birthdate);
                    setVal('gender', data.gender);
                    setVal('marital_status', data.marital_status);
                    setVal('province', data.province);
                    setVal('city', data.city);
                    setVal('barangay', data.barangay);
                    setVal('address_line', data.address_line);
                    setVal('email', data.email);
                    setVal('contact', data.contact);
                    setVal('referral_code', data.referral_code);
                } else {
                    msg.textContent = result.message;
                    msg.style.color = 'red';
                    clearFields();
                }
            } catch (error) {
                console.error(error);
                msg.textContent = 'Connection error. Please try again.';
                msg.style.color = 'red';
            } finally {
                btnVerify.disabled = false;
                btnVerify.innerHTML = '<i class="fas fa-search"></i> Verify';
            }
        });
    }

    if (btnVerifyPromo) {
        btnVerifyPromo.addEventListener('click', async function () {
            const agentCode = promoAgentInput.value.trim();
            if (!agentCode) {
                promoMsg.textContent = 'Please enter your Agent Code.';
                promoMsg.style.color = 'red';
                return;
            }

            btnVerifyPromo.disabled = true;
            btnVerifyPromo.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            promoMsg.textContent = '';
            document.getElementById('promo-agent-info').style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('agent_code', agentCode);

                const response = await fetch('api/api.php?action=check_agent_for_promotion', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    promoMsg.textContent = 'Agent Verified!';
                    promoMsg.style.color = 'green';

                    document.getElementById('promo-agent-info').style.display = 'block';
                    document.getElementById('promo-agent-name').textContent = result.data.full_name;
                    document.getElementById('promo-agent-pos').textContent = 'Current: ' + result.data.current_position;
                } else {
                    promoMsg.textContent = result.message;
                    promoMsg.style.color = 'red';
                }
            } catch (error) {
                console.error(error);
                promoMsg.textContent = 'Connection error. Please try again.';
                promoMsg.style.color = 'red';
            } finally {
                btnVerifyPromo.disabled = false;
                btnVerifyPromo.innerHTML = '<i class="fas fa-search"></i> Verify';
            }
        });
    }

    // Handle FORM SUBMISSION
    const form = document.querySelector('#wizardForm');
    const btnSubmit = document.querySelector('.btn-submit');

    if (form && btnSubmit) {
        btnSubmit.addEventListener('click', async function (e) {
            e.preventDefault(); // Stop normal submission to process.php

            // Basic Validation again just in case
            // ... (relies on HTML5 required mostly or form-wizard.js validation)

            // Loading
            const originalText = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            try {
                const formData = new FormData(form);
                const endpoint = 'api/api.php?action=' + (window.APP_TYPE === 'promotion' ? 'process_promotion' : 'register_agent_from_sub');

                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showSuccessReceipt(result.data, window.APP_TYPE === 'promotion');
                } else {
                    alert('Application Failed: ' + result.message);
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }
            } catch (error) {
                console.error(error);
                alert('An error occurred. Please try again.');
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;
            }
        });
    }

    function showSuccessReceipt(data, isPromotion = false) {
        // Replace entire body or wizard content with Receipt UI
        const container = document.querySelector('.wizard-container');
        const title = isPromotion ? 'Promotion Applied!' : 'Congratulations, Agent!';
        const subtitle = isPromotion ? 'Your request has been submitted for review.' : 'You are now a registered Sales Agent.';
        const cardTitle = isPromotion ? 'Agent Promotion Application' : 'Official Agent Reference Card';

        container.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <div style="color: #2ecc71; font-size: 60px; margin-bottom: 20px;"><i class="fas fa-check-circle"></i></div>
                <h2>${title}</h2>
                <p>${subtitle}</p>
                
                <div class="receipt-box" style="background: #fff; padding: 25px; border: 1px dashed #333; margin: 20px auto; max-width: 450px; text-align: left; font-family: 'Courier New', monospace; color: #333; line-height: 1.4; box-shadow: 10px 10px 0 rgba(0,0,0,0.1);">
                    <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px;">
                        <strong style="font-size: 1.2rem;">PRIDENS TRADING CORP.</strong><br>
                        ${cardTitle}
                    </div>
                    <div style="margin-bottom: 10px; text-align:center;">
                         <strong>AGENT CODE: ${data.agent_code}</strong>
                    </div>
                    <table style="width: 100%; font-size: 0.9rem; border-collapse: collapse;">
                        <tr><td style="padding: 2px 0;"><strong>Full Name:</strong></td><td>${data.full_name.toUpperCase()}</td></tr>
                        ${isPromotion ? `<tr><td style="padding: 2px 0;"><strong>Requested Pos:</strong></td><td>${data.position}</td></tr>` : ''}
                        ${!isPromotion ? `<tr><td style="padding: 2px 0;"><strong>Member ID:</strong></td><td>${data.referral_code || 'N/A'}</td></tr>` : ''}
                        ${!isPromotion ? `<tr><td style="padding: 2px 0;"><strong>Init. Position:</strong></td><td>${data.position}</td></tr>` : ''}
                        <tr><td style="padding: 2px 0;"><strong>Phone:</strong></td><td>${data.phone}</td></tr>
                        ${!isPromotion ? `<tr><td style="padding: 2px 0;"><strong>Birthday:</strong></td><td>${data.birthdate}</td></tr>` : ''}
                        ${!isPromotion ? `<tr><td style="padding: 2px 0;"><strong>Address:</strong></td><td>${data.address}</td></tr>` : ''}
                        <tr><td style="padding: 2px 0;"><strong>Date:</strong></td><td>${new Date().toLocaleDateString()}</td></tr>
                        <tr><td style="padding: 2px 0;"><strong>Status:</strong></td><td><span style="color:#d35400; font-weight:bold;">PENDING APPROVAL</span></td></tr>
                    </table>
                    
                    ${!isPromotion ? `
                    <div style="border-top: 1px dashed #333; margin-top: 15px; padding-top: 10px; background: #f9f9f9; padding: 10px; border-radius: 4px;">
                        <strong style="font-size: 0.8rem; display: block; margin-bottom: 5px;">LOGIN CREDENTIALS</strong>
                        <div style="font-size: 0.9rem;">
                            Username: <span style="font-weight:bold;">${data.username}</span><br>
                            Password: <span style="font-weight:bold; color: #d32f2f; font-size: 1.1rem;">${data.password}</span>
                        </div>
                    </div>
                    ` : `
                    <div style="border-top: 1px dashed #333; margin-top: 15px; padding-top: 10px; font-size: 0.85rem; color: #666; text-align: center;">
                        Please coordinate with the admin for the approval of your promotion.
                    </div>
                    `}
                    
                    <div style="text-align: center; margin-top: 15px; font-size: 0.75rem; color: #666;">
                        * This serves as your temporary proof of application.<br>
                        Keep this receipt safe.
                    </div>
                </div>

                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Receipt</button>
                <br><br>
                <a href="index.php" style="color: #666;">Back to Home</a>
            </div>
        `;
    }

    function setVal(id, value) {
        const el = document.getElementById(id);
        if (!el) return;

        if (el.tagName === 'SELECT' && value) {
            // Case-insensitive match for select options
            const options = Array.from(el.options);
            const match = options.find(opt => opt.value.toLowerCase() === value.toLowerCase());
            if (match) {
                el.value = match.value;
            } else {
                console.warn(`No matching option for ${id}: ${value}`);
                el.value = '';
            }
        } else if (el.type === 'date' && value) {
            // Ensure YYYY-MM-DD for date inputs
            try {
                const date = new Date(value);
                if (!isNaN(date.getTime())) {
                    el.value = date.toISOString().split('T')[0];
                } else {
                    el.value = value; // Fallback
                }
            } catch (e) {
                el.value = value;
            }
        } else {
            el.value = value || '';
        }

        // Trigger input event for validation cleanup
        el.dispatchEvent(new Event('input'));
        el.dispatchEvent(new Event('change'));
    }

    function clearFields() {
        const ids = ['firstname', 'lastname', 'middlename', 'birthdate', 'gender', 'marital_status', 'province', 'city', 'barangay', 'address_line'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    }
});
