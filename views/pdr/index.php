<?php
// views/pdr/index.php
if (!function_exists('pdr_taux_color')) {
    function pdr_taux_color(float $taux): string {
        if ($taux >= 100) return '#27AE60';
        if ($taux >= 70)  return '#F39C12';
        return '#C0392B';
    }
    function pdr_statut_label(string $s): array {
        return match($s) {
            'en_cours'    => ['En cours',      'actif'],
            'atteint'     => ['✅ Atteint',    'actif'],
            'non_atteint' => ['❌ Non atteint','alerte'],
            'a_venir'     => ['À venir',       'inactif'],
            default       => [$s,              'inactif'],
        };
    }
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>
<style>
.multiselect-box {
    border: 1.5px solid var(--border-2);
    border-radius: var(--radius-sm);
    max-height: 180px;
    overflow-y: auto;
    background: var(--surface);
}
.multiselect-box label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    font-size: .82rem;
    cursor: pointer;
    border-bottom: 1px solid var(--border);
    transition: background .15s;
}
.multiselect-box label:last-child { border-bottom: none; }
.multiselect-box label:hover { background: var(--surface-2); }
.multiselect-box input[type=checkbox] { accent-color: var(--primary); width:15px; height:15px; flex-shrink:0; }
.select-all-btn { font-size:.72rem; color:var(--primary); cursor:pointer; text-decoration:underline; margin-bottom:4px; display:inline-block; }
</style>

<div class="main-content">
<?php include __DIR__ . '/../layouts/topbar.php'; ?>
<div class="page-content">

  <div class="page-header">
    <div>
      <h1 class="page-title">Objectifs PDR</h1>
      <p class="page-subtitle">Définissez et suivez vos objectifs de vente</p>
    </div>
    <?php if (($_SESSION['user_role']??'') === 'admin'): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPdr">
      <i class="fas fa-plus"></i> Nouvel objectif
    </button>
    <?php endif; ?>
  </div>

  <?php render_flash(); ?>

  <!-- Filtres -->
  <div class="card mb-4 fade-up">
    <div class="card-body" style="padding:14px 20px;">
      <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="pdr">
        <div class="col-md-3">
          <label class="form-label">Cible</label>
          <select name="type_cible" class="form-control form-select">
            <option value="">Toutes</option>
            <option value="global"  <?= ($filters['type_cible']==='global') ?'selected':'' ?>>Global</option>
            <option value="vendeur" <?= ($filters['type_cible']==='vendeur')?'selected':'' ?>>Vendeur</option>
            <option value="secteur" <?= ($filters['type_cible']==='secteur')?'selected':'' ?>>Secteur</option>
            <option value="ville"   <?= ($filters['type_cible']==='ville')  ?'selected':'' ?>>Ville</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Statut</label>
          <select name="statut" class="form-control form-select">
            <option value="">Tous</option>
            <option value="en_cours"    <?= ($filters['statut']==='en_cours')   ?'selected':'' ?>>En cours</option>
            <option value="atteint"     <?= ($filters['statut']==='atteint')    ?'selected':'' ?>>Atteint</option>
            <option value="non_atteint" <?= ($filters['statut']==='non_atteint')?'selected':'' ?>>Non atteint</option>
            <option value="a_venir"     <?= ($filters['statut']==='a_venir')    ?'selected':'' ?>>À venir</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Période</label>
          <select name="type_periode" class="form-control form-select">
            <option value="">Toutes</option>
            <option value="jour"    <?= ($filters['type_periode']==='jour')   ?'selected':'' ?>>Journalier</option>
            <option value="semaine" <?= ($filters['type_periode']==='semaine')?'selected':'' ?>>Hebdomadaire</option>
            <option value="mois"    <?= ($filters['type_periode']==='mois')   ?'selected':'' ?>>Mensuel</option>
            <option value="annee"   <?= ($filters['type_periode']==='annee')  ?'selected':'' ?>>Annuel</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-filter"></i> Filtrer</button>
          <a href="?page=pdr" class="btn btn-outline"><i class="fas fa-times"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Cartes PDR -->
  <?php if (empty($pdrs)): ?>
  <div class="card fade-up">
    <div class="card-body" style="text-align:center;padding:60px;color:var(--text-muted);">
      <i class="fas fa-bullseye" style="font-size:2.5rem;margin-bottom:16px;color:var(--border-2);display:block;"></i>
      <div style="font-size:1rem;font-weight:600;margin-bottom:8px;">Aucun objectif PDR défini</div>
      <?php if (($_SESSION['user_role']??'') === 'admin'): ?>
      <div style="font-size:.83rem;">Cliquez sur <strong>"Nouvel objectif"</strong> pour commencer.</div>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>

  <div class="row g-4 mb-4">
    <?php foreach ($pdrs as $i => $pdr):
      $taux_ca     = floatval($pdr['taux_ca']     ?? 0);
      $taux_pieces = floatval($pdr['taux_pieces'] ?? 0);
      // Priorité : si objectif pièces défini, on prend taux_pieces ; sinon taux_ca
      $taux_global = $pdr['objectif_pieces'] > 0 ? $taux_pieces : $taux_ca;
      [$sLabel, $sBadge] = pdr_statut_label($pdr['statut']);
      $periode_labels = ['jour'=>'Journalier','semaine'=>'Hebdomadaire','mois'=>'Mensuel','annee'=>'Annuel'];
      $cible_icons    = ['global'=>'globe','vendeur'=>'user-tie','secteur'=>'map','ville'=>'city'];
    ?>
    <div class="col-xl-4 col-md-6 fade-up" style="animation-delay:<?= $i*0.05 ?>s;">
      <div class="card h-100" style="border-top:4px solid <?= pdr_taux_color($taux_global) ?>;">
        <div class="card-body" style="padding:20px;">
          <!-- En-tête -->
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
            <div style="flex:1;min-width:0;">
              <div style="font-weight:700;font-size:.92rem;margin-bottom:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= e($pdr['libelle']) ?>
              </div>
              <div style="display:flex;gap:5px;flex-wrap:wrap;">
                <span style="font-size:.68rem;background:var(--surface-2);padding:2px 8px;border-radius:20px;color:var(--text-muted);">
                  <i class="fas fa-<?= $cible_icons[$pdr['type_cible']] ?? 'circle' ?>"></i>
                  <?= ucfirst($pdr['type_cible']) ?>
                </span>
                <span style="font-size:.68rem;background:var(--info-soft);padding:2px 8px;border-radius:20px;color:var(--info);">
                  <?= $periode_labels[$pdr['type_periode']] ?? $pdr['type_periode'] ?>
                </span>
              </div>
            </div>
            <span class="badge-status <?= $sBadge ?>" style="margin-left:8px;flex-shrink:0;font-size:.65rem;">
              <?= $sLabel ?>
            </span>
          </div>

          <!-- Dates -->
          <div style="font-size:.74rem;color:var(--text-muted);margin-bottom:14px;">
            <i class="fas fa-calendar-alt" style="color:var(--primary);"></i>
            <?= format_date($pdr['date_debut']) ?> → <?= format_date($pdr['date_fin']) ?>
            <?php if ($pdr['jours_restants'] > 0 && $pdr['statut'] === 'en_cours'): ?>
            &nbsp;·&nbsp;<strong style="color:var(--warning);"><?= $pdr['jours_restants'] ?>j restants</strong>
            <?php endif; ?>
          </div>

          <!-- Barre pièces (prioritaire) -->
          <?php if ($pdr['objectif_pieces'] > 0): ?>
          <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
              <span style="font-size:.74rem;font-weight:600;color:var(--text-secondary);"><i class="fas fa-tshirt"></i> Pièces</span>
              <span style="font-size:.85rem;font-weight:800;color:<?= pdr_taux_color($taux_pieces) ?>;"><?= $taux_pieces ?>%</span>
            </div>
            <div style="height:12px;background:var(--border);border-radius:99px;overflow:hidden;">
              <div style="height:12px;width:<?= min($taux_pieces,100) ?>%;background:<?= pdr_taux_color($taux_pieces) ?>;border-radius:99px;transition:width 1s;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:5px;font-size:.72rem;color:var(--text-muted);">
              <span>Réalisé : <strong style="color:var(--text-primary);"><?= number_format($pdr['realise_pieces']) ?> pcs</strong></span>
              <span>Objectif : <strong><?= number_format($pdr['objectif_pieces']) ?> pcs</strong></span>
            </div>
            <?php if ($pdr['realise_pieces'] >= $pdr['objectif_pieces']): ?>
            <div style="margin-top:6px;padding:5px 10px;background:var(--success-soft);border-radius:6px;font-size:.74rem;color:var(--success);font-weight:700;">
              <i class="fas fa-rocket"></i>
              <?php if ($pdr['realise_pieces'] > $pdr['objectif_pieces']): ?>
              Dépassement : +<?= number_format($pdr['realise_pieces'] - $pdr['objectif_pieces']) ?> pcs
              <?php else: ?>
              ✅ Objectif atteint !
              <?php endif; ?>
            </div>
            <?php elseif ($pdr['statut'] === 'en_cours'): ?>
            <div style="margin-top:6px;padding:5px 10px;background:var(--primary-soft);border-radius:6px;font-size:.74rem;color:var(--primary);font-weight:700;">
              <i class="fas fa-flag-checkered"></i> Reste : <?= number_format($pdr['reste_pieces']) ?> pièces
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Barre CA -->
          <?php if ($pdr['objectif_ca'] > 0): ?>
          <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
              <span style="font-size:.74rem;font-weight:600;color:var(--text-secondary);"><i class="fas fa-coins"></i> CA</span>
              <span style="font-size:.82rem;font-weight:800;color:<?= pdr_taux_color($taux_ca) ?>;"><?= $taux_ca ?>%</span>
            </div>
            <div style="height:8px;background:var(--border);border-radius:99px;overflow:hidden;">
              <div style="height:8px;width:<?= min($taux_ca,100) ?>%;background:<?= pdr_taux_color($taux_ca) ?>;border-radius:99px;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:5px;font-size:.72rem;color:var(--text-muted);">
              <span>Réalisé : <strong><?= format_money($pdr['realise_ca']) ?></strong></span>
              <span>Objectif : <strong><?= format_money($pdr['objectif_ca']) ?></strong></span>
            </div>
            <?php if ($pdr['depassement_ca'] > 0): ?>
            <div style="margin-top:5px;font-size:.72rem;color:var(--success);font-weight:700;">
              <i class="fas fa-rocket"></i> Dépassement CA : +<?= format_money($pdr['depassement_ca']) ?>
            </div>
            <?php elseif ($pdr['reste_ca'] > 0 && $pdr['statut']==='en_cours'): ?>
            <div style="margin-top:5px;font-size:.72rem;color:var(--primary);font-weight:700;">
              <i class="fas fa-flag-checkered"></i> Reste CA : <?= format_money($pdr['reste_ca']) ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Nb ventes -->
          <?php if ($pdr['objectif_ventes'] > 0): ?>
          <div style="display:flex;justify-content:space-between;padding:7px 12px;background:var(--surface-2);border-radius:8px;font-size:.78rem;margin-bottom:10px;">
            <span><i class="fas fa-shopping-cart"></i> Ventes</span>
            <span><strong><?= $pdr['realise_ventes'] ?></strong> / <?= $pdr['objectif_ventes'] ?></span>
          </div>
          <?php endif; ?>

          <?php if ($pdr['note']): ?>
          <div style="font-size:.72rem;color:var(--text-muted);font-style:italic;padding:6px 10px;background:var(--surface-2);border-radius:6px;margin-bottom:10px;">
            <i class="fas fa-sticky-note"></i> <?= e($pdr['note']) ?>
          </div>
          <?php endif; ?>

          <!-- Actions admin -->
          <?php if (($_SESSION['user_role']??'') === 'admin'): ?>
          <div style="display:flex;gap:6px;margin-top:10px;border-top:1px solid var(--border);padding-top:12px;">
            <button class="btn btn-outline btn-sm flex-fill"
                    onclick="editPdr(<?= htmlspecialchars(json_encode($pdr),ENT_QUOTES) ?>)">
              <i class="fas fa-edit"></i> Modifier
            </button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action_form" value="delete">
              <input type="hidden" name="id" value="<?= $pdr['id'] ?>">
              <button type="submit" class="btn btn-sm btn-icon" style="background:var(--primary-soft);color:var(--primary);border:none;">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Tableau récap -->
  <div class="card fade-up">
    <div class="card-header"><div class="card-title"><i class="fas fa-table"></i> Récapitulatif</div></div>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>Objectif</th><th>Cible</th><th>Période</th><th>Du → Au</th>
            <th>Obj. Pièces</th><th>Réalisé</th><th>Taux</th>
            <th>Obj. CA</th><th>Réalisé CA</th><th>Taux CA</th>
            <th>Statut</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pdrs as $pdr):
            $tpi=floatval($pdr['taux_pieces']??0);
            $tca=floatval($pdr['taux_ca']??0);
            [$sLabel,$sBadge]=pdr_statut_label($pdr['statut']);
          ?>
          <tr>
            <td><strong><?= e($pdr['libelle']) ?></strong></td>
            <td><span style="font-size:.74rem;background:var(--surface-2);padding:2px 8px;border-radius:4px;"><?= ucfirst($pdr['type_cible']) ?></span></td>
            <td style="font-size:.78rem;"><?= ucfirst($pdr['type_periode']) ?></td>
            <td style="font-size:.75rem;"><?= format_date($pdr['date_debut']) ?> → <?= format_date($pdr['date_fin']) ?></td>
            <td><?= $pdr['objectif_pieces']>0 ? number_format($pdr['objectif_pieces']).' pcs' : '—' ?></td>
            <td><strong><?= number_format($pdr['realise_pieces']) ?> pcs</strong></td>
            <td><?php if($pdr['objectif_pieces']>0): ?><span style="font-weight:800;color:<?= pdr_taux_color($tpi) ?>;"><?= $tpi ?>%</span><?php else: ?>—<?php endif;?></td>
            <td><?= $pdr['objectif_ca']>0 ? format_money($pdr['objectif_ca']) : '—' ?></td>
            <td><?= format_money($pdr['realise_ca']) ?></td>
            <td><?php if($pdr['objectif_ca']>0): ?><span style="font-weight:800;color:<?= pdr_taux_color($tca) ?>;"><?= $tca ?>%</span><?php else: ?>—<?php endif;?></td>
            <td><span class="badge-status <?= $sBadge ?>"><?= $sLabel ?></span></td>
            <td>
              <?php if(($_SESSION['user_role']??'')==='admin'): ?>
              <button class="btn btn-outline btn-sm btn-icon"
                      onclick="editPdr(<?= htmlspecialchars(json_encode($pdr),ENT_QUOTES) ?>)">
                <i class="fas fa-edit"></i>
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<!-- Modal PDR -->
<?php if (($_SESSION['user_role']??'') === 'admin'): ?>
<div class="modal fade" id="modalPdr" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:var(--radius);">
      <div class="modal-header" style="border-color:var(--border);">
        <h5 class="modal-title" id="modalPdrTitle">Nouvel objectif PDR</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="formPdr">
        <?= csrf_field() ?>
        <input type="hidden" name="action_form" id="pdrActionForm" value="create">
        <input type="hidden" name="id"          id="pdrId"         value="">
        <div class="modal-body">
          <div class="row g-3">

            <div class="col-12">
              <label class="form-label">Libellé *</label>
              <input type="text" name="libelle" id="pdrLibelle" class="form-control"
                     placeholder="Ex : Objectif semaine 20 — Équipe Antananarivo" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Type de période *</label>
              <select name="type_periode" id="pdrTypePeriode" class="form-control form-select" onchange="autoDateFin()">
                <option value="jour">Journalier</option>
                <option value="semaine">Hebdomadaire</option>
                <option value="mois" selected>Mensuel</option>
                <option value="annee">Annuel</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Date début *</label>
              <!-- Pas de max → permet les dates futures -->
              <input type="date" name="date_debut" id="pdrDateDebut"
                     class="form-control" value="<?= date('Y-m-01') ?>"
                     onchange="autoDateFin()" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Date fin *</label>
              <input type="date" name="date_fin" id="pdrDateFin"
                     class="form-control" value="<?= date('Y-m-t') ?>" required>
            </div>

            <!-- Type cible -->
            <div class="col-md-4">
              <label class="form-label">Appliquer à</label>
              <select name="type_cible" id="pdrTypeCible" class="form-control form-select" onchange="toggleCible(this.value)">
                <option value="global">Global (tous)</option>
                <option value="vendeur">Vendeur(s)</option>
                <option value="secteur">Secteur(s)</option>
                <option value="ville">Ville(s)</option>
              </select>
            </div>

            <!-- Zone multicible -->
            <div class="col-md-8" id="cibleContainer" style="display:none;">
              <label class="form-label" id="cibleLabel">Choisir</label>
              <span class="select-all-btn" onclick="selectAll()">Tout sélectionner</span>
              <div class="multiselect-box" id="multiselectBox"></div>
              <!-- Pour édition simple (1 seul) : champ caché -->
              <input type="hidden" name="cible_id" id="pdrCibleId" value="">
            </div>

            <div class="col-12">
              <hr style="border-color:var(--border);margin:4px 0 8px;">
              <div style="font-size:.78rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.06em;">
                Objectifs à atteindre <span style="font-weight:400;color:var(--text-muted);">(0 = non défini)</span>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label"><i class="fas fa-tshirt" style="color:var(--accent);"></i> Objectif pièces</label>
              <input type="number" name="objectif_pieces" id="pdrObjPieces" class="form-control" min="0" value="0">
            </div>
            <div class="col-md-4">
              <label class="form-label"><i class="fas fa-coins" style="color:var(--primary);"></i> Objectif CA (Ar)</label>
              <input type="number" name="objectif_ca" id="pdrObjCA" class="form-control" min="0" step="100" value="0">
            </div>
            <div class="col-md-4">
              <label class="form-label"><i class="fas fa-shopping-cart" style="color:var(--info);"></i> Objectif nb ventes</label>
              <input type="number" name="objectif_ventes" id="pdrObjVentes" class="form-control" min="0" value="0">
            </div>

            <div class="col-12">
              <label class="form-label">Note</label>
              <textarea name="note" id="pdrNote" class="form-control" rows="2" placeholder="Commentaire optionnel…"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border-color:var(--border);">
          <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-bullseye"></i> Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$vendeursJson = json_encode(array_map(fn($v)=>['id'=>$v['id'],'nom'=>$v['prenom'].' '.$v['nom']], $vendeurs));
$secteursJson = json_encode(array_map(fn($s)=>['id'=>$s['id'],'nom'=>$s['nom']], $secteurs));
$villesJson   = json_encode(array_map(fn($v)=>['id'=>$v['id'],'nom'=>$v['nom']], $villes));

$extra_scripts = <<<JS
<script>
const PDR_DATA = { vendeur: {$vendeursJson}, secteur: {$secteursJson}, ville: {$villesJson} };
let isEdit = false;

function toggleCible(type) {
    const cont  = document.getElementById('cibleContainer');
    const label = document.getElementById('cibleLabel');
    const box   = document.getElementById('multiselectBox');
    if (type === 'global') { cont.style.display='none'; return; }
    cont.style.display = '';
    const labels = { vendeur:'Vendeur(s)', secteur:'Secteur(s)', ville:'Ville(s)' };
    label.textContent = labels[type] || 'Choisir';
    const items = PDR_DATA[type] || [];
    box.innerHTML = '';
    items.forEach(item => {
        const lbl = document.createElement('label');
        lbl.innerHTML = `<input type="checkbox" name="cibles[]" value="\${item.id}"> \${item.nom}`;
        box.appendChild(lbl);
    });
}

function selectAll() {
    document.querySelectorAll('#multiselectBox input[type=checkbox]').forEach(cb => cb.checked = true);
}

function autoDateFin() {
    const type  = document.getElementById('pdrTypePeriode').value;
    const debut = document.getElementById('pdrDateDebut').value;
    if (!debut) return;
    const d = new Date(debut + 'T00:00:00');
    let fin;
    if      (type==='jour')    fin = debut;
    else if (type==='semaine') { const f=new Date(d); f.setDate(f.getDate()+6); fin=f.toISOString().split('T')[0]; }
    else if (type==='mois')    { const f=new Date(d.getFullYear(),d.getMonth()+1,0); fin=f.toISOString().split('T')[0]; }
    else if (type==='annee')   fin = d.getFullYear()+'-12-31';
    if (fin) document.getElementById('pdrDateFin').value = fin;
}

function editPdr(p) {
    isEdit = true;
    document.getElementById('pdrActionForm').value  = 'update';
    document.getElementById('pdrId').value          = p.id||'';
    document.getElementById('pdrLibelle').value     = p.libelle||'';
    document.getElementById('pdrTypePeriode').value = p.type_periode||'mois';
    document.getElementById('pdrDateDebut').value   = p.date_debut||'';
    document.getElementById('pdrDateFin').value     = p.date_fin||'';
    document.getElementById('pdrTypeCible').value   = p.type_cible||'global';
    document.getElementById('pdrObjPieces').value   = p.objectif_pieces||0;
    document.getElementById('pdrObjCA').value       = p.objectif_ca||0;
    document.getElementById('pdrObjVentes').value   = p.objectif_ventes||0;
    document.getElementById('pdrNote').value        = p.note||'';
    toggleCible(p.type_cible||'global');
    // En mode édition : cocher uniquement la cible existante
    if (p.cible_id && p.type_cible !== 'global') {
        setTimeout(() => {
            document.querySelectorAll('#multiselectBox input[type=checkbox]').forEach(cb => {
                cb.checked = (cb.value == p.cible_id);
            });
        }, 60);
    }
    document.getElementById('pdrCibleId').value = p.cible_id||'';
    document.getElementById('modalPdrTitle').textContent = 'Modifier l\'objectif';
    new bootstrap.Modal(document.getElementById('modalPdr')).show();
}

// Reset pour nouveau PDR
document.querySelector('[data-bs-target="#modalPdr"]')?.addEventListener('click', () => {
    if (isEdit) return;
    document.getElementById('pdrActionForm').value = 'create';
    document.getElementById('pdrId').value = '';
    document.getElementById('pdrLibelle').value = '';
    document.getElementById('pdrObjPieces').value = 0;
    document.getElementById('pdrObjCA').value = 0;
    document.getElementById('pdrObjVentes').value = 0;
    document.getElementById('pdrNote').value = '';
    document.getElementById('pdrTypeCible').value = 'global';
    toggleCible('global');
    document.getElementById('pdrDateDebut').value = '<?= date('Y-m-01') ?>';
    autoDateFin();
    document.getElementById('modalPdrTitle').textContent = 'Nouvel objectif PDR';
});
document.getElementById('modalPdr')?.addEventListener('hidden.bs.modal', () => { isEdit = false; });
</script>
JS;

include __DIR__ . '/../layouts/footer.php';
?>