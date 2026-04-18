<?php
/** @var array $cfg */
/** @var array $products */
/** @var string $productsError */
$mode   = $cfg['mode'] ?? 'sandbox';
$isLive = $mode === 'live';
$mask   = fn(string $v): string => $v === '' ? '' : str_repeat('*', max(6, strlen($v) - 4)) . substr($v, -4);

$activeProducts = array_filter($products, fn($p) => empty($p['is_archived']));
$starterPrice   = null; $proPrice = null;
foreach ($products as $p) {
    $id = $p['id'] ?? '';
    $amt = isset($p['prices'][0]['price_amount']) ? $p['prices'][0]['price_amount'] / 100 : null;
    if ($id === ($cfg[$mode.'_starter_product_id'] ?? '')) $starterPrice = $amt;
    if ($id === ($cfg[$mode.'_pro_product_id']     ?? '')) $proPrice     = $amt;
}
?>

<style>
.polar-hero {
    background: linear-gradient(135deg, <?= $isLive ? '#991B1B 0%, #DC2626' : '#0369A1 0%, #0EA5E9' ?> 100%);
    border-radius: 16px; padding: 28px 32px; color: #fff; margin-bottom: 24px;
    display: flex; justify-content: space-between; align-items: center; gap: 24px;
    box-shadow: 0 10px 30px -10px <?= $isLive ? 'rgba(220,38,38,0.4)' : 'rgba(14,165,233,0.35)' ?>;
}
.polar-hero h1 { font-size: 22px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 12px; }
.polar-hero p  { margin: 6px 0 0; font-size: 13px; opacity: 0.85; }
.polar-hero-badge {
    background: rgba(255,255,255,0.2); backdrop-filter: blur(8px);
    padding: 8px 16px; border-radius: 100px; font-weight: 800; font-size: 13px;
    display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(255,255,255,0.3);
}
.polar-hero-badge .dot { width: 8px; height: 8px; border-radius: 100px; background: #fff; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

.mode-switch { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.mode-option { cursor: pointer; position: relative; }
.mode-option input { position: absolute; opacity: 0; }
.mode-option-inner {
    border: 2px solid var(--border); border-radius: 12px; padding: 18px 20px;
    display: flex; align-items: center; gap: 16px; transition: var(--transition);
    background: var(--surface);
}
.mode-option-inner .icon {
    width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center;
    justify-content: center; font-size: 18px; flex-shrink: 0;
}
.mode-option-inner h4 { margin: 0 0 2px; font-size: 15px; font-weight: 700; }
.mode-option-inner p  { margin: 0; font-size: 12px; color: var(--text-muted); }
.mode-option input[value="sandbox"]:checked ~ .mode-option-inner,
.mode-option-sandbox:has(input:checked) .mode-option-inner { border-color: #0EA5E9; background: rgba(14,165,233,0.05); }
.mode-option-live:has(input:checked) .mode-option-inner { border-color: #DC2626; background: rgba(220,38,38,0.05); }
.mode-option-sandbox .icon { background: rgba(14,165,233,0.12); color: #0EA5E9; }
.mode-option-live    .icon { background: rgba(220,38,38,0.12); color: #DC2626; }

.polar-tabs {
    display: flex; gap: 4px; padding: 4px; background: var(--bg-subtle);
    border-radius: 10px; width: fit-content; margin-bottom: 20px;
}
.polar-tab {
    padding: 8px 18px; border: 0; background: transparent; border-radius: 8px;
    font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px; transition: var(--transition);
}
.polar-tab.active { background: var(--surface); color: var(--text); box-shadow: var(--shadow-sm); }
.polar-tab .dot { width: 6px; height: 6px; border-radius: 100px; }
.polar-tab[data-env="sandbox"] .dot { background: #0EA5E9; }
.polar-tab[data-env="live"]    .dot { background: #DC2626; }
.polar-pane { display: none; }
.polar-pane.active { display: block; }

.product-row { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-bottom: 1px solid var(--border); }
.product-row:last-child { border-bottom: 0; }
.product-row:hover { background: var(--bg-subtle); }
.product-row.archived { opacity: 0.5; }
.product-thumb {
    width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #6366F1, #8B5CF6);
    color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px;
    flex-shrink: 0;
}
.product-name  { font-weight: 700; font-size: 14px; }
.product-id    { font-family: var(--font-mono); font-size: 10px; color: var(--text-muted); margin-top: 2px; }
.product-price { font-family: var(--font-mono); font-weight: 800; font-size: 16px; color: var(--text); }
.product-cycle { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.4px; }

.assign-pills { display: inline-flex; gap: 4px; }
.assign-pill { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid var(--border);
    background: var(--surface); color: var(--text-muted); cursor: pointer; }
.assign-pill:hover { border-color: var(--primary); color: var(--primary); }
.assign-pill.active { background: var(--primary); border-color: var(--primary); color: #fff; }

.stat-grid .stat-card { border-radius: 14px; }
.copy-input {
    font-family: var(--font-mono); font-size: 13px; background: var(--bg-subtle);
    padding: 12px 14px; border-radius: 8px; border: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.copy-input span { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.muted-hint { font-size: 12px; color: var(--text-muted); }
</style>

<!-- Hero -->
<div class="polar-hero">
    <div>
        <h1><i class="fas fa-credit-card"></i> Polar.sh</h1>
        <p>Configuration des paiements par carte &middot; <?= $isLive ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh' ?></p>
    </div>
    <div class="polar-hero-badge">
        <span class="dot"></span> <?= strtoupper($mode) ?>
    </div>
</div>

<!-- KPIs -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:<?= $isLive ? 'rgba(220,38,38,0.1);color:#DC2626' : 'rgba(14,165,233,0.1);color:#0EA5E9' ?>;">
            <i class="fas fa-<?= $isLive ? 'bolt' : 'flask' ?>"></i>
        </div>
        <div>
            <div class="stat-value"><?= strtoupper($mode) ?></div>
            <div class="stat-label">Mode actif</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(99,102,241,0.1);color:#6366F1;"><i class="fas fa-box"></i></div>
        <div>
            <div class="stat-value"><?= count($activeProducts) ?></div>
            <div class="stat-label">Produits actifs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(16,185,129,0.1);color:#10B981;"><i class="fas fa-tag"></i></div>
        <div>
            <div class="stat-value"><?= $starterPrice !== null ? '$' . number_format($starterPrice, 0) : '—' ?></div>
            <div class="stat-label">Plan Starter</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:#F59E0B;"><i class="fas fa-crown"></i></div>
        <div>
            <div class="stat-value"><?= $proPrice !== null ? '$' . number_format($proPrice, 0) : '—' ?></div>
            <div class="stat-label">Plan Pro</div>
        </div>
    </div>
</div>

<!-- All-in-one form: mode + credentials -->
<form method="POST" action="<?= adminUrl('/polar/save') ?>">
    <!-- Mode switch -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3><i class="fas fa-toggle-on" style="color:var(--primary);margin-right:8px;"></i> Mode d'execution</h3>
        </div>
        <div class="card-body">
            <div class="mode-switch">
                <label class="mode-option mode-option-sandbox">
                    <input type="radio" name="mode" value="sandbox" <?= !$isLive ? 'checked' : '' ?>>
                    <div class="mode-option-inner">
                        <div class="icon"><i class="fas fa-flask"></i></div>
                        <div>
                            <h4>Sandbox</h4>
                            <p>Tests et developpement &middot; pas de vrais paiements</p>
                        </div>
                    </div>
                </label>
                <label class="mode-option mode-option-live">
                    <input type="radio" name="mode" value="live" <?= $isLive ? 'checked' : '' ?>>
                    <div class="mode-option-inner">
                        <div class="icon"><i class="fas fa-bolt"></i></div>
                        <div>
                            <h4>Live</h4>
                            <p>Production &middot; paiements reels par carte</p>
                        </div>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <!-- Credentials tabs -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3><i class="fas fa-key" style="color:var(--primary);margin-right:8px;"></i> Identifiants</h3>
        </div>
        <div class="card-body">
            <div class="polar-tabs" id="polar-tabs">
                <button type="button" class="polar-tab active" data-env="sandbox"><span class="dot"></span> Sandbox</button>
                <button type="button" class="polar-tab" data-env="live"><span class="dot"></span> Live</button>
            </div>

            <?php foreach (['sandbox' => 'Sandbox', 'live' => 'Live'] as $m => $label):
                $prefix = $m . '_';
            ?>
            <div class="polar-pane <?= $m === 'sandbox' ? 'active' : '' ?>" data-env="<?= $m ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Access token</label>
                        <input type="text" name="<?= $prefix ?>access_token" class="form-control text-mono"
                               placeholder="<?= $cfg[$prefix.'access_token'] ?? '' ? $mask($cfg[$prefix.'access_token']) : 'polar_oat_...' ?>">
                        <div class="form-hint">Laissez vide pour conserver la valeur actuelle.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Webhook secret</label>
                        <input type="text" name="<?= $prefix ?>webhook_secret" class="form-control text-mono"
                               placeholder="<?= $cfg[$prefix.'webhook_secret'] ?? '' ? $mask($cfg[$prefix.'webhook_secret']) : 'polar_whs_...' ?>">
                        <div class="form-hint">Laissez vide pour conserver la valeur actuelle.</div>
                    </div>
                </div>
                <div class="form-row">
                    <?php foreach (['starter' => 'Starter', 'pro' => 'Pro', 'enterprise' => 'Enterprise'] as $plan => $planLabel):
                        $field = $prefix . $plan . '_product_id';
                    ?>
                    <div class="form-group">
                        <label class="form-label"><?= $planLabel ?> product ID</label>
                        <input type="text" name="<?= $field ?>" class="form-control text-mono" value="<?= e($cfg[$field] ?? '') ?>" placeholder="uuid">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer" style="display:flex;justify-content:flex-end;gap:12px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer la configuration
            </button>
        </div>
    </div>
</form>

<!-- Products -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3>
            <i class="fas fa-box" style="color:#6366F1;margin-right:8px;"></i> Produits Polar
            <span class="badge badge-secondary" style="margin-left:8px;font-weight:600;"><?= count($activeProducts) ?> actif(s)</span>
        </h3>
        <span class="muted-hint">Mode <?= strtoupper($mode) ?></span>
    </div>

    <?php if ($productsError): ?>
    <div style="padding:14px 20px;background:rgba(220,38,38,0.08);color:#991B1B;border-bottom:1px solid var(--border);">
        <i class="fas fa-circle-exclamation"></i> <?= e($productsError) ?>
    </div>
    <?php endif; ?>

    <!-- Create form -->
    <div style="padding:20px;border-bottom:1px solid var(--border);background:var(--bg-subtle);">
        <form method="POST" action="<?= adminUrl('/polar/products/create') ?>" class="form-inline" style="gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="flex:2;min-width:200px;margin:0;">
                <label class="form-label">Nom</label>
                <input type="text" name="name" required placeholder="Starter" class="form-control">
            </div>
            <div class="form-group" style="flex:1;min-width:120px;margin:0;">
                <label class="form-label">Prix (USD)</label>
                <input type="number" name="price_usd" step="0.01" min="0.01" required placeholder="12.00" class="form-control text-mono">
            </div>
            <div class="form-group" style="flex:1;min-width:140px;margin:0;">
                <label class="form-label">Periode</label>
                <select name="interval" class="form-control">
                    <option value="month">Mensuel</option>
                    <option value="year">Annuel</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Creer produit</button>
        </form>
    </div>

    <!-- Product list -->
    <div class="card-body" style="padding:0;">
        <?php if (!$products): ?>
        <div class="empty-state" style="padding:40px 20px;text-align:center;">
            <div style="font-size:44px;color:var(--text-muted);margin-bottom:12px;"><i class="fas fa-box-open"></i></div>
            <p style="font-weight:600;margin-bottom:4px;">Aucun produit Polar</p>
            <p class="muted-hint">Creez votre premier produit avec le formulaire ci-dessus.</p>
        </div>
        <?php else: ?>
        <?php foreach ($products as $p):
            $pid      = $p['id'] ?? '';
            $price    = $p['prices'][0] ?? null;
            $amount   = $price ? ($price['price_amount'] / 100) : null;
            $archived = !empty($p['is_archived']);
            $initial  = strtoupper(substr($p['name'] ?? '?', 0, 1));
            $interval = $p['recurring_interval'] ?? '';
            $assignedAs = null;
            foreach (['starter', 'pro', 'enterprise'] as $plan) {
                if ($pid === ($cfg[$mode.'_'.$plan.'_product_id'] ?? '')) { $assignedAs = $plan; break; }
            }
        ?>
        <div class="product-row <?= $archived ? 'archived' : '' ?>">
            <div class="product-thumb"><?= e($initial) ?></div>
            <div style="flex:1;min-width:0;">
                <div class="product-name">
                    <?= e($p['name'] ?? '') ?>
                    <?php if ($assignedAs): ?>
                    <span class="badge badge-primary" style="margin-left:6px;font-size:10px;"><?= strtoupper($assignedAs) ?></span>
                    <?php endif; ?>
                    <?php if ($archived): ?>
                    <span class="badge badge-secondary" style="margin-left:6px;font-size:10px;">Archive</span>
                    <?php endif; ?>
                </div>
                <div class="product-id"><?= e($pid) ?></div>
            </div>
            <div style="text-align:right;min-width:90px;">
                <div class="product-price"><?= $amount !== null ? '$' . number_format($amount, 2) : '—' ?></div>
                <div class="product-cycle"><?= e($interval) ?></div>
            </div>
            <?php if (!$archived): ?>
            <div class="assign-pills">
                <?php foreach (['starter' => 'S', 'pro' => 'P', 'enterprise' => 'E'] as $plan => $letter): ?>
                <form method="POST" action="<?= adminUrl('/polar/products/assign') ?>" style="margin:0;">
                    <input type="hidden" name="plan" value="<?= $plan ?>">
                    <input type="hidden" name="mode" value="<?= $mode ?>">
                    <input type="hidden" name="product_id" value="<?= e($pid) ?>">
                    <button type="submit" class="assign-pill <?= $assignedAs === $plan ? 'active' : '' ?>" title="Assigner comme <?= $plan ?>">
                        <?= $letter ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
            <form method="POST" action="<?= adminUrl('/polar/products/archive/' . $pid) ?>" onsubmit="return confirm('Archiver ce produit ?');" style="margin:0;">
                <button type="submit" class="btn btn-sm btn-ghost" style="color:#DC2626;" title="Archiver">
                    <i class="fas fa-archive"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Webhook -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-bell" style="color:#F59E0B;margin-right:8px;"></i> Webhook</h3>
    </div>
    <div class="card-body">
        <p class="muted-hint" style="margin-bottom:10px;">Endpoint a configurer dans le dashboard Polar (<?= strtoupper($mode) ?>) :</p>
        <div class="copy-input">
            <span id="wh-url"><?= (defined('APP_URL') && APP_URL ? APP_URL : 'https://' . ($_SERVER['HTTP_HOST'] ?? 'your-domain')) ?>/webhook/polar</span>
            <button type="button" class="btn btn-sm btn-ghost" onclick="navigator.clipboard.writeText(document.getElementById('wh-url').innerText)">
                <i class="fas fa-copy"></i>
            </button>
        </div>
        <p class="muted-hint" style="margin-top:14px;">Evenements a activer :</p>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
            <?php foreach (['subscription.active', 'subscription.created', 'subscription.updated', 'subscription.canceled', 'subscription.revoked', 'order.paid'] as $ev): ?>
            <span class="badge badge-secondary text-mono" style="font-size:11px;"><?= $ev ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('#polar-tabs .polar-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const env = tab.dataset.env;
        document.querySelectorAll('#polar-tabs .polar-tab').forEach(t => t.classList.toggle('active', t === tab));
        document.querySelectorAll('.polar-pane').forEach(p => p.classList.toggle('active', p.dataset.env === env));
    });
});
</script>
