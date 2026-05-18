<?php
// views/clients/index.php — Variables injectées par ClientController
include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>
<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>
<div class="page-content">

  <div class="page-header">
    <div>
      <h1 class="page-title">Clients</h1>
      <p class="page-subtitle"><?= $total_clients ?> client(s) enregistré(s)</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalClient">
      <i class="fas fa-plus"></i> Ajouter
    </button>
  </div>

  <?php render_flash(); ?>

  <!-- VIP Alert -->
  <?php if (!empty($top_clients)): ?>
  <div class="card mb-4 fade-up" style="border:2px solid #F39C12;">
    <div class="card-header" style="background:linear-gradient(135deg,#FFD700,#F39C12);border:none;">
      <div class="card-title" style="color:#7A5800;"><i class="fas fa-crown"></i> Clients VIP — +<?= CLIENT_TOP_SEUIL ?> pièces ce mois</div>
      <span class="badge" style="background:#7A5800;color:#FFD700;"><?= count($top_clients) ?> client(s)</span>
    </div>
    <div class="table-container">
      <table class="table">
        <thead><tr><th>#</th><th>Client</th><th>Téléphone</th><th>Secteur</th><th>Pièces</th><th>CA</th></tr></thead>
        <tbody>
          <?php foreach ($top_clients as $i => $c): ?>
          <tr>
            <td><div class="rank-badge <?= $i<3?'rank-'.($i+1):'rank-n' ?>"><?= $i<3?['🥇','🥈','🥉'][$i]:($i+1) ?></div></td>
            <td><strong><?= e($c['nom']) ?></strong></td>
            <td><?= e($c['telephone']??'—') ?></td>
            <td><?= e($c['secteur']??'—') ?></td>
            <td><span style="font-size:1.1rem;font-weight:800;color:var(--accent);"><?= number_format($c['total_pieces']) ?></span> pcs</td>
            <td><strong><?= format_money($c['total_ca']) ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filtres -->
  <div class="card mb-4 fade-up">
    <div class="card-body" style="padding:16px 20px;">
      <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="clients">
        <div class="col-md-4">
          <label class="form-label">Recherche</label>
          <input type="text" name="search" class="form-control" placeholder="Nom, prénom, tél…" value="<?= e($f['search']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Secteur</label>
          <select name="secteur_id" class="form-control form-select">
            <option value="">Tous</option>
            <?php foreach ($secteurs as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $f['secteur_id']==$s['id']?'selected':'' ?>><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Ville</label>
          <select name="ville_id" class="form-control form-select">
            <option value="">Toutes</option>
            <?php foreach ($villes as $vl): ?>
            <option value="<?= $vl['id'] ?>" <?= $f['ville_id']==$vl['id']?'selected':'' ?>><?= e($vl['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-search"></i></button>
          <a href="?page=clients" class="btn btn-outline"><i class="fas fa-times"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Liste -->
  <div class="card fade-up">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-users"></i> Liste des clients</div>
    </div>
    <div class="table-container">
      <table class="table">
        <thead><tr><th>Client</th><th>Téléphone</th><th>Ville</th><th>Secteur</th><th>Depuis</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($clients)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">Aucun client trouvé</td></tr>
          <?php else: foreach ($clients as $c): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar"><?= strtoupper(substr($c['nom'],0,1)) ?></div>
                <strong><?= e($c['prenom'].' '.$c['nom']) ?></strong>
              </div>
            </td>
            <td><?= e($c['telephone']??'—') ?></td>
            <td><?= e($c['ville_nom']??'—') ?></td>
            <td><?= e($c['secteur_nom']??'—') ?></td>
            <td><?= format_date($c['created_at']) ?></td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn btn-outline btn-sm btn-icon"
                        onclick="editClient(<?= htmlspecialchars(json_encode($c),ENT_QUOTES) ?>)">
                  <i class="fas fa-edit"></i>
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action_form" value="delete">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-icon" style="background:var(--primary-soft);color:var(--primary);border:none;">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-body" style="border-top:1px solid var(--border);padding:14px 20px;">
      <ul class="pagination pagination-sm mb-0" style="gap:4px;">
        <?php for ($p=1;$p<=$pagination['total_pages'];$p++): ?>
        <li class="page-item <?= $p===$pagination['current']?'active':'' ?>">
          <a class="page-link" href="?page=clients&p=<?= $p ?>&search=<?= urlencode($f['search']) ?>&secteur_id=<?= $f['secteur_id'] ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>

<!-- Modal Client -->
<div class="modal fade" id="modalClient" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:var(--radius);">
      <div class="modal-header" style="border-color:var(--border);">
        <h5 class="modal-title" id="modalClientTitle">Ajouter un client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action_form" id="clientActionForm" value="create">
        <input type="hidden" name="id" id="clientId" value="">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Prénom *</label><input type="text" name="prenom" id="clientPrenom" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Nom *</label><input type="text" name="nom" id="clientNom" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Téléphone</label><input type="text" name="telephone" id="clientTel" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" id="clientEmail" class="form-control"></div>
            <div class="col-md-6">
              <label class="form-label">Secteur</label>
              <select name="secteur_id" id="clientSecteur" class="form-control form-select" onchange="loadVillesBySecteur(this.value,'clientVille')">
                <option value="">-- Choisir --</option>
                <?php foreach ($secteurs as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['nom']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ville</label>
              <select name="ville_id" id="clientVille" class="form-control form-select">
                <option value="">-- Choisir le secteur --</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border-color:var(--border);">
          <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$allVillesJson = json_encode($villes);
$extra_scripts = <<<JS
<script>
const allVilles = {$allVillesJson};
function loadVillesBySecteur(sid, selId) {
  const sel = document.getElementById(selId);
  sel.innerHTML = '<option value="">-- Choisir --</option>';
  allVilles.filter(v => !sid || v.secteur_id == sid).forEach(v => {
    const o = document.createElement('option'); o.value=v.id; o.textContent=v.nom; sel.appendChild(o);
  });
}
function editClient(c) {
  document.getElementById('clientActionForm').value='update';
  document.getElementById('clientId').value=c.id||'';
  document.getElementById('clientPrenom').value=c.prenom||'';
  document.getElementById('clientNom').value=c.nom||'';
  document.getElementById('clientTel').value=c.telephone||'';
  document.getElementById('clientEmail').value=c.email||'';
  document.getElementById('clientSecteur').value=c.secteur_id||'';
  loadVillesBySecteur(c.secteur_id,'clientVille');
  setTimeout(()=>{ document.getElementById('clientVille').value=c.ville_id||''; },60);
  document.getElementById('modalClientTitle').textContent='Modifier le client';
  new bootstrap.Modal(document.getElementById('modalClient')).show();
}
</script>
JS;
include __DIR__ . '/../layouts/footer.php';
?>
