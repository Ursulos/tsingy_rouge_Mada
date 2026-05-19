<?php
class PdrModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    private function baseQuery(): string {
        return "SELECT p.*,
            COALESCE(r.realise_ca,0)     AS realise_ca,
            COALESCE(r.realise_pieces,0) AS realise_pieces,
            COALESCE(r.realise_ventes,0) AS realise_ventes,
            CASE WHEN p.objectif_ca>0     THEN ROUND((COALESCE(r.realise_ca,0)/p.objectif_ca)*100,1)     ELSE NULL END AS taux_ca,
            CASE WHEN p.objectif_pieces>0 THEN ROUND((COALESCE(r.realise_pieces,0)/p.objectif_pieces)*100,1) ELSE NULL END AS taux_pieces,
            CASE WHEN p.objectif_ca>0     THEN GREATEST(0,p.objectif_ca-COALESCE(r.realise_ca,0))     ELSE 0 END AS reste_ca,
            CASE WHEN p.objectif_pieces>0 THEN GREATEST(0,p.objectif_pieces-COALESCE(r.realise_pieces,0)) ELSE 0 END AS reste_pieces,
            CASE WHEN p.objectif_ca>0 AND COALESCE(r.realise_ca,0)>p.objectif_ca THEN COALESCE(r.realise_ca,0)-p.objectif_ca ELSE 0 END AS depassement_ca,
            GREATEST(0,DATEDIFF(p.date_fin,CURDATE())) AS jours_restants,
            CASE
                WHEN p.objectif_pieces>0 AND p.date_fin<CURDATE() AND COALESCE(r.realise_pieces,0)>=p.objectif_pieces THEN 'atteint'
                WHEN p.objectif_ca>0     AND p.date_fin<CURDATE() AND COALESCE(r.realise_ca,0)>=p.objectif_ca         THEN 'atteint'
                WHEN p.date_fin<CURDATE() THEN 'non_atteint'
                WHEN CURDATE() BETWEEN p.date_debut AND p.date_fin THEN 'en_cours'
                WHEN p.date_debut>CURDATE() THEN 'a_venir'
                ELSE 'inconnu'
            END AS statut
        FROM pdr_objectifs p
        LEFT JOIN (
            SELECT pi2.id AS pdr_id,
                   SUM(v.montant_total) AS realise_ca,
                   SUM(vl.quantite)     AS realise_pieces,
                   COUNT(DISTINCT v.id) AS realise_ventes
            FROM pdr_objectifs pi2
            JOIN ventes v ON v.date_vente BETWEEN pi2.date_debut AND pi2.date_fin
              AND (pi2.type_cible='global'
                OR (pi2.type_cible='vendeur' AND v.vendeur_id=pi2.cible_id)
                OR (pi2.type_cible='secteur' AND v.secteur_id=pi2.cible_id)
                OR (pi2.type_cible='ville'   AND v.ville_id=pi2.cible_id))
            JOIN vente_lignes vl ON vl.vente_id=v.id
            GROUP BY pi2.id
        ) r ON r.pdr_id=p.id";
    }

    public function getAll(array $filters=[]): array {
        $where=['1=1']; $params=[];
        if (!empty($filters['type_cible']))   { $where[]='p.type_cible=:tc';   $params[':tc']=$filters['type_cible']; }
        if (!empty($filters['type_periode'])) { $where[]='p.type_periode=:tp'; $params[':tp']=$filters['type_periode']; }
        $ws   = implode(' AND ',$where);
        $having = !empty($filters['statut']) ? "HAVING statut=:st" : "";
        if (!empty($filters['statut'])) $params[':st']=$filters['statut'];
        $stmt = $this->db->prepare($this->baseQuery()." WHERE $ws $having ORDER BY p.date_debut DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDashboard(int $limit=6): array {
        $stmt=$this->db->prepare($this->baseQuery()." HAVING statut IN ('en_cours','a_venir') ORDER BY p.date_debut ASC LIMIT :lim");
        $stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByVendeurPeriode(string $debut, string $fin): array {
        $stmt=$this->db->prepare(
            "SELECT cible_id AS vendeur_id, SUM(objectif_pieces) AS obj_pieces, SUM(objectif_ca) AS obj_ca
             FROM pdr_objectifs WHERE type_cible='vendeur' AND date_debut<=:fin AND date_fin>=:debut
             GROUP BY cible_id");
        $stmt->execute([':debut'=>$debut,':fin'=>$fin]);
        $idx=[];
        foreach ($stmt->fetchAll() as $r) $idx[$r['vendeur_id']]=['pieces'=>floatval($r['obj_pieces']),'ca'=>floatval($r['obj_ca'])];
        return $idx;
    }

    public function create(array $d): int {
        $stmt=$this->db->prepare(
            "INSERT INTO pdr_objectifs (libelle,type_cible,cible_id,type_periode,date_debut,date_fin,objectif_ca,objectif_pieces,objectif_ventes,note,created_by)
             VALUES (:lib,:tc,:ci,:tp,:dd,:df,:oca,:opi,:ove,:note,:cb)");
        $stmt->execute([
            ':lib'=>$d['libelle'],':tc'=>$d['type_cible'],':ci'=>$d['cible_id']?:null,
            ':tp'=>$d['type_periode'],':dd'=>$d['date_debut'],':df'=>$d['date_fin'],
            ':oca'=>floatval($d['objectif_ca']??0),':opi'=>intval($d['objectif_pieces']??0),
            ':ove'=>intval($d['objectif_ventes']??0),':note'=>$d['note']??null,':cb'=>$_SESSION['user_id']??null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $this->db->prepare(
            "UPDATE pdr_objectifs SET libelle=:lib,type_cible=:tc,cible_id=:ci,type_periode=:tp,
             date_debut=:dd,date_fin=:df,objectif_ca=:oca,objectif_pieces=:opi,objectif_ventes=:ove,
             note=:note,updated_at=NOW() WHERE id=:id")
        ->execute([
            ':lib'=>$d['libelle'],':tc'=>$d['type_cible'],':ci'=>$d['cible_id']?:null,
            ':tp'=>$d['type_periode'],':dd'=>$d['date_debut'],':df'=>$d['date_fin'],
            ':oca'=>floatval($d['objectif_ca']??0),':opi'=>intval($d['objectif_pieces']??0),
            ':ove'=>intval($d['objectif_ventes']??0),':note'=>$d['note']??null,':id'=>$id,
        ]);
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM pdr_objectifs WHERE id=:id")->execute([':id'=>$id]);
    }
}