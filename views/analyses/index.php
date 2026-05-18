<?php
// views/analyses/index.php
$page_title = 'Analyses de performance';

$venteModel   = new VenteModel();
$vendeurModel = new VendeurModel();
$secteurModel = new SecteurModel();
$villeModel   = new VilleModel();

$secteurs = $secteurModel->getAll();
$vendeurs = $vendeurModel->getAll();
$villes   = $villeModel->getAll();

// Filtres
$f = [
    'vendeur_id' => !empty($_GET['vendeur_id']) ? (int)$_GET['vendeur_id'] : null,
    'secteur_id' => !empty($_GET['secteur_id']) ? (int)$_GET['secteur_id'] : null,
    'ville_id'   => !empty($_GET['ville_id'])   ? (int)$_GET['ville_id']   : null,
    'date_debut' => $_GET['date_debut'] ?? date('Y-m-01'),   // 1er du mois par défaut
    'date_fin'   => $_GET['date_fin']   ?? date('Y-m-d'),
];

// Données analyses
$classement_vendeurs = $vendeurModel->classement();
$perf_secteurs       = $secteurModel->getAll();   // utilise la vue via modèle
$top_vendeurs_periode = $venteModel->topVendeursPeriode($f['date_debut'], $f['date_fin']);
$evolution_ca        = $venteModel->getAll($f, 500, 0);  // pour graphique

// Calculs KPI pour la période filtrée
$db = getDB();
$whereClauses = ['v.date_vente BETWEEN :deb AND :fin'];
$params = [':deb' => $f['date_debut'], ':fin' => $f['date_fin']];
if ($f['vendeur_id']) { $whereClauses[] = 'v.vendeur_id=:vid'; $params[':vid'] = $f['vendeur_id']; }
if ($f['secteur_id']) { $whereClauses[] = 'v.secteur_id=:sid'; $params[':sid'] = $f['secteur_id']; }
if ($f['ville_id'])   { $whereClauses[] = 'v.ville_id=:wid';   $params[':wid'] = $f['ville_id']; }
$whereStr = implode(' AND ', $whereClauses);

$kpi_stmt = $db->prepare("SELECT 
    COUNT(DISTINCT v.id) AS nb_ventes,
    COALESCE(SUM(v.montant_total),0) AS ca,
    COALESCE(SUM(vl.quantite),0) AS pieces,
    COUNT(DISTINCT v.client_id) AS nb_clients,
    COUNT(DISTINCT v.vendeur_id) AS nb_vendeurs_actifs
FROM ventes v
JOIN vente_lignes vl ON vl.vente_id = v.id
WHERE $whereStr");
$kpi_stmt->execute($params);
$kpi_periode = $kpi_stmt->fetch();

// Ventes jour par jour sur la période
$daily_stmt = $db->prepare("SELECT v.date_vente AS jour,
    SUM(v.montant_total) AS ca, SUM(vl.quantite) AS pieces, COUNT(v.id) AS nb
FROM ventes v JOIN vente_lignes vl ON vl.vente_id=v.id
WHERE $whereStr GROUP BY v.date_vente ORDER BY v.date_vente");
$daily_stmt->execute($params);
$daily_data = $daily_stmt->fetchAll();

// Perf par ville (période)
$ville_stmt = $db->prepare("SELECT vi.nom AS ville, s.nom AS secteur,
    SUM(v.montant_total) AS ca, SUM(vl.quantite) AS pieces, COUNT(v.id) AS nb_ventes
FROM ventes v JOIN villes vi ON vi.id=v.ville_id JOIN secteurs s ON s.id=v.secteur_id
JOIN vente_lignes vl ON vl.vente_id=v.id
WHERE $whereStr GROUP BY v.ville_id ORDER BY ca DESC LIMIT 10");
$ville_stmt->execute($params);
$perf_villes = $ville_stmt->fetchAll();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="page-content">

  <div class="page-header">
    <div>
      <h1 class="page-title">Analyses de performance</h1>
      <p class="page-subtitle">
        <?= format_date($f['date_debut']) ?> — <?= format_date($f['date_fin']) ?>
      </p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=comparaison" class="btn btn-outline">
      <i class="fas fa-balance-scale"></i> Comparer des périodes
    </a>
  </div>

  <!-- Filtres -->
  <div class="card mb-4 fade-up">
    <div class="card-body" style="padding:16px 20px;">
      <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="analyses">
        <div class="col-md-2">
          <label class="form-label">Du</label>
          <input type="date" name="date_debut" class="form-control" value="<?= e($f['date_debut']) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Au</label>
          <input type="date" name="date_fin" class="form-control" value="<?= e($f['date_fin']) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Vendeur</label>
          <select name="vendeur_id" class="form-control form-select">
            <option value="">Tous</option>
            <?php foreach ($vendeurs as $v): ?>
            <option value="<?= $v['id'] ?>" <?= $f['vendeur_id']==$v['id'] ? 'selected' : '' ?>>
              <?= e($v['prenom'].' '.$v['nom']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Secteur</label>
          <select name="secteur_id" class="form-control form-select">
            <option value="">Tous</option>
            <?php foreach ($secteurs as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $f['secteur_id']==$s['id'] ? 'selected' : '' ?>>
              <?= e($s['nom']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Ville</label>
          <select name="ville_id" class="form-control form-select">
            <option value="">Toutes</option>
            <?php foreach ($villes as $vl): ?>
            <option value="<?= $vl['id'] ?>" <?= $f['ville_id']==$vl['id'] ? 'selected' : '' ?>>
              <?= e($vl['nom']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-filter"></i> Analyser</button>
          <a href="?page=analyses" class="btn btn-outline"><i class="fas fa-undo"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- KPI période filtrée -->
  <div class="row g-3 mb-4">
    <div class="col-md-3 fade-up">
      <div class="kpi-card red">
        <div class="kpi-icon red"><i class="fas fa-shopping-cart"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= number_format($kpi_periode['nb_ventes']) ?></div>
          <div class="kpi-label">Ventes sur la période</div>
        </div>
      </div>
    </div>
    <div class="col-md-3 fade-up delay-1">
      <div class="kpi-card amber">
        <div class="kpi-icon amber"><i class="fas fa-tshirt"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= number_format($kpi_periode['pieces']) ?></div>
          <div class="kpi-label">Pièces vendues</div>
        </div>
      </div>
    </div>
    <div class="col-md-3 fade-up delay-2">
      <div class="kpi-card green">
        <div class="kpi-icon green"><i class="fas fa-coins"></i></div>
        <div class="kpi-info">
          <div class="kpi-value" style="font-size:1.05rem;"><?= format_money($kpi_periode['ca']) ?></div>
          <div class="kpi-label">Chiffre d'affaires</div>
        </div>
      </div>
    </div>
    <div class="col-md-3 fade-up delay-3">
      <div class="kpi-card blue">
        <div class="kpi-icon blue"><i class="fas fa-users"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= number_format($kpi_periode['nb_clients']) ?></div>
          <div class="kpi-label">Clients uniques</div>
          <div class="kpi-trend up">
            <i class="fas fa-user-tie"></i>
            <?= $kpi_periode['nb_vendeurs_actifs'] ?> vendeur(s) actif(s)
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Graphique CA journalier -->
  <div class="card mb-4 fade-up">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-chart-area"></i> CA & pièces vendues par jour</div>
    </div>
    <div class="card-body">
      <canvas id="chartDaily" height="90"></canvas>
    </div>
  </div>

  <div class="row g-4">
    <!-- Classement vendeurs -->
    <div class="col-xl-6 fade-up">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-trophy"></i> Classement vendeurs (période)</div>
        </div>
        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Vendeur</th>
                <th>Secteur</th>
                <th>Pièces</th>
                <th>CA</th>
                <th>Ventes</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($top_vendeurs_periode)): ?>
              <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:28px;">Aucune donnée</td></tr>
              <?php else: ?>
              <?php foreach ($top_vendeurs_periode as $i => $v): ?>
              <tr>
                <td>
                  <div class="rank-badge <?= $i < 3 ? 'rank-' . ($i+1) : 'rank-n' ?>">
                    <?= $i < 3 ? ['🥇','🥈','🥉'][$i] : ($i+1) ?>
                  </div>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar"><?= strtoupper(substr($v['vendeur'], 0, 1)) ?></div>
                    <strong><?= e($v['vendeur']) ?></strong>
                  </div>
                </td>
                <td><span style="font-size:.75rem;color:var(--text-muted);"><?= e($v['secteur']) ?></span></td>
                <td><?= number_format($v['pieces']) ?></td>
                <td><strong style="color:var(--primary);"><?= format_money($v['ca']) ?></strong></td>
                <td><?= $v['nb_ventes'] ?></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Performance par ville -->
    <div class="col-xl-6 fade-up delay-1">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-map-marked-alt"></i> Top villes (période)</div>
        </div>
        <div class="card-body" style="padding-top:14px;">
          <?php if (empty($perf_villes)): ?>
          <div style="text-align:center;color:var(--text-muted);padding:28px;">Aucune donnée</div>
          <?php else:
            $max_ville_ca = $perf_villes[0]['ca'] ?? 1;
          ?>
          <?php foreach ($perf_villes as $i => $vl): ?>
          <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
              <div>
                <span style="font-weight:700;font-size:.83rem;"><?= e($vl['ville']) ?></span>
                <span style="font-size:.72rem;color:var(--text-muted);margin-left:6px;"><?= e($vl['secteur']) ?></span>
              </div>
              <div style="text-align:right;">
                <span style="font-weight:700;font-size:.83rem;color:var(--primary);"><?= format_money($vl['ca']) ?></span>
                <span style="font-size:.7rem;color:var(--text-muted);display:block;"><?= number_format($vl['pieces']) ?> pcs</span>
              </div>
            </div>
            <div class="progress-bar-custom">
              <div class="progress-bar-fill"
                   style="width:<?= round(($vl['ca'] / $max_ville_ca) * 100) ?>%;
                          background:<?= ['#C0392B','#E74C3C','#E67E22','#F39C12','#27AE60','#2980B9','#8E44AD','#16A085','#D35400','#1ABC9C'][$i % 10] ?>;">
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Classement global vendeurs (toutes périodes) -->
  <div class="card mt-4 fade-up">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-medal"></i> Classement global — tous temps</div>
    </div>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>Rang</th>
            <th>Vendeur</th>
            <th>Secteur</th>
            <th>Total ventes</th>
            <th>Total pièces</th>
            <th>CA Total</th>
            <th>Performance</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $max_ca_global = $classement_vendeurs[0]['ca'] ?? 1;
          foreach ($classement_vendeurs as $i => $v):
          ?>
          <tr>
            <td>
              <div class="rank-badge <?= $i < 3 ? 'rank-' . ($i+1) : 'rank-n' ?>">
                <?= $i < 3 ? ['🥇','🥈','🥉'][$i] : ($i+1) ?>
              </div>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="avatar"><?= strtoupper(substr($v['vendeur'], 0, 1)) ?></div>
                <strong><?= e($v['vendeur']) ?></strong>
              </div>
            </td>
            <td><?= e($v['secteur'] ?? '—') ?></td>
            <td><?= number_format($v['nb_ventes'] ?? 0) ?></td>
            <td><?= number_format($v['pieces'] ?? 0) ?> pcs</td>
            <td><strong style="color:var(--primary);"><?= format_money($v['ca'] ?? 0) ?></strong></td>
            <td style="min-width:120px;">
              <?php $pct = $max_ca_global > 0 ? round(($v['ca'] / $max_ca_global) * 100) : 0; ?>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="progress-bar-custom" style="flex:1;">
                  <div class="progress-bar-fill"
                       style="width:<?= $pct ?>%;background:<?= $i === 0 ? '#FFD700' : 'var(--primary)' ?>;"></div>
                </div>
                <span style="font-size:.72rem;color:var(--text-muted);width:32px;"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /page-content -->
</div><!-- /main-content -->

<?php
$dailyLabels = json_encode(array_column($daily_data, 'jour'));
$dailyCa     = json_encode(array_map('floatval', array_column($daily_data, 'ca')));
$dailyPieces = json_encode(array_map('intval',   array_column($daily_data, 'pieces')));

$extra_scripts = <<<JS
<script>
new Chart(document.getElementById('chartDaily'), {
  type: 'bar',
  data: {
    labels: {$dailyLabels},
    datasets: [
      {
        label: 'CA (Ar)',
        data: {$dailyCa},
        backgroundColor: 'rgba(192,57,43,.7)',
        borderRadius: 6,
        yAxisID: 'y',
      },
      {
        label: 'Pièces',
        data: {$dailyPieces},
        type: 'line',
        borderColor: '#F39C12',
        backgroundColor: 'rgba(243,156,18,.1)',
        borderWidth: 2,
        tension: .4,
        fill: true,
        pointRadius: 3,
        yAxisID: 'y1',
      }
    ]
  },
  options: {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
    scales: {
      x:  { grid: { display: false }, ticks: { font: { size: 10 } } },
      y:  { position: 'left',  grid: { color: '#E8ECF4' }, ticks: { font: { size: 10 } } },
      y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { size: 10 } } }
    }
  }
});
</script>
JS;

include __DIR__ . '/../layouts/footer.php';
?>
