<?php
// views/comparaison/index.php
$page_title = 'Comparaison de périodes';

$venteModel  = new VenteModel();
$secteurModel = new SecteurModel();
$vendeurModel = new VendeurModel();

$secteurs = $secteurModel->getAll();
$vendeurs = $vendeurModel->getAll();

$resultats = [];
$periodes_actives = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nb       = (int) ($_POST['nb_periodes'] ?? 2);
    $nb       = min(max($nb, 2), 5);
    $vendeur_id = !empty($_POST['vendeur_id']) ? (int)$_POST['vendeur_id'] : null;
    $secteur_id = !empty($_POST['secteur_id']) ? (int)$_POST['secteur_id'] : null;
    $ville_id   = !empty($_POST['ville_id'])   ? (int)$_POST['ville_id']   : null;

    $periodes = [];
    for ($i = 1; $i <= $nb; $i++) {
        $debut = $_POST['debut_' . $i] ?? '';
        $fin   = $_POST['fin_'   . $i] ?? '';
        $label = sanitize($_POST['label_' . $i] ?? "Période $i");
        if ($debut && $fin && $debut <= $fin) {
            $periodes[] = ['debut' => $debut, 'fin' => $fin, 'label' => $label];
            $periodes_actives[] = ['debut' => $debut, 'fin' => $fin, 'label' => $label];
        }
    }

    if (!empty($periodes)) {
        $resultats = $venteModel->comparerPeriodes($periodes, $vendeur_id, $secteur_id, $ville_id);
    }
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="page-content">
  <div class="page-header">
    <div>
      <h1 class="page-title">Comparaison de périodes</h1>
      <p class="page-subtitle">Comparez jusqu'à 5 intervalles de temps différents</p>
    </div>
  </div>

  <?php render_flash(); ?>

  <!-- Formulaire de configuration -->
  <div class="card mb-4 fade-up">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-sliders-h"></i> Configuration des périodes</div>
    </div>
    <div class="card-body">
      <form method="POST" id="formComparaison">
        <?= csrf_field() ?>

        <!-- Nombre de périodes -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label">Nombre de périodes à comparer</label>
            <div class="d-flex gap-2 flex-wrap" id="nbPeriodesGroup">
              <?php for ($n = 2; $n <= 5; $n++): ?>
              <button type="button"
                      class="btn nb-btn <?= (($_POST['nb_periodes'] ?? 2) == $n) ? 'btn-primary' : 'btn-outline' ?>"
                      data-nb="<?= $n ?>"
                      onclick="setNbPeriodes(<?= $n ?>)">
                <?= $n ?> périodes
              </button>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="nb_periodes" id="nb_periodes" value="<?= (int)($_POST['nb_periodes'] ?? 2) ?>">
          </div>

          <div class="col-md-3">
            <label class="form-label">Filtrer par vendeur</label>
            <select name="vendeur_id" class="form-control form-select">
              <option value="">Tous les vendeurs</option>
              <?php foreach ($vendeurs as $v): ?>
              <option value="<?= $v['id'] ?>" <?= (($_POST['vendeur_id'] ?? '') == $v['id']) ? 'selected' : '' ?>>
                <?= e($v['prenom'] . ' ' . $v['nom']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Filtrer par secteur</label>
            <select name="secteur_id" class="form-control form-select">
              <option value="">Tous les secteurs</option>
              <?php foreach ($secteurs as $s): ?>
              <option value="<?= $s['id'] ?>" <?= (($_POST['secteur_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
                <?= e($s['nom']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Cartes périodes -->
        <div class="row g-3 mb-4" id="periodesContainer">
          <?php for ($i = 1; $i <= 5; $i++):
            $debut_val = $_POST['debut_' . $i] ?? '';
            $fin_val   = $_POST['fin_'   . $i] ?? '';
            $label_val = $_POST['label_' . $i] ?? "Période $i";
            $actif     = $i <= (int)($_POST['nb_periodes'] ?? 2);
          ?>
          <div class="col-xl-4 col-md-6 periode-card-wrap" id="periodeWrap<?= $i ?>"
               style="<?= !$actif ? 'display:none;' : '' ?>">
            <div class="period-card <?= $actif ? 'active' : '' ?>">
              <div class="period-number"><?= $i ?></div>
              <div class="form-group">
                <label class="form-label">Label</label>
                <input type="text" name="label_<?= $i ?>" class="form-control"
                       value="<?= e($label_val) ?>" placeholder="Ex: Janvier 2026">
              </div>
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label">Du</label>
                  <input type="date" name="debut_<?= $i ?>" class="form-control"
                         value="<?= e($debut_val) ?>">
                </div>
                <div class="col-6">
                  <label class="form-label">Au</label>
                  <input type="date" name="fin_<?= $i ?>" class="form-control"
                         value="<?= e($fin_val) ?>">
                </div>
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-chart-bar"></i> Comparer les périodes
        </button>
      </form>
    </div>
  </div>

  <?php if (!empty($resultats)): ?>
  <!-- ====================================================
       RÉSULTATS DE COMPARAISON
       ==================================================== -->

  <!-- Tableau comparatif -->
  <div class="card mb-4 fade-up">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-table"></i> Tableau comparatif</div>
    </div>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>Métrique</th>
            <?php foreach ($resultats as $r): ?>
            <th><?= e($r['label']) ?><br>
              <small style="font-weight:400;color:var(--text-muted);">
                <?= format_date($r['debut']) ?> → <?= format_date($r['fin']) ?>
              </small>
            </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $metriques = [
            ['key' => 'nb_ventes',   'label' => 'Nb ventes',       'format' => 'int'],
            ['key' => 'pieces',      'label' => 'Pièces vendues',  'format' => 'int'],
            ['key' => 'ca',          'label' => 'Chiffre d\'affaires', 'format' => 'money'],
            ['key' => 'nb_clients',  'label' => 'Clients uniques', 'format' => 'int'],
            ['key' => 'nb_vendeurs', 'label' => 'Vendeurs actifs', 'format' => 'int'],
          ];
          foreach ($metriques as $m):
            $vals = array_column($resultats, $m['key']);
            $max  = max($vals) ?: 1;
          ?>
          <tr>
            <td><strong><?= $m['label'] ?></strong></td>
            <?php foreach ($resultats as $j => $r):
              $val = floatval($r[$m['key']]);
              $pct = $j > 0 ? round((($val - floatval($resultats[0][$m['key']])) / max($resultats[0][$m['key']], 1)) * 100) : null;
              $isMax = ($val == $max && $max > 0);
            ?>
            <td style="<?= $isMax ? 'background:var(--success-soft);' : '' ?>">
              <div style="font-weight:700;">
                <?= $m['format'] === 'money' ? format_money($val) : number_format($val) ?>
              </div>
              <?php if ($pct !== null): ?>
              <div class="kpi-trend <?= $pct >= 0 ? 'up' : 'down' ?>" style="margin-top:2px;font-size:.72rem;">
                <i class="fas fa-arrow-<?= $pct >= 0 ? 'up' : 'down' ?>"></i>
                <?= abs($pct) ?>% vs P1
              </div>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Graphiques comparatifs -->
  <div class="row g-3 fade-up">
    <div class="col-xl-6">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-coins"></i> CA par période</div></div>
        <div class="card-body"><canvas id="chartCompCA" height="130"></canvas></div>
      </div>
    </div>
    <div class="col-xl-6">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-tshirt"></i> Pièces vendues par période</div></div>
        <div class="card-body"><canvas id="chartCompPieces" height="130"></canvas></div>
      </div>
    </div>
    <div class="col-xl-6">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-shopping-cart"></i> Nombre de ventes</div></div>
        <div class="card-body"><canvas id="chartCompVentes" height="130"></canvas></div>
      </div>
    </div>
    <div class="col-xl-6">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Clients uniques</div></div>
        <div class="card-body"><canvas id="chartCompClients" height="130"></canvas></div>
      </div>
    </div>
  </div>

  <?php
  $comp_labels  = json_encode(array_column($resultats, 'label'));
  $comp_ca      = json_encode(array_map(fn($r) => floatval($r['ca']), $resultats));
  $comp_pieces  = json_encode(array_map(fn($r) => intval($r['pieces']), $resultats));
  $comp_ventes  = json_encode(array_map(fn($r) => intval($r['nb_ventes']), $resultats));
  $comp_clients = json_encode(array_map(fn($r) => intval($r['nb_clients']), $resultats));
  ?>

  <?php endif; ?>

</div><!-- /page-content -->
</div><!-- /main-content -->

<?php
$extra_scripts = <<<JS
<script>
function setNbPeriodes(n) {
  document.getElementById('nb_periodes').value = n;
  // Afficher/masquer cartes
  for (let i = 1; i <= 5; i++) {
    const wrap = document.getElementById('periodeWrap' + i);
    if (wrap) wrap.style.display = i <= n ? '' : 'none';
  }
  // Mettre à jour boutons
  document.querySelectorAll('.nb-btn').forEach(btn => {
    const isActive = parseInt(btn.dataset.nb) === n;
    btn.className = 'btn ' + (isActive ? 'btn-primary' : 'btn-outline') + ' nb-btn';
  });
}
JS;

if (!empty($resultats)) {
  $extra_scripts .= <<<JS

// Graphiques comparaison
const PCOLORS = ['#C0392B','#F39C12','#27AE60','#2980B9','#8E44AD'];
const compLabels = {$comp_labels};
const mkBar = (id, data, label) => new Chart(document.getElementById(id), {
  type: 'bar',
  data: {
    labels: compLabels,
    datasets: [{ label, data, backgroundColor: PCOLORS.slice(0, compLabels.length), borderRadius: 8, borderSkipped: false }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { x: { grid: { display: false } }, y: { grid: { color: '#E8ECF4' } } }
  }
});
mkBar('chartCompCA',      {$comp_ca},      'CA (Ar)');
mkBar('chartCompPieces',  {$comp_pieces},  'Pièces');
mkBar('chartCompVentes',  {$comp_ventes},  'Ventes');
mkBar('chartCompClients', {$comp_clients}, 'Clients');
JS;
}

$extra_scripts .= '</script>';

include __DIR__ . '/../layouts/footer.php';
?>
