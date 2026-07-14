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
            <button type="button" class="btn btn-secondary" id="scanCamBtn" onclick="openScanner()" title="<?= __('pos.scan_camera') ?>"><i class="fas fa-camera"></i></button>
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
            <span id="pendingSync" class="pos-pending hidden" title="<?= __('pos.pending') ?>"><i class="fas fa-cloud-arrow-up"></i> <span id="pendingCount">0</span></span>
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
<div style="margin-top: 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
    <div style="display:flex; align-items:center; gap:14px;">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;color:var(--text-secondary);">
            <input type="checkbox" id="autoPrint"> <i class="fas fa-print"></i> <?= __('pos.autoprint') ?>
        </label>
        <button class="btn btn-sm btn-secondary" id="reprintBtn" onclick="reprintLast()" disabled><i class="fas fa-receipt"></i> <?= __('pos.reprint') ?></button>
    </div>
    <div>
        <button class="btn btn-sm btn-secondary" onclick="openModal('closeModal')"><i class="fas fa-door-closed"></i> <?= __('pos.close_session') ?></button>
        <a href="<?= url('/caisse/history') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-history"></i> <?= __('pos.history') ?></a>
    </div>
</div>

<!-- Camera barcode scanner overlay -->
<div class="modal-overlay" id="scanModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header"><h3><i class="fas fa-barcode"></i> <?= __('pos.scan_camera') ?></h3><button class="modal-close" onclick="closeScanner()">&times;</button></div>
        <div class="modal-body" style="text-align:center;">
            <video id="scanVideo" playsinline style="width:100%;border-radius:var(--radius);background:#000;max-height:320px;"></video>
            <div id="scanStatus" style="font-size:13px;color:var(--text-secondary);margin-top:8px;"></div>
        </div>
    </div>
</div>

<!-- Printable receipt (hidden on screen, shown only when printing) -->
<div id="posReceipt" aria-hidden="true"></div>

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
const sessionId = <?= json_encode($session['id']) ?>;
const csrfToken = <?= json_encode(csrf_token()) ?>;
const SELL_URL = <?= json_encode(url('/caisse/sell')) ?>;
const POS_SHOP = {
    name: <?= json_encode($shop['name'] ?? 'GestionPro') ?>,
    address: <?= json_encode($shop['address'] ?? '') ?>,
    phone: <?= json_encode($shop['phone'] ?? '') ?>,
    taxId: <?= json_encode($shop['tax_id'] ?? '') ?>,
    cashier: <?= json_encode($_SESSION['user_full_name'] ?? ($_SESSION['user_name'] ?? '')) ?>
};
// json_encode() (not bare quotes) — it emits a properly escaped JS literal, so a
// translation containing an apostrophe (fr: "d'une vente") can't break the script.
const POS_LANG = {
    cartEmpty: <?= json_encode(__('pos.cart_empty')) ?>,
    clearConfirm: <?= json_encode(__('pos.clear_confirm')) ?>,
    noClient: <?= json_encode(__('pos.no_client')) ?>,
    sale: <?= json_encode(__('invoices.invoice')) ?>,
    change: <?= json_encode(__('pos.change')) ?>,
    offlineSaved: <?= json_encode(__('pos.offline_saved')) ?>,
    synced: <?= json_encode(__('pos.synced')) ?>,
    syncFailed: <?= json_encode(__('pos.sync_failed')) ?>,
    notFound: <?= json_encode(__('pos.not_found')) ?>,
    stockLimit: <?= json_encode(__('pos.stock_limit')) ?>,
    thanks: <?= json_encode(__('pos.receipt_thanks')) ?>,
    receipt: <?= json_encode(__('pos.receipt')) ?>,
    total: <?= json_encode(__('invoices.total_ttc')) ?>,
    subtotal: <?= json_encode(__('invoices.subtotal')) ?>,
    tax: <?= json_encode(__('invoices.tax')) ?>,
    paid: <?= json_encode(__('pos.amount_received')) ?>
};

// Visual feedback on the tapped product card (green pulse / red shake).
function flashCard(card, ok) {
    const cls = ok ? 'is-added' : 'is-denied';
    card.classList.remove('is-added', 'is-denied');
    void card.offsetWidth; // restart the animation
    card.classList.add(cls);
    setTimeout(() => card.classList.remove(cls), 450);
}

// Product click
function addToCart(card) {
    const id = card.dataset.id;
    const existing = cart.find(i => i.id === id);
    const stock = parseInt(card.dataset.stock) || 0;

    // Refusing silently used to look like "the click does nothing" — always say why.
    if (stock <= 0 || (existing && existing.qty >= stock)) {
        flashCard(card, false);
        beep(false);
        posToast(`<i class="fas fa-triangle-exclamation"></i> ${POS_LANG.stockLimit} — ${escapeHtml(card.dataset.name)} (${stock})`, 'warning');
        return;
    }

    if (existing) {
        existing.qty++;
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
    flashCard(card, true);
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
    card.innerHTML = `<div class="name">${p.name}</div><div class="price">${formatMoney(p.selling_price)}</div><div class="stock"><?= __('pos.stock_label') ?>: ${p.current_stock}</div>`;
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

    // Auto-add if exact barcode match (scanners that don't send an Enter suffix)
    if (raw.length >= 8) {
        const barcodeMatch = document.querySelector(`.pos-product-card[data-barcode="${raw}"]`);
        if (barcodeMatch) { scanBarcode(raw); this.value = ''; filterClientSide(''); posGridEmpty.classList.add('hidden'); return; }
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

    // Pay/clear only make sense with a non-empty cart — reflect that instead of
    // letting the buttons look active but do nothing.
    const empty = cart.length === 0;
    document.querySelectorAll('.pos-actions .btn-pay, .pos-actions .btn-danger').forEach(b => {
        b.disabled = empty;
        b.classList.toggle('is-disabled', empty);
    });
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

// ============================================================
// Offline-capable checkout
//   - Every sale carries a client_uid (idempotency key).
//   - Online: POST directly. Network failure falls back to the queue.
//   - Offline: store in IndexedDB, decrement local stock, notify.
//   - Reconnect / interval: flush the queue to the server.
// ============================================================

const ODB_NAME = 'gp-pos', ODB_STORE = 'pending-sales';
function odbOpen() {
    return new Promise((resolve, reject) => {
        const rq = indexedDB.open(ODB_NAME, 1);
        rq.onupgradeneeded = () => rq.result.createObjectStore(ODB_STORE, { keyPath: 'client_uid' });
        rq.onsuccess = () => resolve(rq.result);
        rq.onerror = () => reject(rq.error);
    });
}
function odbTx(mode, fn) {
    return odbOpen().then(db => new Promise((res, rej) => {
        const tx = db.transaction(ODB_STORE, mode);
        const store = tx.objectStore(ODB_STORE);
        const out = fn(store);
        tx.oncomplete = () => res(out && out.result !== undefined ? out.result : out);
        tx.onerror = () => rej(tx.error);
    }));
}
const odbAdd = (sale) => odbTx('readwrite', s => s.put(sale));
const odbDel = (uid) => odbTx('readwrite', s => s.delete(uid));
const odbAll = () => odbTx('readonly', s => s.getAll());

function newUid() {
    return (crypto.randomUUID) ? crypto.randomUUID()
        : (Date.now() + '-' + Math.random().toString(16).slice(2));
}

function buildSale() {
    let total = 0;
    cart.forEach(item => { total += item.price * item.qty * (1 + item.tax / 100); });
    return {
        client_uid: newUid(),
        items: cart.map(i => ({ id: i.id, qty: i.qty })),
        total: total,
        customer_id: selectedCustomer || '',
        payment_method: document.getElementById('payMethod').value,
        amount_paid: document.getElementById('payAmount').value,
        is_credit: document.getElementById('creditSale').checked ? '1' : '0',
        created_at: new Date().toISOString(),
        // Snapshot for the printed receipt (also lets us reprint queued offline sales).
        _lines: cart.map(i => ({ name: i.name, qty: i.qty, price: i.price, tax: i.tax }))
    };
}

function saleFormData(sale, offline) {
    const fd = new FormData();
    fd.append('csrf_token', csrfToken);
    fd.append('items', JSON.stringify(sale.items));
    fd.append('customer_id', sale.customer_id || '');
    fd.append('payment_method', sale.payment_method);
    fd.append('amount_paid', sale.amount_paid);
    fd.append('session_id', sessionId);
    fd.append('is_credit', sale.is_credit);
    fd.append('client_uid', sale.client_uid);
    if (offline) fd.append('offline', '1');
    return fd;
}

function posToast(html, type) {
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + (type || 'success');
    toast.innerHTML = html;
    const container = document.querySelector('.toast-container')
        || (() => { const c = document.createElement('div'); c.className = 'toast-container'; document.body.appendChild(c); return c; })();
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}

// Reduce the on-hand stock shown on the grid so the cashier can't oversell offline.
function decrementLocalStock(sale) {
    sale.items.forEach(it => {
        document.querySelectorAll('.pos-product-card[data-id="' + it.id + '"]').forEach(card => {
            const left = Math.max(0, (parseInt(card.dataset.stock) || 0) - it.qty);
            card.dataset.stock = left;
            const s = card.querySelector('.stock');
            if (s) s.textContent = s.textContent.replace(/[0-9]+$/, left);
        });
    });
}

function resetAfterSale() {
    closeModal('payModal');
    cart = [];
    selectedCustomer = null;
    document.getElementById('clientSearch').value = '';
    document.getElementById('clientResult').innerHTML = '';
    document.getElementById('creditSale').checked = false;
    renderCart();
}

async function updatePendingBadge() {
    let n = 0;
    try { n = (await odbAll()).length; } catch (e) {}
    const badge = document.getElementById('pendingSync');
    document.getElementById('pendingCount').textContent = n;
    badge.classList.toggle('hidden', n === 0);
}

async function completeSale() {
    if (cart.length === 0) return;
    const sale = buildSale();

    if (navigator.onLine) {
        try {
            const r = await fetch(SELL_URL, { method: 'POST', body: saleFormData(sale, false) });
            const data = await r.json();
            if (data.error) { alert(data.error); return; }
            handleReceipt(sale, data.invoice_number, false);
            resetAfterSale();
            posToast(`<i class="fas fa-check-circle"></i> ${POS_LANG.sale} ${data.invoice_number} - Total: ${formatMoney(data.total)}${data.change > 0 ? ' | ' + POS_LANG.change + ': ' + formatMoney(data.change) : ''}`, 'success');
            return;
        } catch (e) {
            // Network dropped mid-request → fall through to the offline queue.
        }
    }

    // Offline (or the request failed): persist locally and sync later.
    try {
        await odbAdd(sale);
        decrementLocalStock(sale);
        handleReceipt(sale, null, true);
        resetAfterSale();
        updatePendingBadge();
        const change = Math.max(0, parseFloat(sale.amount_paid || 0) - sale.total);
        posToast(`<i class="fas fa-cloud-arrow-up"></i> ${POS_LANG.offlineSaved} - ${formatMoney(sale.total)}${change > 0 ? ' | ' + POS_LANG.change + ': ' + formatMoney(change) : ''}`, 'warning');
    } catch (e) {
        alert('Erreur: ' + e.message);
    }
}

let flushing = false;
async function flushQueue() {
    if (flushing || !navigator.onLine) return;
    flushing = true;
    let synced = 0, failed = 0;
    try {
        const pending = await odbAll();
        for (const sale of pending) {
            let data;
            try {
                const r = await fetch(SELL_URL, { method: 'POST', body: saleFormData(sale, true) });
                if (r.status === 401 || r.status === 403) break;   // need to re-login; retry later
                if (!r.ok) { continue; }                            // server/transient error; keep
                data = await r.json();
            } catch (e) { break; }                                  // network dropped again; stop
            if (data && data.success) { await odbDel(sale.client_uid); synced++; }
            else if (data && data.error) { await odbDel(sale.client_uid); failed++; } // definitive business error
        }
    } finally {
        flushing = false;
        updatePendingBadge();
        if (synced > 0) posToast(`<i class="fas fa-cloud-arrow-up"></i> ${synced} ${POS_LANG.synced}`, 'success');
        if (failed > 0) posToast(`<i class="fas fa-triangle-exclamation"></i> ${failed} × ${POS_LANG.syncFailed}`, 'danger');
    }
}

window.addEventListener('online', flushQueue);
updatePendingBadge();
flushQueue();
setInterval(flushQueue, 30000);

// Initial cart paint — deferred because formatMoney() lives in app.js, which the
// layout loads *after* this inline script.
document.addEventListener('DOMContentLoaded', renderCart);

// ============================================================
// Receipt printing (58/80mm thermal via the browser print dialog)
// ============================================================
let lastReceiptSale = null;

function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function buildReceiptHTML(sale, invoiceNumber) {
    let sub = 0, tax = 0;
    (sale._lines || []).forEach(l => { const ls = l.price * l.qty; sub += ls; tax += ls * (l.tax / 100); });
    const total = sale.total;
    const paid = parseFloat(sale.amount_paid || 0);
    const change = Math.max(0, paid - total);
    const dt = new Date(sale.created_at || Date.now());
    const p = n => String(n).padStart(2, '0');
    const dstr = `${p(dt.getDate())}/${p(dt.getMonth() + 1)}/${dt.getFullYear()} ${p(dt.getHours())}:${p(dt.getMinutes())}`;
    let rows = '';
    (sale._lines || []).forEach(l => {
        rows += `<tr><td>${escapeHtml(l.name)}</td><td class="c">${l.qty}</td><td class="r">${formatMoney(l.price * l.qty)}</td></tr>`;
    });
    return `
    <div class="rcpt-head">
        <div class="rcpt-shop">${escapeHtml(POS_SHOP.name)}</div>
        ${POS_SHOP.address ? `<div>${escapeHtml(POS_SHOP.address)}</div>` : ''}
        ${POS_SHOP.phone ? `<div>Tel: ${escapeHtml(POS_SHOP.phone)}</div>` : ''}
        ${POS_SHOP.taxId ? `<div>NIF: ${escapeHtml(POS_SHOP.taxId)}</div>` : ''}
    </div>
    <div class="rcpt-meta">
        <div>${POS_LANG.receipt}: ${invoiceNumber ? escapeHtml(invoiceNumber) : '(offline)'}</div>
        <div>${dstr}</div>
        ${POS_SHOP.cashier ? `<div>${escapeHtml(POS_SHOP.cashier)}</div>` : ''}
    </div>
    <table class="rcpt-items"><tbody>${rows}</tbody></table>
    <div class="rcpt-tot">
        <div><span>${POS_LANG.subtotal}</span><span>${formatMoney(sub)}</span></div>
        <div><span>${POS_LANG.tax}</span><span>${formatMoney(tax)}</span></div>
        <div class="g"><span>${POS_LANG.total}</span><span>${formatMoney(total)}</span></div>
        <div><span>${POS_LANG.paid}</span><span>${formatMoney(paid)}</span></div>
        ${change > 0 ? `<div><span>${POS_LANG.change}</span><span>${formatMoney(change)}</span></div>` : ''}
    </div>
    <div class="rcpt-foot">${escapeHtml(POS_LANG.thanks)}</div>`;
}

function printReceipt(sale, invoiceNumber) {
    const el = document.getElementById('posReceipt');
    el.innerHTML = buildReceiptHTML(sale, invoiceNumber);
    // Move to a direct child of <body> so the print stylesheet can isolate it cleanly.
    if (el.parentElement !== document.body) document.body.appendChild(el);
    window.print();
}

function handleReceipt(sale, invoiceNumber, offline) {
    lastReceiptSale = { sale: sale, invoiceNumber: invoiceNumber };
    const btn = document.getElementById('reprintBtn');
    if (btn) btn.disabled = false;
    if (document.getElementById('autoPrint').checked) printReceipt(sale, invoiceNumber);
}

function reprintLast() {
    if (lastReceiptSale) printReceipt(lastReceiptSale.sale, lastReceiptSale.invoiceNumber);
}

(function () {
    const cb = document.getElementById('autoPrint');
    cb.checked = localStorage.getItem('pos_autoprint') === '1';
    cb.addEventListener('change', () => localStorage.setItem('pos_autoprint', cb.checked ? '1' : '0'));
})();

// ============================================================
// Barcode scanner — hardware wedge (keyboard) + camera (BarcodeDetector)
// ============================================================
let lastScan = { code: '', t: 0 };
function scanBarcode(code) {
    code = (code || '').trim();
    if (!code) return;
    const now = Date.now();
    if (code === lastScan.code && now - lastScan.t < 500) return; // dedup double fire
    lastScan = { code: code, t: now };

    const sel = '.pos-product-card[data-barcode="' + ((window.CSS && CSS.escape) ? CSS.escape(code) : code) + '"]';
    const card = document.querySelector(sel);
    if (card) { addToCart(card); beep(true); return; }
    if (navigator.onLine) {
        remoteSearch(code).then(() => {
            const c = document.querySelector(sel);
            if (c) { addToCart(c); beep(true); }
            else { beep(false); posToast('<i class="fas fa-barcode"></i> ' + POS_LANG.notFound + ' (' + escapeHtml(code) + ')', 'danger'); }
        });
    } else {
        beep(false);
        posToast('<i class="fas fa-barcode"></i> ' + POS_LANG.notFound + ' (' + escapeHtml(code) + ')', 'danger');
    }
}

let audioCtx = null;
function beep(ok) {
    try {
        audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
        const o = audioCtx.createOscillator(), g = audioCtx.createGain();
        o.frequency.value = ok ? 880 : 200;
        o.connect(g); g.connect(audioCtx.destination);
        g.gain.setValueAtTime(0.07, audioCtx.currentTime);
        o.start(); o.stop(audioCtx.currentTime + (ok ? 0.08 : 0.2));
    } catch (e) {}
}

// Hardware wedge: fast keystroke bursts ending with Enter.
let wedgeBuf = '', wedgeLast = 0;
document.addEventListener('keydown', function (e) {
    const scanOpen = document.getElementById('scanModal');
    if (scanOpen && scanOpen.classList.contains('active')) return;
    const now = Date.now();
    if (e.key === 'Enter') {
        if (wedgeBuf.length >= 3 && (now - wedgeLast) < 60) {
            e.preventDefault();
            const code = wedgeBuf; wedgeBuf = '';
            const active = document.activeElement;
            if (active && active.id === 'posSearch') active.value = '';
            scanBarcode(code);
        } else { wedgeBuf = ''; }
        return;
    }
    if (now - wedgeLast > 60) wedgeBuf = '';   // human-speed gap → reset
    if (e.key.length === 1) wedgeBuf += e.key;
    wedgeLast = now;
});

// Camera scanner (Chrome / Android). Degrades gracefully when unsupported.
let scanStream = null, scanRaf = null, scanDetector = null;
async function openScanner() {
    if (!('BarcodeDetector' in window) || !(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
        posToast('<i class="fas fa-camera"></i> ' + POS_LANG.notFound, 'danger');
        return;
    }
    openModal('scanModal');
    const status = document.getElementById('scanStatus');
    status.textContent = '…';
    try {
        scanDetector = scanDetector || new BarcodeDetector();
        scanStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        const video = document.getElementById('scanVideo');
        video.srcObject = scanStream;
        await video.play();
        status.textContent = '';
        const tick = async () => {
            if (!scanStream) return;
            try {
                const codes = await scanDetector.detect(video);
                if (codes && codes.length) { scanBarcode(codes[0].rawValue); closeScanner(); return; }
            } catch (e) {}
            scanRaf = requestAnimationFrame(tick);
        };
        scanRaf = requestAnimationFrame(tick);
    } catch (e) {
        status.textContent = e.message || 'camera error';
    }
}
function closeScanner() {
    closeModal('scanModal');
    if (scanRaf) cancelAnimationFrame(scanRaf);
    scanRaf = null;
    if (scanStream) { scanStream.getTracks().forEach(t => t.stop()); scanStream = null; }
}

</script>
<?php endif; ?>
