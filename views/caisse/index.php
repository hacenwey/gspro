<?php if (!$session): ?>
<!-- Open Cash Session -->
<div class="card" style="max-width: 500px; margin: 60px auto;">
    <div class="card-header"><h3><i class="fas fa-cash-register" style="margin-right:8px;color:var(--primary);"></i><?= __('pos.open') ?></h3></div>
    <div class="card-body">
        <form method="POST" action="<?= url('/caisse/open') ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label"><?= __('pos.opening_balance') ?> (<?= CURRENCY ?>)</label>
                <input type="number" name="opening_balance" class="form-control" step="0.01" min="0" value="0" style="font-size:24px;font-weight:700;text-align:center;font-family:var(--font-mono);" required autofocus>
            </div>
            <button type="submit" class="btn btn-success btn-lg w-100" style="justify-content:center;"><i class="fas fa-door-open"></i> <?= __('pos.open') ?></button>
        </form>
    </div>
</div>
<?php else: ?>
<!-- POS Interface -->
<div class="pos-layout">
    <!-- Products Grid -->
    <div class="pos-products">
        <div style="margin-bottom: 12px; display: flex; gap: 8px;">
            <div class="toolbar-search" style="flex:1;max-width:100%;">
                <span class="search-icon"><i class="fas fa-search"></i></span>
                <input type="text" id="posSearch" class="form-control" placeholder="<?= __('pos.search') ?>" autofocus>
            </div>
        </div>
        <div style="margin-bottom: 12px; display: flex; gap: 6px; flex-wrap: wrap;">
            <button class="btn btn-sm btn-primary cat-filter active" data-cat="all"><?= __('pos.all') ?></button>
            <?php foreach ($categories as $cat): ?>
            <button class="btn btn-sm btn-secondary cat-filter" data-cat="<?= $cat['id'] ?>"><?= e($cat['name']) ?></button>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($totalProducts) && $totalProducts > $productsShown): ?>
        <div class="pos-catalog-hint mb-2 text-sm text-muted">
            <i class="fas fa-circle-info"></i>
            <?= $productsShown ?> / <?= $totalProducts ?> <?= __('pos.catalog_hint') ?>
        </div>
        <?php endif; ?>
        <div class="pos-grid" id="posGrid">
            <?php foreach ($products as $p): ?>
            <div class="pos-product-card" data-id="<?= $p['id'] ?>" data-name="<?= e($p['name']) ?>" data-price="<?= $p['selling_price'] ?>" data-tax="<?= $p['tax_rate'] ?>" data-stock="<?= $p['current_stock'] ?>" data-cat="<?= $p['category_id'] ?>" data-ref="<?= e($p['reference']) ?>" data-barcode="<?= e($p['barcode'] ?? '') ?>">
                <div class="name"><?= e($p['name']) ?></div>
                <div class="price"><?= formatMoney($p['selling_price']) ?></div>
                <div class="stock"><?= __('pos.stock_label') ?>: <?= $p['current_stock'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="posGridEmpty" class="empty-state hidden" style="padding:40px 20px;">
            <div class="icon"><i class="fas fa-magnifying-glass"></i></div>
            <p class="mb-0"><?= __('pos.no_results') ?></p>
        </div>
    </div>

    <!-- Cart -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            <i class="fas fa-shopping-cart" style="margin-right: 8px;"></i><?= __('pos.cart') ?>
            <span id="cartCount" style="margin-left: auto; background: var(--primary); color: #fff; padding: 2px 10px; border-radius: 12px; font-size: 12px;">0</span>
        </div>

        <!-- Client Selection -->
        <div style="padding: 10px 16px; border-bottom: 1px solid var(--border);">
            <input type="text" id="clientSearch" class="form-control" placeholder="<?= __('pos.client_search') ?>" style="font-size: 12px; padding: 8px 12px;">
            <div id="clientResult" style="font-size: 12px; margin-top: 4px; color: var(--text-secondary);"></div>
        </div>

        <div class="pos-cart-items" id="cartItems">
            <div class="empty-state" id="cartEmpty" style="padding: 40px 20px;">
                <div class="icon"><i class="fas fa-shopping-basket"></i></div>
                <p><?= __('pos.cart_empty') ?></p>
            </div>
        </div>

        <div class="pos-cart-totals">
            <div class="pos-total-row"><span><?= __('invoices.subtotal') ?></span><span class="amount" id="subtotal">0,00 <?= CURRENCY ?></span></div>
            <div class="pos-total-row"><span><?= __('invoices.tax') ?></span><span class="amount" id="taxTotal">0,00 <?= CURRENCY ?></span></div>
            <div class="pos-total-row grand-total"><span><?= __('invoices.total_ttc') ?></span><span class="amount" id="grandTotal">0,00 <?= CURRENCY ?></span></div>
        </div>

        <div class="pos-actions">
            <button class="btn btn-warning" onclick="holdCart()"><i class="fas fa-pause"></i> <?= __('pos.hold') ?></button>
            <button class="btn btn-danger" onclick="clearCart()"><i class="fas fa-times"></i> <?= __('pos.cancel') ?></button>
            <button class="btn btn-success btn-pay" onclick="openPayment()"><i class="fas fa-money-bill-wave"></i> <?= __('pos.pay') ?></button>
        </div>
    </div>
</div>

<!-- Close Session Button -->
<div style="margin-top: 16px; text-align: right;">
    <button class="btn btn-sm btn-secondary" onclick="openModal('closeModal')"><i class="fas fa-door-closed"></i> <?= __('pos.close_session') ?></button>
    <a href="<?= url('/caisse/history') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-history"></i> <?= __('pos.history') ?></a>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="payModal">
    <div class="modal">
        <div class="modal-header"><h3><?= __('pos.payment') ?></h3><button class="modal-close" onclick="closeModal('payModal')">&times;</button></div>
        <div class="modal-body">
            <div style="text-align:center;margin-bottom:20px;">
                <div style="font-size:14px;color:var(--text-secondary);"><?= __('pos.total_to_pay') ?></div>
                <div style="font-size:36px;font-weight:800;font-family:var(--font-mono);color:var(--primary);" id="payTotal">0,00 <?= CURRENCY ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('pos.payment_method') ?></label>
                <select id="payMethod" class="form-control">
                    <option value="cash"><?= __('payments.method.cash') ?></option>
                    <option value="card"><?= __('payments.method.card') ?></option>
                    <option value="bankily"><?= __('payments.method.bankily') ?></option>
                    <option value="masrivi"><?= __('payments.method.masrivi') ?></option>
                    <option value="sedad"><?= __('payments.method.sedad') ?></option>
                    <option value="check"><?= __('payments.method.check') ?></option>
                    <option value="transfer"><?= __('payments.method.transfer') ?></option>
                    <option value="mobile"><?= __('payments.method.mobile') ?></option>
                </select>
            </div>
            <div class="form-group" id="cashGroup">
                <label class="form-label"><?= __('pos.amount_received') ?></label>
                <input type="number" id="payAmount" class="form-control" step="0.01" min="0" style="font-size:24px;font-weight:700;text-align:center;font-family:var(--font-mono);">
            </div>
            <div id="changeDisplay" style="text-align:center;padding:12px;border-radius:var(--radius);background:rgba(39,174,96,0.08);display:none;">
                <div style="font-size:13px;color:var(--text-secondary);"><?= __('pos.change') ?></div>
                <div style="font-size:28px;font-weight:800;font-family:var(--font-mono);color:var(--success);" id="changeAmount">0,00 <?= CURRENCY ?></div>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" id="creditSale"> <?= __('pos.credit_sale') ?>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('payModal')"><?= __('common.cancel') ?></button>
            <button class="btn btn-success" onclick="completeSale()"><i class="fas fa-check"></i> <?= __('pos.validate') ?></button>
        </div>
    </div>
</div>

<!-- Close Session Modal -->
<div class="modal-overlay" id="closeModal">
    <div class="modal">
        <div class="modal-header"><h3><?= __('pos.close') ?></h3><button class="modal-close" onclick="closeModal('closeModal')">&times;</button></div>
        <form method="POST" action="<?= url('/caisse/close') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><?= __('pos.opening_initial') ?></label>
                    <div class="text-mono fw-bold" style="font-size:18px;"><?= formatMoney($session['opening_balance']) ?></div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('pos.closing_balance') ?></label>
                    <input type="number" name="closing_balance" class="form-control" step="0.01" min="0" style="font-size:20px;font-weight:700;font-family:var(--font-mono);" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('common.notes') ?></label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('closeModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-door-closed"></i> <?= __('pos.close') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
let cart = [];
let selectedCustomer = null;
const sessionId = '<?= $session['id'] ?>';
const csrfToken = '<?= csrf_token() ?>';
const POS_LANG = {
    cartEmpty: '<?= __('pos.cart_empty') ?>',
    clearConfirm: '<?= __('pos.clear_confirm') ?>',
    noClient: '<?= __('pos.no_client') ?>',
    sale: '<?= __('invoices.invoice') ?>',
    change: '<?= __('pos.change') ?>'
};

// Product click
function addToCart(card) {
    const id = card.dataset.id;
    const existing = cart.find(i => i.id === id);
    const stock = parseInt(card.dataset.stock);
    if (existing) {
        if (existing.qty < stock) existing.qty++;
    } else {
        cart.push({
            id: id,
            name: card.dataset.name,
            price: parseFloat(card.dataset.price),
            tax: parseFloat(card.dataset.tax),
            qty: 1,
            stock: stock
        });
    }
    renderCart();
}

document.querySelectorAll('.pos-product-card').forEach(card => {
    card.addEventListener('click', () => addToCart(card));
});

// Search (hybride : client-side d'abord, fallback serveur si 0 match sur catalogue partiel)
const POS_TOTAL_PRODUCTS = <?= (int)($totalProducts ?? 0) ?>;
const POS_SHOWN = <?= (int)($productsShown ?? 0) ?>;
const posGrid = document.getElementById('posGrid');
const posGridEmpty = document.getElementById('posGridEmpty');
let searchTimer;

function filterClientSide(q) {
    let visible = 0;
    document.querySelectorAll('.pos-product-card').forEach(card => {
        // Only filter cards that originated server-side; remote-fetched cards we leave.
        const remote = card.dataset.remote === '1';
        if (remote && q === '') { card.remove(); return; }
        const match = q === '' ||
                     card.dataset.name.toLowerCase().includes(q) ||
                     card.dataset.ref.toLowerCase().includes(q) ||
                     card.dataset.barcode === q;
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    return visible;
}

function appendRemoteProduct(p) {
    const card = document.createElement('div');
    card.className = 'pos-product-card';
    card.dataset.id = p.id;
    card.dataset.name = p.name;
    card.dataset.price = p.selling_price;
    card.dataset.tax = p.tax_rate;
    card.dataset.stock = p.current_stock;
    card.dataset.ref = p.reference || '';
    card.dataset.barcode = p.barcode || '';
    card.dataset.remote = '1';
    card.innerHTML = `<div class="name">${p.name}</div><div class="price">${p.selling_price}</div><div class="stock"><?= __('pos.stock_label') ?>: ${p.current_stock}</div>`;
    card.addEventListener('click', () => addToCart(card));
    posGrid.appendChild(card);
}

async function remoteSearch(q) {
    try {
        const res = await fetch('<?= url('/caisse/search') ?>?q=' + encodeURIComponent(q));
        const rows = await res.json();
        // Remove previous remote cards
        document.querySelectorAll('.pos-product-card[data-remote="1"]').forEach(c => c.remove());
        const existingIds = new Set([...document.querySelectorAll('.pos-product-card')].map(c => c.dataset.id));
        rows.forEach(p => { if (!existingIds.has(p.id)) appendRemoteProduct(p); });
        return rows.length + filterClientSide(q.toLowerCase());
    } catch (e) { return 0; }
}

document.getElementById('posSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    const raw = this.value;
    const q = raw.toLowerCase();

    // Auto-add if exact barcode match
    if (raw.length >= 8) {
        const barcodeMatch = document.querySelector(`.pos-product-card[data-barcode="${raw}"]`);
        if (barcodeMatch) { barcodeMatch.click(); this.value = ''; filterClientSide(''); posGridEmpty.classList.add('hidden'); return; }
    }

    const visible = filterClientSide(q);
    if (visible === 0 && q.length >= 2 && POS_TOTAL_PRODUCTS > POS_SHOWN) {
        // Delay server hit slightly
        searchTimer = setTimeout(async () => {
            const count = await remoteSearch(q);
            posGridEmpty.classList.toggle('hidden', count > 0);
        }, 180);
    } else {
        posGridEmpty.classList.toggle('hidden', visible > 0 || q === '');
    }
});

// Category filter
document.querySelectorAll('.cat-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.cat-filter').forEach(b => { b.classList.remove('active','btn-primary'); b.classList.add('btn-secondary'); });
        this.classList.add('active','btn-primary'); this.classList.remove('btn-secondary');
        const cat = this.dataset.cat;
        document.querySelectorAll('.pos-product-card').forEach(card => {
            card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
        });
    });
});

// Client search
let clientTimeout;
document.getElementById('clientSearch').addEventListener('input', function() {
    clearTimeout(clientTimeout);
    const q = this.value;
    if (q.length < 2) { document.getElementById('clientResult').innerHTML = ''; selectedCustomer = null; return; }
    clientTimeout = setTimeout(() => {
        fetch('<?= url('/api/clients/search') ?>?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(clients => {
                let html = '';
                clients.forEach(c => {
                    html += `<div style="padding:6px;cursor:pointer;border-radius:4px;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''" onclick="selectClient('${c.id}','${(c.first_name||'')} ${c.last_name}')">
                        <b>${c.first_name || ''} ${c.last_name}</b> ${c.phone ? '- '+c.phone : ''}
                    </div>`;
                });
                document.getElementById('clientResult').innerHTML = html || '<div style="padding:6px;color:var(--text-muted);">' + POS_LANG.noClient + '</div>';
            });
    }, 300);
});

function selectClient(id, name) {
    selectedCustomer = id;
    document.getElementById('clientSearch').value = name;
    document.getElementById('clientResult').innerHTML = '<span style="color:var(--success);"><i class="fas fa-check"></i> ' + name + '</span>';
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const emptyHtml = `<div class="empty-state" style="padding: 40px 20px;">
        <div class="icon"><i class="fas fa-shopping-basket"></i></div>
        <p>${POS_LANG.cartEmpty}</p>
    </div>`;

    if (cart.length === 0) {
        container.innerHTML = emptyHtml;
    } else {
        let html = '';
        cart.forEach((item, i) => {
            const lineTotal = item.price * item.qty;
            html += `<div class="pos-cart-item">
                <div class="item-info">
                    <div class="item-name">${item.name}</div>
                    <div class="item-price">${formatMoney(item.price)} x ${item.qty}</div>
                </div>
                <div class="qty-control">
                    <button class="qty-btn" onclick="changeQty(${i},-1)">-</button>
                    <span class="qty-value">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty(${i},1)">+</button>
                </div>
                <div class="item-total">${formatMoney(lineTotal)}</div>
                <button class="remove-btn" onclick="removeItem(${i})"><i class="fas fa-times"></i></button>
            </div>`;
        });
        container.innerHTML = html;
    }

    // Totals
    let subtotal = 0, tax = 0;
    cart.forEach(item => {
        const line = item.price * item.qty;
        subtotal += line;
        tax += line * (item.tax / 100);
    });
    document.getElementById('subtotal').textContent = formatMoney(subtotal);
    document.getElementById('taxTotal').textContent = formatMoney(tax);
    document.getElementById('grandTotal').textContent = formatMoney(subtotal + tax);
    document.getElementById('cartCount').textContent = cart.reduce((s, i) => s + i.qty, 0);
}

function changeQty(index, delta) {
    cart[index].qty += delta;
    if (cart[index].qty <= 0) cart.splice(index, 1);
    else if (cart[index].qty > cart[index].stock) cart[index].qty = cart[index].stock;
    renderCart();
}

function removeItem(index) { cart.splice(index, 1); renderCart(); }
function clearCart() { if (cart.length === 0 || confirm(POS_LANG.clearConfirm)) { cart = []; renderCart(); } }
function holdCart() { alert('Fonctionnalite en attente disponible prochainement.'); }

function openPayment() {
    if (cart.length === 0) return;
    let total = 0;
    cart.forEach(item => { total += item.price * item.qty * (1 + item.tax / 100); });
    document.getElementById('payTotal').textContent = formatMoney(total);
    document.getElementById('payAmount').value = total.toFixed(2);
    openModal('payModal');
}

document.getElementById('payAmount').addEventListener('input', function() {
    let total = 0;
    cart.forEach(item => { total += item.price * item.qty * (1 + item.tax / 100); });
    const change = Math.max(0, parseFloat(this.value || 0) - total);
    document.getElementById('changeAmount').textContent = formatMoney(change);
    document.getElementById('changeDisplay').style.display = change > 0 ? '' : 'none';
});

function completeSale() {
    const items = cart.map(i => ({ id: i.id, qty: i.qty }));
    let total = 0;
    cart.forEach(item => { total += item.price * item.qty * (1 + item.tax / 100); });

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('items', JSON.stringify(items));
    formData.append('customer_id', selectedCustomer || '');
    formData.append('payment_method', document.getElementById('payMethod').value);
    formData.append('amount_paid', document.getElementById('payAmount').value);
    formData.append('session_id', sessionId);
    formData.append('is_credit', document.getElementById('creditSale').checked ? '1' : '0');

    fetch('<?= url('/caisse/sell') ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            closeModal('payModal');
            cart = [];
            selectedCustomer = null;
            document.getElementById('clientSearch').value = '';
            document.getElementById('clientResult').innerHTML = '';
            renderCart();

            // Success notification
            const toast = document.createElement('div');
            toast.className = 'toast toast-success';
            toast.innerHTML = `<i class="fas fa-check-circle"></i> ${POS_LANG.sale} ${data.invoice_number} - Total: ${formatMoney(data.total)}${data.change > 0 ? ' | ' + POS_LANG.change + ': '+formatMoney(data.change) : ''}`;
            const container = document.querySelector('.toast-container') || (() => { const c = document.createElement('div'); c.className = 'toast-container'; document.body.appendChild(c); return c; })();
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        })
        .catch(e => alert('Erreur: ' + e.message));
}

function formatMoney(amount) {
    const symbol = (typeof APP_CURRENCY_SYMBOL !== 'undefined') ? APP_CURRENCY_SYMBOL : '$';
    const locale = (typeof APP_LANG !== 'undefined') ? APP_LANG : 'en';
    return symbol + new Intl.NumberFormat(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
}
</script>
<?php endif; ?>
