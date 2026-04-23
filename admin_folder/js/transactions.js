// admin_folder/js/transactions.js

document.addEventListener('DOMContentLoaded', () => {
    // === State ===
    let transactions = [];
    let currentTransaction = null;

    // === DOM Elements ===
    const transactionsTbody = document.getElementById('transactions-tbody');
    const transactionSearch = document.getElementById('transaction-search');
    const dateFromInput = document.getElementById('date-from');
    const dateToInput = document.getElementById('date-to');
    const statusFilter = document.getElementById('status-filter');
    const btnRefresh = document.getElementById('btn-refresh');

    const detailModal = document.getElementById('detail-modal');
    const receiptView = document.getElementById('receipt-view');
    const receiptTitle = document.getElementById('receipt-title');
    const btnVoid = document.getElementById('btn-void-transaction');
    const closeModalBtn = document.getElementById('close-modal');
    const btnCloseDetail = document.getElementById('btn-close-detail');

    // === Initial Load ===
    fetchTransactions();

    // === Event Listeners ===
    btnRefresh.addEventListener('click', fetchTransactions);
    closeModalBtn.addEventListener('click', hideModal);
    btnCloseDetail.addEventListener('click', hideModal);

    [transactionSearch, dateFromInput, dateToInput, statusFilter].forEach(el => {
        el.addEventListener('change', fetchTransactions);
        if (el.tagName === 'INPUT' && el.type === 'text') {
            el.addEventListener('input', debounce(fetchTransactions, 500));
        }
    });

    // Close modal on outside click
    window.addEventListener('click', (e) => {
        if (e.target === detailModal) hideModal();
    });

    btnVoid.addEventListener('click', async () => {
        if (!currentTransaction) return;
        const reason = prompt('Please enter a reason for voiding this transaction:');
        if (reason === null) return; // Cancelled prompt
        
        await voidTransaction(currentTransaction.id, reason);
    });

    // === Functions ===

    async function fetchTransactions() {
        const query = new URLSearchParams({
            search: transactionSearch.value,
            status: statusFilter.value,
            from_date: dateFromInput.value,
            to_date: dateToInput.value
        });

        try {
            const res = await fetch(`../admin_API/get_pos_transactions.php?${query.toString()}`);
            const r = await res.json();
            if (r.success) {
                transactions = r.data;
                renderTransactions(transactions);
            } else {
                showError('Failed to load transactions: ' + r.error);
            }
        } catch (e) {
            showError('Connection error while fetching transactions');
        }
    }

    function renderTransactions(data) {
        if (data.length === 0) {
            transactionsTbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">No transactions found.</td></tr>';
            return;
        }

        transactionsTbody.innerHTML = data.map(t => {
            const dateStr = new Date(t.created_at).toLocaleString();
            const customer = t.customer_last_name ? `${t.customer_last_name}, ${t.customer_first_name}` : 'Walk-in';
            const statusClass = `status-${t.status.toLowerCase()}`;
            
            return `
                <tr class="${t.status === 'Voided' ? 'row-voided' : ''}">
                    <td style="font-family: monospace; font-weight: 600;">${t.receipt_number}</td>
                    <td style="font-size: 13px; color: var(--text-muted);">${dateStr}</td>
                    <td style="color: white; font-weight: 500;">${customer}</td>
                    <td style="font-weight: 600; color: #4ade80;">₱${parseFloat(t.grand_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td style="font-size: 12px;">${t.payment_method}</td>
                    <td><span class="status-badge ${statusClass}">${t.status}</span></td>
                    <td>
                        <button class="btn-action view" onclick="viewDetails(${t.id})" title="View Receipt">
                            <ion-icon name="receipt-outline"></ion-icon>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    window.viewDetails = async (id) => {
        const transaction = transactions.find(t => t.id == id);
        if (!transaction) return;
        currentTransaction = transaction;

        receiptTitle.textContent = `SI# ${transaction.receipt_number}`;
        receiptView.innerHTML = '<div class="loader" style="margin: 20px auto;"></div>';
        
        // Show/Hide Void button based on status
        btnVoid.style.display = transaction.status === 'Completed' ? 'flex' : 'none';
        
        detailModal.style.display = 'flex';

        try {
            const res = await fetch(`../admin_API/get_pos_transaction_details.php?transaction_id=${id}`);
            const r = await res.json();
            if (r.success) {
                renderReceipt(transaction, r.data);
            } else {
                receiptView.innerHTML = `<p style="color:red">Failed to load details: ${r.error}</p>`;
            }
        } catch (e) {
            receiptView.innerHTML = `<p style="color:red">Connection error.</p>`;
        }
    };

    function renderReceipt(t, items) {
        const customer = t.customer_last_name ? `${t.customer_last_name}, ${t.customer_first_name}` : 'WALK-IN';
        const dateStr = new Date(t.created_at).toLocaleString();

        // Calculate VAT Breakdown (BIR POS Style)
        let vatableSales = 0, vatAmount = 0, vatExemptSales = 0;
        if (t.discount_type === 'Senior' || t.discount_type === 'PWD') {
            vatExemptSales = parseFloat(t.grand_total);
        } else {
            vatableSales = parseFloat(t.grand_total) / 1.12;
            vatAmount = parseFloat(t.grand_total) - vatableSales;
        }

        let itemsHtml = items.map(item => `
            <div class="r-item-row">
                <span class="r-col-qty">${item.quantity}</span>
                <span class="r-col-desc">${item.product_name_snapshot}</span>
                <span class="r-col-price">${parseFloat(item.price_snapshot).toFixed(0)}</span>
                <span class="r-col-total">${parseFloat(item.subtotal).toFixed(2)}</span>
            </div>
        `).join('');

        receiptView.innerHTML = `
            <div class="r-center r-bold r-title">PRIDENS TRADING CORPORATION</div>
            <div class="r-center r-small">123 Business St., City, Philippines</div>
            <div class="r-center r-small">VAT REG TIN: 123-456-789-00000</div>
            <div class="r-center r-small">MIN: 123456789</div>
            <div class="r-center r-small">SN: AB123456</div>
            
            <div class="r-dashed"></div>
            
            <div class="r-row"><span>Date:</span> <span>${dateStr}</span></div>
            <div class="r-row"><span>SI #:</span> <span>${t.receipt_number}</span></div>
            <div class="r-row"><span>Cashier:</span> <span>Administrator</span></div>
            <div class="r-row"><span>Terminal:</span> <span>TERM-WEB-01</span></div>
            <div class="r-row"><span>Customer:</span> <span>${customer}</span></div>
            <div class="r-row"><span>Agent Ref:</span> <span>${t.agent_referral_code || '—'}</span></div>
            
            <div class="r-dashed"></div>
            
            <div class="r-items-header">
                <span class="r-col-qty">Qty</span>
                <span class="r-col-desc">Desc</span>
                <span class="r-col-price">Price</span>
                <span class="r-col-total">Total</span>
            </div>
            <div class="r-items-body">
                ${itemsHtml}
            </div>
            
            <div class="r-dashed"></div>
            
            <div class="r-row"><span>Subtotal</span> <span>${parseFloat(t.subtotal).toFixed(2)}</span></div>
            <div class="r-row"><span>Less: Discount</span> <span>${parseFloat(t.discount_amount).toFixed(2)}</span></div>
            <div class="r-row r-bold r-grand"><span>TOTAL DUE</span> <span>₱${parseFloat(t.grand_total).toFixed(2)}</span></div>
            
            <div class="r-spacer"></div>
            
            <div class="r-row"><span>Cash</span> <span>${parseFloat(t.tendered_amount).toFixed(2)}</span></div>
            <div class="r-row"><span>Change</span> <span>${parseFloat(t.change_amount).toFixed(2)}</span></div>
            
            <div class="r-dashed"></div>
            
            <div class="r-row r-small"><span>Vatable Sales</span> <span>${vatableSales.toFixed(2)}</span></div>
            <div class="r-row r-small"><span>VAT Amount</span> <span>${vatAmount.toFixed(2)}</span></div>
            <div class="r-row r-small"><span>VAT Exempt Sales</span> <span>${vatExemptSales.toFixed(2)}</span></div>
            <div class="r-row r-small"><span>Zero Rated Sales</span> <span>0.00</span></div>
            
            <div class="r-spacer"></div>
            
            <div class="r-center r-bold r-small r-disclaimer">THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX</div>
            <div class="r-center r-small r-dim">(Unless configured as VAT Invoice)</div>
            
            <div class="r-spacer"></div>
            <div class="r-center r-small">Accreditation No: 048-000000000-000000</div>
            <div class="r-center r-small">Date Issued: 08/01/2024</div>
            <div class="r-center r-small">Valid Until: 07/31/2029</div>
            <div class="r-center r-small r-bold">PTU No: FP122024-001-0000001</div>
            
            <div class="r-spacer"></div>
            
            <div id="receipt-qrcode" style="display: flex; justify-content: center; padding: 10px;"></div>
            
            <div class="r-spacer"></div>
            <div class="r-center r-thanks">Thank you for shopping!</div>
        `;

        // Generate QR code for the OR number
        setTimeout(() => {
            const qrContainer = document.getElementById('receipt-qrcode');
            if (qrContainer && typeof QRCode !== 'undefined') {
                new QRCode(qrContainer, {
                    text: t.receipt_number,
                    width: 120,
                    height: 120,
                    colorDark: "#ffffff", // Invert for dark theme preview
                    colorLight: "#1a1f2e",
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        }, 100);
    }

    async function voidTransaction(id, reason) {
        if (!confirm('Are you sure you want to VOID this transaction? This will return all items to inventory and decrement sales totals.')) return;

        try {
            const res = await fetch('../admin_API/void_pos_transaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ transaction_id: id, reason: reason })
            });
            const r = await res.json();
            if (r.success) {
                alert('Transaction voided successfully.');
                hideModal();
                fetchTransactions();
            } else {
                alert('Error: ' + r.error);
            }
        } catch (e) {
            alert('Connection error while voiding transaction.');
        }
    }

    function hideModal() {
        detailModal.style.display = 'none';
        currentTransaction = null;
    }

    function showError(msg) {
        transactionsTbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--error-color);">${msg}</td></tr>`;
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
});
