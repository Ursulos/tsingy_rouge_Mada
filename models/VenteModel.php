<?php
class VenteModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function getAll(array $filters = [], int $limit = 50, int $offset = 0): array {
        $where=['1=1']; $params=[];
        if (!empty($filters['vendeur_id'])) { $where[]='v.vendeur_id=:vid'; $params[':vid']=$filters['vendeur_id']; }
        if (!empty($filters['secteur_id'])) { $where[]='v.secteur_id=:sid'; $params[':sid']=$filters['secteur_id']; }
        if (!empty($filters['ville_id']))   { $where[]='v.ville_id=:wid';   $params[':wid']=$filters['ville_id']; }
        if (!empty($filters['date_debut'])) { $where[]='v.date_vente>=:dd'; $params[':dd']=$filters['date_debut']; }
        if (!empty($filters['date_fin']))   { $where[]='v.date_vente<=:df'; $params[':df']=$filters['date_fin']; }
        $ws  = implode(' AND ', $where);
        $stmt = $this->db->prepare(
            "SELECT v.id, v.reference, v.date_vente, v.montant_total, v.note,
                    CONCAT(c.prenom,' ',c.nom) AS client, CONCAT(vd.prenom,' ',vd.nom) AS vendeur,
                    s.nom AS secteur, vi.nom AS ville,
                    (SELECT SUM(quantite) FROM vente_lignes WHERE vente_id=v.id) AS total_pieces
             FROM ventes v
             JOIN clients c   ON c.id=v.client_id
             JOIN vendeurs vd ON vd.id=v.vendeur_id
             JOIN secteurs s  ON s.id=v.secteur_id
             JOIN villes vi   ON vi.id=v.ville_id
             WHERE $ws ORDER BY v.date_vente DESC, v.id DESC
             LIMIT :lim OFFSET :off");
        foreach ($params as $k=>$val) $stmt->bindValue($k,$val);
        $stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
        $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count(array $filters = []): int {
        $where=['1=1']; $params=[];
        if (!empty($filters['vendeur_id'])) { $where[]='vendeur_id=:vid'; $params[':vid']=$filters['vendeur_id']; }
        if (!empty($filters['secteur_id'])) { $where[]='secteur_id=:sid'; $params[':sid']=$filters['secteur_id']; }
        if (!empty($filters['ville_id']))   { $where[]='ville_id=:wid';   $params[':wid']=$filters['ville_id']; }
        if (!empty($filters['date_debut'])) { $where[]='date_vente>=:dd'; $params[':dd']=$filters['date_debut']; }
        if (!empty($filters['date_fin']))   { $where[]='date_vente<=:df'; $params[':df']=$filters['date_fin']; }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ventes WHERE ".implode(' AND ',$where));
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getById(int $id): array {
        $stmt = $this->db->prepare(
            "SELECT v.*, CONCAT(c.prenom,' ',c.nom) AS client_nom, c.id AS client_id_val,
                    CONCAT(vd.prenom,' ',vd.nom) AS vendeur_nom,
                    s.nom AS secteur_nom, vi.nom AS ville_nom
             FROM ventes v
             JOIN clients c   ON c.id=v.client_id
             JOIN vendeurs vd ON vd.id=v.vendeur_id
             JOIN secteurs s  ON s.id=v.secteur_id
             JOIN villes vi   ON vi.id=v.ville_id
             WHERE v.id=:id");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch() ?: [];
    }

    public function getLignes(int $venteId): array {
        $stmt = $this->db->prepare(
            "SELECT vl.*, p.nom AS produit_nom, p.reference AS produit_ref, p.prix AS prix_actuel
             FROM vente_lignes vl JOIN produits p ON p.id=vl.produit_id
             WHERE vl.vente_id=:id");
        $stmt->execute([':id'=>$venteId]);
        return $stmt->fetchAll();
    }

    private function makeRef(): string {
        $date = date('Ymd');
        do {
            $stmt = $this->db->query("SELECT COUNT(*) FROM ventes WHERE reference LIKE 'VTE-{$date}-%'");
            $n    = (int)$stmt->fetchColumn() + mt_rand(1,9);
            $ref  = sprintf('VTE-%s-%04d', $date, $n);
            $chk  = $this->db->prepare("SELECT COUNT(*) FROM ventes WHERE reference=:r");
            $chk->execute([':r'=>$ref]);
        } while ((int)$chk->fetchColumn() > 0);
        return $ref;
    }

    public function create(array $data, array $lignes): int {
        $this->db->beginTransaction();
        try {
            $ref = $this->makeRef();
            $stmt = $this->db->prepare(
                "INSERT INTO ventes (reference,vendeur_id,client_id,secteur_id,ville_id,montant_total,note,date_vente)
                 VALUES (:ref,:vd,:cl,:se,:vi,0,:note,:dv)");
            $stmt->execute([':ref'=>$ref,':vd'=>$data['vendeur_id'],':cl'=>$data['client_id'],
                            ':se'=>$data['secteur_id'],':vi'=>$data['ville_id'],
                            ':note'=>$data['note']??null,':dv'=>$data['date_vente']]);
            $venteId = (int)$this->db->lastInsertId();
            $this->insertLignes($venteId, $lignes, $ref);
            $this->recalcMontant($venteId);
            $this->db->commit();
            return $venteId;
        } catch (Exception $e) { $this->db->rollBack(); throw $e; }
    }

    public function update(int $id, array $data, array $lignes): void {
        $this->db->beginTransaction();
        try {
            // Remettre stock anciennes lignes
            foreach ($this->getLignes($id) as $ol) {
                $this->db->prepare("UPDATE produits SET stock=stock+:qty WHERE id=:id")
                         ->execute([':qty'=>$ol['quantite'],':id'=>$ol['produit_id']]);
            }
            $this->db->prepare("DELETE FROM vente_lignes WHERE vente_id=:id")->execute([':id'=>$id]);
            $this->db->prepare(
                "UPDATE ventes SET vendeur_id=:vd,client_id=:cl,secteur_id=:se,ville_id=:vi,
                 date_vente=:dv,note=:note,updated_at=NOW() WHERE id=:id")
                ->execute([':vd'=>$data['vendeur_id'],':cl'=>$data['client_id'],
                           ':se'=>$data['secteur_id'],':vi'=>$data['ville_id'],
                           ':dv'=>$data['date_vente'],':note'=>$data['note']??null,':id'=>$id]);
            $vente = $this->getById($id);
            $this->insertLignes($id, $lignes, $vente['reference']);
            $this->recalcMontant($id);
            $this->db->commit();
        } catch (Exception $e) { $this->db->rollBack(); throw $e; }
    }

    public function delete(int $id): void {
        $this->db->beginTransaction();
        try {
            foreach ($this->getLignes($id) as $l) {
                $this->db->prepare("UPDATE produits SET stock=stock+:qty WHERE id=:id")
                         ->execute([':qty'=>$l['quantite'],':id'=>$l['produit_id']]);
            }
            $this->db->prepare("DELETE FROM ventes WHERE id=:id")->execute([':id'=>$id]);
            $this->db->commit();
        } catch (Exception $e) { $this->db->rollBack(); throw $e; }
    }

    private function insertLignes(int $venteId, array $lignes, string $ref): void {
        $stmtL = $this->db->prepare("INSERT INTO vente_lignes (vente_id,produit_id,quantite,prix_unitaire) VALUES (:vid,:pid,:qty,:pu)");
        $stmtS = $this->db->prepare("SELECT stock FROM produits WHERE id=:id");
        $stmtU = $this->db->prepare("UPDATE produits SET stock=stock-:qty WHERE id=:id");
        $stmtH = $this->db->prepare("INSERT INTO stock_historique (produit_id,type,quantite,stock_avant,stock_apres,note,user_id) VALUES (:pid,'sortie',:qty,:sa,:sp,:note,:uid)");
        foreach ($lignes as $l) {
            $stmtS->execute([':id'=>$l['produit_id']]);
            $sa = (int)$stmtS->fetchColumn();
            $sp = $sa - (int)$l['quantite'];
            $stmtL->execute([':vid'=>$venteId,':pid'=>$l['produit_id'],':qty'=>$l['quantite'],':pu'=>$l['prix']]);
            $stmtU->execute([':qty'=>$l['quantite'],':id'=>$l['produit_id']]);
            $stmtH->execute([':pid'=>$l['produit_id'],':qty'=>$l['quantite'],':sa'=>$sa,':sp'=>$sp,':note'=>"Vente $ref",':uid'=>$_SESSION['user_id']??null]);
        }
    }

    private function recalcMontant(int $id): void {
        $this->db->prepare("UPDATE ventes SET montant_total=COALESCE((SELECT SUM(sous_total) FROM vente_lignes WHERE vente_id=:id),0) WHERE id=:id2")
                 ->execute([':id'=>$id,':id2'=>$id]);
    }

    public function comparerPeriodes(array $periodes, ?int $vendeurId=null, ?int $secteurId=null, ?int $villeId=null): array {
        $results=[];
        foreach ($periodes as $i=>$p) {
            if (empty($p['debut'])||empty($p['fin'])) continue;
            $where=['v.date_vente BETWEEN :debut AND :fin']; $params=[':debut'=>$p['debut'],':fin'=>$p['fin']];
            if ($vendeurId) { $where[]='v.vendeur_id=:vid'; $params[':vid']=$vendeurId; }
            if ($secteurId) { $where[]='v.secteur_id=:sid'; $params[':sid']=$secteurId; }
            if ($villeId)   { $where[]='v.ville_id=:wid';   $params[':wid']=$villeId; }
            $stmt=$this->db->prepare("SELECT COUNT(DISTINCT v.id) AS nb_ventes, COALESCE(SUM(v.montant_total),0) AS ca,
                    COALESCE(SUM(vl.quantite),0) AS pieces, COUNT(DISTINCT v.client_id) AS nb_clients,
                    COUNT(DISTINCT v.vendeur_id) AS nb_vendeurs
                FROM ventes v JOIN vente_lignes vl ON vl.vente_id=v.id WHERE ".implode(' AND ',$where));
            $stmt->execute($params);
            $row=$stmt->fetch(); $row['label']=$p['label']??('Période '.($i+1)); $row['debut']=$p['debut']; $row['fin']=$p['fin'];
            $results[]=$row;
        }
        return $results;
    }

    public function topVendeursPeriode(string $debut, string $fin): array {
        $stmt=$this->db->prepare(
            "SELECT CONCAT(vd.prenom,' ',vd.nom) AS vendeur, s.nom AS secteur,
                    SUM(v.montant_total) AS ca, SUM(vl.quantite) AS pieces, COUNT(v.id) AS nb_ventes
             FROM ventes v JOIN vendeurs vd ON vd.id=v.vendeur_id JOIN secteurs s ON s.id=vd.secteur_id
             JOIN vente_lignes vl ON vl.vente_id=v.id
             WHERE v.date_vente BETWEEN :debut AND :fin
             GROUP BY v.vendeur_id ORDER BY ca DESC LIMIT 10");
        $stmt->execute([':debut'=>$debut,':fin'=>$fin]);
        return $stmt->fetchAll();
    }
}