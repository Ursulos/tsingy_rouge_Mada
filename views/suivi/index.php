<?php
// ============================================================
// views/suivi/index.php
// Tableau de suivi hebdo/mensuel/annuel
// Reproduit la structure Excel : Semaine > Secteur > Vendeur
// Colonnes : Lundi-Sam + TOTAL + PDR + % + Reste + Clients
// ============================================================

$page_title = 'Suivi des ventes';

// ---- Paramètres de filtre ----
$mode     = $_GET['mode']   ?? 'semaine';   // semaine | mois | annee
$ref_date = $_GET['ref']    ?? date('Y-m-d'); // date de référence

// Calcul des bornes selon le mode
switch ($mode) {
    case 'semaine':
        // Lundi → Samedi de la semaine contenant ref_date
        $ts      = strtotime($ref_date);
        $dow     = (int)date('N', $ts); // 1=lundi … 7=dim
        $lundi   = date('Y-m-d', $ts - ($dow - 1) * 86400);
        $samedi  = date('Y-m-d', strtotime($lundi) + 5 * 86400);
        $debut   = $lundi;
        $fin     = $samedi;
        // Colonnes = jours Lun→Sam
        $cols = [];
        for ($d = 0; $d < 6; $d++) {
            $ts2 = strtotime($lundi) + $d * 86400;
            $cols[] = [
                'label' => ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'][$d],
                'date'  => date('Y-m-d', $ts2),
                'short' => date('d/m', $ts2),
            ];
        }
        $periode_label = 'Semaine du ' . date('d/m/Y', strtotime($lundi))
                       . ' au ' . date('d/m/Y', strtotime($samedi));
        $prev_ref = date('Y-m-d', strtotime($lundi) - 7 * 86400);
        $next_ref = date('Y-m-d', strtotime($lundi) + 7 * 86400);
        break;

    case 'mois':
        $annee  = date('Y', strtotime($ref_date));
        $mois_n = date('m', strtotime($ref_date));
        $debut  = "$annee-$mois_n-01";
        $fin    = date('Y-m-t', strtotime($debut));
        // Colonnes = semaines du mois
        $cols = [];
        $cur  = strtotime($debut);
        $end  = strtotime($fin);
        $week = 1;
        while ($cur <= $end) {
            $wend = min($end, strtotime(date('Y-m-d', $cur) . ' +6 days'));
            $cols[] = [
                'label' => 'S' . $week,
                'date'  => date('Y-m-d', $cur),
                'date_fin' => date('Y-m-d', $wend),
                'short' => date('d/m', $cur) . '-' . date('d/m', $wend),
            ];
            $cur = $wend + 86400;
            $week++;
        }
        $periode_label = date('F Y', strtotime($debut));
        $prev_ref = date('Y-m-d', strtotime("first day of previous month", strtotime($debut)));
        $next_ref = date('Y-m-d', strtotime("first day of next month",     strtotime($debut)));
        break;

    case 'annee':
        $annee = date('Y', strtotime($ref_date));
        $debut = "$annee-01-01";
        $fin   = "$annee-12-31";
        $cols  = [];
        $mois_noms = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'];
        for ($m = 1; $m <= 12; $m++) {
            $md  = sprintf('%04d-%02d-01', $annee, $m);
            $mdf = date('Y-m-t', strtotime($md));
            $cols[] = [
                'label'    => $mois_noms[$m-1],
                'date'     => $md,
                'date_fin' => $mdf,
                'short'    => $mois_noms[$m-1],
            ];
        }
        $periode_label = "Année $annee";
        $prev_ref = ($annee - 1) . '-01-01';
        $next_ref = ($annee + 1) . '-01-01';
        break;
}

// ---- Chargement des données ----
$db = getDB();

// Secteurs + vendeurs
$secteurs_stmt = $db->query(
    "SELECT s.id AS sid, s.nom AS secteur,
            vd.id AS vid, CONCAT(vd.prenom,' ',vd.nom) AS vendeur,
            vd.photo
     FROM secteurs s
     JOIN vendeurs vd ON vd.secteur_id = s.id AND vd.statut = 'actif'
     ORDER BY s.nom, vd.nom"
);
$rows = $secteurs_stmt->fetchAll();

// Grouper : secteur → vendeurs
$tree = [];
foreach ($rows as $r) {
    $tree[$r['sid']]['nom'] = $r['secteur'];
    $tree[$r['sid']]['vendeurs'][$r['vid']] = [
        'nom'   => $r['vendeur'],
        'photo' => $r['photo'],
    ];
}

// Ventes par vendeur + colonne
// Pour semaine : colonne = date exacte
// Pour mois/annee : colonne = semaine/mois contenant la date
function getColKey(array $col): string {
    return $col['date'];
}

// Charger toutes les ventes de la période avec détail vendeur + clients
$ventes_stmt = $db->prepare(
    "SELECT
        v.vendeur_id,
        v.date_vente,
        SUM(vl.quantite) AS pieces,
        SUM(v.montant_total) AS ca,
        GROUP_CONCAT(DISTINCT CONCAT(c.prenom,' ',c.nom) ORDER BY c.nom SEPARATOR ', ') AS clients_liste
     FROM ventes v
     JOIN vente_lignes vl ON vl.vente_id = v.id
     JOIN clients c ON c.id = v.client_id
     WHERE v.date_vente BETWEEN :debut AND :fin
     GROUP BY v.vendeur_id, v.date_vente"
);
$ventes_stmt->execute([':debut' => $debut, ':fin' => $fin]);
$ventes_raw = $ventes_stmt->fetchAll();

// Indexer par vendeur_id → col_key → données
$ventes_idx = [];
foreach ($ventes_raw as $vr) {
    $vid  = $vr['vendeur_id'];
    $dkey = $vr['date_vente'];

    // Pour mois/annee : trouver la colonne correspondante
    if ($mode !== 'semaine') {
        foreach ($cols as $col) {
            $col_fin = $col['date_fin'] ?? $col['date'];
            if ($dkey >= $col['date'] && $dkey <= $col_fin) {
                $dkey = $col['date'];
                break;
            }
        }
    }

    if (!isset($ventes_idx[$vid][$dkey])) {
        $ventes_idx[$vid][$dkey] = ['pieces' => 0, 'ca' => 0, 'clients' => []];
    }
    $ventes_idx[$vid][$dkey]['pieces'] += $vr['pieces'];
    $ventes_idx[$vid][$dkey]['ca']     += $vr['ca'];
    // Clients uniques
    foreach (explode(', ', $vr['clients_liste']) as $cl) {
        $cl = trim($cl);
        if ($cl && !in_array($cl, $ventes_idx[$vid][$dkey]['clients'])) {
            $ventes_idx[$vid][$dkey]['clients'][] = $cl;
        }
    }
}

// PDR par vendeur (objectif pièces sur la période)
$pdr_stmt = $db->prepare(
    "SELECT cible_id AS vendeur_id,
            SUM(objectif_pieces) AS obj_pieces,
            SUM(objectif_ca)     AS obj_ca
     FROM pdr_objectifs
     WHERE type_cible = 'vendeur'
       AND date_debut <= :fin
       AND date_fin   >= :debut
     GROUP BY cible_id"
);
$pdr_stmt->execute([':debut' => $debut, ':fin' => $fin]);
$pdr_idx = [];
foreach ($pdr_stmt->fetchAll() as $p) {
    $pdr_idx[$p['vendeur_id']] = [
        'pieces' => floatval($p['obj_pieces']),
        'ca'     => floatval($p['obj_ca']),
    ];
}

// Totaux globaux
$grand_total_pieces = 0;
$grand_obj_pieces   = 0;

// Affichage : pièces ou CA ?
$metric = $_GET['metric'] ?? 'pieces'; // pieces | ca

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
/* ---- Styles spécifiques au tableau de suivi ---- */
.suivi-table { font-size:.78rem; border-collapse:collapse; width:100%; min-width:900px; }
.suivi-table th, .suivi-table td {
    border: 1px solid var(--border);
    padding: 6px 8px;
    vertical-align: middle;
    white-space: nowrap;
}
.suivi-table thead th {
    background: #1A1D2E;
    color: #fff;
    font-weight: 700;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    text-align: center;
    position: sticky;
    top: 0;
    z-index: 10;
}
.suivi-table thead th.col-secteur { background: var(--primary); }
.suivi-table .row-secteur td {
    background: linear-gradient(135deg, #1A1D2E, #12141F);
    color: #fff;
    font-weight: 800;
    font-size: .85rem;
    letter-spacing: .03em;
}
.suivi-table .row-vendeur td { background: var(--surface); }
.suivi-table .row-vendeur:hover td { background: var(--surface-2); }
.suivi-table .row-total-secteur td {
    background: var(--primary-soft);
    font-weight: 700;
    color: var(--primary);
    font-size: .8rem;
}
.suivi-table .row-grand-total td {
    background: #1A1D2E;
    color: #fff;
    font-weight: 800;
    font-size: .85rem;
}
.suivi-table .row-objectif td  { background: #EAF4FB; font-weight:700; color:var(--info); }
.suivi-table .row-realise td   { background: #EAF9ED; font-weight:700; color:var(--success); }
.suivi-table .row-ecart td     { background: #FFF3E0; font-weight:700; color:var(--warning); }
.suivi-table .row-pct td       { background: #F9F0FF; font-weight:700; color:#8E44AD; }

.cell-val { text-align: right; font-weight: 600; }
.cell-zero { color: var(--text-muted); text-align:right; }

.pdr-col   { background: rgba(41,128,185,.06) !important; }
.pct-col   { text-align:center !important; }
.reste-col { background: rgba(192,57,43,.04) !important; }

.taux-ok   { color: var(--success); font-weight:800; }
.taux-mid  { color: var(--warning); font-weight:800; }
.taux-bad  { color: var(--primary); font-weight:800; }

.clients-cell {
    max-width: 160px;
    white-space: normal;
    font-size: .68rem;
    color: var(--text-muted);
    line-height: 1.4;
}
.clients-badge {
    display:inline-block;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 1px 5px;
    margin: 1px 1px;
    font-size:.65rem;
    color: var(--text-secondary);
}

.mode-btn { border-radius:8px; padding:6px 16px; font-size:.8rem; font-weight:600; cursor:pointer; border:1.5px solid var(--border-2); background:var(--surface); color:var(--text-secondary); transition:var(--transition); }
.mode-btn.active { background:var(--primary); color:#fff; border-color:var(--primary); }
.mode-btn:hover:not(.active) { background:var(--surface-2); }

.nav-week { display:flex; align-items:center; gap:8px; }
.nav-btn  { width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border-2);background:var(--surface);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem;color:var(--text-secondary);transition:var(--transition);text-decoration:none; }
.nav-btn:hover { background:var(--primary);color:#fff;border-color:var(--primary); }
</style>

<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>

<div class="page-content">

  <!-- En-tête -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Suivi des ventes</h1>
      <p class="page-subtitle"><?= $periode_label ?></p>
    </div>
    <!-- Boutons export -->
    <div class="d-flex gap-2">
      <a href="?page=suivi&mode=<?= $mode ?>&ref=<?= $ref_date ?>&export=csv"
         class="btn btn-outline btn-sm"><i class="fas fa-file-csv"></i> CSV</a>
    </div>
  </div>

  <!-- ====================================================
       BARRE DE CONTRÔLE
       ==================================================== -->
  <div class="card mb-4 fade-up">
    <div class="card-body" style="padding:14px 20px;">
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">

        <!-- Mode -->
        <div style="display:flex;gap:6px;">
          <?php foreach (['semaine'=>'Semaine','mois'=>'Mois','annee'=>'Année'] as $m => $lbl): ?>
          <a href="?page=suivi&mode=<?= $m ?>&ref=<?= $ref_date ?>&metric=<?= $metric ?>"
             class="mode-btn <?= $mode===$m?'active':'' ?>"><?= $lbl ?></a>
          <?php endforeach; ?>
        </div>

        <!-- Navigation période -->
        <div class="nav-week">
          <a href="?page=suivi&mode=<?= $mode ?>&ref=<?= $prev_ref ?>&metric=<?= $metric ?>"
             class="nav-btn" title="Période précédente"><i class="fas fa-chevron-left"></i></a>
          <input type="date" id="refDate" value="<?= $ref_date ?>"
                 onchange="window.location='?page=suivi&mode=<?= $mode ?>&ref='+this.value+'&metric=<?= $metric ?>'"
                 style="padding:5px 10px;border:1.5px solid var(--border-2);border-radius:8px;font-size:.8rem;font-family:inherit;">
          <a href="?page=suivi&mode=<?= $mode ?>&ref=<?= $next_ref ?>&metric=<?= $metric ?>"
             class="nav-btn" title="Période suivante"><i class="fas fa-chevron-right"></i></a>
          <a href="?page=suivi&mode=<?= $mode ?>&ref=<?= date('Y-m-d') ?>&metric=<?= $metric ?>"
             class="mode-btn" style="font-size:.75rem;">Aujourd'hui</a>
        </div>

        <!-- Métrique -->
        <div style="display:flex;gap:6px;margin-left:auto;">
          <a href="?page=suivi&mode=<?= $mode ?>&ref=<?= $ref_date ?>&metric=pieces"
             class="mode-btn <?= $metric==='pieces'?'active':'' ?>">
            <i class="fas fa-tshirt"></i> Pièces
          </a>
          <a href="?page=suivi&mode=<?= $mode ?>&ref=<?= $ref_date ?>&metric=ca"
             class="mode-btn <?= $metric==='ca'?'active':'' ?>">
            <i class="fas fa-coins"></i> CA (Ar)
          </a>
        </div>

      </div>
    </div>
  </div>

  <!-- ====================================================
       TABLEAU PRINCIPAL
       ==================================================== -->
  <div class="card fade-up" style="overflow:hidden;">
    <div style="overflow-x:auto;">
    <table class="suivi-table">
      <thead>
        <tr>
          <th style="width:90px;">Secteur</th>
          <th style="width:140px;">Vendeur</th>
          <?php foreach ($cols as $col): ?>
          <th style="min-width:70px;">
            <?= e($col['label']) ?>
            <?php if (isset($col['short'])): ?>
            <div style="font-size:.62rem;font-weight:400;opacity:.7;"><?= e($col['short']) ?></div>
            <?php endif; ?>
          </th>
          <?php endforeach; ?>
          <th style="background:#C0392B;min-width:75px;">TOTAL</th>
          <th class="pdr-col" style="min-width:75px;">PDR</th>
          <th class="pct-col" style="min-width:65px;">%</th>
          <th class="reste-col" style="min-width:80px;">Reste à faire</th>
          <th style="min-width:160px;white-space:normal;">Clients</th>
        </tr>
      </thead>
      <tbody>
      <?php
      // Totaux globaux pour le grand total
      $gt_cols   = array_fill_keys(array_column($cols,'date'), 0);
      $gt_total  = 0;
      $gt_obj    = 0;
      $gt_clients_all = [];

      foreach ($tree as $sid => $secteur):
        $secteur_cols   = array_fill_keys(array_column($cols,'date'), 0);
        $secteur_total  = 0;
        $secteur_obj    = 0;
        $secteur_clients= [];
        ?>

        <!-- Ligne SECTEUR -->
        <tr class="row-secteur">
          <td colspan="2" style="font-size:.85rem;">
            <i class="fas fa-map-marker-alt" style="margin-right:6px;color:var(--primary-light);"></i>
            <?= e($secteur['nom']) ?>
          </td>
          <?php foreach ($cols as $col): ?>
          <td></td>
          <?php endforeach; ?>
          <td></td><td class="pdr-col"></td><td class="pct-col"></td>
          <td class="reste-col"></td><td></td>
        </tr>

        <?php foreach ($secteur['vendeurs'] as $vid => $vendeur):
          $v_total  = 0;
          $v_obj    = $pdr_idx[$vid] ?? ['pieces'=>0,'ca'=>0];
          $v_obj_val= $metric === 'ca' ? $v_obj['ca'] : $v_obj['pieces'];
          $v_clients_all = [];
        ?>
        <tr class="row-vendeur">
          <td></td>
          <td>
            <div style="display:flex;align-items:center;gap:7px;">
              <div class="avatar" style="width:26px;height:26px;font-size:.65rem;flex-shrink:0;">
                <?= strtoupper(substr($vendeur['nom'],0,1)) ?>
              </div>
              <span style="font-weight:600;font-size:.78rem;"><?= e($vendeur['nom']) ?></span>
            </div>
          </td>

          <?php foreach ($cols as $col):
            $ckey = $col['date'];
            $val  = $ventes_idx[$vid][$ckey][$metric] ?? 0;
            $clients_col = $ventes_idx[$vid][$ckey]['clients'] ?? [];
            $v_total += $val;
            $secteur_cols[$ckey] += $val;
            $v_clients_all = array_unique(array_merge($v_clients_all, $clients_col));
          ?>
          <td class="<?= $val > 0 ? 'cell-val' : 'cell-zero' ?>">
            <?php if ($val > 0): ?>
              <?= $metric==='ca' ? number_format($val,0,',',' ') : number_format($val) ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <?php endforeach; ?>

          <?php
          $secteur_total += $v_total;
          $secteur_obj   += $v_obj_val;
          $secteur_clients = array_unique(array_merge($secteur_clients, $v_clients_all));
          $gt_total += $v_total;
          $gt_obj   += $v_obj_val;
          $gt_clients_all = array_unique(array_merge($gt_clients_all, $v_clients_all));

          // Taux
          $taux = $v_obj_val > 0 ? round(($v_total / $v_obj_val) * 100, 1) : null;
          $reste= $v_obj_val > 0 ? max(0, $v_obj_val - $v_total) : 0;
          $taux_class = $taux === null ? '' : ($taux >= 100 ? 'taux-ok' : ($taux >= 70 ? 'taux-mid' : 'taux-bad'));
          ?>
          <!-- TOTAL vendeur -->
          <td class="cell-val" style="background:rgba(192,57,43,.06);font-weight:800;color:var(--primary);">
            <?= $metric==='ca' ? number_format($v_total,0,',',' ') : number_format($v_total) ?>
          </td>
          <!-- PDR -->
          <td class="pdr-col cell-val" style="color:var(--info);">
            <?= $v_obj_val > 0 ? ($metric==='ca' ? number_format($v_obj_val,0,',',' ') : number_format($v_obj_val)) : '—' ?>
          </td>
          <!-- % -->
          <td class="pct-col">
            <?php if ($taux !== null): ?>
            <span class="<?= $taux_class ?>">
              <?= $taux ?>%
              <?= $taux >= 100 ? ' ✅' : '' ?>
            </span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <!-- Reste à faire -->
          <td class="reste-col" style="text-align:right;color:<?= $reste>0?'var(--primary)':'var(--success)' ?>;">
            <?php if ($v_obj_val > 0): ?>
              <?php if ($v_total >= $v_obj_val): ?>
                <span style="color:var(--success);font-weight:700;">+<?= number_format($v_total - $v_obj_val) ?></span>
              <?php else: ?>
                <?= $metric==='ca' ? number_format($reste,0,',',' ') : number_format($reste) ?>
              <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <!-- Clients -->
          <td class="clients-cell">
            <?php foreach (array_slice($v_clients_all, 0, 5) as $cl): ?>
            <span class="clients-badge"><?= e($cl) ?></span>
            <?php endforeach; ?>
            <?php if (count($v_clients_all) > 5): ?>
            <span style="font-size:.63rem;color:var(--text-muted);">+<?= count($v_clients_all)-5 ?> autres</span>
            <?php endif; ?>
            <?php if (empty($v_clients_all)): ?>
            <span style="color:var(--border-2);font-size:.68rem;">— aucun achat</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; // vendeurs ?>

        <!-- Ligne TOTAL SECTEUR -->
        <?php
        $s_taux  = $secteur_obj > 0 ? round(($secteur_total / $secteur_obj) * 100, 1) : null;
        $s_reste = $secteur_obj > 0 ? max(0, $secteur_obj - $secteur_total) : 0;
        foreach ($cols as $col) { $gt_cols[$col['date']] += $secteur_cols[$col['date']]; }
        ?>
        <tr class="row-total-secteur">
          <td colspan="2" style="font-size:.78rem;">
            <i class="fas fa-sigma" style="margin-right:4px;"></i> Total <?= e($secteur['nom']) ?>
          </td>
          <?php foreach ($cols as $col): ?>
          <td class="cell-val"><?= $secteur_cols[$col['date']] > 0 ? number_format($secteur_cols[$col['date']]) : '—' ?></td>
          <?php endforeach; ?>
          <td class="cell-val" style="color:var(--primary);font-weight:800;"><?= number_format($secteur_total) ?></td>
          <td class="pdr-col cell-val"><?= $secteur_obj > 0 ? number_format($secteur_obj) : '—' ?></td>
          <td class="pct-col">
            <?php if ($s_taux !== null): ?>
            <span class="<?= $s_taux>=100?'taux-ok':($s_taux>=70?'taux-mid':'taux-bad') ?>"><?= $s_taux ?>%</span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="reste-col" style="text-align:right;">
            <?php if ($secteur_obj > 0): ?>
              <?php if ($secteur_total >= $secteur_obj): ?>
              <span style="color:var(--success);font-weight:700;">+<?= number_format($secteur_total - $secteur_obj) ?></span>
              <?php else: ?><?= number_format($s_reste) ?><?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="clients-cell" style="font-size:.68rem;color:var(--primary);">
            <?= count($secteur_clients) ?> client(s) unique(s)
          </td>
        </tr>

      <?php endforeach; // secteurs ?>

      <!-- ====================================================
           GRAND TOTAL
           ==================================================== -->
      <?php
      $gt_taux  = $gt_obj > 0 ? round(($gt_total / $gt_obj) * 100, 1) : null;
      $gt_reste = $gt_obj > 0 ? max(0, $gt_obj - $gt_total) : 0;
      ?>
      <tr class="row-grand-total">
        <td colspan="2">
          <i class="fas fa-chart-bar" style="margin-right:6px;color:var(--accent);"></i>
          GRAND TOTAL
        </td>
        <?php foreach ($cols as $col): ?>
        <td class="cell-val" style="color:#fff;"><?= $gt_cols[$col['date']] > 0 ? number_format($gt_cols[$col['date']]) : '—' ?></td>
        <?php endforeach; ?>
        <td class="cell-val" style="color:var(--accent);font-size:.95rem;"><?= number_format($gt_total) ?></td>
        <td class="pdr-col cell-val" style="color:#81CFE0;"><?= $gt_obj > 0 ? number_format($gt_obj) : '—' ?></td>
        <td class="pct-col">
          <?php if ($gt_taux !== null): ?>
          <span style="font-weight:800;color:<?= $gt_taux>=100?'#2ECC71':($gt_taux>=70?'#F39C12':'#E74C3C') ?>;">
            <?= $gt_taux ?>%
          </span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td class="reste-col" style="text-align:right;color:<?= $gt_reste>0?'#E74C3C':'#2ECC71' ?>;">
          <?php if ($gt_obj > 0): ?>
            <?= $gt_total >= $gt_obj ? '+'.number_format($gt_total-$gt_obj) : number_format($gt_reste) ?>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td style="color:#aaa;font-size:.72rem;"><?= count($gt_clients_all) ?> clients uniques</td>
      </tr>

      <!-- ====================================================
           LIGNES RÉSUMÉ : Objectif / Réalisation / Écart / %
           ==================================================== -->
      <tr><td colspan="<?= count($cols)+9 ?>" style="height:12px;background:var(--bg);border:none;"></td></tr>

      <tr class="row-objectif">
        <td colspan="2"><i class="fas fa-bullseye"></i> Objectif (PDR)</td>
        <?php foreach ($cols as $col): ?><td class="cell-val">—</td><?php endforeach; ?>
        <td class="cell-val"><?= $gt_obj > 0 ? number_format($gt_obj) : '—' ?></td>
        <td colspan="4"></td>
      </tr>
      <tr class="row-realise">
        <td colspan="2"><i class="fas fa-check-circle"></i> Réalisation</td>
        <?php foreach ($cols as $col): ?>
        <td class="cell-val"><?= $gt_cols[$col['date']] > 0 ? number_format($gt_cols[$col['date']]) : '—' ?></td>
        <?php endforeach; ?>
        <td class="cell-val"><?= number_format($gt_total) ?></td>
        <td colspan="4"></td>
      </tr>
      <tr class="row-ecart">
        <td colspan="2"><i class="fas fa-arrows-alt-v"></i> Écart</td>
        <?php foreach ($cols as $col): ?><td class="cell-val">—</td><?php endforeach; ?>
        <td class="cell-val">
          <?php $ecart = $gt_total - $gt_obj; ?>
          <span style="color:<?= $ecart >= 0 ? 'var(--success)' : 'var(--primary)' ?>;">
            <?= ($ecart >= 0 ? '+' : '') . number_format($ecart) ?>
          </span>
        </td>
        <td colspan="4"></td>
      </tr>
      <tr class="row-pct">
        <td colspan="2"><i class="fas fa-percent"></i> Taux d'atteinte</td>
        <?php foreach ($cols as $col): ?><td class="pct-col">—</td><?php endforeach; ?>
        <td class="pct-col">
          <?php if ($gt_taux !== null): ?>
          <span style="font-size:1rem;font-weight:900;color:<?= $gt_taux>=100?'#27AE60':($gt_taux>=70?'#E67E22':'#C0392B') ?>;">
            <?= $gt_taux ?>%
          </span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td colspan="4"></td>
      </tr>

      </tbody>
    </table>
    </div>
  </div>

  <!-- Légende -->
  <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:16px;font-size:.74rem;color:var(--text-muted);">
    <span><span class="taux-ok">■</span> ≥100% : Objectif atteint</span>
    <span><span class="taux-mid">■</span> ≥70% : En bonne voie</span>
    <span><span class="taux-bad">■</span> &lt;70% : En retard</span>
    <span style="margin-left:12px;"><i class="fas fa-info-circle"></i> Colonne Clients : acheteurs actifs sur la période</span>
  </div>

</div><!-- /page-content -->
</div><!-- /main-content -->

<?php
// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="suivi_ventes_' . $mode . '_' . $ref_date . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

    // En-tête
    $header = ['Secteur','Vendeur'];
    foreach ($cols as $col) $header[] = $col['label'];
    $header = array_merge($header, ['TOTAL','PDR','%','Reste','Clients']);
    fputcsv($out, $header, ';');

    foreach ($tree as $sid => $secteur) {
        foreach ($secteur['vendeurs'] as $vid => $vendeur) {
            $row = [$secteur['nom'], $vendeur['nom']];
            $v_total = 0;
            foreach ($cols as $col) {
                $val = $ventes_idx[$vid][$col['date']][$metric] ?? 0;
                $row[] = $val;
                $v_total += $val;
            }
            $v_obj_val = $metric==='ca' ? ($pdr_idx[$vid]['ca']??0) : ($pdr_idx[$vid]['pieces']??0);
            $taux  = $v_obj_val > 0 ? round(($v_total/$v_obj_val)*100,1) : '';
            $reste = $v_obj_val > 0 ? max(0,$v_obj_val-$v_total) : '';
            $v_clients = [];
            foreach ($cols as $col) {
                $v_clients = array_unique(array_merge($v_clients, $ventes_idx[$vid][$col['date']]['clients']??[]));
            }
            $row[] = $v_total;
            $row[] = $v_obj_val ?: '';
            $row[] = $taux !== '' ? $taux.'%' : '';
            $row[] = $reste;
            $row[] = implode(', ', $v_clients);
            fputcsv($out, $row, ';');
        }
    }
    fclose($out);
    exit;
}

$extra_scripts = '<script>// Suivi chargé</script>';
include __DIR__ . '/../layouts/footer.php';
?>
