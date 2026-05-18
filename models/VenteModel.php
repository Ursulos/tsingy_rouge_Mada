<?php
// ============================================================
// models/VenteModel.php
// ============================================================

class VenteModel
{

    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['vendeur_id'])) {
            $where[] = 'v.vendeur_id = :vendeur_id';
            $params[':vendeur_id'] = $filters['vendeur_id'];
        }
        if (!empty($filters['client_id'])) {
            $where[] = 'v.client_id = :client_id';
            $params[':client_id'] = $filters['client_id'];
        }
        if (!empty($filters['secteur_id'])) {
            $where[] = 'v.secteur_id = :secteur_id';
            $params[':secteur_id'] = $filters['secteur_id'];
        }
        if (!empty($filters['ville_id'])) {
            $where[] = 'v.ville_id = :ville_id';
            $params[':ville_id'] = $filters['ville_id'];
        }
        if (!empty($filters['date_debut'])) {
            $where[] = 'v.date_vente >= :date_debut';
            $params[':date_debut'] = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $where[] = 'v.date_vente <= :date_fin';
            $params[':date_fin'] = $filters['date_fin'];
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT v.id, v.reference, v.date_vente, v.montant_total, v.note,
                       CONCAT(c.prenom,' ',c.nom) AS client,
                       CONCAT(vd.prenom,' ',vd.nom) AS vendeur,
                       s.nom AS secteur, vi.nom AS ville,
                       (SELECT SUM(quantite) FROM vente_lignes WHERE vente_id=v.id) AS total_pieces
                FROM ventes v
                JOIN clients c   ON c.id  = v.client_id
                JOIN vendeurs vd ON vd.id = v.vendeur_id
                JOIN secteurs s  ON s.id  = v.secteur_id
                JOIN villes vi   ON vi.id = v.ville_id
                WHERE $whereStr
                ORDER BY v.date_vente DESC, v.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $val) $stmt->bindValue($k, $val);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['vendeur_id'])) {
            $where[] = 'vendeur_id = :vendeur_id';
            $params[':vendeur_id']  = $filters['vendeur_id'];
        }
        if (!empty($filters['secteur_id'])) {
            $where[] = 'secteur_id = :secteur_id';
            $params[':secteur_id']  = $filters['secteur_id'];
        }
        if (!empty($filters['ville_id'])) {
            $where[] = 'ville_id   = :ville_id';
            $params[':ville_id']    = $filters['ville_id'];
        }
        if (!empty($filters['date_debut'])) {
            $where[] = 'date_vente >= :date_debut';
            $params[':date_debut']  = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $where[] = 'date_vente <= :date_fin';
            $params[':date_fin']    = $filters['date_fin'];
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ventes WHERE " . implode(' AND ', $where));
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function getById(int $id): array
    {
        $stmt = $this->db->prepare("SELECT v.*, 
               CONCAT(c.prenom,' ',c.nom) AS client_nom,
               CONCAT(vd.prenom,' ',vd.nom) AS vendeur_nom,
               s.nom AS secteur_nom, vi.nom AS ville_nom
            FROM ventes v
            JOIN clients c   ON c.id  = v.client_id
            JOIN vendeurs vd ON vd.id = v.vendeur_id
            JOIN secteurs s  ON s.id  = v.secteur_id
            JOIN villes vi   ON vi.id = v.ville_id
            WHERE v.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: [];
    }

    public function getLignes(int $venteId): array
    {
        $stmt = $this->db->prepare("SELECT vl.*, p.nom AS produit_nom, p.reference AS produit_ref
            FROM vente_lignes vl
            JOIN produits p ON p.id = vl.produit_id
            WHERE vl.vente_id = :id");
        $stmt->execute([':id' => $venteId]);
        return $stmt->fetchAll();
    }

    public function create(array $data, array $lignes): int
    {
        $this->db->beginTransaction();

        try {

            // Calcul du montant total
            $montant = array_sum(array_map(
                fn($l) => $l['quantite'] * $l['prix'],
                $lignes
            ));

            // Insertion provisoire avec référence temporaire
            $stmt = $this->db->prepare("
            INSERT INTO ventes
            (reference, vendeur_id, client_id, secteur_id, ville_id, montant_total, note, date_vente)
            VALUES
            ('TMP', :vd, :cl, :se, :vi, :mt, :note, :dv)
        ");

            $stmt->execute([
                ':vd'   => $data['vendeur_id'],
                ':cl'   => $data['client_id'],
                ':se'   => $data['secteur_id'],
                ':vi'   => $data['ville_id'],
                ':mt'   => $montant,
                ':note' => $data['note'] ?? null,
                ':dv'   => $data['date_vente'],
            ]);

            // ID généré automatiquement
            $venteId = (int) $this->db->lastInsertId();

            // Génération de la vraie référence unique
            $ref = "VTE-" . date('Ymd') . "-" . str_pad($venteId, 3, '0', STR_PAD_LEFT);

            // Mise à jour de la référence
            $updateRef = $this->db->prepare("
            UPDATE ventes
            SET reference = :ref
            WHERE id = :id
        ");

            $updateRef->execute([
                ':ref' => $ref,
                ':id'  => $venteId
            ]);

            // Préparation des requêtes
            $stmtL = $this->db->prepare("
            INSERT INTO vente_lignes
            (vente_id, produit_id, quantite, prix_unitaire)
            VALUES
            (:vid, :pid, :qty, :pu)
        ");

            $stmtS = $this->db->prepare("
            SELECT stock
            FROM produits
            WHERE id = :id
            FOR UPDATE
        ");

            $stmtU = $this->db->prepare("
            UPDATE produits
            SET stock = stock - :qty
            WHERE id = :id
        ");

            $stmtH = $this->db->prepare("
            INSERT INTO stock_historique
            (produit_id, type, quantite, stock_avant, stock_apres, note, user_id)
            VALUES
            (:pid, 'sortie', :qty, :sa, :sp, :note, :uid)
        ");

            // Insertion des lignes
            foreach ($lignes as $l) {

                $stmtS->execute([
                    ':id' => $l['produit_id']
                ]);

                $stockAvant = (int) $stmtS->fetchColumn();
                $stockApres = $stockAvant - (int) $l['quantite'];

                // Ligne vente
                $stmtL->execute([
                    ':vid' => $venteId,
                    ':pid' => $l['produit_id'],
                    ':qty' => $l['quantite'],
                    ':pu'  => $l['prix']
                ]);

                // Mise à jour stock
                $stmtU->execute([
                    ':qty' => $l['quantite'],
                    ':id'  => $l['produit_id']
                ]);

                // Historique stock
                $stmtH->execute([
                    ':pid'  => $l['produit_id'],
                    ':qty'  => $l['quantite'],
                    ':sa'   => $stockAvant,
                    ':sp'   => $stockApres,
                    ':note' => "Vente $ref",
                    ':uid'  => $_SESSION['user_id'] ?? null
                ]);
            }

            $this->db->commit();

            return $venteId;
        } catch (Exception $e) {

            $this->db->rollBack();

            throw $e;
        }
    }

    // ---- Comparaison de périodes (jusqu'à 5 intervalles) ----
    public function comparerPeriodes(array $periodes, ?int $vendeurId = null, ?int $secteurId = null, ?int $villeId = null): array
    {
        $results = [];
        foreach ($periodes as $i => $p) {
            if (empty($p['debut']) || empty($p['fin'])) continue;
            $where = ['v.date_vente BETWEEN :debut AND :fin'];
            $params = [':debut' => $p['debut'], ':fin' => $p['fin']];

            if ($vendeurId) {
                $where[] = 'v.vendeur_id = :vid';
                $params[':vid'] = $vendeurId;
            }
            if ($secteurId) {
                $where[] = 'v.secteur_id = :sid';
                $params[':sid'] = $secteurId;
            }
            if ($villeId) {
                $where[] = 'v.ville_id   = :wid';
                $params[':wid'] = $villeId;
            }

            $whereStr = implode(' AND ', $where);
            $sql = "SELECT
                COUNT(DISTINCT v.id)         AS nb_ventes,
                COALESCE(SUM(v.montant_total),0)   AS ca,
                COALESCE(SUM(vl.quantite),0)       AS pieces,
                COUNT(DISTINCT v.client_id)  AS nb_clients,
                COUNT(DISTINCT v.vendeur_id) AS nb_vendeurs
            FROM ventes v
            JOIN vente_lignes vl ON vl.vente_id = v.id
            WHERE $whereStr";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            $row['label'] = $p['label'] ?? ('Période ' . ($i + 1));
            $row['debut'] = $p['debut'];
            $row['fin']   = $p['fin'];
            $results[] = $row;
        }
        return $results;
    }

    // Top vendeurs sur une période
    public function topVendeursPeriode(string $debut, string $fin): array
    {
        $sql = "SELECT CONCAT(vd.prenom,' ',vd.nom) AS vendeur,
                       s.nom AS secteur,
                       SUM(v.montant_total) AS ca,
                       SUM(vl.quantite) AS pieces,
                       COUNT(v.id) AS nb_ventes
                FROM ventes v
                JOIN vendeurs vd ON vd.id = v.vendeur_id
                JOIN secteurs s  ON s.id  = vd.secteur_id
                JOIN vente_lignes vl ON vl.vente_id = v.id
                WHERE v.date_vente BETWEEN :debut AND :fin
                GROUP BY v.vendeur_id
                ORDER BY ca DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':debut' => $debut, ':fin' => $fin]);
        return $stmt->fetchAll();
    }
}
