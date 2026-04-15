<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-plus-circle"></i> <?= __('tickets.create') ?: 'Nouveau ticket' ?></h1>
        <p class="page-subtitle">Decrivez votre probleme ou demande. Notre equipe vous repondra rapidement.</p>
    </div>
    <a href="<?= url('/tickets') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/tickets/store') ?>">
            <div class="form-group">
                <label class="form-label">Sujet *</label>
                <input type="text" name="subject" class="form-control" placeholder="Ex: Probleme avec la caisse POS" required maxlength="255">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Categorie</label>
                    <select name="category" class="form-control">
                        <option value="general">General</option>
                        <option value="bug">Bug / Probleme technique</option>
                        <option value="feature">Demande de fonctionnalite</option>
                        <option value="billing">Facturation / Abonnement</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Priorite</label>
                    <select name="priority" class="form-control">
                        <option value="low">Basse</option>
                        <option value="medium" selected>Moyenne</option>
                        <option value="high">Haute</option>
                        <option value="critical">Critique</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description detaillee *</label>
                <textarea name="message" class="form-control" rows="6" required
                    placeholder="Decrivez votre probleme en detail. Plus vous etes precis, plus vite nous pourrons vous aider.&#10;&#10;- Qu'essayez-vous de faire ?&#10;- Que se passe-t-il exactement ?&#10;- Quand cela a commence ?"></textarea>
            </div>

            <div style="padding:14px 16px;background:var(--bg-subtle);border-radius:var(--radius);margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
                <i class="fas fa-info-circle" style="color:var(--primary);margin-top:2px;"></i>
                <div style="font-size:13px;color:var(--text-secondary);line-height:1.6;">
                    Votre ticket sera envoye a notre equipe de support. Vous recevrez une reponse directement ici.
                    Vous pouvez suivre l'etat de vos tickets depuis la page <strong>Support</strong>.
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Envoyer le ticket
                </button>
                <a href="<?= url('/tickets') ?>" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
