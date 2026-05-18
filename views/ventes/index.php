<?php
// views/ventes/index.php
$page_title = 'Ventes';

$venteModel   = new VenteModel();
$vendeurModel = new VendeurModel();
$clientModel  = new ClientModel();
$secteurModel = new SecteurModel();
$villeModel   = new VilleModel();
$produitModel = new ProduitModel();

// POST : créer une vente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action_post = $_POST['action_form'] ?? '';

    if ($action_post === 'create_vente') {
        // Récupérer les lignes
        $produit_ids = $_POST['produit_id']  ?? [];
        $quantites   = $_POST['quantite']    ?? [];
        $prix_units  = $_POST['prix_unitaire'] ?? [];
        $lignes = [];
        foreach ($produit_ids as $k => $pid) {
            if (!empty($pid) && !empty($quantites[$k]) && $quantites[$k] > 0) {
                $lignes[] = [
                    'produit_id' => (int)$pid,
                    'quantite'   => (int)$quantites[$k],
                    'prix'       => floatval($prix_units[$k]),
                ];
            }
        }
        if (empty($lignes)) {
            set_flash('danger', 'Ajoutez au moins une ligne de produit.');
        } else {
            try {
                $venteModel->create([
                    'vendeur_id'  => (int)$_POST['vendeur_id'],
                    'client_id'   => (int)$_POST['client_id'],
                    'secteur_id'  => (int)$_POST['secteur_id'],
                    'ville_id'    => (int)$_POST['ville_id'],
                    'date_vente'  => $_POST['date_vente'],
                    'note'        => sanitize($_POST['note'] ?? ''),
                ], $lignes);
                set_flash('success', 'Vente enregistrée avec succès.');
                header('Location: ' . APP_URL . '/index.php?page=ventes');
                exit;
            } catch (Exception $e) {
                set_flash('danger', 'Erreur : ' . $e->getMessage());
            }
        }
    }
}

// Vue détail ?
$show_create = ($_GET['action'] ?? '') === 'create';

// Filtres liste
$f = [
    'vendeur_id'  => !empty($_GET['vendeur_id'])  ? (int)$_GET['vendeur_id']  : null,
    'secteur_id'  => !empty($_GET['secteur_id'])  ? (int)$_GET['secteur_id']  : null,
    'ville_id'    => !empty($_GET['ville_id'])    ? (int)$_GET['ville_id']    : null,
    'date_debut'  => $_GET['date_debut'] ?? '',
    'date_fin'    => $_GET['date_fin']   ?? '',
];
$page_num  = max(1, (int)($_GET['p'] ?? 1));
$per_page  = 30;
$total     = $venteModel->count($f);
$pagination = paginate($total, $page_num, $per_page);
$ventes     = $venteModel->getAll($f, $per_page, $pagination['offset']);

// Données pour le formulaire
$vendeurs = $vendeurModel->getAll();
$clients  = $clientModel->getAll(500, 0);
$secteurs = $secteurModel->getAll();
$villes   = $villeModel->getAll();
$produits = $produitModel->getAll(true);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="page-content">

  <?php render_flash(); ?>

  <?php if ($show_create): ?>
  <!-- ====================================================
       FORMULAIRE NOUVELLE VENTE
       ==================================================== -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Nouvelle vente</h1>
      <p class="page-subtitle">Enregistrez une vente avec ses lignes de produits</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=ventes" class="btn btn-outline">
      <i class="fas fa-arrow-left"></i> Retour
    </a>
  </div>

  <form method="POST" id="formVente">
    <?= csrf_field() ?>
    <input type="hidden" name="action_form" value="create_vente">

    <div class="row g-4">
      <!-- Infos principales -->
      <div class="col-xl-8">
        <div class="card mb-4">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-info-circle"></i> Informations vente</div>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Vendeur *</label>
                <select name="vendeur_id" class="form-control form-select" required>
                  <option value="">-- Choisir --</option>
                  <?php foreach ($vendeurs as $v): ?>
                  <option value="<?= $v['id'] ?>"><?= e($v['prenom'] . ' ' . $v['nom']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Client *</label>
                <select name="client_id" class="form-control form-select" required>
                  <option value="">-- Choisir --</option>
                  <?php foreach ($clients as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= e($c['prenom'] . ' ' . $c['nom']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Secteur *</label>
                <select name="secteur_id" id="venteSecteur" class="form-control form-select" required
                        onchange="loadVillesVente(this.value)">
                  <option value="">-- Choisir --</option>
                  <?php foreach ($secteurs as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= e($s['nom']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Ville *</label>
                <select name="ville_id" id="venteVille" class="form-control form-select" required>
                  <option value="">-- Choisir le secteur --</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Date de vente *</label>
                <input type="date" name="date_vente" class="form-control"
                       value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-12">
                <label class="form-label">Note</label>
                <textarea name="note" class="form-control" rows="2" placeholder="Commentaire optionnel…"></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Lignes de produits -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-tshirt"></i> Produits</div>
            <button type="button" class="btn btn-outline btn-sm" onclick="ajouterLigne()">
              <i class="fas fa-plus"></i> Ajouter ligne
            </button>
          </div>
          <div class="card-body">
            <div id="lignesContainer">
              <!-- Ligne initiale -->
              <div class="ligne-produit" id="ligne_1">
                <div class="row g-2 align-items-end mb-3">
                  <div class="col-md-5">
                    <label class="form-label">Produit *</label>
                    <select name="produit_id[]" class="form-control form-select produit-select"
                            onchange="fillPrix(this, 1)" required>
                      <option value="">-- Choisir --</option>
                      <?php foreach ($produits as $p): ?>
                      <option value="<?= $p['id'] ?>" data-prix="<?= $p['prix'] ?>" data-stock="<?= $p['stock'] ?>">
                        <?= e($p['nom']) ?> — Stock: <?= $p['stock'] ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Qté *</label>
                    <input type="number" name="quantite[]" id="qte_1" class="form-control"
                           min="1" value="1" required oninput="calcTotal()">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Prix unitaire (Ar)</label>
                    <input type="number" name="prix_unitaire[]" id="pu_1" class="form-control"
                           min="0" step="0.01" value="0" oninput="calcTotal()">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Sous-total</label>
                    <div id="st_1" style="padding:10px 0;font-weight:700;color:var(--primary);">0 Ar</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Total -->
            <div style="border-top:2px solid var(--border);padding-top:16px;margin-top:8px;text-align:right;">
              <span style="font-size:.85rem;color:var(--text-muted);">TOTAL :</span>
              <span id="grandTotal" style="font-size:1.4rem;font-weight:800;color:var(--primary);margin-left:12px;">0 Ar</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Panneau résumé -->
      <div class="col-xl-4">
        <div class="card" style="position:sticky;top:calc(var(--topbar-h) + 20px);">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-receipt"></i> Résumé</div>
          </div>
          <div class="card-body">
            <div style="font-size:.82rem;color:var(--text-muted);line-height:2;">
              <div>Nb lignes : <strong id="resumeNbLignes">1</strong></div>
              <div>Total pièces : <strong id="resumePieces">1</strong></div>
              <div>Montant total :</div>
              <div style="font-size:1.5rem;font-weight:800;color:var(--primary);margin-top:4px;" id="resumeTotal">0 Ar</div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-4" style="height:46px;font-size:.95rem;">
              <i class="fas fa-check-circle"></i> Enregistrer la vente
            </button>
            <a href="<?= APP_URL ?>/index.php?page=ventes" class="btn btn-outline w-100 mt-2">
              Annuler
            </a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <?php else: ?>
  <!-- ====================================================
       LISTE DES VENTES
       ==================================================== -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Ventes</h1>
      <p class="page-subtitle"><?= $total ?> vente(s) au total</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=ventes&action=create" class="btn btn-primary">
      <i class="fas fa-plus"></i> Nouvelle vente
    </a>
  </div>

  <!-- Filtres -->
  <div class="card mb-4 fade-up">
    <div class="card-body" style="padding:16px 20px;">
      <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="ventes">
        <div class="col-md-2">
          <label class="form-label">Vendeur</label>
          <select name="vendeur_id" class="form-control form-select">
            <option value="">Tous</option>
            <?php foreach ($vendeurs as $v): ?>
            <option value="<?= $v['id'] ?>" <?= $f['vendeur_id'] == $v['id'] ? 'selected' : '' ?>>
              <?= e($v['prenom'] . ' ' . $v['nom']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Secteur</label>
          <select name="secteur_id" class="form-control form-select">
            <option value="">Tous</option>
            <?php foreach ($secteurs as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $f['secteur_id'] == $s['id'] ? 'selected' : '' ?>>
              <?= e($s['nom']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Du</label>
          <input type="date" name="date_debut" class="form-control" value="<?= e($f['date_debut']) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Au</label>
          <input type="date" name="date_fin" class="form-control" value="<?= e($f['date_fin']) ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-search"></i> Filtrer</button>
          <a href="?page=ventes" class="btn btn-outline"><i class="fas fa-times"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Tableau ventes -->
  <div class="card fade-up">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-shopping-cart"></i> Liste des ventes</div>
    </div>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>Référence</th>
            <th>Date</th>
            <th>Client</th>
            <th>Vendeur</th>
            <th>Secteur / Ville</th>
            <th>Pièces</th>
            <th>Montant</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ventes)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px;">Aucune vente trouvée</td></tr>
          <?php else: ?>
          <?php foreach ($ventes as $v): ?>
          <tr>
            <td>
              <span style="font-family:monospace;font-size:.76rem;background:var(--surface-2);
                           padding:2px 8px;border-radius:4px;color:var(--text-secondary);">
                <?= e($v['reference']) ?>
              </span>
            </td>
            <td><?= format_date($v['date_vente']) ?></td>
            <td><strong><?= e($v['client']) ?></strong></td>
            <td><?= e($v['vendeur']) ?></td>
            <td>
              <div style="font-size:.8rem;">
                <span class="badge-status actif"><?= e($v['secteur']) ?></span>
                <span style="color:var(--text-muted);margin-left:4px;"><?= e($v['ville'] ?? '') ?></span>
              </div>
            </td>
            <td>
              <strong><?= number_format($v['total_pieces'] ?? 0) ?></strong>
              <span style="font-size:.72rem;color:var(--text-muted);"> pcs</span>
            </td>
            <td><strong style="color:var(--primary);"><?= format_money($v['montant_total']) ?></strong></td>
            <td>
              <a href="?page=ventes&action=detail&id=<?= $v['id'] ?>"
                 class="btn btn-outline btn-sm btn-icon" title="Détail">
                <i class="fas fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-body" style="border-top:1px solid var(--border);padding:14px 20px;">
      <nav aria-label="Pagination ventes">
        <ul class="pagination pagination-sm mb-0" style="gap:4px;">
          <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
          <li class="page-item <?= $p === $pagination['current'] ? 'active' : '' ?>">
            <a class="page-link" href="?page=ventes&p=<?= $p ?>&vendeur_id=<?= $f['vendeur_id'] ?>&secteur_id=<?= $f['secteur_id'] ?>&date_debut=<?= $f['date_debut'] ?>&date_fin=<?= $f['date_fin'] ?>">
              <?= $p ?>
            </a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div><!-- /page-content -->
</div><!-- /main-content -->

<?php
$allVillesJson = json_encode($villes);
$produitsJson  = json_encode($produits);
$extra_scripts = <<<JS
<script>
const allVilles  = {$allVillesJson};
const allProduits = {$produitsJson};
let ligneCount   = 1;

// Charger villes par secteur
function loadVillesVente(secteurId) {
  const sel = document.getElementById('venteVille');
  if (!sel) return;
  sel.innerHTML = '<option value="">-- Choisir --</option>';
  allVilles.filter(v => !secteurId || v.secteur_id == secteurId).forEach(v => {
    const opt = document.createElement('option');
    opt.value = v.id; opt.textContent = v.nom;
    sel.appendChild(opt);
  });
}

// Remplir le prix unitaire depuis le select produit
function fillPrix(sel, n) {
  const opt = sel.options[sel.selectedIndex];
  if (opt) {
    const pu = document.getElementById('pu_' + n);
    if (pu && opt.dataset.prix) pu.value = opt.dataset.prix;
  }
  calcTotal();
}

// Calculer total
function calcTotal() {
  let grand = 0, pieces = 0, lignes = 0;
  document.querySelectorAll('.ligne-produit').forEach((ligne, i) => {
    const n   = ligne.id.replace('ligne_', '');
    const qte = parseFloat(document.getElementById('qte_' + n)?.value || 0);
    const pu  = parseFloat(document.getElementById('pu_' + n)?.value  || 0);
    const st  = qte * pu;
    const stEl = document.getElementById('st_' + n);
    if (stEl) stEl.textContent = new Intl.NumberFormat('fr-MG').format(st) + ' Ar';
    grand  += st;
    pieces += qte;
    lignes++;
  });
  const fmt = v => new Intl.NumberFormat('fr-MG').format(v) + ' Ar';
  document.getElementById('grandTotal').textContent  = fmt(grand);
  document.getElementById('resumeTotal').textContent = fmt(grand);
  document.getElementById('resumePieces').textContent = Math.round(pieces);
  document.getElementById('resumeNbLignes').textContent = lignes;
}

// Ajouter une ligne produit
function ajouterLigne() {
  ligneCount++;
  const n = ligneCount;
  const opts = allProduits.map(p =>
    `<option value="\${p.id}" data-prix="\${p.prix}" data-stock="\${p.stock}">
       \${p.nom} — Stock: \${p.stock}
     </option>`
  ).join('');

  const div = document.createElement('div');
  div.className = 'ligne-produit';
  div.id = 'ligne_' + n;
  div.innerHTML = `
    <div class="row g-2 align-items-end mb-3">
      <div class="col-md-5">
        <label class="form-label">Produit *</label>
        <select name="produit_id[]" class="form-control form-select"
                onchange="fillPrix(this, \${n})" required>
          <option value="">-- Choisir --</option>\${opts}
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Qté *</label>
        <input type="number" name="quantite[]" id="qte_\${n}"
               class="form-control" min="1" value="1" required oninput="calcTotal()">
      </div>
      <div class="col-md-3">
        <label class="form-label">Prix unitaire (Ar)</label>
        <input type="number" name="prix_unitaire[]" id="pu_\${n}"
               class="form-control" min="0" step="0.01" value="0" oninput="calcTotal()">
      </div>
      <div class="col-md-1">
        <label class="form-label">S-Total</label>
        <div id="st_\${n}" style="padding:10px 0;font-weight:700;color:var(--primary);">0 Ar</div>
      </div>
      <div class="col-md-1 d-flex align-items-end pb-1">
        <button type="button" class="btn btn-sm btn-icon"
                style="background:var(--primary-soft);color:var(--primary);border:none;"
                onclick="supprimerLigne('ligne_\${n}')">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>`;
  document.getElementById('lignesContainer').appendChild(div);
}

function supprimerLigne(id) {
  const el = document.getElementById(id);
  if (el) { el.remove(); calcTotal(); }
}
</script>
JS;

include __DIR__ . '/../layouts/footer.php';
?>
