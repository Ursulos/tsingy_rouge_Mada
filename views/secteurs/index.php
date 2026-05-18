<?php
// views/secteurs/index.php
$page_title = 'Secteurs & Villes';

$secteurModel = new SecteurModel();
$villeModel   = new VilleModel();

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $ap   = $_POST['action_form'] ?? '';
    $type = $_POST['type'] ?? 'secteur';

    if ($type === 'secteur') {
        if ($ap === 'create') {
            $secteurModel->create(['nom' => sanitize($_POST['nom']), 'description' => sanitize($_POST['description'] ?? '')]);
            set_flash('success', 'Secteur ajouté.');
        } elseif ($ap === 'update') {
            $secteurModel->update((int)$_POST['id'], ['nom' => sanitize($_POST['nom']), 'description' => sanitize($_POST['description'] ?? '')]);
            set_flash('success', 'Secteur mis à jour.');
        } elseif ($ap === 'delete') {
            try {
                $secteurModel->delete((int)$_POST['id']);
                set_flash('success', 'Secteur supprimé.');
            } catch (Exception $e) {
                set_flash('danger', 'Impossible : des villes ou vendeurs y sont rattachés.');
            }
        }
    } else {
        if ($ap === 'create') {
            $villeModel->create(['nom' => sanitize($_POST['nom']), 'secteur_id' => (int)$_POST['secteur_id']]);
            set_flash('success', 'Ville ajoutée.');
        } elseif ($ap === 'update') {
            $villeModel->update((int)$_POST['id'], ['nom' => sanitize($_POST['nom']), 'secteur_id' => (int)$_POST['secteur_id']]);
            set_flash('success', 'Ville mise à jour.');
        } elseif ($ap === 'delete') {
            try {
                $villeModel->delete((int)$_POST['id']);
                set_flash('success', 'Ville supprimée.');
            } catch (Exception $e) {
                set_flash('danger', 'Impossible : des clients ou ventes y sont rattachés.');
            }
        }
    }
    header('Location: ' . APP_URL . '/index.php?page=secteurs'); exit;
}

$secteurs = $secteurModel->getAll();
$villes   = $villeModel->getAll();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="page-content">
  <div class="page-header">
    <div>
      <h1 class="page-title">Secteurs & Villes</h1>
      <p class="page-subtitle">Organisation géographique des ventes</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#modalSecteur">
        <i class="fas fa-map"></i> Ajouter secteur
      </button>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalVille">
        <i class="fas fa-city"></i> Ajouter ville
      </button>
    </div>
  </div>

  <?php render_flash(); ?>

  <div class="row g-4">
    <!-- SECTEURS -->
    <div class="col-xl-5 fade-up">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-map"></i> Secteurs</div>
          <span class="badge" style="background:var(--primary-soft);color:var(--primary);font-weight:700;">
            <?= count($secteurs) ?>
          </span>
        </div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($secteurs)): ?>
          <div style="text-align:center;padding:40px;color:var(--text-muted);">
            Aucun secteur. <a href="#" data-bs-toggle="modal" data-bs-target="#modalSecteur">Ajouter</a>
          </div>
          <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:0;">
            <?php foreach ($secteurs as $i => $s): ?>
            <div style="padding:14px 20px;display:flex;align-items:center;gap:12px;
                        border-bottom:<?= $i < count($secteurs)-1 ? '1px solid var(--border)' : 'none' ?>;
                        transition:var(--transition);"
                 onmouseover="this.style.background='var(--surface-2)'"
                 onmouseout="this.style.background='transparent'">
              <!-- Icone -->
              <div style="width:38px;height:38px;border-radius:10px;background:var(--primary-soft);
                          display:flex;align-items:center;justify-content:center;
                          color:var(--primary);flex-shrink:0;">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.88rem;"><?= e($s['nom']) ?></div>
                <?php if ($s['description']): ?>
                <div style="font-size:.73rem;color:var(--text-muted);"><?= e($s['description']) ?></div>
                <?php endif; ?>
              </div>
              <span style="font-size:.72rem;background:var(--info-soft);color:var(--info);
                           padding:2px 8px;border-radius:20px;font-weight:700;white-space:nowrap;">
                <?= $s['nb_villes'] ?> ville(s)
              </span>
              <div class="d-flex gap-1">
                <button class="btn btn-outline btn-sm btn-icon"
                        onclick="editSecteur({id:<?= $s['id'] ?>,nom:'<?= addslashes($s['nom']) ?>',description:'<?= addslashes($s['description'] ?? '') ?>'})"
                        title="Modifier">
                  <i class="fas fa-edit"></i>
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce secteur ?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action_form" value="delete">
                  <input type="hidden" name="type" value="secteur">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-icon"
                          style="background:var(--primary-soft);color:var(--primary);border:none;">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- VILLES -->
    <div class="col-xl-7 fade-up delay-1">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-city"></i> Villes</div>
          <span class="badge" style="background:var(--info-soft);color:var(--info);font-weight:700;">
            <?= count($villes) ?>
          </span>
        </div>
        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>Ville</th>
                <th>Secteur</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($villes)): ?>
              <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:32px;">Aucune ville</td></tr>
              <?php else: ?>
              <?php foreach ($villes as $v): ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-building" style="color:var(--info);font-size:.8rem;"></i>
                    <strong><?= e($v['nom']) ?></strong>
                  </div>
                </td>
                <td>
                  <span style="font-size:.78rem;background:var(--primary-soft);color:var(--primary);
                               padding:2px 10px;border-radius:20px;font-weight:600;">
                    <?= e($v['secteur_nom']) ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline btn-sm btn-icon"
                            onclick="editVille({id:<?= $v['id'] ?>,nom:'<?= addslashes($v['nom']) ?>',secteur_id:<?= $v['secteur_id'] ?>})"
                            title="Modifier">
                      <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette ville ?')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action_form" value="delete">
                      <input type="hidden" name="type" value="ville">
                      <input type="hidden" name="id" value="<?= $v['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-icon"
                              style="background:var(--primary-soft);color:var(--primary);border:none;">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Modal Secteur -->
<div class="modal fade" id="modalSecteur" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:var(--radius);">
      <div class="modal-header" style="border-color:var(--border);">
        <h5 class="modal-title" id="modalSecteurTitle">Ajouter un secteur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action_form" id="secteurActionForm" value="create">
        <input type="hidden" name="type" value="secteur">
        <input type="hidden" name="id" id="secteurId" value="">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Nom du secteur *</label>
            <input type="text" name="nom" id="secteurNom" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" id="secteurDesc" class="form-control" rows="2"></textarea>
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

<!-- Modal Ville -->
<div class="modal fade" id="modalVille" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:var(--radius);">
      <div class="modal-header" style="border-color:var(--border);">
        <h5 class="modal-title" id="modalVilleTitle">Ajouter une ville</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action_form" id="villeActionForm" value="create">
        <input type="hidden" name="type" value="ville">
        <input type="hidden" name="id" id="villeId" value="">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Nom de la ville *</label>
            <input type="text" name="nom" id="villeNom" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Secteur *</label>
            <select name="secteur_id" id="villeSecteur" class="form-control form-select" required>
              <option value="">-- Choisir --</option>
              <?php foreach ($secteurs as $s): ?>
              <option value="<?= $s['id'] ?>"><?= e($s['nom']) ?></option>
              <?php endforeach; ?>
            </select>
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
function editSecteur(s) {
  document.getElementById('secteurActionForm').value = 'update';
  document.getElementById('secteurId').value         = s.id;
  document.getElementById('secteurNom').value        = s.nom;
  document.getElementById('secteurDesc').value       = s.description;
  document.getElementById('modalSecteurTitle').textContent = 'Modifier le secteur';
  new bootstrap.Modal(document.getElementById('modalSecteur')).show();
}

function editVille(v) {
  document.getElementById('villeActionForm').value = 'update';
  document.getElementById('villeId').value         = v.id;
  document.getElementById('villeNom').value        = v.nom;
  document.getElementById('villeSecteur').value    = v.secteur_id;
  document.getElementById('modalVilleTitle').textContent = 'Modifier la ville';
  new bootstrap.Modal(document.getElementById('modalVille')).show();
}
</script>
JS;

include __DIR__ . '/../layouts/footer.php';
?>
