<?php
/** @var array $cfg */
/** @var array $products */
/** @var string $productsError */
$mode      = $cfg['mode'] ?? 'sandbox';
$isLive    = $mode === 'live';
$mask = fn(string $v): string => $v === '' ? '' : str_repeat('*', max(6, strlen($v) - 4)) . substr($v, -4);

$adminUrlFn = static fn(string $p) => adminUrl($p);
?>

<div class="admin-header">
    <h1>Configuration Polar.sh</h1>
    <p style="color:var(--text-muted);margin-top:6px;">
        Mode actif : <strong style="color:<?= $isLive ? '#DC2626' : '#0EA5E9' ?>;"><?= strtoupper($mode) ?></strong>
        &nbsp;&middot;&nbsp; API : <code><?= $isLive ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh' ?></code>
    </p>
</div>

<!-- Mode + credentials form -->
<form method="POST" action="<?= $adminUrlFn('/polar/save') ?>" class="card" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">Mode</h3>
    <div style="display:flex;gap:16px;margin-bottom:24px;">
        <label style="display:flex;align-items:center;gap:8px;padding:10px 16px;border:2px solid <?= !$isLive ? '#0EA5E9' : 'var(--border)' ?>;border-radius:10px;cursor:pointer;">
            <input type="radio" name="mode" value="sandbox" <?= !$isLive ? 'checked' : '' ?>>
            <strong style="color:#0EA5E9;">SANDBOX</strong>
        </label>
        <label style="display:flex;align-items:center;gap:8px;padding:10px 16px;border:2px solid <?= $isLive ? '#DC2626' : 'var(--border)' ?>;border-radius:10px;cursor:pointer;">
            <input type="radio" name="mode" value="live" <?= $isLive ? 'checked' : '' ?>>
            <strong style="color:#DC2626;">LIVE</strong>
        </label>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <?php foreach (['sandbox' => 'Sandbox', 'live' => 'Live'] as $m => $label):
            $prefix = $m . '_';
            $accent = $m === 'live' ? '#DC2626' : '#0EA5E9';
        ?>
        <fieldset style="border:1px solid var(--border);border-radius:12px;padding:20px;">
            <legend style="padding:0 8px;color:<?= $accent ?>;font-weight:800;"><?= $label ?></legend>

            <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;">Access token</label>
            <input type="text" name="<?= $prefix ?>access_token"
                   placeholder="<?= $cfg[$prefix.'access_token'] ?? '' ? $mask($cfg[$prefix.'access_token']) : 'polar_oat_...' ?>"
                   class="input" style="width:100%;margin-bottom:12px;font-family:var(--font-mono);font-size:12px;">

            <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;">Webhook secret</label>
            <input type="text" name="<?= $prefix ?>webhook_secret"
                   placeholder="<?= $cfg[$prefix.'webhook_secret'] ?? '' ? $mask($cfg[$prefix.'webhook_secret']) : 'polar_whs_...' ?>"
                   class="input" style="width:100%;margin-bottom:12px;font-family:var(--font-mono);font-size:12px;">

            <?php foreach (['starter', 'pro', 'enterprise'] as $plan):
                $field = $prefix . $plan . '_product_id';
            ?>
            <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;"><?= ucfirst($plan) ?> product ID</label>
            <input type="text" name="<?= $field ?>" value="<?= e($cfg[$field] ?? '') ?>"
                   placeholder="uuid"
                   class="input" style="width:100%;margin-bottom:10px;font-family:var(--font-mono);font-size:12px;">
            <?php endforeach; ?>
        </fieldset>
        <?php endforeach; ?>
    </div>

    <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
        </button>
        <span style="color:var(--text-muted);font-size:12px;margin-left:12px;">
            Laissez un champ vide pour conserver la valeur existante (tokens & secrets).
        </span>
    </div>
</form>

<!-- Products -->
<div class="card" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3>Produits Polar (mode <?= strtoupper($mode) ?>)</h3>
    </div>

    <?php if ($productsError): ?>
    <div class="toast toast-error" style="position:relative;animation:none;margin-bottom:16px;">
        <i class="fas fa-circle-exclamation"></i> <?= e($productsError) ?>
    </div>
    <?php endif; ?>

    <!-- Create form -->
    <form method="POST" action="<?= $adminUrlFn('/polar/products/create') ?>" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end;margin-bottom:24px;padding:16px;background:var(--bg-subtle);border-radius:10px;">
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;">Nom</label>
            <input type="text" name="name" required placeholder="Starter" class="input" style="width:100%;">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;">Prix (USD)</label>
            <input type="number" name="price_usd" step="0.01" min="0.01" required placeholder="12.00" class="input" style="width:100%;">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;">Periode</label>
            <select name="interval" class="input" style="width:100%;">
                <option value="month">Mensuel</option>
                <option value="year">Annuel</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Creer</button>
    </form>

    <!-- Product list -->
    <?php if (!$products): ?>
    <p style="color:var(--text-muted);padding:20px;text-align:center;">Aucun produit. Creez-en un ci-dessus.</p>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);font-size:12px;color:var(--text-muted);text-align:left;">
                <th style="padding:10px;">Nom</th>
                <th style="padding:10px;">ID</th>
                <th style="padding:10px;">Prix</th>
                <th style="padding:10px;">Periode</th>
                <th style="padding:10px;">Etat</th>
                <th style="padding:10px;text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p):
                $price = $p['prices'][0] ?? null;
                $amount = $price ? ($price['price_amount'] / 100) : null;
                $archived = !empty($p['is_archived']);
            ?>
            <tr style="border-bottom:1px solid var(--border);<?= $archived ? 'opacity:0.5;' : '' ?>">
                <td style="padding:12px 10px;font-weight:600;"><?= e($p['name'] ?? '') ?></td>
                <td style="padding:12px 10px;font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= e($p['id'] ?? '') ?></td>
                <td style="padding:12px 10px;font-family:var(--font-mono);"><?= $amount !== null ? '$' . number_format($amount, 2) : '—' ?></td>
                <td style="padding:12px 10px;"><?= e($p['recurring_interval'] ?? '—') ?></td>
                <td style="padding:12px 10px;">
                    <?php if ($archived): ?>
                        <span style="background:#F1F5F9;color:#64748B;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;">Archive</span>
                    <?php else: ?>
                        <span style="background:#DCFCE7;color:#166534;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;">Actif</span>
                    <?php endif; ?>
                </td>
                <td style="padding:12px 10px;text-align:right;white-space:nowrap;">
                    <?php if (!$archived): ?>
                    <?php foreach (['starter', 'pro', 'enterprise'] as $plan): ?>
                    <form method="POST" action="<?= $adminUrlFn('/polar/products/assign') ?>" style="display:inline;">
                        <input type="hidden" name="plan" value="<?= $plan ?>">
                        <input type="hidden" name="mode" value="<?= $mode ?>">
                        <input type="hidden" name="product_id" value="<?= e($p['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-secondary" title="Utiliser comme <?= $plan ?>" style="padding:4px 8px;font-size:11px;">
                            &rarr; <?= strtoupper($plan[0]) ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                    <form method="POST" action="<?= $adminUrlFn('/polar/products/archive/' . $p['id']) ?>" style="display:inline;" onsubmit="return confirm('Archiver ce produit ?');">
                        <button type="submit" class="btn btn-sm" style="padding:4px 8px;font-size:11px;color:#DC2626;">
                            <i class="fas fa-archive"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Webhook info -->
<div class="card" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;">
    <h3 style="margin-bottom:12px;">Webhook</h3>
    <p style="color:var(--text-muted);margin-bottom:12px;">A configurer dans le dashboard Polar (<?= strtoupper($mode) ?>) :</p>
    <div style="background:var(--bg-subtle);padding:12px;border-radius:8px;font-family:var(--font-mono);font-size:13px;margin-bottom:8px;">
        <?= (defined('APP_URL') ? APP_URL : '') ?>/webhook/polar
    </div>
    <p style="font-size:12px;color:var(--text-muted);">
        Evenements : subscription.active, subscription.created, subscription.updated, subscription.canceled, subscription.revoked, order.paid
    </p>
</div>
