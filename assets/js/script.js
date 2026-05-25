// Cart Data
let cart = [];
let currentOrderType = 'dine_in'; // Default Value

// Format Currency
const formatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
});

// Reward Points System
let customerPointsGlobal = 0;
const POINT_CONVERSION_RATE = 100; // 1 Point = Rp 100 Discount

// Store global calc for Checkout
window.currentTotalCalc = {
    subtotal: 0,
    discount: 0,
    pointsDed: 0,
    tax: 0,
    total: 0
};

// Debounce for customer lookup
let debounceTimer;
const customerInput = document.getElementById('customerName');
if (customerInput) {
    customerInput.addEventListener('input', function (e) {
        clearTimeout(debounceTimer);
        const name = e.target.value.trim();

        // Reset Reward UI if empty
        if (!name) {
            toggleRewardUI('', 0);
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchCustomerPoints(name);
        }, 800);
    });
}

function fetchCustomerPoints(name) {
    // Call backend to get points
    fetch('get_customer_points.php?name=' + encodeURIComponent(name))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.points > 0) {
                customerPointsGlobal = data.points;
                toggleRewardUI(name, data.points);
            } else {
                customerPointsGlobal = 0;
                toggleRewardUI(name, 0);
            }
        })
        .catch(() => toggleRewardUI(name, 0));
}

function toggleRewardUI(name, points) {
    const section = document.getElementById('rewardSection');
    const avail = document.getElementById('availPoints');
    if (section && avail) {
        if (points > 0) {
            section.style.display = 'flex';
            avail.innerText = points;
        } else {
            section.style.display = 'none';
            const chk = document.getElementById('usePoints');
            if (chk) chk.checked = false;
            if (typeof updateCartUI === 'function') updateCartUI();
        }
    }
}

// ... (code continues) ...

// ... (code continues) ...

// === FIX: Order Type Selector Logic ===
function selectOrderType(type) {
    currentOrderType = type;

    // Reset visual
    document.getElementById('btn-dine-in').classList.remove('active');
    document.getElementById('btn-take-away').classList.remove('active');

    // Set active visual
    const activeBtn = document.getElementById(type === 'dine_in' ? 'btn-dine-in' : 'btn-take-away');
    activeBtn.classList.add('active');

    console.log('Order Type selected:', currentOrderType);
}

// Search Filter & Autofocus
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.focus(); // Autofocus on load

    searchInput.addEventListener('input', function (e) {
        const keyword = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const title = card.querySelector('.product-info h3').innerText.toLowerCase();
            const category = card.dataset.category;
            // Filter by name only (category filter handles visibility separately)
            if (title.includes(keyword)) {
                card.style.display = 'flex'; // Restore if hidden by search
            } else {
                card.style.display = 'none';
            }
        });
    });
}

// Add to Cart
function addToCart(id, name, price, stock, element = null) {
    // Visual Feedback
    if (element) {
        element.classList.add('clicked-pulse');
        setTimeout(() => element.classList.remove('clicked-pulse'), 400);
    }

    if (stock <= 0) {
        alert('Stok habis!');
        return;
    }

    const existingItem = cart.find(item => item.id === id);

    if (existingItem) {
        if (existingItem.qty < stock) {
            existingItem.qty++;
        } else {
            alert('Stok tidak mencukupi!');
            return;
        }
    } else {
        cart.push({ id, name, price, qty: 1, stock });
    }

    updateCartUI();
}

// Update Qty
function updateQty(id, change) {
    const item = cart.find(item => item.id === id);
    if (!item) return;

    const newQty = item.qty + change;

    if (newQty > item.stock) {
        alert('Stok maksimal tercapai');
        return;
    }

    if (newQty <= 0) {
        cart = cart.filter(i => i.id !== id);
    } else {
        item.qty = newQty;
    }

    updateCartUI();
}

// Update UI
function updateCartUI() {
    const container = document.getElementById('cartItemsContainer');
    const subtotalEl = document.getElementById('cartSubtotal');
    const taxEl = document.getElementById('cartTax');
    const totalEl = document.getElementById('cartTotal');
    const itemCountEl = document.getElementById('cartItemCountDisplay');
    const badge = document.querySelector('.cart-badge'); // If exists

    if (cart.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; color: var(--text-muted); margin-top: 4rem;">
                <i class="fas fa-shopping-basket fa-3x" style="margin-bottom: 1rem; opacity: 0.3;"></i>
                <p>Keranjang kosong</p>
                <small>Pilih menu disamping</small>
            </div>`;
        if (subtotalEl) subtotalEl.innerText = 'Rp 0';
        if (taxEl) taxEl.innerText = 'Rp 0';
        if (totalEl) totalEl.innerText = 'Rp 0';
        if (itemCountEl) itemCountEl.innerText = '0 Item';
        return;
    }

    let subtotal = 0;
    let totalItems = 0;

    container.innerHTML = cart.map(item => {
        subtotal += item.price * item.qty;
        totalItems += item.qty;
        const isMax = item.qty >= item.stock;

        return `
            <div class="cart-item">
                <div style="flex: 1; padding-right: 1rem;">
                    <h4>${item.name}</h4>
                    <div class="price">${formatter.format(item.price)}</div>
                </div>
                <div class="qty-controls">
                    <button class="qty-btn" onclick="updateQty(${item.id}, -1)">
                        ${item.qty === 1 ? '<i class="fas fa-trash-alt" style="font-size:0.7rem;"></i>' : '-'}
                    </button>
                    <div class="qty-val">${item.qty}</div>
                    <button class="qty-btn" onclick="updateQty(${item.id}, 1)" ${isMax ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''}>+</button>
                </div>
            </div>
        `;
    }).join('');

    // === CALCULATION UPDATE ===
    // 1. Subtotal
    if (subtotalEl) subtotalEl.innerText = formatter.format(subtotal);

    // 2. Manual Discount
    const discInput = document.getElementById('inputDiscount');
    let discount = discInput ? parseFloat(discInput.value) || 0 : 0;

    // 3. Points Redemption
    let pointsDed = 0;
    const usePointsCheck = document.getElementById('usePoints');
    if (usePointsCheck && usePointsCheck.checked) {
        // Simple Logic: Use ALL points or max up to subtotal
        const maxDiscPoints = customerPointsGlobal * POINT_CONVERSION_RATE;
        // Cap discount at subtotal - manual discount
        const billAfterDisc = Math.max(0, subtotal - discount);

        // Use points to cover the rest
        const valToUse = Math.min(maxDiscPoints, billAfterDisc);
        pointsDed = valToUse;

        // Show deduction row
        document.getElementById('rowPointsDed').style.display = 'flex';
        document.getElementById('valPointsDed').innerText = '-' + formatter.format(pointsDed);
    } else {
        document.getElementById('rowPointsDed').style.display = 'none';
    }

    // 4. Tax (After all discounts? Or Before? Usually after discounts). 
    // Let's tax the Final Value to be fair to customer.
    // Or tax the original price? Let's use Tax on Net Amount (Standard).
    const taxableAmount = Math.max(0, subtotal - discount - pointsDed);
    let tax = taxableAmount * (typeof TAX_RATE !== 'undefined' ? TAX_RATE : 0.1);

    // 5. Total
    let total = taxableAmount + tax;

    if (taxEl) taxEl.innerText = formatter.format(tax);
    if (totalEl) totalEl.innerText = formatter.format(total);
    if (itemCountEl) itemCountEl.innerText = `${totalItems} Item`;

    // Update mobile cart count if exists
    const mobileCartCount = document.getElementById('mobileCartCount');
    if (mobileCartCount) {
        mobileCartCount.innerText = totalItems;
    }

    // Store global totals for Checkout
    window.currentTotalCalc = {
        subtotal, discount, pointsDed, tax, total
    };
}

// Payment Method Selection Helper
function changePaymentMethod(method) {
    // Update hidden select
    const paymentSelect = document.getElementById('paymentMethod');
    if (paymentSelect) paymentSelect.value = method;

    // Update UI Buttons
    document.querySelectorAll('.method-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'transparent';
        btn.style.borderColor = 'rgba(255,255,255,0.1)';
        btn.style.color = 'white';
    });

    const activeBtn = document.getElementById(`btn-meth-${method}`);
    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.background = 'rgba(212,163,115,0.1)';
        activeBtn.style.borderColor = 'var(--primary)';
        activeBtn.style.color = 'var(--primary)';
    }

    // Toggle Cash Input visibility
    const cashSection = document.getElementById('cash-input-section');
    if (cashSection) {
        cashSection.style.display = (method === 'cash') ? 'block' : 'none';

        // Auto-focus cash input if cash selected
        if (method === 'cash') {
            setTimeout(() => document.getElementById('inputCashReceived').focus(), 100);
        }
    }
}

// === CHECKOUT LOGIC ===

// Checkout Button Click
const checkoutBtn = document.querySelector('.checkout-btn');
if (checkoutBtn) {
    checkoutBtn.addEventListener('click', function () {
        if (cart.length === 0) {
            alert('Keranjang belanja kosong!');
            return;
        }

        const customerName = document.getElementById('customerName').value.trim();
        if (!customerName) {
            alert('Mohon isi nama pelanggan!');
            document.getElementById('customerName').focus();
            return;
        }

        // FIX: Consistently use the calculated total (including discounts/points)
        const calc = window.currentTotalCalc || { total: 0 };
        const grandTotal = calc.total;

        // Reset Method to Cash every time we open modal
        changePaymentMethod('cash');

        // Show Modal
        document.getElementById('modalGrandTotal').innerText = formatter.format(grandTotal);
        // Reset Inputs
        document.getElementById('inputCashReceived').value = '';
        document.getElementById('paymentModal').style.display = 'flex';

        // Setup Confirm Button in Modal
        document.getElementById('btnConfirmPayment').onclick = function () {
            const method = document.getElementById('paymentMethod').value;
            // Re-fetch grandTotal from current state to be safe
            const currentCalc = window.currentTotalCalc || { total: 0 };
            const currentTotal = currentCalc.total;

            if (method === 'cash') {
                const cashInput = parseFloat(document.getElementById('inputCashReceived').value);

                if (isNaN(cashInput) || cashInput < currentTotal) {
                    alert(`Uang kurang! Total: ${formatter.format(currentTotal)}`);
                    return;
                }

                processTransaction(customerName, method, cashInput, cashInput - currentTotal);
            } else {
                // Non-cash (QRIS/Debit) directly process
                processTransaction(customerName, method, currentTotal, 0);
            }
        };
    });
}

// Process Transaction (API Call)
async function processTransaction(customerName, paymentMethod, receivedAmount, changeAmount) {
    // Show Loading or change button text could be good here

    // Get Order Type
    // FIX: Use global variable from new selection logic
    const orderType = currentOrderType;

    // Get Totals from Global Calc
    const totals = window.currentTotalCalc || { discount: 0, pointsDed: 0 };

    try {
        const response = await fetch('process_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cart: cart,
                customer_name: customerName,
                payment_method: paymentMethod,
                cash_received: receivedAmount,
                change_amount: changeAmount,
                order_type: orderType,
                discount_amount: totals.discount,
                points_used_val: totals.pointsDed,
                points_used_qty: (totals.pointsDed / POINT_CONVERSION_RATE) // Assuming POINT_CONVERSION_RATE is defined
            })
        });

        const result = await response.json();

        if (result.success) {
            // Hide Payment Modal if Open
            document.getElementById('paymentModal').style.display = 'none';

            // Show Success Modal
            const modal = document.getElementById('successModal');
            const msgEl = document.getElementById('successMessage');

            let msg = `Invoice: ${result.invoice}<br>Pelanggan: ${customerName}`;
            if (changeAmount > 0) {
                msg += `<br><br><strong style="color: #4ade80; font-size: 1.25rem;">Kembalian: ${formatter.format(changeAmount)}</strong>`;
            }

            msgEl.innerHTML = msg;
            modal.style.display = 'flex';

            // Print Button Action
            document.getElementById('btnPrintStruk').onclick = function () {
                window.open('print_receipt.php?id=' + result.invoice, '_blank', 'width=400,height=600');
            };

            // Reset Shop
            cart = [];
            document.getElementById('customerName').value = '';
            document.getElementById('paymentMethod').value = 'cash';
            updateCartUI();

        } else {
            alert('Gagal memproses transaksi: ' + result.message);
        }

    } catch (error) {
        console.error(error);
        alert('Terjadi kesalahan koneksi!');
    }
}

// Toggle Order Type UI (Pill Button)
function toggleOrderType(radio) {
    const options = document.querySelectorAll('.type-option');
    options.forEach(opt => {
        opt.classList.remove('active');
        opt.style.background = 'transparent';
        opt.style.color = 'var(--text-muted)';
    });

    const activeOption = radio.nextElementSibling;
    activeOption.classList.add('active');
    activeOption.style.background = 'var(--primary)';
    activeOption.style.color = 'var(--bg-body)';
}

// Filter Category
function filterCategory(category) {
    const buttons = document.querySelectorAll('.filter-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active');
        // Match button by category argument (case insensitive check)
        const btnText = btn.innerText.trim().toLowerCase();
        if (category === 'all' && btnText === 'semua') {
            btn.classList.add('active');
        } else if (btnText === category.toLowerCase()) {
            btn.classList.add('active');
        }
    });

    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });

    // Reset search if any
    if (searchInput) searchInput.value = '';
}

// === KEYBOARD SHORTCUTS ===
document.addEventListener('keydown', function (e) {
    // ESC to clear search
    if (e.key === 'Escape') {
        const search = document.getElementById('searchInput');
        if (search && search === document.activeElement) {
            search.value = '';
            search.dispatchEvent(new Event('input'));
            search.blur();
        }
    }

    // F9 for Quick Checkout
    if (e.key === 'F9') {
        e.preventDefault();
        const checkoutBtn = document.querySelector('.checkout-btn');
        if (checkoutBtn && cart.length > 0) {
            checkoutBtn.click();
        }
    }
});
