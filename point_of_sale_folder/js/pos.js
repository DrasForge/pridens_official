// point_of_sale_folder/js/pos.js — OSPOS-Style Pridens POS

document.addEventListener('DOMContentLoaded', () => {

    // === State ===
    let products = [];
    let cart = [];           // { id, name, price, qty, discountPct }
    let discountType = 'Regular';
    let selectedCustomer = null;
    let verifiedAgentCode = null;

    // === DOM Refs ===
    const cartTbody = document.getElementById('cart-tbody');
    const emptyCartMsg = document.getElementById('empty-cart-msg');
    const subtotalEl = document.getElementById('subtotal-amount');
    const discountRow = document.getElementById('discount-row');
    const discountLabel = document.getElementById('discount-label');
    const discountEl = document.getElementById('discount-amount');
    const totalEl = document.getElementById('total-amount');
    const tenderedInput = document.getElementById('tendered-amount');
    const liveChangeEl = document.getElementById('live-change-amount');
    const changeDisplay = document.getElementById('change-display');
    const btnComplete = document.getElementById('btn-complete-sale');
    const btnCancel = document.getElementById('btn-cancel-sale');
    const btnNewSale = document.getElementById('btn-new-sale');
    const quickpickGrid = document.getElementById('quickpick-grid');
    const liveClock = document.getElementById('live-clock');
    const itemSearchInput = document.getElementById('item-search');
    const itemSearchResults = document.getElementById('item-search-results');
    const customerSearchInput = document.getElementById('customer-search');
    const customerSearchResults = document.getElementById('customer-search-results');
    const selectedCustomerBadge = document.getElementById('selected-customer-badge');
    const selectedCustomerName = document.getElementById('selected-customer-name');
    const removeCustomerBtn = document.getElementById('remove-customer');

    // Modals
    const modalOverlay = document.getElementById('modal-overlay');
    const modalShift = document.getElementById('modal-shift');
    const modalSuccess = document.getElementById('modal-success');
    const modalXReading = document.getElementById('modal-xreading');
    const modalZReading = document.getElementById('modal-zreading');
    const modalZSuccess = document.getElementById('modal-zsuccess');
    const modalHistory = document.getElementById('modal-history');
    const modalProducts = document.getElementById('modal-products');
    const btnOpenShift = document.getElementById('btn-open-shift');
    const openingBalanceInput = document.getElementById('opening-balance');
    const btnNewOrder = document.getElementById('btn-new-order');

    // === Clock ===
    function updateClock() {
        const now = new Date();
        liveClock.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    // === Init ===
    checkShift();

    // =====================
    //  SHIFT MANAGEMENT
    // =====================
    async function checkShift() {
        try {
            const res = await fetch('../point_of_sale_API/shift_operations.php?action=check');
            const r = await res.json();
            if (r.error === 'Unauthorized') {
                window.location.href = '../admin_folder/index.html';
                return;
            }
            if (!r.has_shift) {
                showModal(modalShift);
            } else {
                modalOverlay.style.display = 'none';
                loadProducts();
            }
        } catch (e) {
            console.error('Shift check failed', e);
        }
    }

    btnOpenShift.addEventListener('click', async () => {
        btnOpenShift.disabled = true;
        btnOpenShift.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Opening...';
        try {
            const res = await fetch('../point_of_sale_API/shift_operations.php?action=open', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ opening_balance: openingBalanceInput.value })
            });
            const r = await res.json();
            if (r.success) {
                modalOverlay.style.display = 'none';
                loadProducts();
            } else {
                alert(r.error);
            }
        } catch (e) {
            alert('Failed to open shift');
        } finally {
            btnOpenShift.disabled = false;
            btnOpenShift.innerHTML = '<ion-icon name="play-outline"></ion-icon> START SHIFT';
        }
    });

    // =====================
    //  LOAD PRODUCTS
    // =====================
    async function loadProducts() {
        try {
            const res = await fetch('../point_of_sale_API/get_products.php');
            const r = await res.json();
            if (r.success) {
                products = r.data;
                renderQuickPicks();
            }
        } catch (e) {
            console.error('Failed to load products', e);
        }
    }

    function renderQuickPicks() {
        quickpickGrid.innerHTML = '';
        products.forEach((p, i) => {
            const btn = document.createElement('button');
            btn.className = `quickpick-btn qp-color-${i % 8}`;
            btn.textContent = p.name;
            btn.title = `₱${parseFloat(p.price).toFixed(2)}`;
            btn.addEventListener('click', () => addToCart(p));
            quickpickGrid.appendChild(btn);
        });
    }

    // =====================
    //  ITEM SEARCH (Typeahead)
    // =====================
    let searchTimer = null;

    itemSearchInput.addEventListener('input', () => {
        const q = itemSearchInput.value.trim();
        if (searchTimer) clearTimeout(searchTimer);
        if (!q) {
            itemSearchResults.style.display = 'none';
            return;
        }
        searchTimer = setTimeout(() => searchItems(q), 250);
    });

    itemSearchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            itemSearchResults.style.display = 'none';
            itemSearchInput.value = '';
        }
    });

    async function searchItems(query) {
        try {
            const res = await fetch(`../point_of_sale_API/search_products.php?q=${encodeURIComponent(query)}`);
            const r = await res.json();
            if (r.success && r.data.length > 0) {
                itemSearchResults.innerHTML = '';
                r.data.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'search-result-item';
                    div.innerHTML = `
                        <div>
                            <div class="search-result-name">${p.name}</div>
                            <div class="search-result-sub">${p.category || 'General'}</div>
                        </div>
                        <div class="search-result-price">₱${parseFloat(p.price).toFixed(2)}</div>
                    `;
                    div.addEventListener('click', () => {
                        addToCart(p);
                        itemSearchResults.style.display = 'none';
                        itemSearchInput.value = '';
                        itemSearchInput.focus();
                    });
                    itemSearchResults.appendChild(div);
                });
                itemSearchResults.style.display = 'block';
            } else {
                itemSearchResults.innerHTML = '<div class="search-result-item"><span class="search-result-name" style="color:var(--text-dim);">No items found</span></div>';
                itemSearchResults.style.display = 'block';
            }
        } catch (e) {
            console.error('Item search failed', e);
        }
    }

    // Close dropdowns on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.item-search-box')) itemSearchResults.style.display = 'none';
        if (!e.target.closest('.customer-search-box')) customerSearchResults.style.display = 'none';
    });

    // =====================
    //  CUSTOMER SEARCH
    // =====================
    let custSearchTimer = null;

    customerSearchInput.addEventListener('input', () => {
        const q = customerSearchInput.value.trim();
        if (custSearchTimer) clearTimeout(custSearchTimer);
        if (!q) {
            customerSearchResults.style.display = 'none';
            return;
        }
        custSearchTimer = setTimeout(() => searchCustomers(q), 400);
    });

    async function searchCustomers(query) {
        try {
            const res = await fetch(`../point_of_sale_API/search_customers.php?q=${encodeURIComponent(query)}`);
            const r = await res.json();
            if (r.success && r.data.length > 0) {
                customerSearchResults.innerHTML = '';
                r.data.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'search-result-item';
                    div.innerHTML = `
                        <div>
                            <div class="search-result-name">${c.last_name}, ${c.first_name} ${c.middle_name || ''}</div>
                            <div class="search-result-sub">${c.account_id}</div>
                        </div>
                    `;
                    div.addEventListener('click', () => {
                        selectCustomer(c);
                        customerSearchResults.style.display = 'none';
                        customerSearchInput.value = '';
                    });
                    customerSearchResults.appendChild(div);
                });
                customerSearchResults.style.display = 'block';
            } else {
                customerSearchResults.innerHTML = '<div class="search-result-item"><span class="search-result-name" style="color:var(--text-dim);">No customers found</span></div>';
                customerSearchResults.style.display = 'block';
            }
        } catch (e) {
            console.error('Customer search failed', e);
        }
    }

    function selectCustomer(customer) {
        selectedCustomer = customer;
        selectedCustomerBadge.style.display = 'flex';
        selectedCustomerName.textContent = `${customer.last_name}, ${customer.first_name} (${customer.account_id})`;
        // Auto-fill customer name inputs
        document.getElementById('cust-last-name').value = customer.last_name || '';
        document.getElementById('cust-first-name').value = customer.first_name || '';
        document.getElementById('cust-middle-name').value = customer.middle_name || '';
    }

    removeCustomerBtn.addEventListener('click', () => {
        selectedCustomer = null;
        selectedCustomerBadge.style.display = 'none';
        document.getElementById('cust-last-name').value = '';
        document.getElementById('cust-first-name').value = '';
        document.getElementById('cust-middle-name').value = '';
    });

    // =====================
    //  CART MANAGEMENT
    // =====================
    function addToCart(product) {
        const existing = cart.find(item => item.id === product.id);
        if (existing) {
            existing.qty++;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                qty: 1,
                discountPct: 0
            });
        }
        renderCart();
    }

    function removeFromCart(id) {
        cart = cart.filter(item => item.id !== id);
        renderCart();
    }

    function updateQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        item.qty = Math.max(1, item.qty + delta);
        renderCart();
    }

    function setQty(id, val) {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        item.qty = Math.max(1, parseInt(val) || 1);
        renderCart();
    }

    function setItemDiscount(id, pct) {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        item.discountPct = Math.max(0, Math.min(100, parseFloat(pct) || 0));
        renderCart();
    }

    // Expose to inline handlers
    window._posUpdateQty = updateQty;
    window._posSetQty = setQty;
    window._posSetItemDisc = setItemDiscount;
    window._posRemoveItem = removeFromCart;
    window._posAddToCart = (id) => {
        const p = products.find(prod => prod.id === id);
        if (p) addToCart(p);
    };

    function renderCart() {
        if (cart.length === 0) {
            cartTbody.innerHTML = '';
            emptyCartMsg.style.display = 'block';
            btnComplete.disabled = true;
        } else {
            emptyCartMsg.style.display = 'none';
            btnComplete.disabled = false;

            let html = '';
            cart.forEach(item => {
                const lineTotal = item.price * item.qty * (1 - item.discountPct / 100);
                html += `
                    <tr>
                        <td class="col-item">${item.name}</td>
                        <td class="col-price">₱${item.price.toFixed(2)}</td>
                        <td class="col-qty">
                            <div class="qty-control">
                                <button class="qty-btn" onclick="_posUpdateQty(${item.id}, -1)">−</button>
                                <input class="qty-value" type="number" value="${item.qty}" min="1"
                                    onchange="_posSetQty(${item.id}, this.value)">
                                <button class="qty-btn" onclick="_posUpdateQty(${item.id}, 1)">+</button>
                            </div>
                        </td>
                        <td class="col-disc">
                            <input class="disc-input" type="number" value="${item.discountPct}" min="0" max="100" step="1"
                                onchange="_posSetItemDisc(${item.id}, this.value)">
                        </td>
                        <td class="col-total">₱${lineTotal.toFixed(2)}</td>
                        <td class="col-action">
                            <button class="row-delete" onclick="_posRemoveItem(${item.id})" title="Remove">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                        </td>
                    </tr>
                `;
            });
            cartTbody.innerHTML = html;
        }
        updateTotals();
    }

    function updateTotals() {
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += item.price * item.qty * (1 - item.discountPct / 100);
        });

        let globalDiscount = 0;
        if (discountType === 'Senior' || discountType === 'PWD') {
            globalDiscount = subtotal * 0.20;
            discountRow.style.display = 'flex';
            discountLabel.textContent = `(${discountType} 20%)`;
        } else {
            discountRow.style.display = 'none';
        }

        const grandTotal = subtotal - globalDiscount;

        subtotalEl.textContent = '₱' + subtotal.toFixed(2);
        discountEl.textContent = '-₱' + globalDiscount.toFixed(2);
        totalEl.textContent = '₱' + grandTotal.toFixed(2);

        updateLiveChange();
    }

    // Discount Type Toggles
    document.querySelectorAll('.discount-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.discount-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            discountType = e.target.getAttribute('data-type');
            updateTotals();
        });
    });

    // =====================
    //  LIVE CHANGE CALCULATOR
    // =====================
    tenderedInput.addEventListener('input', updateLiveChange);

    function updateLiveChange() {
        const totalStr = totalEl.textContent.replace(/[₱,]/g, '');
        const total = parseFloat(totalStr) || 0;
        const tendered = parseFloat(tenderedInput.value) || 0;
        const change = tendered - total;

        if (!tenderedInput.value || tendered === 0) {
            liveChangeEl.textContent = '₱0.00';
            liveChangeEl.style.color = 'var(--text-dim)';
            changeDisplay.style.borderColor = 'var(--border)';
            btnComplete.disabled = cart.length === 0;
        } else if (change < 0) {
            liveChangeEl.textContent = '-₱' + Math.abs(change).toFixed(2);
            liveChangeEl.style.color = 'var(--danger)';
            changeDisplay.style.borderColor = 'rgba(239,68,68,0.3)';
            btnComplete.disabled = true;
        } else {
            liveChangeEl.textContent = '₱' + change.toFixed(2);
            liveChangeEl.style.color = 'var(--primary)';
            changeDisplay.style.borderColor = 'rgba(16,185,129,0.3)';
            btnComplete.disabled = cart.length === 0;
        }
    }

    // =====================
    //  COMPLETE SALE
    // =====================
    btnComplete.addEventListener('click', completeSale);

    async function completeSale() {
        if (cart.length === 0) return;

        const totalStr = totalEl.textContent.replace(/[₱,]/g, '');
        const total = parseFloat(totalStr) || 0;
        const tendered = parseFloat(tenderedInput.value) || 0;

        if (tendered < total) {
            alert('Tendered amount is less than the total.');
            return;
        }

        btnComplete.disabled = true;
        btnComplete.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Processing...';

        const payload = {
            cart: cart.map(item => ({ id: item.id, qty: item.qty, discount_pct: item.discountPct })),
            discount_type: discountType,
            tendered_amount: tendered,
            customer_last_name: document.getElementById('cust-last-name').value.trim(),
            customer_first_name: document.getElementById('cust-first-name').value.trim(),
            customer_middle_name: document.getElementById('cust-middle-name').value.trim(),
            agent_referral_code: document.getElementById('agent_code').value.trim(),
            subscriber_account_id: selectedCustomer ? selectedCustomer.account_id : null
        };

        try {
            const res = await fetch('../point_of_sale_API/checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const r = await res.json();

            if (r.success) {
                // Build receipt preview
                buildReceiptPreview(r, payload, tendered);
                showModal(modalSuccess);
                resetSale();
            } else {
                alert('Sale Failed: ' + r.error);
            }
        } catch (e) {
            alert('Sale request failed. Check your connection.');
        } finally {
            btnComplete.disabled = false;
            btnComplete.innerHTML = '<ion-icon name="checkmark-circle-outline"></ion-icon> Complete Sale (F9)';
        }
    }

    function buildReceiptPreview(result, payload, tendered) {
        const now = new Date();
        const dateStr = now.getFullYear() + '-' +
            String(now.getMonth() + 1).padStart(2, '0') + '-' +
            String(now.getDate()).padStart(2, '0') + ' ' +
            String(now.getHours()).padStart(2, '0') + ':' +
            String(now.getMinutes()).padStart(2, '0') + ':' +
            String(now.getSeconds()).padStart(2, '0');

        // Transaction info
        document.getElementById('r-date').textContent = dateStr;
        document.getElementById('r-si-number').textContent = result.receipt_number;

        // Customer
        if (selectedCustomer) {
            document.getElementById('r-customer').textContent =
                selectedCustomer.last_name + ', ' + selectedCustomer.first_name;
        } else {
            document.getElementById('r-customer').textContent = 'WALK-IN';
        }

        // Agent Ref
        const agentCode = document.getElementById('agent_code').value.trim();
        document.getElementById('r-agent-ref').textContent = agentCode || '—';

        // Items body
        const itemsBody = document.getElementById('r-items-body');
        itemsBody.innerHTML = '';
        let rawSubtotal = 0;
        cart.forEach(item => {
            const lineTotal = item.price * item.qty * (1 - item.discountPct / 100);
            rawSubtotal += lineTotal;
            const row = document.createElement('div');
            row.className = 'r-item-row';
            row.innerHTML = `
                <span class="r-col-qty">${item.qty}</span>
                <span class="r-col-desc">${item.name}</span>
                <span class="r-col-price">${item.price.toFixed(0)}</span>
                <span class="r-col-total">${lineTotal.toFixed(2)}</span>
            `;
            itemsBody.appendChild(row);
        });

        // Discount
        let globalDiscount = 0;
        if (discountType === 'Senior' || discountType === 'PWD') {
            globalDiscount = rawSubtotal * 0.20;
        }
        const grandTotal = rawSubtotal - globalDiscount;

        // Totals
        document.getElementById('r-subtotal').textContent = rawSubtotal.toFixed(2);
        document.getElementById('r-discount').textContent = globalDiscount.toFixed(2);
        document.getElementById('r-total-due').textContent = grandTotal.toFixed(2);

        // Payment
        document.getElementById('r-cash').textContent = tendered.toFixed(2);
        document.getElementById('r-change').textContent = (tendered - grandTotal).toFixed(2);

        // VAT Breakdown
        // BIR: If Senior/PWD, the sale is VAT Exempt. Otherwise 12% VAT.
        let vatableSales = 0, vatAmount = 0, vatExemptSales = 0;
        if (discountType === 'Senior' || discountType === 'PWD') {
            vatExemptSales = grandTotal;
        } else {
            // VAT is inclusive: Vatable Sales = Total / 1.12, VAT = Total - Vatable
            vatableSales = grandTotal / 1.12;
            vatAmount = grandTotal - vatableSales;
        }
        document.getElementById('r-vatable').textContent = vatableSales.toFixed(2);
        document.getElementById('r-vat-amount').textContent = vatAmount.toFixed(2);
        document.getElementById('r-vat-exempt').textContent = vatExemptSales.toFixed(2);

        // QR Code — encodes the OR number so scanning returns it
        const qrContainer = document.getElementById('receipt-qrcode');
        qrContainer.innerHTML = ''; // Clear any previous QR
        try {
            new QRCode(qrContainer, {
                text: result.receipt_number,
                width: 120,
                height: 120,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        } catch (e) {
            console.error('QR code generation failed', e);
        }
    }

    // Print receipt
    document.getElementById('btn-print-receipt').addEventListener('click', () => {
        window.print();
    });

    function resetSale() {
        cart = [];
        discountType = 'Regular';
        selectedCustomer = null;
        verifiedAgentCode = null;
        document.querySelectorAll('.discount-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.discount-btn[data-type="Regular"]').classList.add('active');
        selectedCustomerBadge.style.display = 'none';
        document.getElementById('agent_code').value = '';
        document.getElementById('cust-last-name').value = '';
        document.getElementById('cust-first-name').value = '';
        document.getElementById('cust-middle-name').value = '';
        resetAgentVerification();
        tenderedInput.value = '';
        renderCart();
    }

    // Cancel / New Sale
    btnCancel.addEventListener('click', () => {
        if (cart.length > 0 && !confirm('Discard current sale?')) return;
        resetSale();
    });

    btnNewSale.addEventListener('click', resetSale);

    btnNewOrder.addEventListener('click', () => {
        modalOverlay.style.display = 'none';
        resetSale();
    });

    // =====================
    //  AGENT VERIFICATION
    // =====================
    const agentCodeInput = document.getElementById('agent_code');
    const agentVerification = document.getElementById('agent-verification');
    const agentStatusIcon = document.getElementById('agent-status-icon');
    const agentStatusText = document.getElementById('agent-status-text');
    let agentVerifyTimer = null;

    function resetAgentVerification() {
        agentVerification.style.display = 'none';
        agentVerification.className = 'agent-verification';
        agentCodeInput.classList.remove('input-verified', 'input-invalid');
        verifiedAgentCode = null;
    }

    agentCodeInput.addEventListener('input', () => {
        const code = agentCodeInput.value.trim();
        if (agentVerifyTimer) clearTimeout(agentVerifyTimer);
        if (!code) { resetAgentVerification(); return; }

        agentVerification.style.display = 'flex';
        agentVerification.className = 'agent-verification checking';
        agentStatusIcon.textContent = '⏳';
        agentStatusText.textContent = 'Checking...';
        agentCodeInput.classList.remove('input-verified', 'input-invalid');

        agentVerifyTimer = setTimeout(async () => {
            try {
                const res = await fetch(`../point_of_sale_API/verify_agent.php?agent_id=${encodeURIComponent(code)}`);
                const r = await res.json();
                if (r.success && r.found) {
                    if (r.agent.status === 'Active') {
                        agentVerification.className = 'agent-verification verified';
                        agentStatusIcon.textContent = '✅';
                        agentStatusText.textContent = `Verified: ${r.agent.name} (${r.agent.agent_id})`;
                        agentCodeInput.classList.add('input-verified');
                        verifiedAgentCode = r.agent.agent_id;
                    } else {
                        agentVerification.className = 'agent-verification inactive';
                        agentStatusIcon.textContent = '⚠️';
                        agentStatusText.textContent = `Agent ${r.agent.name} is ${r.agent.status}`;
                        agentCodeInput.classList.add('input-invalid');
                    }
                } else {
                    agentVerification.className = 'agent-verification not-found';
                    agentStatusIcon.textContent = '❌';
                    agentStatusText.textContent = 'Agent not found';
                    agentCodeInput.classList.add('input-invalid');
                }
            } catch (e) {
                agentVerification.className = 'agent-verification not-found';
                agentStatusIcon.textContent = '❌';
                agentStatusText.textContent = 'Verification failed';
            }
        }, 500);
    });

    // =====================
    //  MODALS
    // =====================
    function showModal(el) {
        modalOverlay.style.display = 'flex';
        [modalShift, modalSuccess, modalXReading, modalZReading, modalZSuccess, modalHistory, modalProducts].forEach(m => {
            if (m) m.style.display = 'none';
        });
        el.style.display = 'flex';
    }

    // =====================
    //  PRODUCT CATALOG
    // =====================
    document.getElementById('nav-products').addEventListener('click', loadProductsList);

    async function loadProductsList() {
        showModal(modalProducts);
        const tbody = document.getElementById('products-tbody');
        tbody.innerHTML = '<tr><td colspan="5" class="empty-row">Loading...</td></tr>';
        
        try {
            // Products are already loaded in global 'products' array on shift init
            if (products.length === 0) {
                await loadProducts();
            }

            if (products.length > 0) {
                tbody.innerHTML = products.map(p => `
                    <tr>
                        <td style="font-family:monospace">${p.sku}</td>
                        <td>${p.name}</td>
                        <td>${p.category || 'General'}</td>
                        <td style="font-weight:600;">₱${parseFloat(p.price).toFixed(2)}</td>
                        <td>
                            <button class="hist-action-btn reprint-btn" onclick="_posAddToCart(${p.id})" title="Add to cart">Add</button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="empty-row">No products found.</td></tr>';
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-row" style="color:var(--danger);">Failed to load</td></tr>';
        }
    }

    // =====================
    //  TRANSACTION HISTORY
    // =====================
    document.getElementById('nav-history').addEventListener('click', loadHistory);

    async function loadHistory() {
        showModal(modalHistory);
        const tbody = document.getElementById('history-tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="empty-row">Loading...</td></tr>';
        try {
            const res = await fetch('../point_of_sale_API/history.php');
            const r = await res.json();
            if (r.success && r.transactions.length > 0) {
                tbody.innerHTML = r.transactions.map(t => {
                    const time = new Date(t.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    const custName = [t.customer_first_name, t.customer_last_name].filter(Boolean).join(' ') || 'WALK-IN';
                    const isVoided = t.status === 'Voided';
                    const statusColor = isVoided ? 'var(--danger)' : 'var(--success)';
                    const statusBadge = isVoided
                        ? '<span style="color:var(--danger);font-weight:600;">VOIDED</span>'
                        : '<span style="color:var(--success);font-weight:600;">Completed</span>';

                    const voidBtn = !isVoided
                        ? `<button class="hist-action-btn void-btn" onclick="_posVoidTxn(${t.id})" title="Void this transaction">Void</button>`
                        : '';

                    return `<tr style="${isVoided ? 'opacity:0.5;' : ''}">
                        <td style="font-family:monospace">${t.receipt_number}</td>
                        <td>${time}</td>
                        <td>${custName}</td>
                        <td style="font-weight:600;">₱${parseFloat(t.grand_total).toFixed(2)}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="hist-actions">
                                <button class="hist-action-btn reprint-btn" onclick="_posReprintTxn(${t.id})" title="Reprint receipt">Reprint</button>
                                ${voidBtn}
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-row">No transactions in this shift yet.</td></tr>';
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-row" style="color:var(--danger);">Failed to load</td></tr>';
        }
    }

    // --- VOID TRANSACTION ---
    window._posVoidTxn = async function(txnId) {
        if (!confirm('Are you sure you want to VOID this transaction? This cannot be undone.')) return;
        try {
            const res = await fetch('../point_of_sale_API/void_transaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: txnId })
            });
            const r = await res.json();
            if (r.success) {
                alert(r.message);
                loadHistory(); // Refresh the table
            } else {
                alert('Void failed: ' + r.error);
            }
        } catch (e) {
            alert('Void request failed.');
        }
    };

    // --- REPRINT RECEIPT ---
    window._posReprintTxn = async function(txnId) {
        try {
            const res = await fetch(`../point_of_sale_API/reprint_receipt.php?id=${txnId}`);
            const r = await res.json();
            if (r.success) {
                buildReprintReceipt(r.transaction, r.items);
                showModal(modalSuccess);
            } else {
                alert('Reprint failed: ' + r.error);
            }
        } catch (e) {
            alert('Reprint request failed.');
        }
    };

    function buildReprintReceipt(txn, items) {
        // Date
        const d = new Date(txn.created_at);
        const dateStr = d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0') + ' ' +
            String(d.getHours()).padStart(2, '0') + ':' +
            String(d.getMinutes()).padStart(2, '0') + ':' +
            String(d.getSeconds()).padStart(2, '0');

        document.getElementById('r-date').textContent = dateStr;
        document.getElementById('r-si-number').textContent = txn.receipt_number;

        // Customer
        const custName = [txn.customer_first_name, txn.customer_last_name].filter(Boolean).join(' ');
        document.getElementById('r-customer').textContent = custName || 'WALK-IN';
        document.getElementById('r-agent-ref').textContent = txn.agent_referral_code || '—';

        // Items
        const itemsBody = document.getElementById('r-items-body');
        itemsBody.innerHTML = '';
        items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'r-item-row';
            row.innerHTML = `
                <span class="r-col-qty">${item.quantity}</span>
                <span class="r-col-desc">${item.product_name_snapshot}</span>
                <span class="r-col-price">${parseFloat(item.price_snapshot).toFixed(0)}</span>
                <span class="r-col-total">${parseFloat(item.subtotal).toFixed(2)}</span>
            `;
            itemsBody.appendChild(row);
        });

        // Totals
        const subtotal = parseFloat(txn.subtotal);
        const discount = parseFloat(txn.discount_amount);
        const grandTotal = parseFloat(txn.grand_total);
        const tendered = parseFloat(txn.tendered_amount);
        const change = parseFloat(txn.change_amount);

        document.getElementById('r-subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('r-discount').textContent = discount.toFixed(2);
        document.getElementById('r-total-due').textContent = grandTotal.toFixed(2);
        document.getElementById('r-cash').textContent = tendered.toFixed(2);
        document.getElementById('r-change').textContent = change.toFixed(2);

        // VAT
        let vatableSales = 0, vatAmount = 0, vatExemptSales = 0;
        if (txn.discount_type === 'Senior' || txn.discount_type === 'PWD') {
            vatExemptSales = grandTotal;
        } else {
            vatableSales = grandTotal / 1.12;
            vatAmount = grandTotal - vatableSales;
        }
        document.getElementById('r-vatable').textContent = vatableSales.toFixed(2);
        document.getElementById('r-vat-amount').textContent = vatAmount.toFixed(2);
        document.getElementById('r-vat-exempt').textContent = vatExemptSales.toFixed(2);

        // QR Code
        const qrContainer = document.getElementById('receipt-qrcode');
        qrContainer.innerHTML = '';
        try {
            new QRCode(qrContainer, {
                text: txn.receipt_number,
                width: 120,
                height: 120,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        } catch (e) { console.error('QR reprint failed', e); }
    }

    // =====================
    //  X-READING
    // =====================
    document.getElementById('nav-xreading').addEventListener('click', async () => {
        try {
            const res = await fetch('../point_of_sale_API/shift_reports.php?action=x_reading');
            const r = await res.json();
            if (r.success) {
                document.getElementById('x-opening').textContent = '₱' + parseFloat(r.opening_balance).toFixed(2);
                document.getElementById('x-sales').textContent = '₱' + parseFloat(r.total_sales).toFixed(2);
                document.getElementById('x-discounts').textContent = '₱' + parseFloat(r.total_discounts).toFixed(2);
                document.getElementById('x-expected').textContent = '₱' + parseFloat(r.expected_drawer_cash).toFixed(2);
                showModal(modalXReading);
            } else {
                alert(r.error);
            }
        } catch (e) { alert('Failed to generate X-Reading'); }
    });

    // =====================
    //  Z-READING (CLOSE SHIFT)
    // =====================
    document.getElementById('nav-closeshift').addEventListener('click', () => {
        document.getElementById('actual-cash').value = '';
        showModal(modalZReading);
    });

    document.getElementById('btn-confirm-close-shift').addEventListener('click', async () => {
        const actualCashInput = document.getElementById('actual-cash');
        if (!actualCashInput.value) return alert('Please enter the physically counted cash.');

        const btn = document.getElementById('btn-confirm-close-shift');
        btn.disabled = true;
        btn.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> CLOSING...';

        try {
            const res = await fetch('../point_of_sale_API/shift_reports.php?action=z_reading_close', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ actual_cash: parseFloat(actualCashInput.value) })
            });
            const r = await res.json();
            if (r.success) {
                document.getElementById('z-counter').textContent = String(r.shift_z_counter).padStart(6, '0');
                document.getElementById('z-sales').textContent = '₱' + parseFloat(r.total_sales).toFixed(2);
                document.getElementById('z-variance').textContent = '₱' + parseFloat(r.variance).toFixed(2);
                document.getElementById('z-grand-total').textContent = '₱' + parseFloat(r.accumulated_grand_total).toFixed(2);
                showModal(modalZSuccess);
            } else {
                alert(r.error);
            }
        } catch (e) {
            alert('Failed to close shift');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<ion-icon name="lock-closed-outline"></ion-icon> CLOSE SHIFT';
        }
    });

    // =====================
    //  KEYBOARD SHORTCUTS
    // =====================
    document.addEventListener('keydown', (e) => {
        // F2 = New Sale
        if (e.key === 'F2') {
            e.preventDefault();
            resetSale();
            itemSearchInput.focus();
        }
        // F9 = Complete Sale
        if (e.key === 'F9') {
            e.preventDefault();
            if (!btnComplete.disabled) completeSale();
        }
        // Escape = Close modals
        if (e.key === 'Escape') {
            if (modalOverlay.style.display === 'flex' && !modalShift.style.display.includes('flex')) {
                modalOverlay.style.display = 'none';
            }
        }
    });

    // Focus item search on load
    setTimeout(() => itemSearchInput.focus(), 500);
});
