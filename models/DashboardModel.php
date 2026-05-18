<?php
// ============================================================
// models/DashboardModel.php — Données statistiques dashboard
// ============================================================

class DashboardModel {

    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    // Cartes KPI principales
    public function getKpis(): array {
        $sql = "SELECT
            (SELECT COUNT(*) FROM ventes)                          AS total_ventes,
            (SELECT COALESCE(SUM(quantite),0) FROM vente_lignes)  AS total_pieces,
            (SELECT COALESCE(SUM(montant_total),0) FROM ventes)   AS total_ca,
            (SELECT COUNT(*) FROM vendeurs WHERE statut='actif')   AS nb_vendeurs,
            (SELECT COUNT(*) FROM clients)                         AS nb_clients,
            (SELECT COUNT(*) FROM produits WHERE stock <= stock_min AND actif=1) AS stock_faible,
            (SELECT COUNT(*) FROM ventes WHERE MONTH(date_vente)=MONTH(CURDATE()) AND YEAR(date_vente)=YEAR(CURDATE())) AS ventes_mois,
            (SELECT COALESCE(SUM(montant_total),0) FROM ventes WHERE MONTH(date_vente)=MONTH(CURDATE()) AND YEAR(date_vente)=YEAR(CURDATE())) AS ca_mois";
        return $this->db->query($sql)->fetch();
    }

    // Meilleur vendeur du mois
    public function getMeilleurVendeur(): array {
        $sql = "SELECT CONCAT(vd.prenom,' ',vd.nom) AS nom, 
                       SUM(v.montant_total) AS ca,
                       COUNT(v.id) AS nb_ventes
                FROM ventes v
                JOIN vendeurs vd ON vd.id = v.vendeur_id
                WHERE MONTH(v.date_vente)=MONTH(CURDATE()) AND YEAR(v.date_vente)=YEAR(CURDATE())
                GROUP BY v.vendeur_id
                ORDER BY ca DESC LIMIT 1";
        return $this->db->query($sql)->fetch() ?: [];
    }

    // Meilleur secteur du mois
    public function getMeilleurSecteur(): array {
        $sql = "SELECT s.nom, SUM(v.montant_total) AS ca, COUNT(v.id) AS nb_ventes
                FROM ventes v
                JOIN secteurs s ON s.id = v.secteur_id
                WHERE MONTH(v.date_vente)=MONTH(CURDATE()) AND YEAR(v.date_vente)=YEAR(CURDATE())
                GROUP BY v.secteur_id
                ORDER BY ca DESC LIMIT 1";
        return $this->db->query($sql)->fetch() ?: [];
    }

    // Top clients du mois (> 300 pièces)
    public function getTopClients(int $limit = 10): array {
        $sql = "SELECT c.id,
                       CONCAT(c.prenom,' ',c.nom) AS client_nom,
                       c.telephone,
                       s.nom AS secteur,
                       vl_sum.total_pieces,
                       vl_sum.total_ca
                FROM clients c
                JOIN (
                    SELECT v.client_id,
                           SUM(vl.quantite)    AS total_pieces,
                           SUM(v.montant_total) AS total_ca
                    FROM ventes v
                    JOIN vente_lignes vl ON vl.vente_id = v.id
                    WHERE MONTH(v.date_vente)=MONTH(CURDATE()) AND YEAR(v.date_vente)=YEAR(CURDATE())
                    GROUP BY v.client_id
                    HAVING total_pieces > :seuil
                ) vl_sum ON vl_sum.client_id = c.id
                LEFT JOIN secteurs s ON s.id = c.secteur_id
                ORDER BY vl_sum.total_pieces DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':seuil', CLIENT_TOP_SEUIL, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Évolution CA sur 12 mois
    public function getCaEvolution(): array {
        $sql = "SELECT DATE_FORMAT(date_vente,'%Y-%m') AS mois,
                       SUM(montant_total) AS ca,
                       COUNT(*) AS nb_ventes
                FROM ventes
                WHERE date_vente >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY mois
                ORDER BY mois ASC";
        return $this->db->query($sql)->fetchAll();
    }

    // Ventes par vendeur (mois courant)
    public function getVentesParVendeur(): array {
        $sql = "SELECT CONCAT(vd.prenom,' ',vd.nom) AS vendeur,
                       SUM(v.montant_total) AS ca,
                       SUM(vl.quantite) AS pieces,
                       COUNT(v.id) AS nb_ventes
                FROM ventes v
                JOIN vendeurs vd ON vd.id = v.vendeur_id
                JOIN vente_lignes vl ON vl.vente_id = v.id
                WHERE MONTH(v.date_vente)=MONTH(CURDATE()) AND YEAR(v.date_vente)=YEAR(CURDATE())
                GROUP BY v.vendeur_id
                ORDER BY ca DESC
                LIMIT 10";
        return $this->db->query($sql)->fetchAll();
    }

    // Ventes par secteur
    public function getVentesParSecteur(): array {
        $sql = "SELECT s.nom AS secteur,
                       SUM(v.montant_total) AS ca,
                       SUM(vl.quantite) AS pieces,
                       COUNT(DISTINCT v.client_id) AS nb_clients
                FROM ventes v
                JOIN secteurs s ON s.id = v.secteur_id
                JOIN vente_lignes vl ON vl.vente_id = v.id
                GROUP BY v.secteur_id
                ORDER BY ca DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // Top produits vendus
    public function getTopProduits(): array {
        $sql = "SELECT p.nom, p.reference,
                       SUM(vl.quantite) AS total_vendu,
                       SUM(vl.sous_total) AS total_ca,
                       p.stock
                FROM vente_lignes vl
                JOIN produits p ON p.id = vl.produit_id
                GROUP BY vl.produit_id
                ORDER BY total_vendu DESC
                LIMIT 8";
        return $this->db->query($sql)->fetchAll();
    }

    // Ventes récentes
    public function getVentesRecentes(int $limit = 8): array {
        $sql = "SELECT v.id, v.reference, v.date_vente, v.montant_total,
                       CONCAT(c.prenom,' ',c.nom) AS client,
                       CONCAT(vd.prenom,' ',vd.nom) AS vendeur,
                       s.nom AS secteur
                FROM ventes v
                JOIN clients c ON c.id = v.client_id
                JOIN vendeurs vd ON vd.id = v.vendeur_id
                JOIN secteurs s ON s.id = v.secteur_id
                ORDER BY v.created_at DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Produits stock faible
    public function getStockFaible(): array {
        $sql = "SELECT id, nom, reference, stock, stock_min
                FROM produits
                WHERE stock <= stock_min AND actif = 1
                ORDER BY stock ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
