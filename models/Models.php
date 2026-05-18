<?php
// ============================================================
// models/VendeurModel.php
// ============================================================
class VendeurModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function getAll(int $limit = 100, int $offset = 0): array {
        $sql = "SELECT vd.*, s.nom AS secteur_nom,
                (SELECT COUNT(*) FROM ventes WHERE vendeur_id=vd.id) AS nb_ventes,
                (SELECT COALESCE(SUM(montant_total),0) FROM ventes WHERE vendeur_id=vd.id) AS total_ca
                FROM vendeurs vd
                JOIN secteurs s ON s.id = vd.secteur_id
                ORDER BY vd.nom ASC LIMIT :l OFFSET :o";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $id): array {
        $stmt = $this->db->prepare("SELECT vd.*, s.nom AS secteur_nom FROM vendeurs vd JOIN secteurs s ON s.id=vd.secteur_id WHERE vd.id=:id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: [];
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare("INSERT INTO vendeurs (nom,prenom,telephone,email,photo,secteur_id,statut) VALUES (:n,:p,:t,:e,:ph,:s,:st)");
        $stmt->execute([':n'=>$d['nom'],':p'=>$d['prenom'],':t'=>$d['telephone']??null,':e'=>$d['email']??null,':ph'=>$d['photo']??null,':s'=>$d['secteur_id'],':st'=>$d['statut']??'actif']);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $stmt = $this->db->prepare("UPDATE vendeurs SET nom=:n,prenom=:p,telephone=:t,email=:e,secteur_id=:s,statut=:st,updated_at=NOW() WHERE id=:id");
        $stmt->execute([':n'=>$d['nom'],':p'=>$d['prenom'],':t'=>$d['telephone']??null,':e'=>$d['email']??null,':s'=>$d['secteur_id'],':st'=>$d['statut'],':id'=>$id]);
        if (!empty($d['photo'])) {
            $this->db->prepare("UPDATE vendeurs SET photo=:ph WHERE id=:id")->execute([':ph'=>$d['photo'],':id'=>$id]);
        }
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM vendeurs WHERE id=:id")->execute([':id'=>$id]);
    }

    public function getStats(int $id, ?string $debut = null, ?string $fin = null): array {
        $params = [':id' => $id];
        $where  = 'v.vendeur_id = :id';
        if ($debut) { $where .= ' AND v.date_vente >= :debut'; $params[':debut'] = $debut; }
        if ($fin)   { $where .= ' AND v.date_vente <= :fin';   $params[':fin']   = $fin; }

        $sql = "SELECT COUNT(v.id) AS nb_ventes, COALESCE(SUM(v.montant_total),0) AS ca, COALESCE(SUM(vl.quantite),0) AS pieces
                FROM ventes v JOIN vente_lignes vl ON vl.vente_id=v.id WHERE $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [];
    }

    public function classement(): array {
        $sql = "SELECT CONCAT(vd.prenom,' ',vd.nom) AS vendeur, s.nom AS secteur,
                       SUM(v.montant_total) AS ca, SUM(vl.quantite) AS pieces, COUNT(v.id) AS nb_ventes,
                       RANK() OVER (ORDER BY SUM(v.montant_total) DESC) AS rang
                FROM vendeurs vd
                LEFT JOIN ventes v ON v.vendeur_id=vd.id
                LEFT JOIN vente_lignes vl ON vl.vente_id=v.id
                LEFT JOIN secteurs s ON s.id=vd.secteur_id
                WHERE vd.statut='actif'
                GROUP BY vd.id ORDER BY ca DESC";
        return $this->db->query($sql)->fetchAll();
    }
}


// ============================================================
// models/ClientModel.php
// ============================================================
class ClientModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function getAll(int $limit = 100, int $offset = 0, array $f = []): array {
        $where = ['1=1']; $params = [];
        if (!empty($f['secteur_id'])) { $where[] = 'c.secteur_id=:s'; $params[':s']=$f['secteur_id']; }
        if (!empty($f['ville_id']))   { $where[] = 'c.ville_id=:v';   $params[':v']=$f['ville_id']; }
        if (!empty($f['search']))     { $where[] = "(c.nom LIKE :q OR c.prenom LIKE :q OR c.telephone LIKE :q)"; $params[':q']='%'.$f['search'].'%'; }
        $whereStr = implode(' AND ', $where);
        $sql = "SELECT c.*, s.nom AS secteur_nom, v.nom AS ville_nom FROM clients c
                LEFT JOIN secteurs s ON s.id=c.secteur_id
                LEFT JOIN villes v ON v.id=c.ville_id
                WHERE $whereStr ORDER BY c.nom ASC LIMIT :l OFFSET :o";
        $stmt = $this->db->prepare($sql);
        foreach($params as $k=>$val) $stmt->bindValue($k,$val);
        $stmt->bindValue(':l',$limit,PDO::PARAM_INT);
        $stmt->bindValue(':o',$offset,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $id): array {
        $stmt = $this->db->prepare("SELECT c.*, s.nom AS secteur_nom, v.nom AS ville_nom FROM clients c LEFT JOIN secteurs s ON s.id=c.secteur_id LEFT JOIN villes v ON v.id=c.ville_id WHERE c.id=:id");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch() ?: [];
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare("INSERT INTO clients (nom,prenom,telephone,email,ville_id,secteur_id) VALUES (:n,:p,:t,:e,:v,:s)");
        $stmt->execute([':n'=>$d['nom'],':p'=>$d['prenom'],':t'=>$d['telephone']??null,':e'=>$d['email']??null,':v'=>$d['ville_id']??null,':s'=>$d['secteur_id']??null]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $stmt = $this->db->prepare("UPDATE clients SET nom=:n,prenom=:p,telephone=:t,email=:e,ville_id=:v,secteur_id=:s WHERE id=:id");
        $stmt->execute([':n'=>$d['nom'],':p'=>$d['prenom'],':t'=>$d['telephone']??null,':e'=>$d['email']??null,':v'=>$d['ville_id']??null,':s'=>$d['secteur_id']??null,':id'=>$id]);
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM clients WHERE id=:id")->execute([':id'=>$id]);
    }

    public function topClients(int $mois, int $annee, int $seuil = 300): array {
        $sql = "SELECT c.id, CONCAT(c.prenom,' ',c.nom) AS nom, c.telephone,
                       s.nom AS secteur, vl_s.total_pieces, vl_s.total_ca
                FROM clients c
                JOIN (SELECT v.client_id, SUM(vl.quantite) AS total_pieces, SUM(v.montant_total) AS total_ca
                      FROM ventes v JOIN vente_lignes vl ON vl.vente_id=v.id
                      WHERE MONTH(v.date_vente)=:m AND YEAR(v.date_vente)=:y
                      GROUP BY v.client_id HAVING total_pieces > :seuil) vl_s ON vl_s.client_id=c.id
                LEFT JOIN secteurs s ON s.id=c.secteur_id
                ORDER BY vl_s.total_pieces DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':m'=>$mois,':y'=>$annee,':seuil'=>$seuil]);
        return $stmt->fetchAll();
    }
}


// ============================================================
// models/ProduitModel.php
// ============================================================
class ProduitModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function getAll(bool $actifSeulement = false): array {
        $sql = "SELECT * FROM produits" . ($actifSeulement ? " WHERE actif=1" : "") . " ORDER BY nom ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getById(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM produits WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch() ?: [];
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare("INSERT INTO produits (nom,reference,taille,couleur,prix,stock,stock_min,image) VALUES (:n,:r,:t,:c,:p,:s,:sm,:i)");
        $stmt->execute([':n'=>$d['nom'],':r'=>$d['reference']??null,':t'=>$d['taille']??null,':c'=>$d['couleur']??null,':p'=>$d['prix'],':s'=>$d['stock']??0,':sm'=>$d['stock_min']??10,':i'=>$d['image']??null]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $stmt = $this->db->prepare("UPDATE produits SET nom=:n,reference=:r,taille=:t,couleur=:c,prix=:p,stock=:s,stock_min=:sm WHERE id=:id");
        $stmt->execute([':n'=>$d['nom'],':r'=>$d['reference']??null,':t'=>$d['taille']??null,':c'=>$d['couleur']??null,':p'=>$d['prix'],':s'=>$d['stock'],':sm'=>$d['stock_min']??10,':id'=>$id]);
        if (!empty($d['image'])) $this->db->prepare("UPDATE produits SET image=:i WHERE id=:id")->execute([':i'=>$d['image'],':id'=>$id]);
    }

    public function delete(int $id): void {
        $this->db->prepare("UPDATE produits SET actif=0 WHERE id=:id")->execute([':id'=>$id]);
    }

    public function getStockFaible(): array {
        return $this->db->query("SELECT * FROM produits WHERE stock<=stock_min AND actif=1 ORDER BY stock ASC")->fetchAll();
    }

    public function getHistorique(int $produitId): array {
        $stmt = $this->db->prepare("SELECT sh.*, u.nom AS user_nom FROM stock_historique sh LEFT JOIN users u ON u.id=sh.user_id WHERE sh.produit_id=:id ORDER BY sh.created_at DESC LIMIT 50");
        $stmt->execute([':id'=>$produitId]);
        return $stmt->fetchAll();
    }
}


// ============================================================
// models/SecteurModel.php
// ============================================================
class SecteurModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function getAll(): array {
        return $this->db->query("SELECT s.*, COUNT(v.id) AS nb_villes FROM secteurs s LEFT JOIN villes v ON v.secteur_id=s.id GROUP BY s.id ORDER BY s.nom")->fetchAll();
    }

    public function getById(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM secteurs WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch() ?: [];
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare("INSERT INTO secteurs (nom,description) VALUES (:n,:d)");
        $stmt->execute([':n'=>$d['nom'],':d'=>$d['description']??null]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $this->db->prepare("UPDATE secteurs SET nom=:n,description=:d WHERE id=:id")->execute([':n'=>$d['nom'],':d'=>$d['description']??null,':id'=>$id]);
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM secteurs WHERE id=:id")->execute([':id'=>$id]);
    }

    public function getVilles(int $secteurId): array {
        $stmt = $this->db->prepare("SELECT * FROM villes WHERE secteur_id=:id ORDER BY nom");
        $stmt->execute([':id'=>$secteurId]);
        return $stmt->fetchAll();
    }
}


// ============================================================
// models/VilleModel.php
// ============================================================
class VilleModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function getAll(): array {
        return $this->db->query("SELECT v.*, s.nom AS secteur_nom FROM villes v JOIN secteurs s ON s.id=v.secteur_id ORDER BY v.nom")->fetchAll();
    }

    public function getBySecteur(int $secteurId): array {
        $stmt = $this->db->prepare("SELECT * FROM villes WHERE secteur_id=:id ORDER BY nom");
        $stmt->execute([':id'=>$secteurId]);
        return $stmt->fetchAll();
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare("INSERT INTO villes (nom,secteur_id) VALUES (:n,:s)");
        $stmt->execute([':n'=>$d['nom'],':s'=>$d['secteur_id']]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $this->db->prepare("UPDATE villes SET nom=:n,secteur_id=:s WHERE id=:id")->execute([':n'=>$d['nom'],':s'=>$d['secteur_id'],':id'=>$id]);
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM villes WHERE id=:id")->execute([':id'=>$id]);
    }
}
