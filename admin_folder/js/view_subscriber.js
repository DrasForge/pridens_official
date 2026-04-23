document.addEventListener('DOMContentLoaded', () => {
    // Mobile Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const openBtn = document.getElementById('open-sidebar');
    const closeBtn = document.getElementById('close-sidebar');

    const toggleSidebar = () => {
        sidebar.classList.toggle('open');
    };

    if (openBtn && closeBtn) {
        openBtn.addEventListener('click', toggleSidebar);
        closeBtn.addEventListener('click', toggleSidebar);
    }

    // Generic Submenu Toggle logic
    document.querySelectorAll('.nav-item-has-children').forEach(toggle => {
        const submenu = toggle.nextElementSibling;
        if (submenu && submenu.classList.contains('sub-menu')) {
            toggle.addEventListener('click', () => {
                submenu.classList.toggle('open');
                const icon = toggle.querySelector('.chevron');
                if (icon) {
                    icon.style.transform = submenu.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            });
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const accountId = urlParams.get('id');

    if (!accountId) {
        alert("No Account ID provided!");
        window.location.href = 'accounts.html';
        return;
    }

    const apiBaseUrl = '../admin_API';

    async function loadSubscriber() {
        try {
            const res = await fetch(`${apiBaseUrl}/get_subscriber.php?id=${accountId}`);
            const data = await res.json();

            if (data.status === 'success') {
                populateUI(data.data);
            } else {
                alert(data.message || 'Failed to load subscriber details');
                window.location.href = 'accounts.html';
            }
        } catch (e) {
            console.error(e);
            alert('Error connecting to backend API');
        }
    }

    function populateUI(sub) {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('detailsContainer').style.display = 'grid';

        // Header
        const fullName = `${sub.first_name} ${sub.middle_name ? sub.middle_name + ' ' : ''}${sub.last_name}${sub.suffix ? ' ' + sub.suffix : ''}`;
        document.getElementById('fullName').innerText = fullName;
        document.getElementById('accountId').innerText = sub.account_id;
        document.getElementById('avatarInitials').innerText = sub.avatar_initials;

        const badge = document.getElementById('statusBadge');
        badge.innerText = sub.subscription_status;
        if (sub.subscription_status === 'Active') badge.className = 'status-badge badge-active';
        else if (sub.subscription_status === 'Pending') badge.className = 'status-badge badge-pending';
        else badge.className = 'status-badge badge-rejected';

        // Account & Registration
        document.getElementById('valEmail').innerText = sub.email;
        document.getElementById('valJoined').innerText = sub.joined_date_formatted || sub.joined_date;
        document.getElementById('valInvoice').innerText = sub.sales_invoice_number;
        document.getElementById('valAgent').innerText = sub.referral_code ? `[${sub.referral_code}] ${sub.agent_first_name} ${sub.agent_last_name}` : 'N/A';
        document.getElementById('valSubStatus').innerText = sub.subscription_status || 'Pending';
        if (sub.approved_by_name) {
            document.getElementById('valSubApprovedBy').innerText = 'by ' + sub.approved_by_name;
        }
        
        document.getElementById('valInsStatus').innerText = sub.insurance_status || 'Pending';
        if (sub.insurance_approved_by_name) {
            document.getElementById('valInsApprovedBy').innerText = 'by ' + sub.insurance_approved_by_name;
        }

        // Personal
        document.getElementById('valDOB').innerText = sub.date_of_birth;
        document.getElementById('valPOB').innerText = sub.place_of_birth;
        document.getElementById('valGender').innerText = sub.gender;
        document.getElementById('valCivil').innerText = sub.civil_status;
        document.getElementById('valNationality').innerText = sub.nationality;

        // Contact
        document.getElementById('valPhone').innerText = sub.contact_number;
        document.getElementById('valStreet').innerText = sub.address_house_street;
        document.getElementById('valBrgy').innerText = sub.address_barangay;
        document.getElementById('valCity').innerText = sub.address_city;
        document.getElementById('valRegion').innerText = sub.address_region;
        document.getElementById('valZip').innerText = sub.address_zip_code;

        // Work
        document.getElementById('valOccupation').innerText = sub.occupation;
        document.getElementById('valSource').innerText = sub.source_of_income;
        const formatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
        document.getElementById('valIncome').innerText = formatter.format(sub.monthly_income);

        // Subscription Plan Details
        if (sub.plan) {
            document.getElementById('planDetailsCard').style.display = 'block';
            
            // General Details
            document.getElementById('valPlanName').innerText = sub.plan.plan_name || '--';
            document.getElementById('valPlanTerms').innerText = sub.plan.payment_terms || '--';
            document.getElementById('valPlanSubTerm').innerText = sub.plan.subscription_term_months ? sub.plan.subscription_term_months + ' Months' : '--';
            document.getElementById('valPlanInsTerm').innerText = sub.plan.insurance_term_months ? sub.plan.insurance_term_months + ' Months' : '--';
            document.getElementById('valPlanCont').innerText = sub.plan.contestability_period_days ? sub.plan.contestability_period_days + ' Days' : 'None';
            document.getElementById('valPlanReqBen').innerText = sub.plan.requires_beneficiaries == 1 ? 'Yes' : 'No';
            
            let pDate = '--';
            if (sub.plan.purchase_date) {
                const pd = new Date(sub.plan.purchase_date);
                pDate = pd.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            }
            document.getElementById('valPlanPurchase').innerText = pDate;

            // Subscription Plan Financials (Base Fees from Plan)
            document.getElementById('valPlanOTCFee').innerText = formatter.format(sub.plan.base_onboarding_fee || 0);
            document.getElementById('valPlanMonthlyFee').innerText = formatter.format(sub.plan.base_monthly_fee || 0);
            document.getElementById('valPlanPostTerm').innerText = formatter.format(sub.plan.post_term_fee || 0);
            document.getElementById('valPlanPVoucher').innerText = formatter.format(sub.plan.monthly_pvoucher || 0);
            document.getElementById('valPlanPoints').innerText = formatter.format(sub.plan.monthly_points_rewards || 0);

            // Subscription Report (Aggregated Data)
            const monthlyFee = parseFloat(sub.plan.base_monthly_fee || 0);
            
            // Initial month is implicitly paid via registration fee
            const initialMonthly = (parseFloat(sub.plan.actual_monthly_paid) > 0) 
                ? parseFloat(sub.plan.actual_monthly_paid) 
                : monthlyFee; 
            
            const subsequentMonthly = (sub.payment_history || [])
                .filter(tx => tx.txn_type === 'Monthly')
                .reduce((acc, tx) => acc + parseFloat(tx.grand_total || 0), 0);
            
            const totalMonthlyPaid = initialMonthly + subsequentMonthly;
            document.getElementById('valReportTotalPaid').innerText = formatter.format(totalMonthlyPaid);

            // Total Subscription Payable (Commitment for the whole term)
            const termMonths = parseInt(sub.plan.subscription_term_months || 12);
            const totalPayable = termMonths * monthlyFee;
            document.getElementById('valReportTotalPayable').innerText = formatter.format(totalPayable);

            // Calculate "total monthly not yet paid" (Remaining Balance)
            const notYetPaid = Math.max(0, totalPayable - totalMonthlyPaid);
            const reportNotPaid = document.getElementById('valReportNotPaid');
            reportNotPaid.innerText = formatter.format(notYetPaid);

            // Highlight in red ONLY if they are behind the current billing cycle (Arrears)
            const joinedDate = new Date(sub.joined_date);
            const now = new Date();
            const bday = parseInt(sub.billing_day || 1);
            let monthsExpectedToDate = 0;
            let tempDate = new Date(joinedDate.getFullYear(), joinedDate.getMonth(), bday);
            while (tempDate <= now) {
                monthsExpectedToDate++;
                tempDate.setMonth(tempDate.getMonth() + 1);
            }
            const expectedToDateAmount = monthsExpectedToDate * monthlyFee;
            if (totalMonthlyPaid < expectedToDateAmount) {
                reportNotPaid.style.color = '#f87171'; // Arrears
            } else {
                reportNotPaid.style.color = ''; // Normal balance
            }

            // Rewards Report (Accumulated based on paid amount / monthly fee)
            const paidCount = monthlyFee > 0 ? Math.round(totalMonthlyPaid / monthlyFee) : 0;
            const totalPVouchersValue = paidCount * parseFloat(sub.plan.monthly_pvoucher || 0);
            const totalPointsValue = paidCount * parseFloat(sub.plan.monthly_points_rewards || 0);
            
            document.getElementById('valReportPVouchers').innerText = formatter.format(totalPVouchersValue);
            document.getElementById('valReportPoints').innerText = formatter.format(totalPointsValue);

            // Billing Info Layout
            if (sub.billing_day) {
                const bdayVal = parseInt(sub.billing_day);
                document.getElementById('valBillingDay').innerText = bdayVal + (['11','12','13'].includes(bdayVal.toString()) ? 'th' : (bdayVal.toString().endsWith('1') ? 'st' : (bdayVal.toString().endsWith('2') ? 'nd' : (bdayVal.toString().endsWith('3') ? 'rd' : 'th'))));
                
                // Calculate Next Billing Date
                let nextBill = new Date(now.getFullYear(), now.getMonth(), bdayVal);
                if (now.getDate() >= bdayVal) {
                    nextBill.setMonth(nextBill.getMonth() + 1);
                }
                document.getElementById('valNextBilling').innerText = nextBill.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            } else {
                document.getElementById('valBillingDay').innerText = 'Not Set';
                document.getElementById('valNextBilling').innerText = 'N/A';
            }

            // Insurance Benefits & Eligibility Check
            let joined = new Date(sub.joined_date);
            let contestabilityEnd = new Date(joined);
            contestabilityEnd.setDate(contestabilityEnd.getDate() + (sub.plan.contestability_period_days || 0));
            let isPastContestability = new Date() >= contestabilityEnd;
            
            const benefits = sub.plan.insurance_benefits || [];
            const renderBenefit = (name, valId) => {
                const b = benefits.find(x => x.benefit_name.trim().toLowerCase() === name.trim().toLowerCase());
                if (b) {
                    let text = formatter.format(b.amount);
                    
                    if (b.requires_contestability == 1) {
                        if (isPastContestability) {
                            text += ' <span style="color:#4ade80; font-size:11px; margin-left:8px; padding:2px 6px; background:rgba(74,222,128,0.1); border-radius:10px;">Eligible</span>';
                        } else {
                            let now = new Date();
                            let diffTime = contestabilityEnd - now;
                            let remainingDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                            text += ` <span style="color:#fbbf24; font-size:11px; margin-left:8px; padding:2px 6px; background:rgba(251,191,36,0.1); border-radius:10px;">Not Yet (${remainingDays} Days Remaining)</span>`;
                        }
                    } else {
                        // Inherently eligible if it doesn't require a contestability period
                        text += ' <span style="color:#4ade80; font-size:11px; margin-left:8px; padding:2px 6px; background:rgba(74,222,128,0.1); border-radius:10px;">Eligible</span>';
                    }
                    
                    document.getElementById(valId).innerHTML = text;
                } else {
                    document.getElementById(valId).innerText = '--';
                }
            };

            // Insurance fields visibility
            const hasInsurance = sub.plan.has_insurance == 1;
            document.getElementById('rowPlanInsTerm').style.display = hasInsurance ? 'flex' : 'none';
            document.getElementById('rowPlanCont').style.display = hasInsurance ? 'flex' : 'none';
            document.getElementById('insuranceBenefitsSection').style.display = hasInsurance ? 'block' : 'none';
            if (document.getElementById('valInsStatus')) {
                document.getElementById('valInsStatus').innerText = hasInsurance ? sub.insurance_status : 'N/A (No Insurance)';
            }

            if (hasInsurance) {
                renderBenefit('Accidental Benefit', 'valInsAccidental');
                renderBenefit('Life Benefit', 'valInsLife');
                renderBenefit('Burial Expense', 'valInsBurial');
                
                // Handle Pending State
                const insSection = document.getElementById('insuranceBenefitsSection');
                const pendingInfo = document.getElementById('pendingInsuranceInfo');
                const btnApprove = document.getElementById('btnApproveInsurance');
                
                if (sub.insurance_status === 'Pending') {
                    insSection.classList.add('insurance-pending');
                    pendingInfo.style.display = 'block';
                    btnApprove.style.display = 'flex';
                } else {
                    insSection.classList.remove('insurance-pending');
                    pendingInfo.style.display = 'none';
                    btnApprove.style.display = 'none';
                }

                // Approve Insurance Handler
                btnApprove.onclick = async () => {
                    if (!confirm("Are you sure you want to approve this insurance?")) return;
                    
                    try {
                        const res = await fetch(`${apiBaseUrl}/update_subscriber_status.php`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                account_id: sub.account_id, 
                                insurance_status: 'Active' 
                            })
                        });
                        const resData = await res.json();
                        if (resData.status === 'success') {
                            alert("Insurance status updated to Active!");
                            location.reload();
                        } else {
                            alert(resData.message || "Failed to update insurance status");
                        }
                    } catch (e) {
                        console.error(e);
                        alert("Error updating insurance status");
                    }
                };
            }
        }

        // Beneficiaries
        const benList = document.getElementById('beneficiariesList');
        if (sub.beneficiaries && sub.beneficiaries.length > 0) {
            benList.innerHTML = sub.beneficiaries.map(b => `
                <div class="beneficiary-item">
                    <div class="ben-name">${b.full_name}</div>
                    <div class="ben-info">
                        <strong>Relation:</strong> ${b.relation} &nbsp;|&nbsp; 
                        <strong>DOB:</strong> ${b.date_of_birth} &nbsp;|&nbsp; 
                        <strong>Contact:</strong> ${b.contact_number || 'N/A'}
                    </div>
                </div>
            `).join('');
        }

        // Payment Schedule (Checklist)
        renderPaymentSchedule(sub);
    }

    function renderPaymentSchedule(sub) {
        const scheduleBody = document.getElementById('paymentScheduleBody');
        if (!sub.plan || !sub.billing_day) {
            scheduleBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:40px; color:var(--text-muted);">Incomplete billing data.</td></tr>';
            return;
        }

        const bday = parseInt(sub.billing_day);
        const joinedDate = new Date(sub.joined_date);
        const expectedAmount = 425; // Standard monthly amount as per screenshot
        const formatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

        let html = '';
        const now = new Date();

        for (let i = 0; i < 12; i++) {
            let dueDate = new Date(joinedDate.getFullYear(), joinedDate.getMonth() + i, bday);
            
            // Match with payment_history by Month and Year
            const matchingTxn = (sub.payment_history || []).find(tx => {
                const txDate = new Date(tx.created_at);
                return txDate.getMonth() === dueDate.getMonth() && txDate.getFullYear() === dueDate.getFullYear();
            });

            let status = 'pending';
            let paidDate = '—';
            let refNum = '—';

            if (matchingTxn) {
                status = i === 0 ? 'paid-initial' : 'paid';
                paidDate = new Date(matchingTxn.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                refNum = matchingTxn.receipt_number;
            } else if (now > new Date(dueDate.getTime() + 86400000)) { // Add 1 day grace
                status = 'overdue';
            }

            const statusClass = status.startsWith('paid') ? 'paid' : (status === 'overdue' ? 'overdue' : 'pending');
            const statusText = status === 'paid-initial' ? 'Paid (Initial)' : (status === 'paid' ? 'Paid' : (status === 'overdue' ? 'Overdue' : 'Pending'));

            html += `
                <tr>
                    <td style="font-weight: 500;">${dueDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                    <td>${formatter.format(expectedAmount)}</td>
                    <td><span class="status-pill ${statusClass}">${statusText}</span></td>
                    <td>${paidDate}</td>
                    <td>${refNum}</td>
                </tr>
            `;
        }
        scheduleBody.innerHTML = html;
    }

    loadSubscriber();
});
