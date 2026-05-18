<?php
// views/dashboard/index.php — reçoit les variables de DashboardController
include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="page-content">

  <!-- En-tête -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Tableau de bord</h1>
      <p class="page-subtitle">Vue d'ensemble · <?= date('F Y') ?></p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= APP_URL ?>/index.php?page=ventes&action=create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nouvelle vente
      </a>
      <a href="<?= APP_URL ?>/index.php?page=comparaison" class="btn btn-outline">
        <i class="fas fa-balance-scale"></i> Comparer
      </a>
    </div>
  </div>

  <?php render_flash(); ?>

  <!-- KPI CARDS -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 fade-up delay-1">
      <div class="kpi-card red">
        <div class="kpi-icon red"><i class="fas fa-shopping-cart"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= number_format($kpis['total_ventes']) ?></div>
          <div class="kpi-label">Total ventes</div>
          <div class="kpi-trend up">
            <i class="fas fa-arrow-up"></i> <?= number_format($kpis['ventes_mois']) ?> ce mois
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6 fade-up delay-2">
      <div class="kpi-card amber">
        <div class="kpi-icon amber"><i class="fas fa-tshirt"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= number_format($kpis['total_pieces']) ?></div>
          <div class="kpi-label">Pièces vendues</div>
          <div class="kpi-trend up"><i class="fas fa-boxes"></i> Total cumulé</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6 fade-up delay-3">
      <div class="kpi-card green">
        <div class="kpi-icon green"><i class="fas fa-coins"></i></div>
        <div class="kpi-info">
          <div class="kpi-value" style="font-size:1.05rem;"><?= format_money($kpis['total_ca']) ?></div>
          <div class="kpi-label">CA Total</div>
          <div class="kpi-trend up"><i class="fas fa-calendar"></i> <?= format_money($kpis['ca_mois']) ?> ce mois</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6 fade-up delay-4">
      <div class="kpi-card blue">
        <div class="kpi-icon blue"><i class="fas fa-users"></i></div>
        <div class="kpi-info">
          <div class="kpi-value"><?= number_format($kpis['nb_clients']) ?></div>
          <div class="kpi-label">Clients</div>
          <div class="kpi-trend up"><i class="fas fa-user-tie"></i> <?= $kpis['nb_vendeurs'] ?> vendeurs actifs</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Meilleur vendeur / secteur / stock -->
  <div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6 fade-up">
      <div class="card h-100">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-trophy"></i> Meilleur vendeur</div>
          <span class="badge-status top">Ce mois</span>
        </div>
        <div class="card-body text-center py-4">
          <?php if ($meilleur_vendeur): ?>
            <div class="avatar mx-auto mb-3" style="width:56px;height:56px;font-size:1.2rem;">
              <?= strtoupper(substr($meilleur_vendeur['nom'], 0, 1)) ?>
            </div>
            <div class="fw-bold fs-6"><?= e($meilleur_vendeur['nom']) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted);margin:4px 0 12px;"><?= $meilleur_vendeur['nb_ventes'] ?> vente(s)</div>
            <div style="font-size:1.2rem;font-weight:800;color:var(--primary);"><?= format_money($meilleur_vendeur['ca']) ?></div>
          <?php else: ?><div style="color:var(--text-muted);font-size:.85rem;">Aucune vente ce mois</div><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-md-6 fade-up delay-1">
      <div class="card h-100">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-map-marked-alt"></i> Meilleur secteur</div>
          <span class="badge-status top">Ce mois</span>
        </div>
        <div class="card-body text-center py-4">
          <?php if ($meilleur_secteur): ?>
            <div style="width:56px;height:56px;background:var(--success-soft);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:var(--success);font-size:1.3rem;">
              <i class="fas fa-map"></i>
            </div>
            <div class="fw-bold fs-6"><?= e($meilleur_secteur['nom']) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted);margin:4px 0 12px;"><?= $meilleur_secteur['nb_ventes'] ?> vente(s)</div>
            <div style="font-size:1.2rem;font-weight:800;color:var(--success);"><?= format_money($meilleur_secteur['ca']) ?></div>
          <?php else: ?><div style="color:var(--text-muted);font-size:.85rem;">Aucune donnée</div><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-xl-4 fade-up delay-2">
      <div class="card h-100">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-exclamation-triangle" style="color:var(--warning);"></i> Stock faible</div>
          <?php if ($stock_faible): ?><span class="badge-status alerte"><?= count($stock_faible) ?> produit(s)</span><?php endif; ?>
        </div>
        <div class="card-body" style="padding:12px 16px;">
          <?php if (empty($stock_faible)): ?>
            <div style="text-align:center;padding:20px;color:var(--success);">
              <i class="fas fa-check-circle" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>Stocks OK
            </div>
          <?php else: ?>
            <?php foreach (array_slice($stock_faible, 0, 5) as $prod): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:var(--surface-2);border-radius:8px;margin-bottom:6px;">
              <div style="font-size:.8rem;font-weight:600;"><?= e($prod['nom']) ?></div>
              <div style="font-size:.78rem;color:<?= $prod['stock']==0?'var(--primary)':'var(--warning)' ?>;font-weight:700;"><?= $prod['stock'] ?>/<?= $prod['stock_min'] ?></div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Graphiques -->
  <div class="row g-3 mb-4">
    <div class="col-xl-8 fade-up">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-chart-line"></i> Évolution CA (12 mois)</div>
          <a href="<?= APP_URL ?>/index.php?page=analyses" class="btn btn-outline btn-sm">Détails</a>
        </div>
        <div class="card-body"><canvas id="chartCaEvolution" height="100"></canvas></div>
      </div>
    </div>
    <div class="col-xl-4 fade-up delay-1">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Par secteur</div></div>
        <div class="card-body"><canvas id="chartSecteurs" height="160"></canvas></div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-xl-7 fade-up">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Ventes par vendeur</div></div>
        <div class="card-body"><canvas id="chartVendeurs" height="130"></canvas></div>
      </div>
    </div>
    <div class="col-xl-5 fade-up delay-1">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-star"></i> Top produits</div></div>
        <div class="card-body" style="padding-top:12px;">
          <?php foreach (array_slice($top_produits, 0, 6) as $i => $prod):
            $max = $top_produits[0]['total_vendu'] ?? 1; ?>
            <div style="margin-bottom:12px;">
              <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:.78rem;font-weight:600;"><?= e($prod['nom']) ?></span>
                <span style="font-size:.78rem;color:var(--text-muted);"><?= number_format($prod['total_vendu']) ?> pcs</span>
              </div>
              <div class="progress-bar-custom">
                <div class="progress-bar-fill" style="width:<?= round(($prod['total_vendu']/$max)*100) ?>%;background:<?= ['var(--primary)','var(--accent)','var(--success)','var(--info)','var(--warning)','var(--primary-light)'][$i%6] ?>;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Top clients VIP -->
  <?php if (!empty($top_clients)): ?>
  <div class="card mb-4 fade-up">
    <div class="card-header" style="background:linear-gradient(135deg,#FFD700,#F39C12);">
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
            <td><strong><?= e($c['client_nom']) ?></strong></td>
            <td><?= e($c['telephone']??'—') ?></td>
            <td><?= e($c['secteur']??'—') ?></td>
            <td><span style="font-weight:800;color:var(--accent);"><?= number_format($c['total_pieces']) ?></span> pcs</td>
            <td><strong><?= format_money($c['total_ca']) ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Ventes récentes -->
  <div class="card fade-up">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-history"></i> Ventes récentes</div>
      <a href="<?= APP_URL ?>/index.php?page=ventes" class="btn btn-outline btn-sm">Tout voir</a>
    </div>
    <div class="table-container">
      <table class="table">
        <thead><tr><th>Référence</th><th>Date</th><th>Client</th><th>Vendeur</th><th>Secteur</th><th>Pièces</th><th>Montant</th></tr></thead>
        <tbody>
          <?php if (empty($ventes_recentes)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:32px;">Aucune vente enregistrée</td></tr>
          <?php else: foreach ($ventes_recentes as $v): ?>
          <tr>
            <td><span style="font-family:monospace;font-size:.76rem;background:var(--surface-2);padding:2px 8px;border-radius:4px;"><?= e($v['reference']) ?></span></td>
            <td><?= format_date($v['date_vente']) ?></td>
            <td><?= e($v['client']) ?></td>
            <td><?= e($v['vendeur']) ?></td>
            <td><span class="badge-status actif"><?= e($v['secteur']) ?></span></td>
            <td><?= number_format($v['total_pieces']??0) ?> pcs</td>
            <td><strong><?= format_money($v['montant_total']) ?></strong></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /page-content -->
</div><!-- /main-content -->

<?php
$caLabels  = json_encode(array_column($ca_evolution,    'mois'));
$caValues  = json_encode(array_map('floatval', array_column($ca_evolution, 'ca')));
$secLabels = json_encode(array_column($ventes_secteur,  'secteur'));
$secValues = json_encode(array_map('floatval', array_column($ventes_secteur, 'ca')));
$vdLabels  = json_encode(array_column($ventes_vendeur,  'vendeur'));
$vdValues  = json_encode(array_map('floatval', array_column($ventes_vendeur, 'ca')));

$extra_scripts = <<<JS
<script>
const COLORS = ['#C0392B','#E74C3C','#E67E22','#F39C12','#27AE60','#2980B9','#8E44AD','#16A085'];
new Chart(document.getElementById('chartCaEvolution'), {
  type:'line',
  data:{ labels:{$caLabels}, datasets:[{ label:'CA (Ar)', data:{$caValues}, borderColor:'#C0392B', backgroundColor:'rgba(192,57,43,.08)', borderWidth:2.5, tension:.4, fill:true, pointRadius:4, pointBackgroundColor:'#C0392B' }] },
  options:{ responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{grid:{display:false}}, y:{grid:{color:'#E8ECF4'}} } }
});
new Chart(document.getElementById('chartSecteurs'), {
  type:'doughnut',
  data:{ labels:{$secLabels}, datasets:[{ data:{$secValues}, backgroundColor:COLORS, borderWidth:0, hoverOffset:6 }] },
  options:{ responsive:true, cutout:'65%', plugins:{ legend:{position:'bottom',labels:{font:{size:11},padding:12}} } }
});
new Chart(document.getElementById('chartVendeurs'), {
  type:'bar',
  data:{ labels:{$vdLabels}, datasets:[{ label:'CA (Ar)', data:{$vdValues}, backgroundColor:COLORS, borderRadius:8, borderSkipped:false }] },
  options:{ responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{grid:{display:false}}, y:{grid:{color:'#E8ECF4'}} } }
});
</script>
JS;
include __DIR__ . '/../layouts/footer.php';
?>
