<?php
// views/produits/index.php
$page_title = 'Produits';

$produitModel = new ProduitModel();

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $ap = $_POST['action_form'] ?? '';

    if ($ap === 'create') {
        $img = null;
        if (!empty($_FILES['image']['name'])) $img = upload_image($_FILES['image'], 'produits');
        $produitModel->create([
            'nom'       => sanitize($_POST['nom']),
            'reference' => sanitize($_POST['reference'] ?? ''),
            'taille'    => sanitize($_POST['taille']    ?? ''),
            'couleur'   => sanitize($_POST['couleur']   ?? ''),
            'prix'      => floatval($_POST['prix']       ?? 0),
            'stock'     => (int)($_POST['stock']         ?? 0),
            'stock_min' => (int)($_POST['stock_min']     ?? 10),
            'image'     => $img,
        ]);
        set_flash('success', 'Produit créé.');
        header('Location: ' . APP_URL . '/index.php?page=produits'); exit;
    }
    if ($ap === 'update') {
        $id   = (int)$_POST['id'];
        $data = [
            'nom'       => sanitize($_POST['nom']),
            'reference' => sanitize($_POST['reference'] ?? ''),
            'taille'    => sanitize($_POST['taille']    ?? ''),
            'couleur'   => sanitize($_POST['couleur']   ?? ''),
            'prix'      => floatval($_POST['prix']       ?? 0),
            'stock'     => (int)($_POST['stock']         ?? 0),
            'stock_min' => (int)($_POST['stock_min']     ?? 10),
        ];
        if (!empty($_FILES['image']['name'])) $data['image'] = upload_image($_FILES['image'], 'produits');
        $produitModel->update($id, $data);
        set_flash('success', 'Produit mis à jour.');
        header('Location: ' . APP_URL . '/index.php?page=produits'); exit;
    }
    if ($ap === 'delete') {
        $produitModel->delete((int)$_POST['id']);
        set_flash('success', 'Produit archivé.');
        header('Location: ' . APP_URL . '/index.php?page=produits'); exit;
    }
}

$produits    = $produitModel->getAll();
$stock_faible = $produitModel->getStockFaible();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="page-content">
  <div class="page-header">
    <div>
      <h1 class="page-title">Produits</h1>
      <p class="page-subtitle"><?= count($produits) ?> T-Shirt(s) référencé(s)</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduit">
      <i class="fas fa-plus"></i> Ajouter produit
    </button>
  </div>

  <?php render_flash(); ?>

  <!-- KPI produits -->
  <div class="row g-3 mb-4">
    <?php
    $total_stock  = array_sum(array_column($produits, 'stock'));
    $nb_alerte    = count($stock_faible);
    $nb_rupture   = count(array_filter($produits, fn($p) => $p['stock'] == 0));
    ?>
    <div class="col-md-3 fade-up">
      <div class="kpi-card blue">
        <div class="kpi-icon blue"><i class="fas fa-tshirt"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= count($produits) ?></div>
          <div class="kpi-label">Références actives</div>
        </div>
      </div>
    </div>
    <div class="col-md-3 fade-up delay-1">
      <div class="kpi-card green">
        <div class="kpi-icon green"><i class="fas fa-boxes"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= number_format($total_stock) ?></div>
          <div class="kpi-label">Pièces en stock total</div>
        </div>
      </div>
    </div>
    <div class="col-md-3 fade-up delay-2">
      <div class="kpi-card amber">
        <div class="kpi-icon amber"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= $nb_alerte ?></div>
          <div class="kpi-label">Stock faible</div>
        </div>
      </div>
    </div>
    <div class="col-md-3 fade-up delay-3">
      <div class="kpi-card red">
        <div class="kpi-icon red"><i class="fas fa-ban"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= $nb_rupture ?></div>
          <div class="kpi-label">En rupture</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tableau produits -->
  <div class="card fade-up">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-tshirt"></i> Catalogue produits</div>
      <div class="d-flex gap-2">
        <input type="text" id="searchProduit" class="form-control form-control-sm"
               placeholder="Rechercher…" style="width:180px;"
               oninput="filterTable('tableProduits', this.value)">
      </div>
    </div>
    <div class="table-container">
      <table class="table" id="tableProduits">
        <thead>
          <tr>
            <th>Produit</th>
            <th>Référence</th>
            <th>Taille</th>
            <th>Couleur</th>
            <th>Prix</th>
            <th>Stock</th>
            <th>Statut stock</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($produits)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px;">Aucun produit</td></tr>
          <?php else: ?>
          <?php foreach ($produits as $p):
            $stock_pct = $p['stock_min'] > 0 ? min(100, round(($p['stock'] / max($p['stock_min'] * 2, 1)) * 100)) : 100;
            $stock_color = $p['stock'] == 0 ? 'var(--primary)' : ($p['stock'] <= $p['stock_min'] ? 'var(--warning)' : 'var(--success)');
            $stock_label = $p['stock'] == 0 ? 'Rupture' : ($p['stock'] <= $p['stock_min'] ? 'Faible' : 'OK');
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($p['image']): ?>
                <img src="<?= APP_URL . '/uploads/' . e($p['image']) ?>"
                     alt="" style="width:36px;height:36px;border-radius:8px;object-fit:cover;">
                <?php else: ?>
                <div style="width:36px;height:36px;border-radius:8px;background:var(--primary-soft);
                            display:flex;align-items:center;justify-content:center;
                            color:var(--primary);font-size:.9rem;">
                  <i class="fas fa-tshirt"></i>
                </div>
                <?php endif; ?>
                <strong><?= e($p['nom']) ?></strong>
              </div>
            </td>
            <td><code style="font-size:.75rem;"><?= e($p['reference'] ?? '—') ?></code></td>
            <td>
              <?php if ($p['taille']): ?>
              <span style="background:var(--surface-2);padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:700;">
                <?= e($p['taille']) ?>
              </span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <?php if ($p['couleur']): ?>
              <span style="font-size:.8rem;"><?= e($p['couleur']) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><strong><?= format_money($p['prix']) ?></strong></td>
            <td>
              <div style="min-width:90px;">
                <div style="font-weight:700;margin-bottom:4px;"><?= $p['stock'] ?> / <?= $p['stock_min'] ?></div>
                <div class="progress-bar-custom">
                  <div class="progress-bar-fill"
                       style="width:<?= $stock_pct ?>%;background:<?= $stock_color ?>;"></div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge-status <?= $p['stock'] == 0 ? 'alerte' : ($p['stock'] <= $p['stock_min'] ? 'alerte' : 'actif') ?>">
                <?= $stock_label ?>
              </span>
            </td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn btn-outline btn-sm btn-icon"
                        onclick="editProduit(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)"
                        title="Modifier">
                  <i class="fas fa-edit"></i>
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Archiver ce produit ?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action_form" value="delete">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-icon"
                          style="background:var(--primary-soft);color:var(--primary);border:none;"
                          title="Archiver">
                    <i class="fas fa-archive"></i>
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

<!-- Modal Produit -->
<div class="modal fade" id="modalProduit" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:var(--radius);">
      <div class="modal-header" style="border-color:var(--border);">
        <h5 class="modal-title" id="modalProduitTitle">Ajouter un produit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action_form" id="produitActionForm" value="create">
        <input type="hidden" name="id" id="produitId" value="">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nom du produit *</label>
              <input type="text" name="nom" id="produitNom" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Référence</label>
              <input type="text" name="reference" id="produitRef" class="form-control" placeholder="EX: TSH-001">
            </div>
            <div class="col-md-3">
              <label class="form-label">Taille</label>
              <select name="taille" id="produitTaille" class="form-control form-select">
                <option value="">—</option>
                <?php foreach (['XS','S','M','L','XL','XXL','XXXL'] as $t): ?>
                <option value="<?= $t ?>"><?= $t ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Couleur</label>
              <input type="text" name="couleur" id="produitCouleur" class="form-control" placeholder="Ex: Rouge">
            </div>
            <div class="col-md-3">
              <label class="form-label">Prix unitaire (Ar) *</label>
              <input type="number" name="prix" id="produitPrix" class="form-control" min="0" step="0.01" value="0" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Stock actuel</label>
              <input type="number" name="stock" id="produitStock" class="form-control" min="0" value="0">
            </div>
            <div class="col-md-3">
              <label class="form-label">Seuil alerte</label>
              <input type="number" name="stock_min" id="produitStockMin" class="form-control" min="0" value="10">
            </div>
            <div class="col-md-9">
              <label class="form-label">Image (optionnel)</label>
              <input type="file" name="image" class="form-control" accept="image/*">
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
function editProduit(p) {
  document.getElementById('produitActionForm').value = 'update';
  document.getElementById('produitId').value         = p.id || '';
  document.getElementById('produitNom').value        = p.nom || '';
  document.getElementById('produitRef').value        = p.reference || '';
  document.getElementById('produitTaille').value     = p.taille || '';
  document.getElementById('produitCouleur').value    = p.couleur || '';
  document.getElementById('produitPrix').value       = p.prix || 0;
  document.getElementById('produitStock').value      = p.stock || 0;
  document.getElementById('produitStockMin').value   = p.stock_min || 10;
  document.getElementById('modalProduitTitle').textContent = 'Modifier le produit';
  new bootstrap.Modal(document.getElementById('modalProduit')).show();
}

function filterTable(tableId, query) {
  const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
  const q = query.toLowerCase();
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
JS;

include __DIR__ . '/../layouts/footer.php';
?>
