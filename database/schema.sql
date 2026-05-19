-- ============================================================
-- TSINGY ROUGE MADAGASCAR — Schema complet
-- Importer dans phpMyAdmin sur la base tsingy_rouge
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";

CREATE DATABASE IF NOT EXISTS `tsingy_rouge` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tsingy_rouge`;

CREATE TABLE IF NOT EXISTS `users` (
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

CREATE TABLE IF NOT EXISTS `secteurs` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nom`         VARCHAR(150) NOT NULL,
  `description` TEXT,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `villes` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nom`        VARCHAR(150) NOT NULL,
  `secteur_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_villes_secteur` FOREIGN KEY (`secteur_id`) REFERENCES `secteurs`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `vendeurs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NULL,
  `nom`        VARCHAR(100) NOT NULL,
  `prenom`     VARCHAR(100) NOT NULL,
  `telephone`  VARCHAR(30),
  `email`      VARCHAR(150),
  `photo`      VARCHAR(255) DEFAULT NULL,
  `secteur_id` INT UNSIGNED NOT NULL,
  `statut`     ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vendeurs_secteur` FOREIGN KEY (`secteur_id`) REFERENCES `secteurs`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `clients` (
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

CREATE TABLE IF NOT EXISTS `produits` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nom`        VARCHAR(200) NOT NULL,
  `reference`  VARCHAR(80) UNIQUE,
  `taille`     VARCHAR(10),
  `couleur`    VARCHAR(50),
  `prix`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock`      INT NOT NULL DEFAULT 0,
  `stock_min`  INT NOT NULL DEFAULT 10,
  `image`      VARCHAR(255) DEFAULT NULL,
  `actif`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `stock_historique` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `produit_id`  INT UNSIGNED NOT NULL,
  `type`        ENUM('entree','sortie','ajustement') NOT NULL,
  `quantite`    INT NOT NULL,
  `stock_avant` INT NOT NULL,
  `stock_apres` INT NOT NULL,
  `note`        VARCHAR(255),
  `user_id`     INT UNSIGNED,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sh_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `ventes` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference`     VARCHAR(30) NOT NULL UNIQUE,
  `vendeur_id`    INT UNSIGNED NOT NULL,
  `client_id`     INT UNSIGNED NOT NULL,
  `secteur_id`    INT UNSIGNED NOT NULL,
  `ville_id`      INT UNSIGNED NOT NULL,
  `montant_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `note`          TEXT,
  `date_vente`    DATE NOT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ventes_vendeur` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ventes_client`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_ventes_secteur` FOREIGN KEY (`secteur_id`) REFERENCES `secteurs`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ventes_ville`   FOREIGN KEY (`ville_id`)   REFERENCES `villes`(`id`)   ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `vente_lignes` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vente_id`      INT UNSIGNED NOT NULL,
  `produit_id`    INT UNSIGNED NOT NULL,
  `quantite`      INT NOT NULL,
  `prix_unitaire` DECIMAL(10,2) NOT NULL,
  `sous_total`    DECIMAL(12,2) GENERATED ALWAYS AS (`quantite` * `prix_unitaire`) STORED,
  CONSTRAINT `fk_vl_vente`   FOREIGN KEY (`vente_id`)   REFERENCES `ventes`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_vl_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `pdr_objectifs` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `libelle`         VARCHAR(200) NOT NULL,
  `type_cible`      ENUM('global','vendeur','secteur','ville') NOT NULL DEFAULT 'global',
  `cible_id`        INT UNSIGNED DEFAULT NULL,
  `type_periode`    ENUM('jour','semaine','mois','annee') NOT NULL DEFAULT 'mois',
  `date_debut`      DATE NOT NULL,
  `date_fin`        DATE NOT NULL,
  `objectif_ca`     DECIMAL(14,2) DEFAULT 0.00,
  `objectif_pieces` INT DEFAULT 0,
  `objectif_ventes` INT DEFAULT 0,
  `note`            TEXT,
  `created_by`      INT UNSIGNED DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Compte admin par défaut
-- Email: admin@tsingy-rouge.mg  |  Mot de passe: générer via generer_hash.php
INSERT IGNORE INTO `users` (`nom`,`prenom`,`email`,`password`,`role`) VALUES
('Admin','Tsingy','admin@tsingy-rouge.mg','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.','admin');
-- ⚠️ Mot de passe par défaut = "password" → à changer avec generer_hash.php