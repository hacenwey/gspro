<?php
/** One ticket: pick dishes on the left, watch the running order on the right. */
$locked = in_array($order['status'], ['paid', 'cancelled'], true);
$itemClass = ['pending' => 'secondary', 'sent' => 'info', 'preparing' => 'warning', 'ready' => 'success', 'served' => 'primary', 'cancelled' => 'danger'];
?>
<div class="toolbar">
    <div>
        <a href="<?= url('/orders') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i></a>
        <span class="text-mono fw-bold" style="margin-left:8px;"><?= e($order['number']) ?></span>
        <span class="badge badge-info" style="margin-left:6px;"><?= __('orders.type.' . $order['type']) ?></span>
        <?php if ($order['table_name']): ?><span class="badge badge-secondary"><?= e($order['table_name']) ?></span><?php endif; ?>
        <span class="badge badge-primary"><?= __('orders.status.' . $order['status']) ?></span>
    </div>
    <?php if (!$locked): ?>
    <form method="POST" action="<?= url('/orders/cancel/' . $order['id']) ?>" style="display:inline;">
        <?= csrf_field() ?>
        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(this.parentNode, '<?= __('common.confirm_delete') ?>')"><i class="fas fa-times"></i> <?= __('orders.cancel') ?></button>
    </form>
    <?php endif; ?>
</div>

<div class="pos-layout">
    <!-- Dish picker -->
    <div class="pos-products">
        <!-- The row wrapper matters: .toolbar-search carries flex:1, which grows it
             sideways here. Dropped straight into .pos-products (a flex column) it
             stretched to full height instead, stranding its absolutely-centred
             search icon in mid-air and pushing the grid to the bottom. -->
        <div style="margin-bottom:12px;display:flex;gap:8px;">
            <div class="toolbar-search" style="max-width:100%;">
                <span class="search-icon"><i class="fas fa-search"></i></span>
                <input type="text" id="dishSearch" class="form-control" placeholder="<?= __('pos.search') ?>" <?= $locked ? 'disabled' : 'autofocus' ?>>
            </div>
        </div>
        <div class="pos-grid" id="dishGrid">
            <?php foreach ($products as $p): ?>
            <div class="pos-product-card" data-id="<?= e($p['id']) ?>" data-name="<?= e($p['name']) ?>" data-cat="<?= e($p['category_id'] ?? '') ?>">
                <div class="name"><?= e($p['name']) ?></div>
                <div class="price"><?= formatMoney($p['selling_price']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Running ticket -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            <i class="fas fa-receipt" style="margin-right:8px;"></i><?= __('orders.items') ?>
            <span id="itemCount" style="margin-left:auto;background:var(--primary);color:#fff;padding:2px 10px;border-radius:12px;font-size:12px;"><?= count($items) ?></span>
        </div>

        <div class="pos-cart-items">
            <?php if (empty($items)): ?>
            <div class="empty-state" style="padding:40px 20px;">
                <div class="icon"><i class="fas fa-utensils"></i></div>
                <p><?= __('pos.cart_empty') ?></p>
            </div>
            <?php else: foreach ($items as $it): ?>
            <?php $isPaid = !empty($it['invoice_id']); ?>
            <div class="pos-cart-item <?= $isPaid ? 'is-settled' : '' ?>">
                <?php if (!$locked && !$isPaid): ?>
                <input type="checkbox" class="split-pick" value="<?= e($it['id']) ?>"
                       data-total="<?= (float)$it['line_total'] ?>" onchange="refreshSplit()"
                       title="<?= __('orders.split') ?>" style="width:18px;height:18px;flex-shrink:0;cursor:pointer;">
                <?php endif; ?>
                <div class="item-info">
                    <div class="item-name"><?= e($it['description']) ?></div>
                    <div class="item-price">
                        <?= formatMoney($it['unit_price']) ?> × <?= (int)$it['quantity'] ?>
                        <?php if ($isPaid): ?>
                        <span class="badge badge-success" style="font-size:9px;margin-left:4px;"><i class="fas fa-check"></i> <?= __('orders.paid_lines') ?></span>
                        <?php else: ?>
                        <span class="badge badge-<?= $itemClass[$it['status']] ?? 'secondary' ?>" style="font-size:9px;margin-left:4px;"><?= __('orders.status.' . ($it['status'] === 'pending' ? 'open' : $it['status'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($it['notes']): ?>
                    <div style="font-size:11px;color:var(--warning);"><i class="fas fa-comment"></i> <?= e($it['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="item-total"><?= formatMoney($it['line_total']) ?></div>
                <?php if (!$locked && !$isPaid && $it['status'] === 'pending'): ?>
                <form method="POST" action="<?= url('/orders/item/remove/' . $it['id']) ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button class="remove-btn" type="submit"><i class="fas fa-times"></i></button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="pos-cart-totals">
            <div class="pos-total-row"><span><?= __('invoices.subtotal') ?></span><span class="amount"><?= formatMoney($totals['subtotal']) ?></span></div>
            <div class="pos-total-row"><span><?= __('invoices.tax') ?></span><span class="amount"><?= formatMoney($totals['tax']) ?></span></div>
            <div class="pos-total-row grand-total"><span><?= __('invoices.total_ttc') ?></span><span class="amount"><?= formatMoney($totals['total']) ?></span></div>
            <?php if (!$locked && $due['total'] < $totals['total'] - 0.001): ?>
            <div class="pos-total-row" style="color:var(--danger);font-weight:700;">
                <span><?= __('orders.remaining') ?></span><span class="amount"><?= formatMoney($due['total']) ?></span>
            </div>
            <?php endif; ?>
            <div class="pos-total-row hidden" id="splitRow" style="color:var(--success);font-weight:700;">
                <span><?= __('orders.selection') ?></span><span class="amount" id="splitTotal">—</span>
            </div>
        </div>

        <?php if (!$locked): ?>
        <div style="padding:8px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:var(--text-secondary);">
                <input type="checkbox" id="autoKitchen"> <i class="fas fa-print"></i> <?= __('kticket.autoprint') ?>
            </label>
            <div style="display:flex;gap:6px;">
                <button class="btn btn-sm btn-secondary" onclick="selectAllSplit()"><i class="fas fa-check-double"></i> <?= __('orders.select_all') ?></button>
                <button class="btn btn-sm btn-secondary" onclick="openModal('mergeModal')" <?= empty($mergeable) ? 'disabled' : '' ?>><i class="fas fa-code-merge"></i> <?= __('orders.merge') ?></button>
                <button class="btn btn-sm btn-secondary" id="reprintKBtn" onclick="reprintKitchen()" disabled><i class="fas fa-receipt"></i> <?= __('kticket.reprint') ?></button>
            </div>
        </div>
        <div style="padding:0 16px 8px;font-size:11px;color:var(--text-muted);"><i class="fas fa-circle-info"></i> <?= __('orders.split_hint') ?></div>
        <div class="pos-actions" style="grid-template-columns:1fr 1fr;">
            <button class="btn btn-warning w-100" id="sendBtn" onclick="sendToKitchen()"><i class="fas fa-fire-burner"></i> <?= __('orders.send_kitchen') ?></button>
            <button class="btn btn-success btn-pay" id="payBtn" onclick="openModal('payOrderModal')" <?= $due['total'] <= 0 ? 'disabled' : '' ?>><i class="fas fa-money-bill-wave"></i> <?= __('orders.pay') ?></button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment -->
<div class="modal-overlay" id="payOrderModal">
    <div class="modal">
        <div class="modal-header"><h3><?= __('pos.payment') ?></h3><button class="modal-close" onclick="closeModal('payOrderModal')">&times;</button></div>
        <form method="POST" action="<?= url('/orders/pay/' . $order['id']) ?>" id="payForm">
            <?= csrf_field() ?>
            <!-- Filled from the ticked lines; empty = settle everything still owed. -->
            <div id="payItemIds"></div>
            <div class="modal-body">
                <div style="text-align:center;margin-bottom:20px;">
                    <div style="font-size:14px;color:var(--text-secondary);" id="payLabel"><?= __('pos.total_to_pay') ?></div>
                    <div style="font-size:36px;font-weight:800;font-family:var(--font-mono);color:var(--primary);" id="payTotalTxt"><?= formatMoney($due['total']) ?></div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('pos.payment_method') ?></label>
                    <select name="payment_method" class="form-control">
                        <option value="cash"><?= __('payments.method.cash') ?></option>
                        <option value="card"><?= __('payments.method.card') ?></option>
                        <option value="bankily"><?= __('payments.method.bankily') ?></option>
                        <option value="masrivi"><?= __('payments.method.masrivi') ?></option>
                        <option value="sedad"><?= __('payments.method.sedad') ?></option>
                        <option value="transfer"><?= __('payments.method.transfer') ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('pos.amount_received') ?></label>
                    <input type="number" name="amount_paid" id="payAmountInput" class="form-control" step="0.01" min="0" value="<?= number_format($due['total'], 2, '.', '') ?>" style="font-size:22px;font-weight:700;text-align:center;font-family:var(--font-mono);">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('payOrderModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> <?= __('pos.validate') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Merge into another open ticket -->
<div class="modal-overlay" id="mergeModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header"><h3><i class="fas fa-code-merge"></i> <?= __('orders.merge') ?></h3><button class="modal-close" onclick="closeModal('mergeModal')">&times;</button></div>
        <form method="POST" action="<?= url('/orders/merge/' . $order['id']) ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <p style="font-size:13px;color:var(--text-secondary);margin-bottom:12px;"><?= __('orders.merge_hint') ?></p>
                <div class="form-group">
                    <label class="form-label"><?= __('orders.merge_into') ?></label>
                    <select name="target_id" class="form-control" required>
                        <?php foreach ($mergeable as $m): ?>
                        <option value="<?= e($m['id']) ?>"><?= e($m['number']) ?><?= $m['table_name'] ? ' — ' . e($m['table_name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('mergeModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-code-merge"></i> <?= __('orders.merge') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Note prompt -->
<div class="modal-overlay" id="noteModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header"><h3 id="noteDish"></h3><button class="modal-close" onclick="closeModal('noteModal')">&times;</button></div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label"><?= __('common.quantity') ?: 'Quantite' ?></label>
                <input type="number" id="noteQty" class="form-control" value="1" min="1" style="text-align:center;font-weight:700;">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('orders.add_note') ?></label>
                <input type="text" id="noteText" class="form-control" placeholder="<?= __('orders.add_note') ?>">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('noteModal')"><?= __('common.cancel') ?></button>
            <button type="button" class="btn btn-primary" onclick="submitDish()"><i class="fas fa-plus"></i> <?= __('common.save') ?></button>
        </div>
    </div>
</div>

<!-- Kitchen ticket: hidden on screen, printed alone (see @media print) -->
<div id="kitchenTicket" aria-hidden="true"></div>

<script>
const ADD_URL = <?= json_encode(url('/orders/item/' . $order['id'])) ?>;
const SEND_URL = <?= json_encode(url('/orders/send/' . $order['id'])) ?>;
const CSRF = <?= json_encode(csrf_token()) ?>;
const LOCKED = <?= $locked ? 'true' : 'false' ?>;
const KT = {
    title: <?= json_encode(__('kticket.title')) ?>,
    waiter: <?= json_encode(__('kticket.waiter')) ?>,
    newItems: <?= json_encode(__('kticket.new_items')) ?>,
    table: <?= json_encode(__('orders.table')) ?>
};
const SHOP_NAME = <?= json_encode(Settings::get('company_name', 'GestionPro')) ?>;
let pendingDish = null;
let lastTicket = null;

// A dish is added through the note dialog: kitchen notes ("no onion") are the
// norm in service, so asking once beats editing the line afterwards.
document.querySelectorAll('.pos-product-card').forEach(card => {
    card.addEventListener('click', () => {
        if (LOCKED) return;
        pendingDish = card.dataset.id;
        document.getElementById('noteDish').textContent = card.dataset.name;
        document.getElementById('noteQty').value = 1;
        document.getElementById('noteText').value = '';
        openModal('noteModal');
    });
});

async function submitDish() {
    if (!pendingDish) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('product_id', pendingDish);
    fd.append('qty', document.getElementById('noteQty').value || 1);
    fd.append('notes', document.getElementById('noteText').value);
    try {
        const r = await fetch(ADD_URL, { method: 'POST', body: fd });
        const d = await r.json();
        if (d.error) { alert(d.error); return; }
        location.reload(); // server-rendered ticket stays the single source of truth
    } catch (e) { alert('Erreur: ' + e.message); }
}

document.getElementById('dishSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.pos-product-card').forEach(c => {
        c.style.display = (q === '' || c.dataset.name.toLowerCase().includes(q)) ? '' : 'none';
    });
});

// ============================================================
// Split bill: tick the dishes this bill covers. No selection means
// "settle everything still owed", so the common case stays one click.
// ============================================================
const DUE_TOTAL = <?= json_encode(round((float)$due['total'], 2)) ?>;
const L_PAY_ALL = <?= json_encode(__('pos.total_to_pay')) ?>;
const L_SELECTION = <?= json_encode(__('orders.selection')) ?>;

function pickedItems() {
    return [...document.querySelectorAll('.split-pick:checked')];
}

function refreshSplit() {
    const picked = pickedItems();
    const sum = picked.reduce((s, c) => s + parseFloat(c.dataset.total || 0), 0);
    const row = document.getElementById('splitRow');
    row.classList.toggle('hidden', picked.length === 0);
    document.getElementById('splitTotal').textContent = formatMoney(sum);

    // The pay dialog follows the selection: partial when some are ticked,
    // whole remaining balance when none are.
    const partial = picked.length > 0;
    document.getElementById('payLabel').textContent = partial ? L_SELECTION : L_PAY_ALL;
    document.getElementById('payTotalTxt').textContent = formatMoney(partial ? sum : DUE_TOTAL);
    document.getElementById('payAmountInput').value = (partial ? sum : DUE_TOTAL).toFixed(2);

    const box = document.getElementById('payItemIds');
    box.innerHTML = picked.map(c => `<input type="hidden" name="item_ids[]" value="${esc(c.value)}">`).join('');
}

function selectAllSplit() {
    const boxes = [...document.querySelectorAll('.split-pick')];
    const allOn = boxes.every(b => b.checked);
    boxes.forEach(b => { b.checked = !allOn; });
    refreshSplit();
}

// ============================================================
// Kitchen ticket — deliberately price-free: the kitchen needs
// dishes, quantities and notes, in type big enough to read on a pass.
// ============================================================
function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function buildKitchenTicket(t) {
    const lines = (t.items || []).map(i => `
        <li class="kt-line">
            <span class="kt-qty">${i.qty}×</span>
            <span class="kt-name">${esc(i.label)}</span>
            ${i.notes ? `<div class="kt-note">** ${esc(i.notes)} **</div>` : ''}
        </li>`).join('');
    return `
    <div class="kt-head">
        <div class="kt-shop">${esc(SHOP_NAME)}</div>
        <div class="kt-title">${esc(KT.title)}</div>
    </div>
    <div class="kt-meta">
        <div class="kt-num">${esc(t.number)}</div>
        <div>${esc(t.type)}${t.table ? ' — ' + KT.table + ' ' + esc(t.table) : ''}</div>
        <div>${esc(t.at)}${t.waiter ? ' — ' + KT.waiter + ': ' + esc(t.waiter) : ''}</div>
    </div>
    <div class="kt-sub">${esc(KT.newItems)}</div>
    <ul class="kt-items">${lines}</ul>`;
}

function printKitchenTicket(t) {
    const el = document.getElementById('kitchenTicket');
    el.innerHTML = buildKitchenTicket(t);
    if (el.parentElement !== document.body) document.body.appendChild(el);
    window.print();
}

function reprintKitchen() { if (lastTicket) printKitchenTicket(lastTicket); }

async function sendToKitchen() {
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    try {
        const r = await fetch(SEND_URL, { method: 'POST', body: fd });
        const d = await r.json();
        if (d.error) { alert(d.error); btn.disabled = false; return; }
        lastTicket = d.ticket;
        document.getElementById('reprintKBtn').disabled = false;
        if (document.getElementById('autoKitchen').checked) {
            printKitchenTicket(d.ticket);
        }
        // Reload once printing is dismissed so the ticket shows its new statuses.
        setTimeout(() => location.reload(), 300);
    } catch (e) {
        alert('Erreur: ' + e.message);
        btn.disabled = false;
    }
}

(function () {
    const cb = document.getElementById('autoKitchen');
    if (!cb) return;
    cb.checked = localStorage.getItem('kitchen_autoprint') === '1';
    cb.addEventListener('change', () => localStorage.setItem('kitchen_autoprint', cb.checked ? '1' : '0'));
})();
</script>
