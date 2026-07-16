<?php /** Kitchen pass. Polls the feed; big touch targets, readable across a kitchen. */ ?>
<div class="toolbar">
    <div><h2 style="font-size:20px;font-weight:800;"><i class="fas fa-fire-burner" style="color:var(--warning);"></i> <?= __('kitchen.title') ?></h2></div>
    <div style="font-size:12px;color:var(--text-muted);"><i class="fas fa-rotate" id="kSpin"></i> <?= __('kitchen.updated') ?> <span id="kAt">—</span></div>
</div>

<div id="kitchenBoard" class="kitchen-board"></div>

<div id="kitchenEmpty" class="empty-state hidden">
    <div class="icon"><i class="fas fa-utensils"></i></div>
    <h4><?= __('kitchen.none') ?></h4>
    <p><?= __('kitchen.none_hint') ?></p>
</div>

<script>
const FEED_URL = <?= json_encode(url('/kitchen/feed')) ?>;
const ITEM_URL = <?= json_encode(url('/kitchen/item/')) ?>;
const CSRF = <?= json_encode(csrf_token()) ?>;
const K = {
    start: <?= json_encode(__('kitchen.start')) ?>,
    ready: <?= json_encode(__('kitchen.ready')) ?>,
    waiting: <?= json_encode(__('kitchen.waiting')) ?>,
    table: <?= json_encode(__('orders.table')) ?>,
    types: {
        dine_in: <?= json_encode(__('orders.type.dine_in')) ?>,
        takeaway: <?= json_encode(__('orders.type.takeaway')) ?>,
        delivery: <?= json_encode(__('orders.type.delivery')) ?>
    }
};

function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function render(orders) {
    const board = document.getElementById('kitchenBoard');
    document.getElementById('kitchenEmpty').classList.toggle('hidden', orders.length > 0);
    board.innerHTML = orders.map(o => {
        // Past 15 minutes a ticket is late — make it impossible to miss.
        const late = o.waiting_min >= 15 ? ' late' : (o.waiting_min >= 8 ? ' warn' : '');
        const items = o.items.map(it => `
            <li class="k-item ${it.status}">
                <div class="k-qty">${it.qty}×</div>
                <div class="k-label">
                    ${esc(it.label)}
                    ${it.notes ? `<div class="k-note"><i class="fas fa-comment"></i> ${esc(it.notes)}</div>` : ''}
                </div>
                <div class="k-act">
                    ${it.status !== 'preparing' && it.status !== 'ready'
                        ? `<button class="btn btn-sm btn-warning" onclick="setStatus('${it.id}','preparing')">${K.start}</button>` : ''}
                    ${it.status !== 'ready'
                        ? `<button class="btn btn-sm btn-success" onclick="setStatus('${it.id}','ready')">${K.ready}</button>`
                        : `<span class="badge badge-success"><i class="fas fa-check"></i> ${K.ready}</span>`}
                </div>
            </li>`).join('');
        return `<div class="k-card${late}">
            <div class="k-head">
                <div>
                    <span class="k-num">${esc(o.number)}</span>
                    <span class="k-type">${K.types[o.type] || o.type}</span>
                    ${o.table_name ? `<span class="k-table">${K.table} ${esc(o.table_name)}</span>` : ''}
                </div>
                <div class="k-time">${o.waiting_min} ${K.waiting}</div>
            </div>
            <ul class="k-items">${items}</ul>
        </div>`;
    }).join('');
}

async function setStatus(itemId, status) {
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('status', status);
    try {
        const r = await fetch(ITEM_URL + itemId, { method: 'POST', body: fd });
        const d = await r.json();
        if (d.error) { alert(d.error); return; }
        refresh();
    } catch (e) { /* offline: the next poll will catch up */ }
}

async function refresh() {
    const spin = document.getElementById('kSpin');
    spin.classList.add('fa-spin');
    try {
        const r = await fetch(FEED_URL, { cache: 'no-store' });
        const d = await r.json();
        render(d.orders || []);
        document.getElementById('kAt').textContent = d.at || '';
    } catch (e) {
        // Weak connection: keep the last board on screen rather than blanking it.
    } finally {
        spin.classList.remove('fa-spin');
    }
}

refresh();
setInterval(refresh, 8000);
</script>
