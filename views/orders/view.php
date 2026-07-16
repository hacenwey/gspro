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
        <div class="toolbar-search" style="margin-bottom:12px;max-width:100%;">
            <span class="search-icon"><i class="fas fa-search"></i></span>
            <input type="text" id="dishSearch" class="form-control" placeholder="<?= __('pos.search') ?>" <?= $locked ? 'disabled' : 'autofocus' ?>>
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
            <div class="pos-cart-item">
                <div class="item-info">
                    <div class="item-name"><?= e($it['description']) ?></div>
                    <div class="item-price">
                        <?= formatMoney($it['unit_price']) ?> × <?= (int)$it['quantity'] ?>
                        <span class="badge badge-<?= $itemClass[$it['status']] ?? 'secondary' ?>" style="font-size:9px;margin-left:4px;"><?= __('orders.status.' . ($it['status'] === 'pending' ? 'open' : $it['status'])) ?></span>
                    </div>
                    <?php if ($it['notes']): ?>
                    <div style="font-size:11px;color:var(--warning);"><i class="fas fa-comment"></i> <?= e($it['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="item-total"><?= formatMoney($it['line_total']) ?></div>
                <?php if (!$locked && $it['status'] === 'pending'): ?>
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
        </div>

        <?php if (!$locked): ?>
        <div class="pos-actions" style="grid-template-columns:1fr 1fr;">
            <form method="POST" action="<?= url('/orders/send/' . $order['id']) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-warning w-100" type="submit"><i class="fas fa-fire-burner"></i> <?= __('orders.send_kitchen') ?></button>
            </form>
            <button class="btn btn-success btn-pay" onclick="openModal('payOrderModal')" <?= empty($items) ? 'disabled' : '' ?>><i class="fas fa-money-bill-wave"></i> <?= __('orders.pay') ?></button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment -->
<div class="modal-overlay" id="payOrderModal">
    <div class="modal">
        <div class="modal-header"><h3><?= __('pos.payment') ?></h3><button class="modal-close" onclick="closeModal('payOrderModal')">&times;</button></div>
        <form method="POST" action="<?= url('/orders/pay/' . $order['id']) ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div style="text-align:center;margin-bottom:20px;">
                    <div style="font-size:14px;color:var(--text-secondary);"><?= __('pos.total_to_pay') ?></div>
                    <div style="font-size:36px;font-weight:800;font-family:var(--font-mono);color:var(--primary);"><?= formatMoney($totals['total']) ?></div>
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
                    <input type="number" name="amount_paid" class="form-control" step="0.01" min="0" value="<?= number_format($totals['total'], 2, '.', '') ?>" style="font-size:22px;font-weight:700;text-align:center;font-family:var(--font-mono);">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('payOrderModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> <?= __('pos.validate') ?></button>
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

<script>
const ADD_URL = <?= json_encode(url('/orders/item/' . $order['id'])) ?>;
const CSRF = <?= json_encode(csrf_token()) ?>;
const LOCKED = <?= $locked ? 'true' : 'false' ?>;
let pendingDish = null;

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
</script>
