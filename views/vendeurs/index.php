<?php
// views/vendeurs/index.php — Variables injectées par VendeurController
include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>
<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>
<div class="page-content">

  <div class="page-header">
    <div>
      <h1 class="page-title">Vendeurs</h1>
      <p class="page-subtitle"><?= count($vendeurs) ?> vendeur(s) enregistré(s)</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalVendeur">
      <i class="fas fa-plus"></i> Ajouter
    </button>
  </div>

  <?php render_flash(); ?>

  <!-- Classement -->
  <div class="card mb-4 fade-up">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-trophy"></i> Classement des vendeurs</div>
    </div>
    <div class="table-container">
      <table class="table">
        <thead><tr><th>Rang</th><th>Vendeur</th><th>Secteur</th><th>Nb ventes</th><th>Pièces</th><th>CA Total</th></tr></thead>
        <tbody>
          <?php if (empty($classement)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">Aucune donnée</td></tr>
          <?php else: foreach ($classement as $i => $v): ?>
          <tr>
            <td><div class="rank-badge <?= $i<3?'rank-'.($i+1):'rank-n' ?>"><?= $i<3?['🥇','🥈','🥉'][$i]:($i+1) ?></div></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar"><?= strtoupper(substr($v['vendeur'],0,1)) ?></div>
                <strong><?= e($v['vendeur']) ?></strong>
              </div>
            </td>
            <td><?= e($v['secteur']??'—') ?></td>
            <td><?= number_format($v['nb_ventes']??0) ?></td>
            <td><?= number_format($v['pieces']??0) ?></td>
            <td><strong style="color:var(--primary);"><?= format_money($v['ca']??0) ?></strong></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Liste vendeurs -->
  <div class="card fade-up delay-1">
    <div class="card-header"><div class="card-title"><i class="fas fa-user-tie"></i> Liste des vendeurs</div></div>
    <div class="table-container">
      <table class="table">
        <thead><tr><th>Vendeur</th><th>Contact</th><th>Secteur</th><th>Statut</th><th>Depuis</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($vendeurs as $v): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <?php if ($v['photo']): ?>
                  <img src="<?= APP_URL.'/uploads/'.e($v['photo']) ?>" style="width:34px;height:34px;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                  <div class="avatar"><?= strtoupper(substr($v['nom'],0,1)) ?></div>
                <?php endif; ?>
                <div>
                  <div style="font-weight:600;"><?= e($v['prenom'].' '.$v['nom']) ?></div>
                  <div style="font-size:.72rem;color:var(--text-muted);"><?= e($v['email']??'') ?></div>
                </div>
              </div>
            </td>
            <td><?= e($v['telephone']??'—') ?></td>
            <td><?= e($v['secteur_nom']) ?></td>
            <td><span class="badge-status <?= $v['statut'] ?>"><?= $v['statut'] ?></span></td>
            <td><?= format_date($v['created_at']) ?></td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn btn-outline btn-sm btn-icon"
                        onclick="editVendeur(<?= htmlspecialchars(json_encode($v),ENT_QUOTES) ?>)">
                  <i class="fas fa-edit"></i>
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action_form" value="delete">
                  <input type="hidden" name="id" value="<?= $v['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-icon" style="background:var(--primary-soft);color:var(--primary);border:none;">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Modal Vendeur -->
<div class="modal fade" id="modalVendeur" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:var(--radius);">
      <div class="modal-header" style="border-color:var(--border);">
        <h5 class="modal-title" id="modalVendeurTitle">Ajouter un vendeur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action_form" id="vendeurActionForm" value="create">
        <input type="hidden" name="id" id="vendeurId" value="">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Prénom *</label>
              <input type="text" name="prenom" id="vendeurPrenom" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nom *</label>
              <input type="text" name="nom" id="vendeurNom" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Téléphone</label>
              <input type="text" name="telephone" id="vendeurTel" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" id="vendeurEmail" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Secteur *</label>
              <select name="secteur_id" id="vendeurSecteur" class="form-control form-select" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($secteurs as $s): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['nom']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Statut</label>
              <select name="statut" id="vendeurStatut" class="form-control form-select">
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Photo</label>
              <input type="file" name="photo" class="form-control" accept="image/*">
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
$extra_scripts = <<<JS
<script>
function editVendeur(v) {
  document.getElementById('vendeurActionForm').value = 'update';
  document.getElementById('vendeurId').value         = v.id||'';
  document.getElementById('vendeurPrenom').value     = v.prenom||'';
  document.getElementById('vendeurNom').value        = v.nom||'';
  document.getElementById('vendeurTel').value        = v.telephone||'';
  document.getElementById('vendeurEmail').value      = v.email||'';
  document.getElementById('vendeurSecteur').value    = v.secteur_id||'';
  document.getElementById('vendeurStatut').value     = v.statut||'actif';
  document.getElementById('modalVendeurTitle').textContent = 'Modifier le vendeur';
  new bootstrap.Modal(document.getElementById('modalVendeur')).show();
}
</script>
JS;
include __DIR__ . '/../layouts/footer.php';
?>
