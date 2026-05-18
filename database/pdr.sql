-- ============================================================
-- PDR — À exécuter dans phpMyAdmin sur la base tsingy_rouge
-- OBLIGATOIRE pour que la page Suivi fonctionne
-- ============================================================

CREATE TABLE IF NOT EXISTS `pdr_objectifs` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `libelle`         VARCHAR(200) NOT NULL,
  `type_cible`      ENUM('global','vendeur','secteur','ville') NOT NULL DEFAULT 'global',
  `cible_id`        INT UNSIGNED DEFAULT NULL,
  `type_periode`    ENUM('jour','semaine','mois','annee') NOT NULL DEFAULT 'mois',
  `date_debut`      DATE NOT NULL,
  `date_fin`        DATE NOT NULL,
  `objectif_ca`     DECIMAL(14,2) DEFAULT 0.00,
  `objectif_pieces` INT          DEFAULT 0,
  `objectif_ventes` INT          DEFAULT 0,
  `note`            TEXT,
  `created_by`      INT UNSIGNED DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pdr_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
