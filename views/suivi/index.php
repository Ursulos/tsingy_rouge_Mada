<?php
$page_title = 'Suivi des ventes';
$mode     = $_GET['mode']   ?? 'semaine';
$ref_date = $_GET['ref']    ?? date('Y-m-d');
$metric   = $_GET['metric'] ?? 'pieces';

switch ($mode) {
    case 'semaine':
        $ts=$ts0=strtotime($ref_date);
        $dow=(int)date('N',$ts);
        $lundi=date('Y-m-d',$ts-($dow-1)*86400);
        $debut=$lundi; $fin=date('Y-m-d',strtotime($lundi)+5*86400);
        $jours=['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        $cols=[];
        for($d=0;$d<6;$d++){$ts2=strtotime($lundi)+$d*86400;$cols[]=['label'=>$jours[$d],'date'=>date('Y-m-d',$ts2),'date_fin'=>date('Y-m-d',$ts2),'short'=>date('d/m',$ts2)];}
        $periode_label='Semaine du '.date('d/m/Y',strtotime($lundi)).' au '.date('d/m/Y',strtotime($fin));
        $prev_ref=date('Y-m-d',strtotime($lundi)-7*86400);
        $next_ref=date('Y-m-d',strtotime($lundi)+7*86400);
        break;
    case 'mois':
        $annee=date('Y',strtotime($ref_date));$mois_n=date('m',strtotime($ref_date));
        $debut="$annee-$mois_n-01";$fin=date('Y-m-t',strtotime($debut));
        $cols=[];$cur=strtotime($debut);$end=strtotime($fin);$week=1;
        while($cur<=$end){$wend=min($end,strtotime(date('Y-m-d',$cur).' +6 days'));$cols[]=['label'=>'S'.$week,'date'=>date('Y-m-d',$cur),'date_fin'=>date('Y-m-d',$wend),'short'=>date('d/m',$cur).'-'.date('d/m',$wend)];$cur=$wend+86400;$week++;}
        $periode_label=date('F Y',strtotime($debut));
        $prev_ref=date('Y-m-d',strtotime("first day of previous month",strtotime($debut)));
        $next_ref=date('Y-m-d',strtotime("first day of next month",strtotime($debut)));
        break;
    case 'annee':
        $annee=date('Y',strtotime($ref_date));$debut="$annee-01-01";$fin="$annee-12-31";
        $mn=['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'];
        $cols=[];
        for($m=1;$m<=12;$m++){$md=sprintf('%04d-%02d-01',$annee,$m);$cols[]=['label'=>$mn[$m-1],'date'=>$md,'date_fin'=>date('Y-m-t',strtotime($md)),'short'=>$mn[$m-1]];}
        $periode_label="Année $annee";
        $prev_ref=($annee-1).'-01-01';$next_ref=($annee+1).'-01-01';
        break;
}

$db=getDB();
$rows=$db->query("SELECT s.id AS sid, s.nom AS secteur, vd.id AS vid, CONCAT(vd.prenom,' ',vd.nom) AS vendeur FROM secteurs s JOIN vendeurs vd ON vd.secteur_id=s.id AND vd.statut='actif' ORDER BY s.nom,vd.nom")->fetchAll();
$tree=[];
foreach($rows as $r){$tree[$r['sid']]['nom']=$r['secteur'];$tree[$r['sid']]['vendeurs'][$r['vid']]=$r['vendeur'];}

$vstmt=$db->prepare("SELECT v.vendeur_id,v.secteur_id,v.date_vente,SUM(vl.quantite) AS pieces,SUM(v.montant_total) AS ca,GROUP_CONCAT(DISTINCT CONCAT(c.prenom,' ',c.nom) ORDER BY c.nom SEPARATOR ', ') AS clients_liste FROM ventes v JOIN vente_lignes vl ON vl.vente_id=v.id JOIN clients c ON c.id=v.client_id WHERE v.date_vente BETWEEN :debut AND :fin GROUP BY v.vendeur_id,v.date_vente");
$vstmt->execute([':debut'=>$debut,':fin'=>$fin]);
$ventes_raw=$vstmt->fetchAll();

$ventes_idx=[];
foreach($ventes_raw as $vr){
    $vid=$vr['vendeur_id'];$dkey=$vr['date_vente'];
    if($mode!=='semaine'){foreach($cols as $col){if($dkey>=$col['date']&&$dkey<=$col['date_fin']){$dkey=$col['date'];break;}}}
    if(!isset($ventes_idx[$vid][$dkey]))$ventes_idx[$vid][$dkey]=['pieces'=>0,'ca'=>0,'clients'=>[]];
    $ventes_idx[$vid][$dkey]['pieces']+=$vr['pieces'];
    $ventes_idx[$vid][$dkey]['ca']+=$vr['ca'];
    foreach(explode(', ',$vr['clients_liste']) as $cl){$cl=trim($cl);if($cl&&!in_array($cl,$ventes_idx[$vid][$dkey]['clients']))$ventes_idx[$vid][$dkey]['clients'][]=$cl;}
}

// PDR tous types
$pdr_stmt=$db->prepare("SELECT type_cible,cible_id,SUM(objectif_pieces) AS obj_pieces,SUM(objectif_ca) AS obj_ca FROM pdr_objectifs WHERE date_debut<=:fin AND date_fin>=:debut GROUP BY type_cible,cible_id");
$pdr_stmt->execute([':debut'=>$debut,':fin'=>$fin]);
$pdr_vendeur=[];$pdr_secteur=[];$pdr_global=['pieces'=>0,'ca'=>0];
foreach($pdr_stmt->fetchAll() as $p){
    $val=['pieces'=>floatval($p['obj_pieces']),'ca'=>floatval($p['obj_ca'])];
    if($p['type_cible']==='vendeur'&&$p['cible_id'])$pdr_vendeur[$p['cible_id']]=$val;
    elseif($p['type_cible']==='secteur'&&$p['cible_id'])$pdr_secteur[$p['cible_id']]=$val;
    elseif($p['type_cible']==='global')$pdr_global=$val;
}

$gt_cols=array_fill_keys(array_column($cols,'date'),0);
$gt_total=0;$gt_obj=0;$gt_clients_all=[];

include __DIR__.'/../layouts/header.php';
include __DIR__.'/../layouts/sidebar.php';
?>
<style>
.suivi-table{font-size:.78rem;border-collapse:collapse;width:100%;min-width:900px;}
.suivi-table th,.suivi-table td{border:1px solid var(--border);padding:6px 8px;vertical-align:middle;white-space:nowrap;}
.suivi-table thead th{background:#1A1D2E;color:#fff;font-weight:700;font-size:.7rem;text-transform:uppercase;text-align:center;position:sticky;top:0;z-index:10;}
.row-secteur td{background:linear-gradient(135deg,#1A1D2E,#12141F);color:#fff;font-weight:800;}
.row-vendeur td{background:var(--surface);}
.row-vendeur:hover td{background:var(--surface-2);}
.row-total-secteur td{background:var(--primary-soft);font-weight:700;color:var(--primary);}
.row-grand-total td{background:#1A1D2E;color:#fff;font-weight:800;}
.row-objectif td{background:#EAF4FB;font-weight:700;color:var(--info);}
.row-realise td{background:#EAF9ED;font-weight:700;color:var(--success);}
.row-ecart td{background:#FFF3E0;font-weight:700;color:var(--warning);}
.row-pct td{background:#F9F0FF;font-weight:700;color:#8E44AD;}
.cell-val{text-align:right;font-weight:600;}
.cell-zero{color:var(--text-muted);text-align:right;}
.pdr-col{background:rgba(41,128,185,.06)!important;}
.clients-cell{max-width:150px;white-space:normal;font-size:.66rem;color:var(--text-muted);line-height:1.4;}
.clients-badge{display:inline-block;background:var(--surface-2);border:1px solid var(--border);border-radius:4px;padding:1px 4px;margin:1px;font-size:.63rem;}
.taux-ok{color:#27AE60;font-weight:800;}.taux-mid{color:#F39C12;font-weight:800;}.taux-bad{color:#C0392B;font-weight:800;}
.mode-btn{border-radius:8px;padding:6px 16px;font-size:.8rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border-2);background:var(--surface);color:var(--text-secondary);transition:var(--transition);text-decoration:none;display:inline-block;}
.mode-btn.active{background:var(--primary);color:#fff;border-color:var(--primary);}
.nav-btn{width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border-2);background:var(--surface);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem;color:var(--text-secondary);transition:var(--transition);text-decoration:none;}
.nav-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary);}
</style>
<div class="main-content">
<?php include __DIR__.'/../layouts/topbar.php';?>
<div class="page-content">
<div class="page-header">
  <div><h1 class="page-title">Suivi des ventes</h1><p class="page-subtitle"><?=$periode_label?></p></div>
  <a href="?page=suivi&mode=<?=$mode?>&ref=<?=$ref_date?>&metric=<?=$metric?>&export=csv" class="btn btn-outline btn-sm"><i class="fas fa-file-csv"></i> CSV</a>
</div>

<div class="card mb-4 fade-up">
  <div class="card-body" style="padding:14px 20px;">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
      <div style="display:flex;gap:6px;">
        <?php foreach(['semaine'=>'Semaine','mois'=>'Mois','annee'=>'Année'] as $m=>$lbl):?>
        <a href="?page=suivi&mode=<?=$m?>&ref=<?=$ref_date?>&metric=<?=$metric?>" class="mode-btn <?=$mode===$m?'active':''?>"><?=$lbl?></a>
        <?php endforeach;?>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <a href="?page=suivi&mode=<?=$mode?>&ref=<?=$prev_ref?>&metric=<?=$metric?>" class="nav-btn"><i class="fas fa-chevron-left"></i></a>
        <input type="date" value="<?=$ref_date?>" onchange="window.location='?page=suivi&mode=<?=$mode?>&ref='+this.value+'&metric=<?=$metric?>'" style="padding:5px 10px;border:1.5px solid var(--border-2);border-radius:8px;font-size:.8rem;font-family:inherit;">
        <a href="?page=suivi&mode=<?=$mode?>&ref=<?=$next_ref?>&metric=<?=$metric?>" class="nav-btn"><i class="fas fa-chevron-right"></i></a>
        <a href="?page=suivi&mode=<?=$mode?>&ref=<?=date('Y-m-d')?>&metric=<?=$metric?>" class="mode-btn" style="font-size:.75rem;">Aujourd'hui</a>
      </div>
      <div style="display:flex;gap:6px;margin-left:auto;">
        <a href="?page=suivi&mode=<?=$mode?>&ref=<?=$ref_date?>&metric=pieces" class="mode-btn <?=$metric==='pieces'?'active':''?>"><i class="fas fa-tshirt"></i> Pièces</a>
        <a href="?page=suivi&mode=<?=$mode?>&ref=<?=$ref_date?>&metric=ca"     class="mode-btn <?=$metric==='ca'?'active':''?>"><i class="fas fa-coins"></i> CA (Ar)</a>
      </div>
    </div>
  </div>
</div>

<div class="card fade-up" style="overflow:hidden;">
<div style="overflow-x:auto;">
<table class="suivi-table">
<thead>
<tr>
  <th style="width:90px;">Secteur</th>
  <th style="width:140px;">Vendeur</th>
  <?php foreach($cols as $col):?><th style="min-width:65px;"><?=e($col['label'])?><div style="font-size:.6rem;opacity:.7;"><?=e($col['short']??'')?></div></th><?php endforeach;?>
  <th style="background:#C0392B;min-width:75px;">TOTAL</th>
  <th class="pdr-col" style="min-width:75px;">PDR</th>
  <th style="min-width:55px;">%</th>
  <th style="min-width:80px;">Reste</th>
  <th style="min-width:150px;white-space:normal;">Clients</th>
</tr>
</thead>
<tbody>
<?php foreach($tree as $sid=>$secteur):
  $s_cols=array_fill_keys(array_column($cols,'date'),0);
  $s_total=0;$s_clients=[];
  $s_pdr_val=0;
  if(!empty($pdr_secteur[$sid])) $s_pdr_val=$metric==='ca'?$pdr_secteur[$sid]['ca']:$pdr_secteur[$sid]['pieces'];
  elseif($pdr_global['pieces']>0||$pdr_global['ca']>0) $s_pdr_val=$metric==='ca'?$pdr_global['ca']:$pdr_global['pieces'];
?>
<tr class="row-secteur">
  <td colspan="2"><i class="fas fa-map-marker-alt" style="color:#E74C3C;margin-right:6px;"></i><?=e($secteur['nom'])?></td>
  <?php foreach($cols as $col):?><td></td><?php endforeach;?>
  <td></td><td class="pdr-col"></td><td></td><td></td><td></td>
</tr>
<?php foreach($secteur['vendeurs'] as $vid=>$vendeur_nom):
  $v_total=0;$v_clients_all=[];
  $v_pdr=$pdr_vendeur[$vid]??($pdr_secteur[$sid]??$pdr_global);
  $v_pdr_val=$metric==='ca'?$v_pdr['ca']:$v_pdr['pieces'];
?>
<tr class="row-vendeur">
  <td></td>
  <td><div style="display:flex;align-items:center;gap:7px;"><div class="avatar" style="width:26px;height:26px;font-size:.65rem;flex-shrink:0;"><?=strtoupper(substr($vendeur_nom,0,1))?></div><span style="font-weight:600;font-size:.78rem;"><?=e($vendeur_nom)?></span></div></td>
  <?php foreach($cols as $col):
    $ck=$col['date'];$val=$ventes_idx[$vid][$ck][$metric]??0;
    $v_total+=$val;$s_cols[$ck]+=$val;
    $v_clients_all=array_unique(array_merge($v_clients_all,$ventes_idx[$vid][$ck]['clients']??[]));
  ?><td class="<?=$val>0?'cell-val':'cell-zero'?>"><?=$val>0?($metric==='ca'?number_format($val,0,',',' '):number_format($val)):'—'?></td><?php endforeach;
  $s_total+=$v_total;
  $s_clients=array_unique(array_merge($s_clients,$v_clients_all));
  $gt_total+=$v_total;
  foreach($cols as $col)$gt_cols[$col['date']]+=$s_cols[$col['date']];
  $gt_clients_all=array_unique(array_merge($gt_clients_all,$v_clients_all));
  $taux=$v_pdr_val>0?round(($v_total/$v_pdr_val)*100,1):null;
  $reste=$v_pdr_val>0?max(0,$v_pdr_val-$v_total):0;
  $tc=$taux===null?'':($taux>=100?'taux-ok':($taux>=70?'taux-mid':'taux-bad'));
  ?>
  <td class="cell-val" style="background:rgba(192,57,43,.06);color:var(--primary);font-weight:800;"><?=$metric==='ca'?number_format($v_total,0,',',' '):number_format($v_total)?></td>
  <td class="pdr-col cell-val" style="color:var(--info);"><?=$v_pdr_val>0?($metric==='ca'?number_format($v_pdr_val,0,',',' '):number_format($v_pdr_val)):'—'?></td>
  <td style="text-align:center;"><?php if($taux!==null):?><span class="<?=$tc?>"><?=$taux?>%<?=$taux>=100?' ✅':''?></span><?php else:?>—<?php endif;?></td>
  <td style="text-align:right;">
    <?php if($v_pdr_val>0):?><?php if($v_total>=$v_pdr_val):?><span style="color:var(--success);font-weight:700;">+<?=number_format($v_total-$v_pdr_val)?></span><?php else:?><?=number_format($reste)?><?php endif;?><?php else:?>—<?php endif;?>
  </td>
  <td class="clients-cell">
    <?php if(empty($v_clients_all)):?><span style="color:var(--border-2);font-style:italic;">— aucun achat</span>
    <?php else:?><?php foreach(array_slice($v_clients_all,0,4) as $cl):?><span class="clients-badge"><?=e($cl)?></span><?php endforeach;?><?php if(count($v_clients_all)>4):?><span style="font-size:.62rem;color:var(--text-muted);">+<?=count($v_clients_all)-4?></span><?php endif;?><?php endif;?>
  </td>
</tr>
<?php endforeach;
  $s_taux=$s_pdr_val>0?round(($s_total/$s_pdr_val)*100,1):null;
  $s_reste=$s_pdr_val>0?max(0,$s_pdr_val-$s_total):0;
  $stc=$s_taux===null?'':($s_taux>=100?'taux-ok':($s_taux>=70?'taux-mid':'taux-bad'));
?>
<tr class="row-total-secteur">
  <td colspan="2" style="font-size:.78rem;"><i class="fas fa-sigma" style="margin-right:4px;"></i> Total <?=e($secteur['nom'])?></td>
  <?php foreach($cols as $col):?><td class="cell-val"><?=$s_cols[$col['date']]>0?number_format($s_cols[$col['date']]):'—'?></td><?php endforeach;?>
  <td class="cell-val" style="color:var(--primary);font-weight:800;"><?=number_format($s_total)?></td>
  <td class="pdr-col cell-val"><?=$s_pdr_val>0?number_format($s_pdr_val):'—'?></td>
  <td style="text-align:center;"><?php if($s_taux!==null):?><span class="<?=$stc?>"><?=$s_taux?>%</span><?php else:?>—<?php endif;?></td>
  <td style="text-align:right;"><?php if($s_pdr_val>0):?><?=$s_total>=$s_pdr_val?'<span style="color:var(--success);font-weight:700;">+'.number_format($s_total-$s_pdr_val).'</span>':number_format($s_reste)?><?php else:?>—<?php endif;?></td>
  <td style="font-size:.68rem;color:var(--primary);"><?=count($s_clients)?> client(s)</td>
</tr>
<?php endforeach;
  $gt_obj=$metric==='ca'?$pdr_global['ca']:$pdr_global['pieces'];
  if($gt_obj==0){foreach($pdr_vendeur as $pv)$gt_obj+=$metric==='ca'?$pv['ca']:$pv['pieces'];}
  $gt_taux=$gt_obj>0?round(($gt_total/$gt_obj)*100,1):null;
  $gt_reste=$gt_obj>0?max(0,$gt_obj-$gt_total):0;
?>
<tr class="row-grand-total">
  <td colspan="2"><i class="fas fa-chart-bar" style="color:var(--accent);margin-right:6px;"></i>GRAND TOTAL</td>
  <?php foreach($cols as $col):?><td class="cell-val" style="color:#fff;"><?=$gt_cols[$col['date']]>0?number_format($gt_cols[$col['date']]):'—'?></td><?php endforeach;?>
  <td class="cell-val" style="color:var(--accent);"><?=number_format($gt_total)?></td>
  <td class="pdr-col cell-val" style="color:#81CFE0;"><?=$gt_obj>0?number_format($gt_obj):'—'?></td>
  <td style="text-align:center;"><?php if($gt_taux!==null):?><span style="font-weight:800;color:<?=$gt_taux>=100?'#2ECC71':($gt_taux>=70?'#F39C12':'#E74C3C')?>"><?=$gt_taux?>%</span><?php else:?>—<?php endif;?></td>
  <td style="text-align:right;color:<?=$gt_reste>0?'#E74C3C':'#2ECC71'?>;"><?php if($gt_obj>0):?><?=$gt_total>=$gt_obj?'+'.number_format($gt_total-$gt_obj):number_format($gt_reste)?><?php else:?>—<?php endif;?></td>
  <td style="color:#aaa;font-size:.72rem;"><?=count($gt_clients_all)?> clients uniques</td>
</tr>
<tr><td colspan="<?=count($cols)+9?>" style="height:10px;background:var(--bg);border:none;"></td></tr>
<tr class="row-objectif"><td colspan="2"><i class="fas fa-bullseye"></i> Objectif (PDR)</td><?php foreach($cols as $c):?><td class="cell-val">—</td><?php endforeach;?><td class="cell-val"><?=$gt_obj>0?number_format($gt_obj):'—'?></td><td colspan="4"></td></tr>
<tr class="row-realise"><td colspan="2"><i class="fas fa-check-circle"></i> Réalisation</td><?php foreach($cols as $col):?><td class="cell-val"><?=$gt_cols[$col['date']]>0?number_format($gt_cols[$col['date']]):'—'?></td><?php endforeach;?><td class="cell-val"><?=number_format($gt_total)?></td><td colspan="4"></td></tr>
<tr class="row-ecart"><td colspan="2"><i class="fas fa-arrows-alt-v"></i> Écart</td><?php foreach($cols as $c):?><td class="cell-val">—</td><?php endforeach;?><td class="cell-val"><?php $ecart=$gt_total-$gt_obj;?><span style="color:<?=$ecart>=0?'var(--success)':'var(--primary)'?>;"><?=($ecart>=0?'+':'').number_format($ecart)?></span></td><td colspan="4"></td></tr>
<tr class="row-pct"><td colspan="2"><i class="fas fa-percent"></i> Taux global</td><?php foreach($cols as $c):?><td style="text-align:center;">—</td><?php endforeach;?><td style="text-align:center;"><?php if($gt_taux!==null):?><span style="font-size:1rem;font-weight:900;color:<?=$gt_taux>=100?'#27AE60':($gt_taux>=70?'#E67E22':'#C0392B')?>"><?=$gt_taux?>%</span><?php else:?>—<?php endif;?></td><td colspan="4"></td></tr>
</tbody>
</table>
</div>
</div>
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px;font-size:.73rem;color:var(--text-muted);">
  <span><span class="taux-ok">■</span> ≥100%</span>
  <span><span class="taux-mid">■</span> ≥70%</span>
  <span><span class="taux-bad">■</span> &lt;70%</span>
  <span><i class="fas fa-info-circle"></i> PDR : Vendeur › Secteur › Global</span>
</div>
</div></div>
<?php
if(isset($_GET['export'])&&$_GET['export']==='csv'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="suivi_'.$mode.'_'.$ref_date.'.csv"');
    $out=fopen('php://output','w');fprintf($out,chr(0xEF).chr(0xBB).chr(0xBF));
    $hdr=['Secteur','Vendeur'];foreach($cols as $c)$hdr[]=$c['label'];$hdr=array_merge($hdr,['TOTAL','PDR','%','Reste','Clients']);fputcsv($out,$hdr,';');
    foreach($tree as $sid=>$secteur){foreach($secteur['vendeurs'] as $vid=>$vnom){$row=[$secteur['nom'],$vnom];$vt=0;$vc=[];foreach($cols as $col){$val=$ventes_idx[$vid][$col['date']][$metric]??0;$row[]=$val;$vt+=$val;$vc=array_unique(array_merge($vc,$ventes_idx[$vid][$col['date']]['clients']??[]));}$vp=$pdr_vendeur[$vid]??($pdr_secteur[$sid]??$pdr_global);$vpv=$metric==='ca'?$vp['ca']:$vp['pieces'];$row[]=$vt;$row[]=$vpv?:'-';$row[]=$vpv>0?round(($vt/$vpv)*100,1).'%':'-';$row[]=$vpv>0?max(0,$vpv-$vt):'-';$row[]=implode(', ',$vc);fputcsv($out,$row,';');}}
    fclose($out);exit;
}
$extra_scripts='<script></script>';
include __DIR__.'/../layouts/footer.php';
?>