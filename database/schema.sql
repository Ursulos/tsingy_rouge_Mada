-- ============================================================
-- TSINGY ROUGE MADAGASCAR - Schéma Base de Données
-- Version : 1.0
-- Encodage : UTF-8
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00"; -- Madagascar (EAT)

CREATE DATABASE IF NOT EXISTS `tsingy_rouge` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tsingy_rouge`;

-- ============================================================
-- TABLE : users (authentification)
-- ============================================================
CREATE TABLE `users` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nom`        VARCHAR(100) NOT NULL,
  `prenom`     VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','vendeur') NOT NULL DEFAULT 'vendeur',
  `statut`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : secteurs
-- ============================================================
CREATE TABLE `secteurs` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nom`         VARCHAR(150) NOT NULL,
  `description` TEXT,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : villes
-- ============================================================
CREATE TABLE `villes` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nom`        VARCHAR(150) NOT NULL,
  `secteur_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_villes_secteur` FOREIGN KEY (`secteur_id`)
    REFERENCES `secteurs`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX `idx_villes_secteur` ON `villes`(`secteur_id`);

-- ============================================================
-- TABLE : vendeurs
-- ============================================================
CREATE TABLE `vendeurs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NULL,               -- lié à un compte users
  `nom`        VARCHAR(100) NOT NULL,
  `prenom`     VARCHAR(100) NOT NULL,
  `telephone`  VARCHAR(30),
  `email`      VARCHAR(150),
  `photo`      VARCHAR(255) DEFAULT NULL,
  `secteur_id` INT UNSIGNED NOT NULL,
  `statut`     ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vendeurs_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE SET NULL,
  CONSTRAINT `fk_vendeurs_secteur` FOREIGN KEY (`secteur_id`) REFERENCES `secteurs`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX `idx_vendeurs_secteur` ON `vendeurs`(`secteur_id`);
CREATE INDEX `idx_vendeurs_statut`  ON `vendeurs`(`statut`);

-- ============================================================
-- TABLE : vendeur_villes (pivot : vendeur ↔ villes)
-- ============================================================
CREATE TABLE `vendeur_villes` (
  `vendeur_id` INT UNSIGNED NOT NULL,
  `ville_id`   INT UNSIGNED NOT NULL,
  PRIMARY KEY (`vendeur_id`, `ville_id`),
  CONSTRAINT `fk_vv_vendeur` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vv_ville`   FOREIGN KEY (`ville_id`)   REFERENCES `villes`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : clients
-- ============================================================
CREATE TABLE `clients` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nom`        VARCHAR(100) NOT NULL,
  `prenom`     VARCHAR(100) NOT NULL,
  `telephone`  VARCHAR(30),
  `email`      VARCHAR(150),
  `ville_id`   INT UNSIGNED,
  `secteur_id` INT UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_clients_ville`   FOREIGN KEY (`ville_id`)   REFERENCES `villes`(`id`)   ON DELETE SET NULL,
  CONSTRAINT `fk_clients_secteur` FOREIGN KEY (`secteur_id`) REFERENCES `secteurs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX `idx_clients_ville`   ON `clients`(`ville_id`);
CREATE INDEX `idx_clients_secteur` ON `clients`(`secteur_id`);

-- ============================================================
-- TABLE : produits (T-Shirts)
-- ============================================================
CREATE TABLE `produits` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nom`        VARCHAR(200) NOT NULL,
  `reference`  VARCHAR(80) UNIQUE,
  `taille`     VARCHAR(10),              -- XS, S, M, L, XL, XXL
  `couleur`    VARCHAR(50),
  `prix`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock`      INT NOT NULL DEFAULT 0,
  `stock_min`  INT NOT NULL DEFAULT 10,  -- seuil alerte stock faible
  `image`      VARCHAR(255) DEFAULT NULL,
  `actif`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX `idx_produits_stock` ON `produits`(`stock`);

-- ============================================================
-- TABLE : stock_historique
-- ============================================================
CREATE TABLE `stock_historique` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `produit_id`  INT UNSIGNED NOT NULL,
  `type`        ENUM('entree','sortie','ajustement') NOT NULL,
  `quantite`    INT NOT NULL,
  `stock_avant` INT NOT NULL,
  `stock_apres` INT NOT NULL,
  `note`        VARCHAR(255),
  `user_id`     INT UNSIGNED,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sh_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sh_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX `idx_sh_produit` ON `stock_historique`(`produit_id`);
CREATE INDEX `idx_sh_date`    ON `stock_historique`(`created_at`);

-- ============================================================
-- TABLE : ventes
-- ============================================================
CREATE TABLE `ventes` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference`   VARCHAR(30) NOT NULL UNIQUE,   -- ex: VTE-20260115-001
  `vendeur_id`  INT UNSIGNED NOT NULL,
  `client_id`   INT UNSIGNED NOT NULL,
  `secteur_id`  INT UNSIGNED NOT NULL,
  `ville_id`    INT UNSIGNED NOT NULL,
  `montant_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `note`        TEXT,
  `date_vente`  DATE NOT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ventes_vendeur` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs`(`id`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_ventes_client`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)   ON DELETE RESTRICT,
  CONSTRAINT `fk_ventes_secteur` FOREIGN KEY (`secteur_id`) REFERENCES `secteurs`(`id`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_ventes_ville`   FOREIGN KEY (`ville_id`)   REFERENCES `villes`(`id`)    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX `idx_ventes_vendeur`    ON `ventes`(`vendeur_id`);
CREATE INDEX `idx_ventes_client`     ON `ventes`(`client_id`);
CREATE INDEX `idx_ventes_secteur`    ON `ventes`(`secteur_id`);
CREATE INDEX `idx_ventes_date`       ON `ventes`(`date_vente`);

-- ============================================================
-- TABLE : vente_lignes (détail vente)
-- ============================================================
CREATE TABLE `vente_lignes` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vente_id`    INT UNSIGNED NOT NULL,
  `produit_id`  INT UNSIGNED NOT NULL,
  `quantite`    INT NOT NULL,
  `prix_unitaire` DECIMAL(10,2) NOT NULL,
  `sous_total`  DECIMAL(12,2) GENERATED ALWAYS AS (`quantite` * `prix_unitaire`) STORED,
  CONSTRAINT `fk_vl_vente`   FOREIGN KEY (`vente_id`)   REFERENCES `ventes`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_vl_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX `idx_vl_vente`   ON `vente_lignes`(`vente_id`);
CREATE INDEX `idx_vl_produit` ON `vente_lignes`(`produit_id`);

-- ============================================================
-- VUE : meilleurs clients (> 300 pièces/mois)
-- ============================================================
CREATE OR REPLACE VIEW `v_top_clients_mensuel` AS
SELECT
  c.id,
  CONCAT(c.prenom, ' ', c.nom) AS client_nom,
  c.telephone,
  s.nom AS secteur,
  v2.nom AS ville,
  DATE_FORMAT(v.date_vente, '%Y-%m') AS mois,
  SUM(vl.quantite) AS total_pieces,
  SUM(vl.sous_total) AS total_ca
FROM clients c
JOIN ventes v      ON v.client_id  = c.id
JOIN vente_lignes vl ON vl.vente_id = v.id
LEFT JOIN secteurs s ON s.id = c.secteur_id
LEFT JOIN villes v2  ON v2.id = c.ville_id
GROUP BY c.id, mois
HAVING total_pieces > 300
ORDER BY total_pieces DESC;

-- ============================================================
-- VUE : performances vendeurs
-- ============================================================
CREATE OR REPLACE VIEW `v_perf_vendeurs` AS
SELECT
  vd.id,
  CONCAT(vd.prenom, ' ', vd.nom) AS vendeur_nom,
  vd.photo,
  s.nom AS secteur,
  COUNT(DISTINCT v.id)    AS nb_ventes,
  SUM(vl.quantite)        AS total_pieces,
  SUM(v.montant_total)    AS total_ca,
  DATE_FORMAT(v.date_vente,'%Y-%m') AS mois
FROM vendeurs vd
LEFT JOIN ventes v        ON v.vendeur_id = vd.id
LEFT JOIN vente_lignes vl ON vl.vente_id  = v.id
LEFT JOIN secteurs s      ON s.id = vd.secteur_id
GROUP BY vd.id, mois;

-- ============================================================
-- VUE : performances secteurs
-- ============================================================
CREATE OR REPLACE VIEW `v_perf_secteurs` AS
SELECT
  s.id,
  s.nom AS secteur,
  COUNT(DISTINCT v.vendeur_id)  AS nb_vendeurs,
  COUNT(DISTINCT v.client_id)   AS nb_clients,
  COUNT(DISTINCT v.id)          AS nb_ventes,
  SUM(vl.quantite)              AS total_pieces,
  SUM(v.montant_total)          AS total_ca
FROM secteurs s
LEFT JOIN ventes v        ON v.secteur_id = s.id
LEFT JOIN vente_lignes vl ON vl.vente_id  = v.id
GROUP BY s.id;

-- ============================================================
-- DONNÉES INITIALES
-- ============================================================

-- Compte admin par défaut (password: Admin@2026)
INSERT INTO `users` (`nom`,`prenom`,`email`,`password`,`role`) VALUES
('Admin','Tsingy','admin@tsingy-rouge.mg', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'admin');

-- Secteurs d'exemple
INSERT INTO `secteurs` (`nom`, `description`) VALUES
('Antananarivo',  'Capitale et région centrale'),
('Fianarantsoa',  'Région des Hautes Terres du Sud'),
('Toamasina',     'Côte Est - Port principal'),
('Mahajanga',     'Côte Ouest - Boeny');

-- Villes d'exemple
INSERT INTO `villes` (`nom`, `secteur_id`) VALUES
('Antananarivo-Renivohitra', 1),
('Ambohidratrimo', 1),
('Fianarantsoa I', 2),
('Ambositra', 2),
('Toamasina I', 3),
('Brickaville', 3),
('Mahajanga I', 4),
('Mitsinjo', 4);
