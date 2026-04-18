<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-file-invoice" style="color:var(--primary);"></i> <?= $selectedType === 'quote' ? __('invoices.new_quote_title') : __('invoices.new_invoice_title') ?></h3>
        <a href="<?= url('/invoices') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('/invoices/store') ?>" id="invoiceForm">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="<?= $selectedType ?>">
            <input type="hidden" name="items_json" id="itemsJson" value="[]">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('invoices.client') ?> *</label>
                    <select name="customer_id" class="form-control" required>
                        <option value=""><?= __('invoices.choose_client') ?></option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selectedCustomer === $c['id'] ? 'selected' : '' ?>><?= e(($c['first_name'] ?? '') . ' ' . $c['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('invoices.issue_date') ?></label>
                    <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= $selectedType === 'quote' ? __('invoices.validity_date') : __('invoices.due_date') ?></label>
                    <input type="date" name="<?= $selectedType === 'quote' ? 'validity_date' : 'due_date' ?>" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
            </div>

            <!-- Items -->
            <div class="card mt-2" style="border: 2px dashed var(--border);">
                <div class="card-header">
                    <h3><?= __('invoices.lines') ?></h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="table" id="itemsTable">
                        <thead><tr><th>Produit</th><th style="width:80px">Qte</th><th style="width:120px">P.U. HT</th><th style="width:80px">TVA %</th><th style="width:80px">Remise %</th><th style="width:120px" class="text-right">Total TTC</th><th style="width:40px"></th></tr></thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                    <div style="padding: 12px 16px;">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addLine()"><i class="fas fa-plus"></i> <?= __('invoices.add_line') ?></button>
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div style="max-width: 350px; margin-left: auto; margin-top: 20px;">
                <div class="pos-total-row"><span><?= __('invoices.subtotal') ?></span><span class="text-mono fw-bold" id="invSubtotal">0,00 <?= CURRENCY ?></span></div>
                <div class="pos-total-row"><span><?= __('invoices.tax') ?></span><span class="text-mono fw-bold" id="invTax">0,00 <?= CURRENCY ?></span></div>
                <div class="form-group" style="margin-top:8px;">
                    <label class="form-label">Remise globale (<?= CURRENCY ?>)</label>
                    <input type="number" name="discount_amount" class="form-control" step="0.01" min="0" value="0" onchange="calcTotals()">
                </div>
                <div class="pos-total-row grand-total"><span><?= __('invoices.total_ttc') ?></span><span class="text-mono" id="invTotal">0,00 <?= CURRENCY ?></span></div>
            </div>

            <div class="form-group mt-2">
                <label class="form-label"><?= __('invoices.notes') ?></label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>

            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= __('common.save') ?></button>
                <a href="<?= url('/invoices') ?>" class="btn btn-secondary"><?= __('common.cancel') ?></a>
            </div>
        </form>
    </div>
</div>

<script>
const products = <?= json_encode($products) ?>;
let lineCounter = 0;

function addLine(productId = '', qty = 1) {
    lineCounter++;
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.id = 'line-' + lineCounter;
    const n = lineCounter;

    let options = '<option value="">-- Choisir --</option>';
    products.forEach(p => {
        options += `<option value="${p.id}" data-price="${p.selling_price}" data-tax="${p.tax_rate}" data-name="${p.name}" ${p.id === productId ? 'selected' : ''}>${p.reference} - ${p.name}</option>`;
    });

    tr.innerHTML = `
        <td><select class="form-control" onchange="onProductChange(${n}, this)" style="font-size:12px;">${options}</select></td>
        <td><input type="number" class="form-control" id="qty-${n}" value="${qty}" min="0.001" step="1" onchange="calcTotals()" style="text-align:center;"></td>
        <td><input type="number" class="form-control text-mono" id="price-${n}" value="0" step="0.01" min="0" onchange="calcTotals()"></td>
        <td><input type="number" class="form-control" id="tax-${n}" value="<?= TAX_RATE_DEFAULT ?>" step="0.01" min="0" onchange="calcTotals()" style="text-align:center;"></td>
        <td><input type="number" class="form-control" id="disc-${n}" value="0" step="0.01" min="0" max="100" onchange="calcTotals()" style="text-align:center;"></td>
        <td class="text-right text-mono fw-bold" id="ltotal-${n}">0,00</td>
        <td><button type="button" class="btn btn-icon btn-sm btn-danger" onclick="removeLine(${n})"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(tr);
    if (productId) { tr.querySelector('select').dispatchEvent(new Event('change')); }
    calcTotals();
}

function onProductChange(n, select) {
    const opt = select.options[select.selectedIndex];
    if (opt.value) {
        document.getElementById('price-' + n).value = parseFloat(opt.dataset.price).toFixed(2);
        document.getElementById('tax-' + n).value = parseFloat(opt.dataset.tax).toFixed(2);
    }
    calcTotals();
}

function removeLine(n) {
    const el = document.getElementById('line-' + n);
    if (el) el.remove();
    calcTotals();
}

function calcTotals() {
    let subtotal = 0, tax = 0;
    const items = [];
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const select = tr.querySelector('select');
        const n = tr.id.split('-')[1];
        const qty = parseFloat(document.getElementById('qty-' + n)?.value || 0);
        const price = parseFloat(document.getElementById('price-' + n)?.value || 0);
        const taxRate = parseFloat(document.getElementById('tax-' + n)?.value || 0);
        const disc = parseFloat(document.getElementById('disc-' + n)?.value || 0);

        const lineSubtotal = price * qty * (1 - disc / 100);
        const lineTax = lineSubtotal * (taxRate / 100);
        const lineTotal = lineSubtotal + lineTax;

        subtotal += lineSubtotal;
        tax += lineTax;

        const el = document.getElementById('ltotal-' + n);
        if (el) el.textContent = formatM(lineTotal);

        if (select && select.value) {
            const opt = select.options[select.selectedIndex];
            items.push({
                product_id: select.value,
                description: opt.dataset.name || opt.text,
                quantity: qty,
                unit_price: price,
                tax_rate: taxRate,
                discount_pct: disc,
                line_total: lineTotal
            });
        }
    });

    const discount = parseFloat(document.querySelector('[name="discount_amount"]')?.value || 0);
    const total = subtotal + tax - discount;

    document.getElementById('invSubtotal').textContent = formatM(subtotal);
    document.getElementById('invTax').textContent = formatM(tax);
    document.getElementById('invTotal').textContent = formatM(total);
    document.getElementById('itemsJson').value = JSON.stringify(items);
}

function formatM(v) {
    const symbol = (typeof APP_CURRENCY_SYMBOL !== 'undefined') ? APP_CURRENCY_SYMBOL : '$';
    const locale = (typeof APP_LANG !== 'undefined') ? APP_LANG : 'en';
    return symbol + new Intl.NumberFormat(locale, {minimumFractionDigits:2}).format(v);
}

addLine();
</script>
