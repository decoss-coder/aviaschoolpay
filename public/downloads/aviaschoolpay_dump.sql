-- ======================================================
-- AviaSchoolPay — Dump complet de la base de données
-- Base       : aviaschoolpay
-- Généré le  : 2026-05-23 15:51:58
-- ======================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ─────────── Table : `affectations` ───────────
DROP TABLE IF EXISTS `affectations`;
CREATE TABLE `affectations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enseignant_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `matiere_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `volume_horaire_hebdo` decimal(4,1) NOT NULL DEFAULT '2.0',
  `est_professeur_principal` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `affectation_unique` (`enseignant_id`,`classe_id`,`matiere_id`,`annee_scolaire_id`),
  KEY `affectations_classe_id_foreign` (`classe_id`),
  KEY `affectations_matiere_id_foreign` (`matiere_id`),
  KEY `affectations_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  CONSTRAINT `affectations_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `affectations_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `affectations_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `affectations_matiere_id_foreign` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `alertes_financieres` ───────────
DROP TABLE IF EXISTS `alertes_financieres`;
CREATE TABLE `alertes_financieres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `type` enum('depassement_budget','tresorerie_basse','masse_salariale_elevee','depense_anormale','depense_inhabituelle','deficit_structurel','impaye_critique','risque_liquidite','seuil_rentabilite','ecart_budget','charge_recurrente_oubliee','anomalie_comptable') COLLATE utf8mb4_unicode_ci NOT NULL,
  `gravite` enum('info','warning','critique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `recommandation_ia` text COLLATE utf8mb4_unicode_ci,
  `montant_concerne` decimal(14,0) DEFAULT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `lue` tinyint(1) NOT NULL DEFAULT '0',
  `traitee` tinyint(1) NOT NULL DEFAULT '0',
  `traitee_par` bigint unsigned DEFAULT NULL,
  `action_prise` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alertes_financieres_traitee_par_foreign` (`traitee_par`),
  KEY `af_etab_trait_grav_idx` (`etablissement_id`,`traitee`,`gravite`),
  CONSTRAINT `alertes_financieres_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alertes_financieres_traitee_par_foreign` FOREIGN KEY (`traitee_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `alertes_pointage` ───────────
DROP TABLE IF EXISTS `alertes_pointage`;
CREATE TABLE `alertes_pointage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `enseignant_id` bigint unsigned NOT NULL,
  `pointage_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `type_alerte` enum('absence','retard','hors_zone','spoofing_gps','scan_trop_court','salle_incorrecte','absence_repetee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `gravite` enum('info','warning','critique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lue` tinyint(1) NOT NULL DEFAULT '0',
  `traitee` tinyint(1) NOT NULL DEFAULT '0',
  `traitee_par` bigint unsigned DEFAULT NULL,
  `commentaire_traitement` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alertes_pointage_enseignant_id_foreign` (`enseignant_id`),
  KEY `alertes_pointage_pointage_id_foreign` (`pointage_id`),
  KEY `alertes_pointage_traitee_par_foreign` (`traitee_par`),
  KEY `alertes_pointage_etablissement_id_date_lue_index` (`etablissement_id`,`date`,`lue`),
  CONSTRAINT `alertes_pointage_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alertes_pointage_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alertes_pointage_pointage_id_foreign` FOREIGN KEY (`pointage_id`) REFERENCES `pointages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `alertes_pointage_traitee_par_foreign` FOREIGN KEY (`traitee_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `analyses_masse_salariale` ───────────
DROP TABLE IF EXISTS `analyses_masse_salariale`;
CREATE TABLE `analyses_masse_salariale` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_salaires` decimal(14,0) NOT NULL DEFAULT '0',
  `total_charges_sociales` decimal(14,0) NOT NULL DEFAULT '0',
  `total_primes` decimal(14,0) NOT NULL DEFAULT '0',
  `masse_salariale_totale` decimal(14,0) NOT NULL DEFAULT '0',
  `revenus_mois` decimal(14,0) NOT NULL DEFAULT '0',
  `ratio_ms_revenus` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Masse salariale / Revenus en %',
  `nb_enseignants` smallint unsigned NOT NULL DEFAULT '0',
  `nb_personnel_admin` smallint unsigned NOT NULL DEFAULT '0',
  `cout_moyen_enseignant` decimal(12,0) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ams_etab_mois_unique` (`etablissement_id`,`mois`),
  CONSTRAINT `analyses_masse_salariale_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `analyses_rentabilite` ───────────
DROP TABLE IF EXISTS `analyses_rentabilite`;
CREATE TABLE `analyses_rentabilite` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `exercice_id` bigint unsigned NOT NULL,
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Null = annuel',
  `niveau_analyse` enum('etablissement','classe','niveau','filiere','service','centre_profit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cible_label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: 3ème A, Cantine, Série D',
  `cible_id` bigint unsigned DEFAULT NULL,
  `revenus` decimal(14,0) NOT NULL DEFAULT '0',
  `couts_directs` decimal(14,0) NOT NULL DEFAULT '0' COMMENT 'Salaires enseignants, fournitures',
  `couts_indirects` decimal(14,0) NOT NULL DEFAULT '0' COMMENT 'Part loyer, électricité, admin',
  `cout_total` decimal(14,0) NOT NULL DEFAULT '0',
  `marge_brute` decimal(14,0) NOT NULL DEFAULT '0',
  `marge_nette` decimal(14,0) NOT NULL DEFAULT '0',
  `taux_marge` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Marge / Revenus en %',
  `rentable` tinyint(1) NOT NULL DEFAULT '1',
  `nb_eleves` smallint unsigned NOT NULL DEFAULT '0',
  `revenu_par_eleve` decimal(12,0) NOT NULL DEFAULT '0',
  `cout_par_eleve` decimal(12,0) NOT NULL DEFAULT '0',
  `marge_par_eleve` decimal(12,0) NOT NULL DEFAULT '0',
  `nb_enseignants` smallint unsigned NOT NULL DEFAULT '0',
  `cout_par_enseignant` decimal(12,0) NOT NULL DEFAULT '0',
  `details` json DEFAULT NULL COMMENT 'Ventilation détaillée',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analyses_rentabilite_exercice_id_foreign` (`exercice_id`),
  KEY `ar_etab_exo_niv_idx` (`etablissement_id`,`exercice_id`,`niveau_analyse`),
  CONSTRAINT `analyses_rentabilite_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `analyses_rentabilite_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices_comptables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `annee_scolaire_restauration_demandes` ───────────
DROP TABLE IF EXISTS `annee_scolaire_restauration_demandes`;
CREATE TABLE `annee_scolaire_restauration_demandes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `demandeur_id` bigint unsigned NOT NULL,
  `montant_fcfa` int unsigned NOT NULL DEFAULT '500',
  `statut` enum('en_attente_paiement','paye','cle_livree','restauree','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente_paiement',
  `reference` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wave_checkout_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paye_at` timestamp NULL DEFAULT NULL,
  `cle_livree_at` timestamp NULL DEFAULT NULL,
  `restauree_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `annee_scolaire_restauration_demandes_reference_unique` (`reference`),
  KEY `annee_scolaire_restauration_demandes_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `annee_scolaire_restauration_demandes_demandeur_id_foreign` (`demandeur_id`),
  KEY `as_restauration_etab_statut_idx` (`etablissement_id`,`statut`),
  CONSTRAINT `annee_scolaire_restauration_demandes_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annee_scolaire_restauration_demandes_demandeur_id_foreign` FOREIGN KEY (`demandeur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annee_scolaire_restauration_demandes_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `annees_scolaires` ───────────
DROP TABLE IF EXISTS `annees_scolaires`;
CREATE TABLE `annees_scolaires` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `libelle` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: 2025-2026',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `en_cours` tinyint(1) NOT NULL DEFAULT '0',
  `cloturee` tinyint(1) NOT NULL DEFAULT '0',
  `archivee` tinyint(1) NOT NULL DEFAULT '0',
  `archive_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archive_checksum` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `restoration_key_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `restoration_key_vault` text COLLATE utf8mb4_unicode_ci COMMENT 'Clé chiffrée (APP_KEY) — livrable par super admin après paiement',
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` bigint unsigned DEFAULT NULL,
  `archive_meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `annees_scolaires_etablissement_id_libelle_unique` (`etablissement_id`,`libelle`),
  KEY `annees_scolaires_archived_by_foreign` (`archived_by`),
  CONSTRAINT `annees_scolaires_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `annees_scolaires_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `annees_scolaires` VALUES
(2, 1, '2026-2027', '2026-09-01', '2027-07-31', 1, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-23 12:03:56', '2026-05-23 12:03:56'),
(3, 3, '2026-2027', '2026-09-01', '2027-06-30', 1, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-23 12:07:29', '2026-05-23 12:07:29');

-- ─────────── Table : `annonces` ───────────
DROP TABLE IF EXISTS `annonces`;
CREATE TABLE `annonces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `auteur_id` bigint unsigned NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('annonce','circulaire','convocation','evenement','urgent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` enum('tous','parents','enseignants','eleves','personnel') COLLATE utf8mb4_unicode_ci NOT NULL,
  `piece_jointe_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_debut_affichage` date NOT NULL,
  `date_fin_affichage` date DEFAULT NULL,
  `envoyer_sms` tinyint(1) NOT NULL DEFAULT '0',
  `envoyer_notification` tinyint(1) NOT NULL DEFAULT '1',
  `publiee` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `annonces_auteur_id_foreign` (`auteur_id`),
  KEY `annonces_etablissement_id_publiee_index` (`etablissement_id`,`publiee`),
  CONSTRAINT `annonces_auteur_id_foreign` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annonces_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `api_sync_dedup` ───────────
DROP TABLE IF EXISTS `api_sync_dedup`;
CREATE TABLE `api_sync_dedup` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `client_mutation_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_id` bigint unsigned DEFAULT NULL,
  `response_snapshot` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_sync_dedup_user_id_client_mutation_id_unique` (`user_id`,`client_mutation_id`),
  CONSTRAINT `api_sync_dedup_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `budgets` ───────────
DROP TABLE IF EXISTS `budgets`;
CREATE TABLE `budgets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `exercice_id` bigint unsigned NOT NULL,
  `libelle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Budget annuel 2025-2026',
  `periodicite` enum('mensuel','trimestriel','annuel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'annuel',
  `total_prevu_revenus` decimal(14,0) NOT NULL DEFAULT '0',
  `total_prevu_depenses` decimal(14,0) NOT NULL DEFAULT '0',
  `total_reel_revenus` decimal(14,0) NOT NULL DEFAULT '0',
  `total_reel_depenses` decimal(14,0) NOT NULL DEFAULT '0',
  `statut` enum('brouillon','valide','en_cours','cloture') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `cree_par` bigint unsigned NOT NULL,
  `valide_par` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budgets_etablissement_id_foreign` (`etablissement_id`),
  KEY `budgets_exercice_id_foreign` (`exercice_id`),
  KEY `budgets_cree_par_foreign` (`cree_par`),
  KEY `budgets_valide_par_foreign` (`valide_par`),
  CONSTRAINT `budgets_cree_par_foreign` FOREIGN KEY (`cree_par`) REFERENCES `users` (`id`),
  CONSTRAINT `budgets_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budgets_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices_comptables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budgets_valide_par_foreign` FOREIGN KEY (`valide_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `bulletins` ───────────
DROP TABLE IF EXISTS `bulletins`;
CREATE TABLE `bulletins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `trimestre_id` bigint unsigned NOT NULL,
  `fichier_pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('brouillon','valide','publie','imprime') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `signe_par_directeur` tinyint(1) NOT NULL DEFAULT '0',
  `remis_parent` tinyint(1) NOT NULL DEFAULT '0',
  `date_remise` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bulletins_eleve_id_trimestre_id_unique` (`eleve_id`,`trimestre_id`),
  KEY `bulletins_classe_id_foreign` (`classe_id`),
  KEY `bulletins_trimestre_id_foreign` (`trimestre_id`),
  CONSTRAINT `bulletins_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bulletins_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bulletins_trimestre_id_foreign` FOREIGN KEY (`trimestre_id`) REFERENCES `trimestres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `cache` ───────────
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cache` VALUES
('laravel-cache-platform_setting.sms_prix_unitaire_fcfa', 's:2:"50";', 1779539923),
('laravel-cache-platform_setting.wave_libelle_restauration', 's:16:"Avia Technologie";', 1779534815),
('laravel-cache-platform_setting.wave_lien_recharge_sms', 'N;', 1779539923),
('laravel-cache-platform_setting.wave_lien_restauration_500', 's:46:"https://pay.wave.com/m/M_ci_1Onagr26EsBs/c/ci/";', 1779539923);

-- ─────────── Table : `cache_locks` ───────────
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `categories_depenses` ───────────
DROP TABLE IF EXISTS `categories_depenses`;
CREATE TABLE `categories_depenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Fournitures, Maintenance, Électricité',
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('fixe','variable','exceptionnelle') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'variable',
  `recurrente` tinyint(1) NOT NULL DEFAULT '0',
  `compte_comptable_numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lien au plan comptable',
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `couleur` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code hex pour UI',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cat_dep_etab_code_uniq` (`etablissement_id`,`code`),
  CONSTRAINT `categories_depenses_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `centres_profit` ───────────
DROP TABLE IF EXISTS `centres_profit`;
CREATE TABLE `centres_profit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Cantine, Transport, Uniformes',
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cp_etab_code_unique` (`etablissement_id`,`code`),
  CONSTRAINT `centres_profit_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `classes` ───────────
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `niveau_id` bigint unsigned NOT NULL,
  `serie_id` bigint unsigned DEFAULT NULL,
  `nom` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: 3ème A, Tle D1',
  `capacite` smallint unsigned NOT NULL DEFAULT '60',
  `effectif` smallint unsigned NOT NULL DEFAULT '0',
  `scolarite_annuelle` bigint unsigned NOT NULL DEFAULT '0',
  `frais_inscription` bigint unsigned NOT NULL DEFAULT '0',
  `frais_reinscription` bigint unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `professeur_principal_id` bigint unsigned DEFAULT NULL COMMENT 'Enseignant PP',
  `educateur` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classes_etablissement_id_annee_scolaire_id_nom_unique` (`etablissement_id`,`annee_scolaire_id`,`nom`),
  KEY `classes_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `classes_serie_id_foreign` (`serie_id`),
  KEY `classes_niveau_id_index` (`niveau_id`),
  CONSTRAINT `classes_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classes_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classes_niveau_id_foreign` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classes_serie_id_foreign` FOREIGN KEY (`serie_id`) REFERENCES `series` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `codes_pin_journaliers` ───────────
DROP TABLE IF EXISTS `codes_pin_journaliers`;
CREATE TABLE `codes_pin_journaliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `code_pin` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `heure_generation` time NOT NULL,
  `heure_expiration` time NOT NULL,
  `envoye_sms` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codes_pin_journaliers_etablissement_id_date_unique` (`etablissement_id`,`date`),
  CONSTRAINT `codes_pin_journaliers_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `comptes_comptables` ───────────
DROP TABLE IF EXISTS `comptes_comptables`;
CREATE TABLE `comptes_comptables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: 701, 601, 411, 512',
  `libelle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Scolarité, Salaires, Fournitures',
  `type` enum('actif','passif','charge','produit','tresorerie') COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` enum('scolarite','inscription','cantine','transport','uniformes','activites','subventions','autres_revenus','salaires','charges_sociales','fournitures','maintenance','loyer','electricite','eau','telecom','assurances','transport_charge','cantine_charge','formation','impots','amortissements','autres_charges','caisse','banque','mobile_money','creances','dettes') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Compte parent pour hiérarchie',
  `solde_initial` decimal(14,0) NOT NULL DEFAULT '0',
  `solde_actuel` decimal(14,0) NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `systeme` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Compte créé auto, non supprimable',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cc_etab_num_unique` (`etablissement_id`,`numero`),
  KEY `comptes_comptables_etablissement_id_type_index` (`etablissement_id`,`type`),
  CONSTRAINT `comptes_comptables_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `comptes_tresorerie` ───────────
DROP TABLE IF EXISTS `comptes_tresorerie`;
CREATE TABLE `comptes_tresorerie` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Caisse principale, Compte SGBCI, Orange Money',
  `type` enum('caisse','banque','mobile_money') COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_compte` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro de compte bancaire',
  `banque` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operateur` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Orange, MTN, Wave pour mobile_money',
  `solde_initial` decimal(14,0) NOT NULL DEFAULT '0',
  `solde_actuel` decimal(14,0) NOT NULL DEFAULT '0',
  `compte_comptable_numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comptes_tresorerie_etablissement_id_type_index` (`etablissement_id`,`type`),
  CONSTRAINT `comptes_tresorerie_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `conges_permissions` ───────────
DROP TABLE IF EXISTS `conges_permissions`;
CREATE TABLE `conges_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enseignant_id` bigint unsigned NOT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `type` enum('conge_maladie','conge_maternite','permission','absence_autorisee','formation','mission') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `motif` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `piece_justificative_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('demande','approuve','refuse','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'demande',
  `approuve_par` bigint unsigned DEFAULT NULL,
  `date_approbation` timestamp NULL DEFAULT NULL,
  `remplacant_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conges_permissions_etablissement_id_foreign` (`etablissement_id`),
  KEY `conges_permissions_approuve_par_foreign` (`approuve_par`),
  KEY `conges_permissions_remplacant_id_foreign` (`remplacant_id`),
  KEY `conges_permissions_enseignant_id_date_debut_date_fin_index` (`enseignant_id`,`date_debut`,`date_fin`),
  CONSTRAINT `conges_permissions_approuve_par_foreign` FOREIGN KEY (`approuve_par`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conges_permissions_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conges_permissions_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conges_permissions_remplacant_id_foreign` FOREIGN KEY (`remplacant_id`) REFERENCES `enseignants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `conseils_classe` ───────────
DROP TABLE IF EXISTS `conseils_classe`;
CREATE TABLE `conseils_classe` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `trimestre_id` bigint unsigned NOT NULL,
  `date_conseil` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time DEFAULT NULL,
  `lieu` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordre_du_jour` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `participants` text COLLATE utf8mb4_unicode_ci COMMENT 'Liste libre',
  `statut` enum('planifie','tenu','reporte','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planifie',
  `cree_par` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conseils_classe_classe_id_foreign` (`classe_id`),
  KEY `conseils_classe_trimestre_id_foreign` (`trimestre_id`),
  KEY `conseils_classe_cree_par_foreign` (`cree_par`),
  KEY `cc_etab_date` (`etablissement_id`,`date_conseil`),
  CONSTRAINT `conseils_classe_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conseils_classe_cree_par_foreign` FOREIGN KEY (`cree_par`) REFERENCES `users` (`id`),
  CONSTRAINT `conseils_classe_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conseils_classe_trimestre_id_foreign` FOREIGN KEY (`trimestre_id`) REFERENCES `trimestres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `conversations_ia` ───────────
DROP TABLE IF EXISTS `conversations_ia`;
CREATE TABLE `conversations_ia` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `question` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reponse` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `contexte` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: finances, pedagogie, pointage',
  `sources_donnees` json DEFAULT NULL COMMENT 'Tables/données utilisées pour la réponse',
  `satisfaction` smallint unsigned DEFAULT NULL COMMENT '1-5 étoiles',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversations_ia_etablissement_id_foreign` (`etablissement_id`),
  KEY `conversations_ia_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `conversations_ia_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_ia_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `creneaux` ───────────
DROP TABLE IF EXISTS `creneaux`;
CREATE TABLE `creneaux` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `libelle` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: 1er cours, 2ème cours, Récréation',
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `type` enum('cours','recreation','pause_dejeuner') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cours',
  `ordre` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `creneaux_etablissement_id_ordre_unique` (`etablissement_id`,`ordre`),
  CONSTRAINT `creneaux_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `decisions_fin_annee` ───────────
DROP TABLE IF EXISTS `decisions_fin_annee`;
CREATE TABLE `decisions_fin_annee` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `eleve_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `moyenne_annuelle` decimal(5,2) NOT NULL,
  `decision` enum('passage','redoublement','exclusion','orientation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'passage',
  `classe_proposee` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Classe suivante proposée',
  `serie_proposee_id` bigint unsigned DEFAULT NULL,
  `suggestion_ia` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Suggestion IA d orientation',
  `statut_validation` enum('proposition','valide_conseil_classe','valide_directeur','soumis_sigfne','approuve_drena','refuse_drena') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proposition',
  `valide_par_pp` bigint unsigned DEFAULT NULL,
  `date_validation_pp` timestamp NULL DEFAULT NULL,
  `valide_par_directeur` bigint unsigned DEFAULT NULL,
  `date_validation_directeur` timestamp NULL DEFAULT NULL,
  `date_soumission_sigfne` timestamp NULL DEFAULT NULL,
  `date_approbation_drena` timestamp NULL DEFAULT NULL,
  `motif_refus_drena` text COLLATE utf8mb4_unicode_ci,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `decisions_fin_annee_eleve_id_annee_scolaire_id_unique` (`eleve_id`,`annee_scolaire_id`),
  KEY `decisions_fin_annee_classe_id_foreign` (`classe_id`),
  KEY `decisions_fin_annee_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `decisions_fin_annee_serie_proposee_id_foreign` (`serie_proposee_id`),
  KEY `decisions_fin_annee_valide_par_pp_foreign` (`valide_par_pp`),
  KEY `decisions_fin_annee_valide_par_directeur_foreign` (`valide_par_directeur`),
  KEY `dfa_etab_annee_statut_idx` (`etablissement_id`,`annee_scolaire_id`,`statut_validation`),
  CONSTRAINT `decisions_fin_annee_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `decisions_fin_annee_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `decisions_fin_annee_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `decisions_fin_annee_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `decisions_fin_annee_serie_proposee_id_foreign` FOREIGN KEY (`serie_proposee_id`) REFERENCES `series` (`id`) ON DELETE SET NULL,
  CONSTRAINT `decisions_fin_annee_valide_par_directeur_foreign` FOREIGN KEY (`valide_par_directeur`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `decisions_fin_annee_valide_par_pp_foreign` FOREIGN KEY (`valide_par_pp`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `depenses` ───────────
DROP TABLE IF EXISTS `depenses`;
CREATE TABLE `depenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `exercice_id` bigint unsigned NOT NULL,
  `categorie_id` bigint unsigned NOT NULL,
  `reference` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: DEP-2026-04-0001',
  `libelle` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `montant` decimal(14,0) NOT NULL COMMENT 'En FCFA',
  `date_depense` date NOT NULL,
  `mode_paiement` enum('especes','cheque','virement','mobile_money','carte') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'especes',
  `beneficiaire` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fournisseur ou personne',
  `numero_facture` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `justificatif_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Photo facture/reçu',
  `frequence` enum('ponctuelle','quotidienne','hebdomadaire','mensuelle','trimestrielle','annuelle') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ponctuelle',
  `statut` enum('brouillon','soumise','approuvee','rejetee','payee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `soumise_par` bigint unsigned NOT NULL,
  `approuvee_par` bigint unsigned DEFAULT NULL,
  `date_approbation` timestamp NULL DEFAULT NULL,
  `motif_rejet` text COLLATE utf8mb4_unicode_ci,
  `ecriture_id` bigint unsigned DEFAULT NULL,
  `comptabilisee` tinyint(1) NOT NULL DEFAULT '0',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `depenses_reference_unique` (`reference`),
  KEY `depenses_exercice_id_foreign` (`exercice_id`),
  KEY `depenses_categorie_id_foreign` (`categorie_id`),
  KEY `depenses_soumise_par_foreign` (`soumise_par`),
  KEY `depenses_approuvee_par_foreign` (`approuvee_par`),
  KEY `depenses_ecriture_id_foreign` (`ecriture_id`),
  KEY `dep_etab_date_idx` (`etablissement_id`,`date_depense`),
  KEY `dep_etab_cat_idx` (`etablissement_id`,`categorie_id`),
  KEY `dep_etab_stat_idx` (`etablissement_id`,`statut`),
  CONSTRAINT `depenses_approuvee_par_foreign` FOREIGN KEY (`approuvee_par`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `depenses_categorie_id_foreign` FOREIGN KEY (`categorie_id`) REFERENCES `categories_depenses` (`id`),
  CONSTRAINT `depenses_ecriture_id_foreign` FOREIGN KEY (`ecriture_id`) REFERENCES `ecritures_comptables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `depenses_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `depenses_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices_comptables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `depenses_soumise_par_foreign` FOREIGN KEY (`soumise_par`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `device_tokens` ───────────
DROP TABLE IF EXISTS `device_tokens`;
CREATE TABLE `device_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_tokens_user_id_token_unique` (`user_id`,`token`),
  KEY `device_tokens_user_id_platform_index` (`user_id`,`platform`),
  CONSTRAINT `device_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `devoirs` ───────────
DROP TABLE IF EXISTS `devoirs`;
CREATE TABLE `devoirs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `matiere_id` bigint unsigned NOT NULL,
  `enseignant_id` bigint unsigned NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('devoir','exercice','tp','projet','lecture','interrogation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'devoir',
  `date_publication` date NOT NULL,
  `date_limite` date DEFAULT NULL,
  `fichier_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fichier_corrige_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publie` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `devoirs_etablissement_id_foreign` (`etablissement_id`),
  KEY `devoirs_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `devoirs_matiere_id_foreign` (`matiere_id`),
  KEY `devoirs_classe_id_matiere_id_index` (`classe_id`,`matiere_id`),
  KEY `devoirs_enseignant_id_annee_scolaire_id_index` (`enseignant_id`,`annee_scolaire_id`),
  CONSTRAINT `devoirs_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `devoirs_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `devoirs_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `devoirs_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `devoirs_matiere_id_foreign` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `documents_eleves` ───────────
DROP TABLE IF EXISTS `documents_eleves`;
CREATE TABLE `documents_eleves` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint unsigned NOT NULL,
  `type` enum('extrait_naissance','certificat_scolarite','bulletin','photo_identite','carnet_vaccination','certificat_medical','decision_affectation','quitus','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemin_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taille_octets` int unsigned DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verifie` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_eleves_eleve_id_foreign` (`eleve_id`),
  CONSTRAINT `documents_eleves_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `echeances` ───────────
DROP TABLE IF EXISTS `echeances`;
CREATE TABLE `echeances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inscription_id` bigint unsigned NOT NULL,
  `plan_paiement_id` bigint unsigned DEFAULT NULL,
  `numero_echeance` tinyint unsigned NOT NULL,
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: 1ère tranche, 2ème tranche',
  `montant` decimal(12,0) NOT NULL COMMENT 'Montant en FCFA',
  `date_echeance` date NOT NULL,
  `montant_paye` decimal(12,0) NOT NULL DEFAULT '0',
  `reste_a_payer` decimal(12,0) NOT NULL,
  `statut` enum('a_venir','en_cours','paye','en_retard','partiellement_paye') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a_venir',
  `nb_relances_envoyees` smallint unsigned NOT NULL DEFAULT '0',
  `derniere_relance_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `echeances_plan_paiement_id_foreign` (`plan_paiement_id`),
  KEY `echeances_inscription_id_statut_index` (`inscription_id`,`statut`),
  CONSTRAINT `echeances_inscription_id_foreign` FOREIGN KEY (`inscription_id`) REFERENCES `inscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `echeances_plan_paiement_id_foreign` FOREIGN KEY (`plan_paiement_id`) REFERENCES `plans_paiement` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `ecritures_comptables` ───────────
DROP TABLE IF EXISTS `ecritures_comptables`;
CREATE TABLE `ecritures_comptables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `exercice_id` bigint unsigned NOT NULL,
  `numero_piece` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: EC-2026-04-0001',
  `date_ecriture` date NOT NULL,
  `libelle` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `compte_debit_id` bigint unsigned NOT NULL,
  `compte_credit_id` bigint unsigned NOT NULL,
  `montant` decimal(14,0) NOT NULL COMMENT 'En FCFA',
  `type_piece` enum('paiement_scolarite','depense','salaire','virement_interne','remboursement','ajustement','ouverture','cloture','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_externe` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lien vers paiement_id, depense_id, etc.',
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'paiement, depense, paie_enseignant',
  `reference_id` bigint unsigned DEFAULT NULL,
  `saisie_par` bigint unsigned NOT NULL,
  `valide_par` bigint unsigned DEFAULT NULL,
  `valide` tinyint(1) NOT NULL DEFAULT '0',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecritures_comptables_compte_debit_id_foreign` (`compte_debit_id`),
  KEY `ecritures_comptables_compte_credit_id_foreign` (`compte_credit_id`),
  KEY `ecritures_comptables_saisie_par_foreign` (`saisie_par`),
  KEY `ecritures_comptables_valide_par_foreign` (`valide_par`),
  KEY `ecr_etab_date_idx` (`etablissement_id`,`date_ecriture`),
  KEY `ecr_exo_type_idx` (`exercice_id`,`type_piece`),
  CONSTRAINT `ecritures_comptables_compte_credit_id_foreign` FOREIGN KEY (`compte_credit_id`) REFERENCES `comptes_comptables` (`id`),
  CONSTRAINT `ecritures_comptables_compte_debit_id_foreign` FOREIGN KEY (`compte_debit_id`) REFERENCES `comptes_comptables` (`id`),
  CONSTRAINT `ecritures_comptables_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecritures_comptables_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices_comptables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecritures_comptables_saisie_par_foreign` FOREIGN KEY (`saisie_par`) REFERENCES `users` (`id`),
  CONSTRAINT `ecritures_comptables_valide_par_foreign` FOREIGN KEY (`valide_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_classe_plage_horaire` ───────────
DROP TABLE IF EXISTS `edt_classe_plage_horaire`;
CREATE TABLE `edt_classe_plage_horaire` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned DEFAULT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `jour` enum('lundi','mardi','mercredi','jeudi','vendredi') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plage` enum('matin','apres_midi') COLLATE utf8mb4_unicode_ci NOT NULL,
  `autorise` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_plage_classe_jour_unique` (`classe_id`,`jour`,`plage`,`annee_scolaire_id`),
  KEY `edt_classe_plage_horaire_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `idx_plage_etab_annee` (`etablissement_id`,`annee_scolaire_id`),
  CONSTRAINT `edt_classe_plage_horaire_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_classe_plage_horaire_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edt_classe_plage_horaire_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_constraint_catalog` ───────────
DROP TABLE IF EXISTS `edt_constraint_catalog`;
CREATE TABLE `edt_constraint_catalog` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `categorie` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `default_weight` decimal(5,2) NOT NULL DEFAULT '1.00',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `edt_constraint_catalog_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `edt_constraint_catalog` VALUES
(1, 'HARD_NO_TEACHER_EXTERNAL_COLLISION', 'Pas de chevauchement inter-écoles', 'Interdit de placer un prof à une heure où il enseigne déjà dans un autre établissement.', 'collision', 1, '1.00', 1, '2026-05-23 10:45:16', '2026-05-23 10:45:16'),
(2, 'SOFT_CONSECUTIVE_DISCIPLINE', 'Regrouper 2h consécutives Maths/Français (1er cycle)', 'Favorise le placement des heures de Maths et Français en créneaux consécutifs pour les classes du 1er cycle (6è-3è), conformément au guide ACE.', 'pedagogique', 1, '0.75', 0, '2026-05-23 10:45:16', '2026-05-23 10:45:16'),
(3, 'SOFT_TP_CONSECUTIVE_SAME_DAY', 'TP PC/SVT consécutifs le même jour', 'Les séances de TP (Physique-Chimie et SVT) doivent être placées en créneaux consécutifs dans la même journée, avec les deux groupes. Tandem recommandé par le guide ACE.', 'pedagogique', 1, '0.90', 0, '2026-05-23 10:45:16', '2026-05-23 10:45:16'),
(4, 'SOFT_EQUITABLE_REPARTITION_SEMAINE', 'Répartition équitable des heures sur la semaine', 'Évite de concentrer toutes les heures d\'un professeur sur un ou deux jours. Pénalise les candidats qui surchargent un jour déjà occupé.', 'enseignant', 1, '0.60', 0, '2026-05-23 10:45:16', '2026-05-23 10:45:16'),
(5, 'SOFT_NO_ISOLATED_HOUR', 'Éviter les heures isolées pour le professeur', 'Pénalise le placement d\'une heure unique séparée par un grand intervalle des autres heures du prof dans la journée (déplacement inutile). Conforme Annexe 2 guide ACE.', 'enseignant', 1, '0.50', 0, '2026-05-23 10:45:16', '2026-05-23 10:45:16'),
(6, 'SOFT_MAX_3_NIVEAUX_PAR_PROF', 'Maximum 3 niveaux par professeur', 'Pénalise l\'attribution d\'un 4ème niveau différent à un même professeur (sauf EDHC, Arts plastiques, Musique). Conforme Annexe 2 guide ACE.', 'enseignant', 1, '0.70', 0, '2026-05-23 10:45:16', '2026-05-23 10:45:16');

-- ─────────── Table : `edt_enseignant_horaires_externes` ───────────
DROP TABLE IF EXISTS `edt_enseignant_horaires_externes`;
CREATE TABLE `edt_enseignant_horaires_externes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enseignant_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned DEFAULT NULL,
  `etablissement_externe` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom de l''autre école',
  `jour` enum('lundi','mardi','mercredi','jeudi','vendredi','samedi') COLLATE utf8mb4_unicode_ci NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `valide` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Validé par l''admin',
  `source` enum('manuel','import','ocr') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manuel',
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `import_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_enseignant_horaires_externes_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `edt_enseignant_horaires_externes_created_by_foreign` (`created_by`),
  KEY `idx_ext_ens_jour` (`enseignant_id`,`jour`),
  KEY `idx_ext_ens_annee` (`enseignant_id`,`annee_scolaire_id`),
  KEY `edt_enseignant_horaires_externes_import_id_foreign` (`import_id`),
  CONSTRAINT `edt_enseignant_horaires_externes_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_enseignant_horaires_externes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_enseignant_horaires_externes_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edt_enseignant_horaires_externes_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `edt_enseignant_horaires_imports` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_enseignant_horaires_imports` ───────────
DROP TABLE IF EXISTS `edt_enseignant_horaires_imports`;
CREATE TABLE `edt_enseignant_horaires_imports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enseignant_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned DEFAULT NULL,
  `source_type` enum('photo','image','scan','pdf') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'photo',
  `fichier_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('uploade','analyse','valide','erreur') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uploade',
  `payload_ocr_json` json DEFAULT NULL COMMENT 'Résultat brut retourné par OpenAI',
  `etablissement_detecte` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nom de l''école détecté par OCR',
  `professeur_detecte` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nom du prof détecté par OCR',
  `confidence_score` smallint unsigned NOT NULL DEFAULT '0' COMMENT 'Score OCR 0-100',
  `notes_ocr` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `validated_by` bigint unsigned DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_enseignant_horaires_imports_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `edt_enseignant_horaires_imports_created_by_foreign` (`created_by`),
  KEY `edt_enseignant_horaires_imports_validated_by_foreign` (`validated_by`),
  KEY `idx_horimport_ens_statut` (`enseignant_id`,`statut`),
  CONSTRAINT `edt_enseignant_horaires_imports_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_enseignant_horaires_imports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_enseignant_horaires_imports_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edt_enseignant_horaires_imports_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_generation_issues` ───────────
DROP TABLE IF EXISTS `edt_generation_issues`;
CREATE TABLE `edt_generation_issues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `run_id` bigint unsigned NOT NULL,
  `niveau` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scope_id` bigint unsigned DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `details_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_issue_run_niveau` (`run_id`,`niveau`),
  CONSTRAINT `edt_generation_issues_run_id_foreign` FOREIGN KEY (`run_id`) REFERENCES `edt_generation_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_generation_runs` ───────────
DROP TABLE IF EXISTS `edt_generation_runs`;
CREATE TABLE `edt_generation_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scenario_id` bigint unsigned DEFAULT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned DEFAULT NULL,
  `run_uuid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `score_global` decimal(8,4) DEFAULT NULL,
  `summary_json` json DEFAULT NULL,
  `conformite_json` json DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `edt_generation_runs_run_uuid_unique` (`run_uuid`),
  KEY `edt_generation_runs_scenario_id_foreign` (`scenario_id`),
  KEY `edt_generation_runs_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `edt_generation_runs_created_by_foreign` (`created_by`),
  KEY `idx_run_etab_status` (`etablissement_id`,`status`),
  CONSTRAINT `edt_generation_runs_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_generation_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_generation_runs_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edt_generation_runs_scenario_id_foreign` FOREIGN KEY (`scenario_id`) REFERENCES `edt_generation_scenarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_generation_scenario_constraints` ───────────
DROP TABLE IF EXISTS `edt_generation_scenario_constraints`;
CREATE TABLE `edt_generation_scenario_constraints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scenario_id` bigint unsigned NOT NULL,
  `constraint_id` bigint unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `weight` decimal(5,2) DEFAULT NULL,
  `params_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_scenario_constraint` (`scenario_id`,`constraint_id`),
  KEY `edt_generation_scenario_constraints_constraint_id_foreign` (`constraint_id`),
  CONSTRAINT `edt_generation_scenario_constraints_constraint_id_foreign` FOREIGN KEY (`constraint_id`) REFERENCES `edt_constraint_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edt_generation_scenario_constraints_scenario_id_foreign` FOREIGN KEY (`scenario_id`) REFERENCES `edt_generation_scenarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_generation_scenario_scopes` ───────────
DROP TABLE IF EXISTS `edt_generation_scenario_scopes`;
CREATE TABLE `edt_generation_scenario_scopes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scenario_id` bigint unsigned NOT NULL,
  `scope_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_generation_scenario_scopes_scenario_id_foreign` (`scenario_id`),
  CONSTRAINT `edt_generation_scenario_scopes_scenario_id_foreign` FOREIGN KEY (`scenario_id`) REFERENCES `edt_generation_scenarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_generation_scenarios` ───────────
DROP TABLE IF EXISTS `edt_generation_scenarios`;
CREATE TABLE `edt_generation_scenarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned DEFAULT NULL,
  `policy_id` bigint unsigned DEFAULT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode_generation` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `portee` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jours_json` json DEFAULT NULL,
  `creneaux_json` json DEFAULT NULL,
  `salles_json` json DEFAULT NULL,
  `options_json` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_generation_scenarios_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `edt_generation_scenarios_policy_id_foreign` (`policy_id`),
  KEY `edt_generation_scenarios_created_by_foreign` (`created_by`),
  KEY `idx_scenario_etab_annee` (`etablissement_id`,`annee_scolaire_id`),
  CONSTRAINT `edt_generation_scenarios_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_generation_scenarios_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_generation_scenarios_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edt_generation_scenarios_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `edt_policies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_parametres` ───────────
DROP TABLE IF EXISTS `edt_parametres`;
CREATE TABLE `edt_parametres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned DEFAULT NULL,
  `policy_id` bigint unsigned DEFAULT NULL,
  `mode_generation_defaut` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jours_autorises_json` json DEFAULT NULL,
  `creneaux_autorises_json` json DEFAULT NULL,
  `salles_autorisees_json` json DEFAULT NULL,
  `attendre_horaires_vacataires` tinyint(1) NOT NULL DEFAULT '0',
  `bloquer_si_vacataire_sans_horaire` tinyint(1) NOT NULL DEFAULT '0',
  `respecter_imports_vacataires` tinyint(1) NOT NULL DEFAULT '1',
  `regrouper_heures_vacataires` tinyint(1) NOT NULL DEFAULT '0',
  `autoriser_reduction_heures` tinyint(1) NOT NULL DEFAULT '0',
  `max_reduction_minutes_par_classe` smallint unsigned DEFAULT NULL,
  `max_reduction_minutes_par_matiere` smallint unsigned DEFAULT NULL,
  `autoriser_matieres_facultatives` tinyint(1) NOT NULL DEFAULT '0',
  `prioriser_classes_examen` tinyint(1) NOT NULL DEFAULT '1',
  `prioriser_permanents` tinyint(1) NOT NULL DEFAULT '1',
  `equilibrer_journees_classes` tinyint(1) NOT NULL DEFAULT '1',
  `equilibrer_journees_profs` tinyint(1) NOT NULL DEFAULT '1',
  `respecter_tp_consecutifs` tinyint(1) NOT NULL DEFAULT '1',
  `eviter_eps_heures_chaudes` tinyint(1) NOT NULL DEFAULT '1',
  `limiter_niveaux_prof` tinyint(1) NOT NULL DEFAULT '1',
  `max_niveaux_par_prof` tinyint unsigned NOT NULL DEFAULT '3',
  `limiter_heures_creuses` tinyint(1) NOT NULL DEFAULT '0',
  `max_heures_creuses_prof` tinyint unsigned DEFAULT NULL,
  `autoriser_trous` tinyint(1) NOT NULL DEFAULT '0',
  `tolerer_surcharge_legere` tinyint(1) NOT NULL DEFAULT '0',
  `activer_apprentissage_ajustements` tinyint(1) NOT NULL DEFAULT '0',
  `verrouiller_ajustements_manuels_par_defaut` tinyint(1) NOT NULL DEFAULT '0',
  `notes_generation` text COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_edt_param_etab_annee` (`etablissement_id`,`annee_scolaire_id`),
  KEY `edt_parametres_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `edt_parametres_policy_id_foreign` (`policy_id`),
  KEY `edt_parametres_created_by_foreign` (`created_by`),
  KEY `edt_parametres_updated_by_foreign` (`updated_by`),
  CONSTRAINT `edt_parametres_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_parametres_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_parametres_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edt_parametres_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `edt_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_parametres_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_policies` ───────────
DROP TABLE IF EXISTS `edt_policies`;
CREATE TABLE `edt_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned DEFAULT NULL,
  `annee_scolaire_id` bigint unsigned DEFAULT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode_generation` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `autoriser_reduction_heures` tinyint(1) NOT NULL DEFAULT '0',
  `autoriser_matieres_facultatives` tinyint(1) NOT NULL DEFAULT '0',
  `prioriser_classes_examen` tinyint(1) NOT NULL DEFAULT '1',
  `prioriser_permanents` tinyint(1) NOT NULL DEFAULT '1',
  `attendre_horaires_vacataires` tinyint(1) NOT NULL DEFAULT '0',
  `max_reduction_minutes_par_classe` smallint unsigned DEFAULT NULL,
  `max_reduction_minutes_par_matiere` smallint unsigned DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_policies_etablissement_id_foreign` (`etablissement_id`),
  KEY `edt_policies_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `edt_policies_created_by_foreign` (`created_by`),
  CONSTRAINT `edt_policies_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_policies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_policies_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_policy_class_overrides` ───────────
DROP TABLE IF EXISTS `edt_policy_class_overrides`;
CREATE TABLE `edt_policy_class_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `policy_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned DEFAULT NULL,
  `niveau_reglementaire_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_reglementaire_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_cible_minutes` smallint unsigned DEFAULT NULL,
  `total_min_minutes` smallint unsigned DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_policy_class_overrides_policy_id_foreign` (`policy_id`),
  KEY `edt_policy_class_overrides_classe_id_foreign` (`classe_id`),
  CONSTRAINT `edt_policy_class_overrides_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_policy_class_overrides_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `edt_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_policy_matiere_overrides` ───────────
DROP TABLE IF EXISTS `edt_policy_matiere_overrides`;
CREATE TABLE `edt_policy_matiere_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `policy_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned DEFAULT NULL,
  `niveau_reglementaire_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_reglementaire_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `matiere_id` bigint unsigned DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `volume_cible_minutes` smallint unsigned DEFAULT NULL,
  `volume_min_minutes` smallint unsigned DEFAULT NULL,
  `priorite` tinyint unsigned DEFAULT NULL,
  `motif` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_policy_matiere_overrides_policy_id_foreign` (`policy_id`),
  KEY `edt_policy_matiere_overrides_classe_id_foreign` (`classe_id`),
  KEY `edt_policy_matiere_overrides_matiere_id_foreign` (`matiere_id`),
  CONSTRAINT `edt_policy_matiere_overrides_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_policy_matiere_overrides_matiere_id_foreign` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_policy_matiere_overrides_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `edt_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_referentiel_lignes` ───────────
DROP TABLE IF EXISTS `edt_referentiel_lignes`;
CREATE TABLE `edt_referentiel_lignes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `profil_id` bigint unsigned NOT NULL,
  `matiere_id` bigint unsigned DEFAULT NULL,
  `obligatoire` tinyint(1) NOT NULL DEFAULT '1',
  `facultatif` tinyint(1) NOT NULL DEFAULT '0',
  `expression_source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequence` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode_seance` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `volume_classe_entiere_minutes` smallint unsigned DEFAULT NULL,
  `volume_demi_classe_minutes` smallint unsigned DEFAULT NULL,
  `volume_eleve_minutes` smallint unsigned DEFAULT NULL,
  `volume_prof_minutes` smallint unsigned DEFAULT NULL,
  `nb_blocs_souhaite` tinyint unsigned DEFAULT NULL,
  `blocs_consecutifs` tinyint(1) NOT NULL DEFAULT '0',
  `ecart_min_jours` tinyint unsigned DEFAULT NULL,
  `ordre_montage` tinyint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_referentiel_lignes_profil_id_foreign` (`profil_id`),
  KEY `edt_referentiel_lignes_matiere_id_foreign` (`matiere_id`),
  CONSTRAINT `edt_referentiel_lignes_matiere_id_foreign` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_referentiel_lignes_profil_id_foreign` FOREIGN KEY (`profil_id`) REFERENCES `edt_referentiel_profils` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_referentiel_profils` ───────────
DROP TABLE IF EXISTS `edt_referentiel_profils`;
CREATE TABLE `edt_referentiel_profils` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_id` bigint unsigned NOT NULL,
  `code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `niveau_code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libelle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cycle` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_eleve_minutes` smallint unsigned NOT NULL DEFAULT '0',
  `total_prof_minutes` smallint unsigned NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_profil_source_niveau` (`source_id`,`niveau_code`),
  CONSTRAINT `edt_referentiel_profils_source_id_foreign` FOREIGN KEY (`source_id`) REFERENCES `edt_referentiel_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_referentiel_sources` ───────────
DROP TABLE IF EXISTS `edt_referentiel_sources`;
CREATE TABLE `edt_referentiel_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned DEFAULT NULL,
  `libelle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_document` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_reference` date DEFAULT NULL,
  `annee_reference` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_referentiel_sources_etablissement_id_foreign` (`etablissement_id`),
  CONSTRAINT `edt_referentiel_sources_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_vacataire_imports` ───────────
DROP TABLE IF EXISTS `edt_vacataire_imports`;
CREATE TABLE `edt_vacataire_imports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned DEFAULT NULL,
  `annee_scolaire_id` bigint unsigned DEFAULT NULL,
  `enseignant_id` bigint unsigned NOT NULL,
  `source_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'photo',
  `fichier_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload_extrait_json` json DEFAULT NULL,
  `resume_extraction` text COLLATE utf8mb4_unicode_ci,
  `confidence_score` smallint unsigned NOT NULL DEFAULT '0',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uploade',
  `validated_by` bigint unsigned DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_vacataire_imports_etablissement_id_foreign` (`etablissement_id`),
  KEY `edt_vacataire_imports_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `edt_vacataire_imports_validated_by_foreign` (`validated_by`),
  KEY `edt_vacataire_imports_created_by_foreign` (`created_by`),
  KEY `idx_vacimport_ens_status` (`enseignant_id`,`status`),
  CONSTRAINT `edt_vacataire_imports_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_vacataire_imports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_vacataire_imports_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edt_vacataire_imports_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_vacataire_imports_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `edt_vacataire_slots` ───────────
DROP TABLE IF EXISTS `edt_vacataire_slots`;
CREATE TABLE `edt_vacataire_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `import_id` bigint unsigned DEFAULT NULL,
  `enseignant_id` bigint unsigned NOT NULL,
  `jour` enum('lundi','mardi','mercredi','jeudi','vendredi','samedi') COLLATE utf8mb4_unicode_ci NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `creneau_id` bigint unsigned DEFAULT NULL,
  `etat` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponible',
  `site_externe` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `source_confidence` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edt_vacataire_slots_import_id_foreign` (`import_id`),
  KEY `edt_vacataire_slots_creneau_id_foreign` (`creneau_id`),
  KEY `idx_vacslot_ens_jour` (`enseignant_id`,`jour`),
  CONSTRAINT `edt_vacataire_slots_creneau_id_foreign` FOREIGN KEY (`creneau_id`) REFERENCES `creneaux` (`id`) ON DELETE SET NULL,
  CONSTRAINT `edt_vacataire_slots_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edt_vacataire_slots_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `edt_vacataire_imports` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `eleve_parent` ───────────
DROP TABLE IF EXISTS `eleve_parent`;
CREATE TABLE `eleve_parent` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned NOT NULL,
  `est_contact_principal` tinyint(1) NOT NULL DEFAULT '0',
  `autorise_recuperation` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eleve_parent_eleve_id_parent_id_unique` (`eleve_id`,`parent_id`),
  KEY `eleve_parent_parent_id_foreign` (`parent_id`),
  CONSTRAINT `eleve_parent_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eleve_parent_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `parents_tuteurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `eleves` ───────────
DROP TABLE IF EXISTS `eleves`;
CREATE TABLE `eleves` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned DEFAULT NULL,
  `matricule_interne` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: AVIA-2026-0001',
  `matricule_desps` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Matricule national DESPS ex: 17443596U',
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexe` enum('M','F') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `redoublant` tinyint(1) NOT NULL DEFAULT '0',
  `lv2` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_arts` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lieu_naissance` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationalite` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ivoirienne',
  `numero_extrait_naissance` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `groupe_sanguin` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `maladies_chroniques` text COLLATE utf8mb4_unicode_ci,
  `contact_urgence_nom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_urgence_tel` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('pre_inscrit','inscrit','transfere','radie','diplome','abandonne') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pre_inscrit',
  `date_premiere_inscription` date DEFAULT NULL,
  `ecole_precedente` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `statut_eleve` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eleves_etablissement_id_matricule_interne_unique` (`etablissement_id`,`matricule_interne`),
  KEY `eleves_user_id_foreign` (`user_id`),
  KEY `eleves_matricule_desps_index` (`matricule_desps`),
  KEY `eleves_etablissement_id_statut_index` (`etablissement_id`,`statut`),
  KEY `eleves_classe_actif_idx` (`classe_id`,`actif`),
  CONSTRAINT `eleves_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eleves_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eleves_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `eleves_import_jobs` ───────────
DROP TABLE IF EXISTS `eleves_import_jobs`;
CREATE TABLE `eleves_import_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `classe_cible_id` bigint unsigned DEFAULT NULL,
  `niveau_id` bigint unsigned DEFAULT NULL,
  `source` enum('excel','csv','pdf','photo_ocr','saisie_rapide') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fichier_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fichier_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fichier_taille` bigint unsigned DEFAULT NULL,
  `statut` enum('upload','parsing','preview','importing','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'upload',
  `donnees_brutes` json DEFAULT NULL,
  `donnees_normalisees` json DEFAULT NULL,
  `erreurs` json DEFAULT NULL,
  `metadonnees` json DEFAULT NULL,
  `total_lignes` int unsigned NOT NULL DEFAULT '0',
  `lignes_valides` int unsigned NOT NULL DEFAULT '0',
  `lignes_erreur` int unsigned NOT NULL DEFAULT '0',
  `lignes_importees` int unsigned NOT NULL DEFAULT '0',
  `progression` tinyint unsigned NOT NULL DEFAULT '0',
  `message_progression` text COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eleves_import_jobs_user_id_foreign` (`user_id`),
  KEY `eleves_import_jobs_classe_cible_id_foreign` (`classe_cible_id`),
  KEY `eleves_import_jobs_niveau_id_foreign` (`niveau_id`),
  KEY `eleves_import_jobs_etablissement_id_statut_index` (`etablissement_id`,`statut`),
  KEY `eleves_import_jobs_etablissement_id_created_at_index` (`etablissement_id`,`created_at`),
  CONSTRAINT `eleves_import_jobs_classe_cible_id_foreign` FOREIGN KEY (`classe_cible_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eleves_import_jobs_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eleves_import_jobs_niveau_id_foreign` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eleves_import_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `emploi_du_temps` ───────────
DROP TABLE IF EXISTS `emploi_du_temps`;
CREATE TABLE `emploi_du_temps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `matiere_id` bigint unsigned NOT NULL,
  `enseignant_id` bigint unsigned NOT NULL,
  `salle_id` bigint unsigned DEFAULT NULL,
  `creneau_id` bigint unsigned NOT NULL,
  `jour` enum('lundi','mardi','mercredi','jeudi','vendredi','samedi') COLLATE utf8mb4_unicode_ci NOT NULL,
  `valide_du` date DEFAULT NULL,
  `valide_au` date DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `source` enum('ia','manuel','ajustement') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manuel',
  `generation_uuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locked_by_user` tinyint(1) NOT NULL DEFAULT '0',
  `ia_score` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_adjusted_by` bigint unsigned DEFAULT NULL,
  `last_adjusted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emploi_du_temps_etablissement_id_foreign` (`etablissement_id`),
  KEY `emploi_du_temps_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `emploi_du_temps_matiere_id_foreign` (`matiere_id`),
  KEY `emploi_du_temps_creneau_id_foreign` (`creneau_id`),
  KEY `emploi_du_temps_classe_id_jour_index` (`classe_id`,`jour`),
  KEY `emploi_du_temps_enseignant_id_jour_index` (`enseignant_id`,`jour`),
  KEY `emploi_du_temps_salle_id_jour_index` (`salle_id`,`jour`),
  KEY `emploi_du_temps_last_adjusted_by_foreign` (`last_adjusted_by`),
  CONSTRAINT `emploi_du_temps_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emploi_du_temps_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emploi_du_temps_creneau_id_foreign` FOREIGN KEY (`creneau_id`) REFERENCES `creneaux` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emploi_du_temps_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emploi_du_temps_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emploi_du_temps_last_adjusted_by_foreign` FOREIGN KEY (`last_adjusted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `emploi_du_temps_matiere_id_foreign` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emploi_du_temps_salle_id_foreign` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `enseignants` ───────────
DROP TABLE IF EXISTS `enseignants`;
CREATE TABLE `enseignants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `matricule_mena` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Matricule fonctionnaire MENA',
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexe` enum('M','F') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_2` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `diplome_plus_eleve` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialite` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('titulaire','contractuel','vacataire','stagiaire') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'titulaire',
  `date_prise_fonction` date DEFAULT NULL,
  `salaire_base` decimal(12,0) DEFAULT NULL COMMENT 'Salaire en FCFA',
  `type_remuneration` enum('fixe','horaire','mixte') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixe' COMMENT 'fixe = salaire de base seul, horaire = uniquement taux × heures, mixte = base + taux × heures',
  `taux_horaire` decimal(8,0) NOT NULL DEFAULT '0' COMMENT 'Taux horaire en FCFA',
  `heures_contractuelles_mois` decimal(5,1) DEFAULT NULL COMMENT 'Heures contractuelles mensuelles (pour calcul prorata)',
  `banque` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_compte` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score_ponctualite` decimal(5,2) NOT NULL DEFAULT '100.00' COMMENT 'Score IA 0-100',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignants_user_id_foreign` (`user_id`),
  KEY `enseignants_etablissement_id_actif_index` (`etablissement_id`,`actif`),
  CONSTRAINT `enseignants_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enseignants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `etablissements` ───────────
DROP TABLE IF EXISTS `etablissements`;
CREATE TABLE `etablissements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `groupe_scolaire_id` bigint unsigned DEFAULT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_desps` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Code établissement DESPS ex: 639000',
  `sigfne_actif` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Activer la synchronisation SIGFNE/AGFNE',
  `sigfne_login` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Identifiant SIGFNE (souvent = code_desps)',
  `sigfne_token` text COLLATE utf8mb4_unicode_ci COMMENT 'Token ou mot de passe chiffré',
  `sigfne_plateforme` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'agfne (secondaire) ou agcp (primaire)',
  `sigfne_derniere_sync` timestamp NULL DEFAULT NULL,
  `sigle` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('prescolaire','primaire','secondaire','lycee','mixte') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'secondaire',
  `statut_juridique` enum('public','prive_laic','prive_confessionnel','communautaire') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'prive_laic',
  `adresse` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Abidjan',
  `commune` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drena` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Direction Régionale',
  `ddena` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Direction Départementale',
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gps_latitude` decimal(10,7) DEFAULT NULL,
  `gps_longitude` decimal(10,7) DEFAULT NULL,
  `gps_rayon_metres` smallint unsigned NOT NULL DEFAULT '100' COMMENT 'Rayon de géolocalisation pour le pointage',
  `directeur_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `directeur_telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `wave_actif` tinyint(1) NOT NULL DEFAULT '0',
  `paiements_manuels_actifs` tinyint(1) NOT NULL DEFAULT '1',
  `wave_libelle` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Libellé affiché ex: Nom de l''école sur Wave',
  `wave_lien_base` text COLLATE utf8mb4_unicode_ci COMMENT 'URL Wave sans montant, ex: https://pay.wave.com/m/.../c/ci/',
  `wave_configured_at` timestamp NULL DEFAULT NULL,
  `wave_configured_by` bigint unsigned DEFAULT NULL,
  `systeme_evaluation` enum('trimestre','semestre','quadrimestre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trimestre',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `etablissements_code_desps_unique` (`code_desps`),
  KEY `etablissements_groupe_scolaire_id_foreign` (`groupe_scolaire_id`),
  KEY `etablissements_code_desps_index` (`code_desps`),
  KEY `etablissements_type_index` (`type`),
  KEY `etablissements_wave_configured_by_foreign` (`wave_configured_by`),
  CONSTRAINT `etablissements_groupe_scolaire_id_foreign` FOREIGN KEY (`groupe_scolaire_id`) REFERENCES `groupes_scolaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `etablissements_wave_configured_by_foreign` FOREIGN KEY (`wave_configured_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `etablissements` VALUES
(1, NULL, 'Avia Technologie — Siège', 'AVIA-SIEGE', 0, NULL, NULL, NULL, NULL, 'AVIA', 'mixte', 'prive_laic', 'Siège Avia', 'Abidjan', NULL, NULL, NULL, NULL, '+225 0000000000', 'contact@avia.ci', NULL, NULL, NULL, NULL, 100, 'Deco', '+225 0000000001', 1, 0, 1, NULL, NULL, NULL, NULL, 'trimestre', '2026-05-23 10:46:03', '2026-05-23 11:14:44'),
(3, NULL, 'Collège Iblo Fofana Mankono', '190639', 0, NULL, NULL, NULL, NULL, 'CIF', 'secondaire', 'prive_laic', 'Mankono, route Kogolo', 'Mankono', 'Mankono', 'Béré', 'Mankono', 'Mankono', '0153463635', 'collegeiblo@gmail.com', NULL, NULL, NULL, NULL, 100, 'TRAORE', '+225 0000000002', 1, 0, 1, NULL, NULL, NULL, NULL, 'trimestre', '2026-05-23 12:07:29', '2026-05-23 12:07:29');

-- ─────────── Table : `evaluations` ───────────
DROP TABLE IF EXISTS `evaluations`;
CREATE TABLE `evaluations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `matiere_id` bigint unsigned NOT NULL,
  `enseignant_id` bigint unsigned NOT NULL,
  `trimestre_id` bigint unsigned NOT NULL,
  `type_evaluation_id` bigint unsigned NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Devoir n°1 - Équations du 2nd degré',
  `date_evaluation` date NOT NULL,
  `note_sur` decimal(4,1) NOT NULL DEFAULT '20.0' COMMENT 'Barème: 10, 20, 40...',
  `coefficient` decimal(3,1) NOT NULL DEFAULT '1.0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `fichier_sujet_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fichier_corrige_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('brouillon','en_saisie','cloturee','validee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `notes_publiees` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluations_etablissement_id_foreign` (`etablissement_id`),
  KEY `evaluations_enseignant_id_foreign` (`enseignant_id`),
  KEY `evaluations_trimestre_id_foreign` (`trimestre_id`),
  KEY `evaluations_type_evaluation_id_foreign` (`type_evaluation_id`),
  KEY `evaluations_classe_id_trimestre_id_index` (`classe_id`,`trimestre_id`),
  KEY `evaluations_matiere_id_trimestre_id_index` (`matiere_id`,`trimestre_id`),
  CONSTRAINT `evaluations_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_matiere_id_foreign` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_trimestre_id_foreign` FOREIGN KEY (`trimestre_id`) REFERENCES `trimestres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_type_evaluation_id_foreign` FOREIGN KEY (`type_evaluation_id`) REFERENCES `types_evaluation` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `evenements_scolaires` ───────────
DROP TABLE IF EXISTS `evenements_scolaires`;
CREATE TABLE `evenements_scolaires` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('rentree','vacances','examen','conseil_classe','reunion_parents','fete','sortie','ferie','autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'autre',
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `lieu` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `couleur` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toute_journee` tinyint(1) NOT NULL DEFAULT '1',
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `publie` tinyint(1) NOT NULL DEFAULT '0',
  `cree_par` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evenements_scolaires_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `evenements_scolaires_cree_par_foreign` (`cree_par`),
  KEY `es_etab_annee_date` (`etablissement_id`,`annee_scolaire_id`,`date_debut`),
  CONSTRAINT `evenements_scolaires_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evenements_scolaires_cree_par_foreign` FOREIGN KEY (`cree_par`) REFERENCES `users` (`id`),
  CONSTRAINT `evenements_scolaires_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `exercices_comptables` ───────────
DROP TABLE IF EXISTS `exercices_comptables`;
CREATE TABLE `exercices_comptables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `libelle` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Exercice 2025-2026',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `en_cours` tinyint(1) NOT NULL DEFAULT '0',
  `cloture` tinyint(1) NOT NULL DEFAULT '0',
  `solde_ouverture` decimal(14,0) NOT NULL DEFAULT '0',
  `solde_cloture` decimal(14,0) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ec_etab_annee_unique` (`etablissement_id`,`annee_scolaire_id`),
  KEY `exercices_comptables_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  CONSTRAINT `exercices_comptables_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exercices_comptables_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `failed_jobs` ───────────
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `fiches_paie` ───────────
DROP TABLE IF EXISTS `fiches_paie`;
CREATE TABLE `fiches_paie` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `enseignant_id` bigint unsigned NOT NULL,
  `reference` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: FP-2026-05-0001',
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'YYYY-MM',
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `type_remuneration` enum('fixe','horaire','mixte') COLLATE utf8mb4_unicode_ci NOT NULL,
  `salaire_base` decimal(12,0) NOT NULL DEFAULT '0',
  `taux_horaire` decimal(8,0) NOT NULL DEFAULT '0',
  `heures_travaillees` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT 'Heures effectives depuis pointage',
  `heures_contractuelles` decimal(6,2) DEFAULT NULL,
  `montant_horaire` decimal(12,0) NOT NULL DEFAULT '0' COMMENT 'heures × taux',
  `primes` decimal(12,0) NOT NULL DEFAULT '0',
  `indemnites` decimal(12,0) NOT NULL DEFAULT '0',
  `avances` decimal(12,0) NOT NULL DEFAULT '0',
  `retenues` decimal(12,0) NOT NULL DEFAULT '0' COMMENT 'Absences, CNPS, IUTS, etc.',
  `details_primes` json DEFAULT NULL,
  `details_retenues` json DEFAULT NULL,
  `salaire_brut` decimal(12,0) NOT NULL DEFAULT '0',
  `cotisations_sociales` decimal(12,0) NOT NULL DEFAULT '0' COMMENT 'Part salariale CNPS',
  `impots` decimal(12,0) NOT NULL DEFAULT '0' COMMENT 'IUTS',
  `salaire_net` decimal(12,0) NOT NULL DEFAULT '0',
  `nb_jours_travailles` smallint unsigned NOT NULL DEFAULT '0',
  `nb_jours_absents` smallint unsigned NOT NULL DEFAULT '0',
  `nb_retards` smallint unsigned NOT NULL DEFAULT '0',
  `statut` enum('brouillon','validee','payee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `generee_par` bigint unsigned NOT NULL,
  `validee_par` bigint unsigned DEFAULT NULL,
  `date_validation` timestamp NULL DEFAULT NULL,
  `date_paiement_effectif` date DEFAULT NULL,
  `mode_paiement` enum('especes','cheque','virement','mobile_money') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fp_enseignant_mois_unique` (`enseignant_id`,`mois`),
  UNIQUE KEY `fiches_paie_reference_unique` (`reference`),
  KEY `fiches_paie_generee_par_foreign` (`generee_par`),
  KEY `fiches_paie_validee_par_foreign` (`validee_par`),
  KEY `fp_etab_mois_idx` (`etablissement_id`,`mois`),
  KEY `fp_etab_stat_idx` (`etablissement_id`,`statut`),
  CONSTRAINT `fiches_paie_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fiches_paie_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fiches_paie_generee_par_foreign` FOREIGN KEY (`generee_par`) REFERENCES `users` (`id`),
  CONSTRAINT `fiches_paie_validee_par_foreign` FOREIGN KEY (`validee_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `fournitures_items` ───────────
DROP TABLE IF EXISTS `fournitures_items`;
CREATE TABLE `fournitures_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `liste_id` bigint unsigned NOT NULL,
  `libelle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cahiers, Stylos, Livres, etc.',
  `quantite` smallint unsigned NOT NULL DEFAULT '1',
  `unite` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'pièce, paquet, boîte',
  `marque_suggeree` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obligatoire` tinyint(1) NOT NULL DEFAULT '1',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `ordre` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fournitures_items_liste_id_categorie_index` (`liste_id`,`categorie`),
  CONSTRAINT `fournitures_items_liste_id_foreign` FOREIGN KEY (`liste_id`) REFERENCES `listes_fournitures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `groupes_scolaires` ───────────
DROP TABLE IF EXISTS `groupes_scolaires`;
CREATE TABLE `groupes_scolaires` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sigle` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `inscriptions` ───────────
DROP TABLE IF EXISTS `inscriptions`;
CREATE TABLE `inscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `date_inscription` date NOT NULL,
  `type` enum('nouvelle','renouvellement','transfert_entrant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nouvelle',
  `statut` enum('en_attente','validee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `montant_scolarite` decimal(12,0) NOT NULL COMMENT 'Montant en FCFA',
  `montant_inscription` bigint unsigned NOT NULL DEFAULT '0',
  `reduction` decimal(12,0) NOT NULL DEFAULT '0' COMMENT 'Bourse ou réduction en FCFA',
  `montant_net` decimal(12,0) NOT NULL COMMENT 'Scolarité - réduction',
  `motif_reduction` text COLLATE utf8mb4_unicode_ci,
  `dossier_complet` tinyint(1) NOT NULL DEFAULT '0',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inscriptions_eleve_id_annee_scolaire_id_unique` (`eleve_id`,`annee_scolaire_id`),
  KEY `inscriptions_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `inscriptions_etablissement_id_foreign` (`etablissement_id`),
  KEY `inscriptions_classe_id_annee_scolaire_id_index` (`classe_id`,`annee_scolaire_id`),
  CONSTRAINT `inscriptions_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `inscriptions_en_ligne` ───────────
DROP TABLE IF EXISTS `inscriptions_en_ligne`;
CREATE TABLE `inscriptions_en_ligne` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `eleve_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `matricule_desps` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `canal` enum('orange','mtn','moov','tresor_pay','web') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('pre_inscrit','confirme','echec','non_reconnu') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pre_inscrit',
  `message_erreur` text COLLATE utf8mb4_unicode_ci,
  `date_inscription_en_ligne` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inscriptions_en_ligne_eleve_id_foreign` (`eleve_id`),
  KEY `inscriptions_en_ligne_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `insc_ligne_etab_annee_idx` (`etablissement_id`,`annee_scolaire_id`),
  CONSTRAINT `inscriptions_en_ligne_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_en_ligne_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_en_ligne_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `job_batches` ───────────
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `jobs` ───────────
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `journal_activites` ───────────
DROP TABLE IF EXISTS `journal_activites`;
CREATE TABLE `journal_activites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: creation_eleve, pointage_enseignant, paiement',
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `ancien_valeurs` json DEFAULT NULL,
  `nouveau_valeurs` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_activites_user_id_foreign` (`user_id`),
  KEY `journal_activites_etablissement_id_action_index` (`etablissement_id`,`action`),
  KEY `journal_activites_created_at_index` (`created_at`),
  CONSTRAINT `journal_activites_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journal_activites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `lignes_budgetaires` ───────────
DROP TABLE IF EXISTS `lignes_budgetaires`;
CREATE TABLE `lignes_budgetaires` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `budget_id` bigint unsigned NOT NULL,
  `categorie_depense_id` bigint unsigned DEFAULT NULL,
  `compte_comptable_numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libelle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('revenu','depense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `service` enum('scolarite','cantine','transport','activites','salaires','fonctionnement','investissement','autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fonctionnement',
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pour budget mensuel, ex: 2026-04',
  `montant_prevu` decimal(14,0) NOT NULL DEFAULT '0',
  `montant_reel` decimal(14,0) NOT NULL DEFAULT '0',
  `ecart` decimal(14,0) NOT NULL DEFAULT '0',
  `taux_realisation` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Réel / Prévu en %',
  `alerte_depassement` tinyint(1) NOT NULL DEFAULT '0',
  `seuil_alerte_pourcent` tinyint unsigned NOT NULL DEFAULT '90',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lignes_budgetaires_categorie_depense_id_foreign` (`categorie_depense_id`),
  KEY `lb_budget_type_idx` (`budget_id`,`type`),
  KEY `lb_budget_serv_idx` (`budget_id`,`service`),
  CONSTRAINT `lignes_budgetaires_budget_id_foreign` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lignes_budgetaires_categorie_depense_id_foreign` FOREIGN KEY (`categorie_depense_id`) REFERENCES `categories_depenses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `listes_fournitures` ───────────
DROP TABLE IF EXISTS `listes_fournitures`;
CREATE TABLE `listes_fournitures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Liste de fournitures',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `publie` tinyint(1) NOT NULL DEFAULT '0',
  `cree_par` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lf_classe_annee_unique` (`classe_id`,`annee_scolaire_id`),
  KEY `listes_fournitures_etablissement_id_foreign` (`etablissement_id`),
  KEY `listes_fournitures_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `listes_fournitures_cree_par_foreign` (`cree_par`),
  CONSTRAINT `listes_fournitures_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `listes_fournitures_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `listes_fournitures_cree_par_foreign` FOREIGN KEY (`cree_par`) REFERENCES `users` (`id`),
  CONSTRAINT `listes_fournitures_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `matiere_niveau` ───────────
DROP TABLE IF EXISTS `matiere_niveau`;
CREATE TABLE `matiere_niveau` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `matiere_id` bigint unsigned NOT NULL,
  `niveau_id` bigint unsigned NOT NULL,
  `serie_id` bigint unsigned DEFAULT NULL,
  `coefficient` tinyint unsigned NOT NULL,
  `volume_horaire_hebdo` decimal(4,1) NOT NULL DEFAULT '2.0' COMMENT 'Heures par semaine',
  `obligatoire` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matiere_niveau_matiere_id_niveau_id_serie_id_unique` (`matiere_id`,`niveau_id`,`serie_id`),
  KEY `matiere_niveau_niveau_id_foreign` (`niveau_id`),
  KEY `matiere_niveau_serie_id_foreign` (`serie_id`),
  CONSTRAINT `matiere_niveau_matiere_id_foreign` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matiere_niveau_niveau_id_foreign` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matiere_niveau_serie_id_foreign` FOREIGN KEY (`serie_id`) REFERENCES `series` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `matieres` ───────────
DROP TABLE IF EXISTS `matieres`;
CREATE TABLE `matieres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `parent_matiere_id` bigint unsigned DEFAULT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Mathématiques',
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: MATH, FRAN, ANG',
  `coefficient_defaut` tinyint unsigned NOT NULL DEFAULT '1',
  `poids_dans_parent` decimal(4,1) NOT NULL DEFAULT '1.0' COMMENT 'Coef de la sous-discipline DANS sa matière parente',
  `ordre` smallint unsigned NOT NULL DEFAULT '0',
  `groupe` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: Sciences, Lettres, Langues',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matieres_etablissement_id_code_unique` (`etablissement_id`,`code`),
  KEY `matieres_parent_matiere_id_foreign` (`parent_matiere_id`),
  CONSTRAINT `matieres_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matieres_parent_matiere_id_foreign` FOREIGN KEY (`parent_matiere_id`) REFERENCES `matieres` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `messages` ───────────
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `expediteur_id` bigint unsigned NOT NULL,
  `destinataire_id` bigint unsigned DEFAULT NULL,
  `classe_id` bigint unsigned DEFAULT NULL,
  `type_destinataire` enum('individuel','classe','niveau','tous_parents','tous_enseignants','tous') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individuel',
  `sujet` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `piece_jointe_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `lu_at` timestamp NULL DEFAULT NULL,
  `important` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_expediteur_id_foreign` (`expediteur_id`),
  KEY `messages_classe_id_foreign` (`classe_id`),
  KEY `messages_destinataire_id_lu_index` (`destinataire_id`,`lu`),
  KEY `messages_etablissement_id_created_at_index` (`etablissement_id`,`created_at`),
  CONSTRAINT `messages_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_destinataire_id_foreign` FOREIGN KEY (`destinataire_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_expediteur_id_foreign` FOREIGN KEY (`expediteur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `migrations` ───────────
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '2026_01_01_000001_create_tables_de_base', 1),
(4, '2026_01_01_000002_create_users_et_roles', 1),
(5, '2026_01_01_000003_create_structure_pedagogique', 1),
(6, '2026_01_01_000004_create_gestion_eleves', 1),
(7, '2026_01_01_000005_create_gestion_enseignants', 1),
(8, '2026_01_01_000006_create_pointage_qr_code', 1),
(9, '2026_01_01_000007_create_notes_et_bulletins', 1),
(10, '2026_01_01_000008_create_paiements_paydunya', 1),
(11, '2026_01_01_000009_create_emploi_temps_et_communication', 1),
(12, '2026_01_01_000010_create_ia_et_sigfne', 1),
(13, '2026_01_01_000011_create_comptabilite', 1),
(14, '2026_01_01_000012_create_depenses_tresorerie_budget', 1),
(15, '2026_01_01_000013_create_rentabilite_simulation_cockpit', 1),
(16, '2026_01_01_000014_create_edt_module_tables', 1),
(17, '2026_04_14_015957_create_personal_access_tokens_table', 1),
(18, '2026_05_13_000001_create_edt_enseignant_horaires_externes', 1),
(19, '2026_05_13_000002_create_edt_enseignant_horaires_imports', 1),
(20, '2026_05_13_000003_add_ia_columns_to_emploi_du_temps', 1),
(21, '2026_05_13_000005_create_edt_classe_plage_horaire', 1),
(22, '2026_05_13_000006_add_statut_eleve_to_eleves', 1),
(23, '2026_05_13_000008_create_devoirs', 1),
(24, '2026_05_13_000010_create_eleves_import_jobs', 1),
(25, '2026_05_13_000011_add_classe_id_to_eleves_and_fix_progression', 1),
(26, '2026_05_13_000012_make_eleves_date_naissance_nullable', 1),
(27, '2026_05_13_000013_align_presences_eleves_schema', 1),
(28, '2026_05_13_000014_add_redoublant_lv2_arts_to_eleves', 1),
(29, '2026_05_13_000015_relax_presences_unique_for_creneaux', 1),
(30, '2026_05_14_000001_add_traitement_to_presences_eleves', 1),
(31, '2026_05_14_000002_evaluation_system_and_sujets', 1),
(32, '2026_05_14_000003_add_publie_to_moyennes_matieres', 1),
(33, '2026_05_14_000004_ivorian_eval_specifics', 1),
(34, '2026_05_14_100000_add_active_etablissement_id_to_users', 1),
(35, '2026_05_14_100001_create_device_tokens_table', 1),
(36, '2026_05_14_100002_create_api_sync_dedup_table', 1),
(37, '2026_05_16_000001_add_cahier_texte_to_pointages', 1),
(38, '2026_05_17_100000_add_pointage_validation_fields', 1),
(39, '2026_05_17_140000_add_tarification_finances', 1),
(40, '2026_05_17_200000_add_wave_payment_to_etablissements', 1),
(41, '2026_05_18_020000_inscriptions_montants_defaults', 1),
(42, '2026_05_18_100000_annee_scolaire_archives_platform', 1),
(43, '2026_05_18_120000_add_paiements_manuels_to_etablissements', 1),
(44, '2026_05_19_100000_enrich_paiements_eleves', 1),
(45, '2026_05_21_100000_add_remuneration_enseignants_and_fiches_paie', 1),
(46, '2026_05_21_120000_create_sms_credits_recharges_envois', 1),
(47, '2026_05_21_140000_create_evenements_et_conseils_classe', 1),
(48, '2026_05_21_160000_create_listes_fournitures', 1),
(49, '2026_05_21_180000_add_sigfne_credentials_to_etablissements', 1),
(50, '2026_05_21_200000_extend_remontees_sigfne_statut', 1);

-- ─────────── Table : `mouvements_tresorerie` ───────────
DROP TABLE IF EXISTS `mouvements_tresorerie`;
CREATE TABLE `mouvements_tresorerie` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `compte_tresorerie_id` bigint unsigned NOT NULL,
  `sens` enum('entree','sortie') COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(14,0) NOT NULL,
  `solde_avant` decimal(14,0) NOT NULL,
  `solde_apres` decimal(14,0) NOT NULL,
  `date_mouvement` date NOT NULL,
  `libelle` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'paiement, depense, virement, salaire',
  `reference_id` bigint unsigned DEFAULT NULL,
  `saisie_par` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mouvements_tresorerie_saisie_par_foreign` (`saisie_par`),
  KEY `mvt_cpt_date_idx` (`compte_tresorerie_id`,`date_mouvement`),
  KEY `mvt_etab_date_idx` (`etablissement_id`,`date_mouvement`),
  CONSTRAINT `mouvements_tresorerie_compte_tresorerie_id_foreign` FOREIGN KEY (`compte_tresorerie_id`) REFERENCES `comptes_tresorerie` (`id`),
  CONSTRAINT `mouvements_tresorerie_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mouvements_tresorerie_saisie_par_foreign` FOREIGN KEY (`saisie_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `moyennes_annuelles` ───────────
DROP TABLE IF EXISTS `moyennes_annuelles`;
CREATE TABLE `moyennes_annuelles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `moyenne_t1` decimal(5,2) DEFAULT NULL,
  `moyenne_t2` decimal(5,2) DEFAULT NULL,
  `moyenne_t3` decimal(5,2) DEFAULT NULL,
  `moyenne_annuelle` decimal(5,2) DEFAULT NULL,
  `rang_annuel` smallint unsigned DEFAULT NULL,
  `decision` enum('passage','redoublement','exclusion','en_attente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `classe_suivante` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Classe affectée pour l année suivante',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `moyennes_annuelles_eleve_id_annee_scolaire_id_unique` (`eleve_id`,`annee_scolaire_id`),
  KEY `moyennes_annuelles_classe_id_foreign` (`classe_id`),
  KEY `moyennes_annuelles_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  CONSTRAINT `moyennes_annuelles_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moyennes_annuelles_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moyennes_annuelles_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `moyennes_generales` ───────────
DROP TABLE IF EXISTS `moyennes_generales`;
CREATE TABLE `moyennes_generales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `trimestre_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `moyenne_generale` decimal(5,2) DEFAULT NULL,
  `total_points` decimal(8,2) DEFAULT NULL,
  `total_coefficients` decimal(6,1) DEFAULT NULL,
  `rang` smallint unsigned DEFAULT NULL,
  `effectif_classe` smallint unsigned DEFAULT NULL,
  `moyenne_premier` decimal(5,2) DEFAULT NULL,
  `moyenne_dernier` decimal(5,2) DEFAULT NULL,
  `moyenne_classe` decimal(5,2) DEFAULT NULL,
  `appreciation_generale` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mention` enum('tableau_honneur','encouragements','felicitations','avertissement','blame','aucune') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aucune',
  `total_absences` smallint unsigned NOT NULL DEFAULT '0',
  `absences_justifiees` smallint unsigned NOT NULL DEFAULT '0',
  `total_retards` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `moyennes_generales_eleve_id_trimestre_id_unique` (`eleve_id`,`trimestre_id`),
  KEY `moyennes_generales_trimestre_id_foreign` (`trimestre_id`),
  KEY `moyennes_generales_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `moyennes_generales_classe_id_trimestre_id_index` (`classe_id`,`trimestre_id`),
  CONSTRAINT `moyennes_generales_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moyennes_generales_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moyennes_generales_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moyennes_generales_trimestre_id_foreign` FOREIGN KEY (`trimestre_id`) REFERENCES `trimestres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `moyennes_matieres` ───────────
DROP TABLE IF EXISTS `moyennes_matieres`;
CREATE TABLE `moyennes_matieres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `matiere_id` bigint unsigned NOT NULL,
  `enseignant_id` bigint unsigned DEFAULT NULL,
  `saisie_par` bigint unsigned DEFAULT NULL,
  `date_saisie` timestamp NULL DEFAULT NULL,
  `trimestre_id` bigint unsigned NOT NULL,
  `moyenne` decimal(5,2) DEFAULT NULL,
  `moyenne_ponderee` decimal(5,2) DEFAULT NULL COMMENT 'Moyenne × coefficient',
  `rang_classe` smallint unsigned DEFAULT NULL,
  `note_min_classe` decimal(5,2) DEFAULT NULL,
  `note_max_classe` decimal(5,2) DEFAULT NULL,
  `moyenne_classe` decimal(5,2) DEFAULT NULL,
  `appreciation` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Peut être générée par IA',
  `saisie_directe` tinyint(1) NOT NULL DEFAULT '0',
  `publie` tinyint(1) NOT NULL DEFAULT '0',
  `date_publication` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `moyennes_matieres_eleve_id_matiere_id_trimestre_id_unique` (`eleve_id`,`matiere_id`,`trimestre_id`),
  KEY `moyennes_matieres_matiere_id_foreign` (`matiere_id`),
  KEY `moyennes_matieres_trimestre_id_foreign` (`trimestre_id`),
  KEY `moyennes_matieres_classe_id_trimestre_id_index` (`classe_id`,`trimestre_id`),
  KEY `moyennes_matieres_enseignant_id_foreign` (`enseignant_id`),
  KEY `moyennes_matieres_saisie_par_foreign` (`saisie_par`),
  CONSTRAINT `moyennes_matieres_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moyennes_matieres_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moyennes_matieres_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `moyennes_matieres_matiere_id_foreign` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moyennes_matieres_saisie_par_foreign` FOREIGN KEY (`saisie_par`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `moyennes_matieres_trimestre_id_foreign` FOREIGN KEY (`trimestre_id`) REFERENCES `trimestres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `niveaux` ───────────
DROP TABLE IF EXISTS `niveaux`;
CREATE TABLE `niveaux` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: 6eme, 5eme, 4eme, 3eme, 2nde, 1ere, Tle',
  `libelle` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Sixième, Cinquième...',
  `cycle` enum('prescolaire','primaire','premier_cycle','second_cycle') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'premier_cycle',
  `ordre` tinyint unsigned NOT NULL COMMENT 'Pour le tri',
  `frais_scolarite_defaut` decimal(12,0) NOT NULL DEFAULT '0' COMMENT 'Montant en FCFA',
  `frais_inscription_defaut` bigint unsigned NOT NULL DEFAULT '0',
  `frais_reinscription_defaut` bigint unsigned NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `niveaux_etablissement_id_code_unique` (`etablissement_id`,`code`),
  CONSTRAINT `niveaux_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `notes` ───────────
DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_id` bigint unsigned NOT NULL,
  `eleve_id` bigint unsigned NOT NULL,
  `note` decimal(5,2) DEFAULT NULL COMMENT 'Note obtenue',
  `absent` tinyint(1) NOT NULL DEFAULT '0',
  `dispense` tinyint(1) NOT NULL DEFAULT '0',
  `observation` text COLLATE utf8mb4_unicode_ci,
  `saisie_par` bigint unsigned DEFAULT NULL,
  `date_saisie` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notes_evaluation_id_eleve_id_unique` (`evaluation_id`,`eleve_id`),
  KEY `notes_saisie_par_foreign` (`saisie_par`),
  KEY `notes_eleve_id_index` (`eleve_id`),
  CONSTRAINT `notes_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_saisie_par_foreign` FOREIGN KEY (`saisie_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `notifications` ───────────
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `canal` enum('app','sms','email','whatsapp') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'app',
  `type` enum('paiement','note','absence','pointage','annonce','bulletin','relance','desps','alerte_ia','systeme') COLLATE utf8mb4_unicode_ci NOT NULL,
  `lien_action` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL vers la page concernée',
  `lue` tinyint(1) NOT NULL DEFAULT '0',
  `lue_at` timestamp NULL DEFAULT NULL,
  `envoyee` tinyint(1) NOT NULL DEFAULT '0',
  `envoyee_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_lue_created_at_index` (`user_id`,`lue`,`created_at`),
  KEY `notifications_etablissement_id_type_index` (`etablissement_id`,`type`),
  CONSTRAINT `notifications_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `paie_enseignants` ───────────
DROP TABLE IF EXISTS `paie_enseignants`;
CREATE TABLE `paie_enseignants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enseignant_id` bigint unsigned NOT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: 2026-04',
  `salaire_base` decimal(12,0) NOT NULL,
  `primes` decimal(12,0) NOT NULL DEFAULT '0',
  `retenues` decimal(12,0) NOT NULL DEFAULT '0',
  `retenue_absence` decimal(12,0) NOT NULL DEFAULT '0' COMMENT 'Déduction pour absences',
  `net_a_payer` decimal(12,0) NOT NULL,
  `jours_presents` tinyint unsigned NOT NULL DEFAULT '0',
  `jours_absents` tinyint unsigned NOT NULL DEFAULT '0',
  `jours_retard` tinyint unsigned NOT NULL DEFAULT '0',
  `statut_paiement` enum('en_attente','paye','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `date_paiement` date DEFAULT NULL,
  `mode_paiement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_paiement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paie_enseignants_enseignant_id_mois_unique` (`enseignant_id`,`mois`),
  KEY `paie_enseignants_etablissement_id_foreign` (`etablissement_id`),
  CONSTRAINT `paie_enseignants_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paie_enseignants_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `paiements` ───────────
DROP TABLE IF EXISTS `paiements`;
CREATE TABLE `paiements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `inscription_id` bigint unsigned NOT NULL,
  `eleve_id` bigint unsigned NOT NULL,
  `echeance_id` bigint unsigned DEFAULT NULL,
  `encaisse_par` bigint unsigned DEFAULT NULL,
  `reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Référence unique AviaSchoolPay',
  `reference_transaction` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant` decimal(12,0) NOT NULL COMMENT 'Montant en FCFA',
  `montant_inscription` int unsigned NOT NULL DEFAULT '0',
  `montant_scolarite` int unsigned NOT NULL DEFAULT '0',
  `date_paiement` date NOT NULL,
  `date_validation` timestamp NULL DEFAULT NULL,
  `mode` enum('orange_money','mtn_money','moov_money','wave','carte_bancaire','virement','especes','cheque') COLLATE utf8mb4_unicode_ci NOT NULL,
  `poste_cible` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto' COMMENT 'inscription, scolarite, auto',
  `canal_paiement` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'manuel, wave',
  `statut` enum('en_attente','confirme','echoue','rembourse','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `paydunya_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token de transaction PayDunya',
  `paydunya_invoice_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL facture PayDunya',
  `wave_checkout_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paydunya_response_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paydunya_response_text` text COLLATE utf8mb4_unicode_ci,
  `paydunya_metadata` json DEFAULT NULL COMMENT 'Réponse complète PayDunya',
  `paydunya_callback_at` timestamp NULL DEFAULT NULL,
  `numero_recu` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: REC-2026-04-0001',
  `recu_pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recu_envoye_sms` tinyint(1) NOT NULL DEFAULT '0',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `motif_annulation` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paiements_reference_unique` (`reference`),
  KEY `paiements_inscription_id_foreign` (`inscription_id`),
  KEY `paiements_echeance_id_foreign` (`echeance_id`),
  KEY `paiements_encaisse_par_foreign` (`encaisse_par`),
  KEY `paiements_etablissement_id_date_paiement_index` (`etablissement_id`,`date_paiement`),
  KEY `paiements_eleve_id_statut_index` (`eleve_id`,`statut`),
  KEY `paiements_paydunya_token_index` (`paydunya_token`),
  CONSTRAINT `paiements_echeance_id_foreign` FOREIGN KEY (`echeance_id`) REFERENCES `echeances` (`id`) ON DELETE SET NULL,
  CONSTRAINT `paiements_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paiements_encaisse_par_foreign` FOREIGN KEY (`encaisse_par`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `paiements_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paiements_inscription_id_foreign` FOREIGN KEY (`inscription_id`) REFERENCES `inscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `parametres` ───────────
DROP TABLE IF EXISTS `parametres`;
CREATE TABLE `parametres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `cle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string' COMMENT 'string, integer, boolean, json',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parametres_etablissement_id_cle_unique` (`etablissement_id`,`cle`),
  CONSTRAINT `parametres_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `parents_tuteurs` ───────────
DROP TABLE IF EXISTS `parents_tuteurs`;
CREATE TABLE `parents_tuteurs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexe` enum('M','F') COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_2` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `profession` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lien_parente` enum('pere','mere','tuteur','tutrice','oncle','tante','frere','soeur','autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pere',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parents_tuteurs_user_id_foreign` (`user_id`),
  KEY `parents_tuteurs_etablissement_id_telephone_index` (`etablissement_id`,`telephone`),
  CONSTRAINT `parents_tuteurs_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parents_tuteurs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `password_reset_tokens` ───────────
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`telephone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `permissions` ───────────
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Module concerné',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_nom_unique` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `personal_access_tokens` ───────────
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `plans_paiement` ───────────
DROP TABLE IF EXISTS `plans_paiement`;
CREATE TABLE `plans_paiement` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Paiement trimestriel, Paiement mensuel',
  `nombre_echeances` tinyint unsigned NOT NULL,
  `echeances_config` json DEFAULT NULL COMMENT 'JSON: [{mois: 10, pourcentage: 40}, ...]',
  `par_defaut` tinyint(1) NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plans_paiement_etablissement_id_foreign` (`etablissement_id`),
  CONSTRAINT `plans_paiement_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `platform_settings` ───────────
DROP TABLE IF EXISTS `platform_settings`;
CREATE TABLE `platform_settings` (
  `cle` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `platform_settings` VALUES
('wave_libelle_restauration', 'Avia Technologie', 'Libellé paiement restauration', '2026-05-23 10:47:35', '2026-05-23 10:47:35'),
('wave_lien_restauration_500', 'https://pay.wave.com/m/M_ci_1Onagr26EsBs/c/ci/', 'Lien Wave restauration archive (500 FCFA)', '2026-05-23 10:47:35', '2026-05-23 10:47:35');

-- ─────────── Table : `pointages` ───────────
DROP TABLE IF EXISTS `pointages`;
CREATE TABLE `pointages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enseignant_id` bigint unsigned NOT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `qr_code_id` bigint unsigned DEFAULT NULL,
  `salle_id` bigint unsigned DEFAULT NULL,
  `emploi_du_temps_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `type_scan` enum('arrivee','depart') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'arrivee',
  `heure_scan` time NOT NULL,
  `methode` enum('qr_gps','pin_gps','nfc_gps','manuel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'qr_gps',
  `statut` enum('present','retard','absent','hors_zone','fraude_detectee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `gps_latitude` decimal(10,7) DEFAULT NULL,
  `gps_longitude` decimal(10,7) DEFAULT NULL,
  `gps_precision_metres` decimal(6,1) DEFAULT NULL COMMENT 'Précision GPS en mètres',
  `distance_ecole_metres` decimal(8,1) DEFAULT NULL COMMENT 'Distance calculée à l école',
  `gps_valide` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'GPS dans le périmètre autorisé',
  `spoofing_detecte` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Tentative de faux GPS détectée',
  `token_validation` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Jeton à usage unique serveur',
  `token_expire_at` timestamp NULL DEFAULT NULL,
  `token_valide` tinyint(1) NOT NULL DEFAULT '0',
  `selfie_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cahier_texte_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cahier_texte_data` json DEFAULT NULL,
  `cahier_texte_validated` tinyint(1) NOT NULL DEFAULT '0',
  `cahier_texte_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `cahier_texte_validated_at` timestamp NULL DEFAULT NULL,
  `cahier_texte_deadline_at` timestamp NULL DEFAULT NULL,
  `cahier_texte_confidence` tinyint unsigned DEFAULT NULL,
  `conforme_emploi_temps` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Le scan correspond à l emploi du temps',
  `validation_finale` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'provisoire',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pointages_qr_code_id_foreign` (`qr_code_id`),
  KEY `pointages_salle_id_foreign` (`salle_id`),
  KEY `pointages_enseignant_id_date_index` (`enseignant_id`,`date`),
  KEY `pointages_etablissement_id_date_statut_index` (`etablissement_id`,`date`,`statut`),
  KEY `pointages_date_type_scan_index` (`date`,`type_scan`),
  KEY `pointages_emploi_du_temps_id_foreign` (`emploi_du_temps_id`),
  CONSTRAINT `pointages_emploi_du_temps_id_foreign` FOREIGN KEY (`emploi_du_temps_id`) REFERENCES `emploi_du_temps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pointages_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pointages_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pointages_qr_code_id_foreign` FOREIGN KEY (`qr_code_id`) REFERENCES `qr_codes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pointages_salle_id_foreign` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `predictions_ia` ───────────
DROP TABLE IF EXISTS `predictions_ia`;
CREATE TABLE `predictions_ia` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `type` enum('recouvrement','reussite_examen','decrochage_eleve','orientation','absence_enseignant','anomalie_notes','performance_classe','budget') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cible_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: eleve, enseignant, classe',
  `cible_id` bigint unsigned DEFAULT NULL,
  `score_confiance` decimal(5,2) DEFAULT NULL COMMENT '0-100',
  `donnees_entree` json DEFAULT NULL,
  `resultat` json DEFAULT NULL,
  `recommandation` text COLLATE utf8mb4_unicode_ci,
  `priorite` enum('basse','moyenne','haute','critique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moyenne',
  `vue_par_directeur` tinyint(1) NOT NULL DEFAULT '0',
  `action_prise` tinyint(1) NOT NULL DEFAULT '0',
  `action_description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `predictions_ia_etablissement_id_type_created_at_index` (`etablissement_id`,`type`,`created_at`),
  KEY `predictions_ia_cible_type_cible_id_index` (`cible_type`,`cible_id`),
  CONSTRAINT `predictions_ia_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `presences_eleves` ───────────
DROP TABLE IF EXISTS `presences_eleves`;
CREATE TABLE `presences_eleves` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint unsigned NOT NULL,
  `classe_id` bigint unsigned NOT NULL,
  `matiere_id` bigint unsigned DEFAULT NULL,
  `enseignant_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `statut` enum('present','absent','retard','excuse','dispense') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `periode` enum('matin','apres_midi','journee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'journee',
  `creneau_id` bigint unsigned DEFAULT NULL,
  `motif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `justification` text COLLATE utf8mb4_unicode_ci,
  `observation` text COLLATE utf8mb4_unicode_ci,
  `justifie` tinyint(1) NOT NULL DEFAULT '0',
  `saisie_par` bigint unsigned DEFAULT NULL,
  `traite_par` bigint unsigned DEFAULT NULL,
  `traite_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `presences_eleve_date_creneau_unique` (`eleve_id`,`date`,`creneau_id`),
  KEY `presences_eleves_saisie_par_foreign` (`saisie_par`),
  KEY `presences_eleves_classe_id_date_index` (`classe_id`,`date`),
  KEY `presences_eleves_matiere_id_foreign` (`matiere_id`),
  KEY `presences_eleves_enseignant_id_foreign` (`enseignant_id`),
  KEY `presences_eleves_creneau_id_foreign` (`creneau_id`),
  KEY `presences_eleves_eleve_id_index` (`eleve_id`),
  KEY `presences_classe_date_creneau_idx` (`classe_id`,`date`,`creneau_id`),
  KEY `presences_eleves_traite_par_foreign` (`traite_par`),
  CONSTRAINT `presences_eleves_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `presences_eleves_creneau_id_foreign` FOREIGN KEY (`creneau_id`) REFERENCES `creneaux` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presences_eleves_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `presences_eleves_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presences_eleves_matiere_id_foreign` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presences_eleves_saisie_par_foreign` FOREIGN KEY (`saisie_par`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presences_eleves_traite_par_foreign` FOREIGN KEY (`traite_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `projections_financieres` ───────────
DROP TABLE IF EXISTS `projections_financieres`;
CREATE TABLE `projections_financieres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `exercice_id` bigint unsigned NOT NULL,
  `mois_projection` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mois futur projeté',
  `revenus_projetes` decimal(14,0) NOT NULL DEFAULT '0',
  `depenses_projetees` decimal(14,0) NOT NULL DEFAULT '0',
  `tresorerie_projetee` decimal(14,0) NOT NULL DEFAULT '0',
  `confiance_pourcent` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Niveau de confiance IA',
  `hypotheses` json DEFAULT NULL,
  `date_calcul` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projections_financieres_exercice_id_foreign` (`exercice_id`),
  KEY `pf_etab_mois_idx` (`etablissement_id`,`mois_projection`),
  CONSTRAINT `projections_financieres_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projections_financieres_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices_comptables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `qr_codes` ───────────
DROP TABLE IF EXISTS `qr_codes`;
CREATE TABLE `qr_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `salle_id` bigint unsigned NOT NULL,
  `code_unique` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash SHA-256 identifiant le QR',
  `contenu_qr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Données encodées dans le QR imprimé',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_impression` date DEFAULT NULL,
  `date_desactivation` date DEFAULT NULL,
  `motif_desactivation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_codes_code_unique_unique` (`code_unique`),
  KEY `qr_codes_salle_id_foreign` (`salle_id`),
  KEY `qr_codes_etablissement_id_actif_index` (`etablissement_id`,`actif`),
  CONSTRAINT `qr_codes_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qr_codes_salle_id_foreign` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `rapports_desps` ───────────
DROP TABLE IF EXISTS `rapports_desps`;
CREATE TABLE `rapports_desps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `type` enum('liste_nominative','rapport_conformite','statistiques_effectifs','rapport_moyennes','rapport_dfa','fichier_orientation','fichier_examens_bepc','fichier_examens_bac','fichier_cepe') COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fichier_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `format` enum('pdf','csv','xlsx') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pdf',
  `genere_par_ia` tinyint(1) NOT NULL DEFAULT '0',
  `genere_par` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rapports_desps_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `rapports_desps_genere_par_foreign` (`genere_par`),
  KEY `rapports_desps_etablissement_id_type_index` (`etablissement_id`,`type`),
  CONSTRAINT `rapports_desps_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rapports_desps_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rapports_desps_genere_par_foreign` FOREIGN KEY (`genere_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `rapprochements_bancaires` ───────────
DROP TABLE IF EXISTS `rapprochements_bancaires`;
CREATE TABLE `rapprochements_bancaires` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `compte_id` bigint unsigned NOT NULL,
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `solde_releve_bancaire` decimal(14,0) NOT NULL,
  `solde_comptable` decimal(14,0) NOT NULL,
  `ecart` decimal(14,0) NOT NULL DEFAULT '0',
  `rapproche` tinyint(1) NOT NULL DEFAULT '0',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rappr_etab_cpt_mois_uniq` (`etablissement_id`,`compte_id`,`mois`),
  KEY `rapprochements_bancaires_compte_id_foreign` (`compte_id`),
  CONSTRAINT `rapprochements_bancaires_compte_id_foreign` FOREIGN KEY (`compte_id`) REFERENCES `comptes_comptables` (`id`),
  CONSTRAINT `rapprochements_bancaires_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `recouvrement_mensuel` ───────────
DROP TABLE IF EXISTS `recouvrement_mensuel`;
CREATE TABLE `recouvrement_mensuel` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_attendu` decimal(14,0) NOT NULL DEFAULT '0',
  `total_encaisse` decimal(14,0) NOT NULL DEFAULT '0',
  `total_reste` decimal(14,0) NOT NULL DEFAULT '0',
  `taux_recouvrement` decimal(5,2) NOT NULL DEFAULT '0.00',
  `eleves_a_jour` smallint unsigned NOT NULL DEFAULT '0',
  `eleves_en_retard` smallint unsigned NOT NULL DEFAULT '0',
  `eleves_impaye_total` smallint unsigned NOT NULL DEFAULT '0',
  `repartition_par_mode` json DEFAULT NULL COMMENT 'JSON: {orange_money: 5000000, ...}',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recouv_etab_annee_mois_unique` (`etablissement_id`,`annee_scolaire_id`,`mois`),
  KEY `recouvrement_mensuel_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  CONSTRAINT `recouvrement_mensuel_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recouvrement_mensuel_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `relances` ───────────
DROP TABLE IF EXISTS `relances`;
CREATE TABLE `relances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `inscription_id` bigint unsigned NOT NULL,
  `echeance_id` bigint unsigned DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `canal` enum('sms','whatsapp','email','notification_app') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sms',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant_du` decimal(12,0) NOT NULL,
  `statut_envoi` enum('programme','envoye','delivre','echoue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'programme',
  `date_envoi` timestamp NULL DEFAULT NULL,
  `lien_paiement_paydunya` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lien PayDunya personnalisé',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `relances_inscription_id_foreign` (`inscription_id`),
  KEY `relances_echeance_id_foreign` (`echeance_id`),
  KEY `relances_parent_id_foreign` (`parent_id`),
  KEY `relances_etablissement_id_statut_envoi_index` (`etablissement_id`,`statut_envoi`),
  CONSTRAINT `relances_echeance_id_foreign` FOREIGN KEY (`echeance_id`) REFERENCES `echeances` (`id`) ON DELETE SET NULL,
  CONSTRAINT `relances_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `relances_inscription_id_foreign` FOREIGN KEY (`inscription_id`) REFERENCES `inscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `relances_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `parents_tuteurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `remontee_eleves` ───────────
DROP TABLE IF EXISTS `remontee_eleves`;
CREATE TABLE `remontee_eleves` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remontee_sigfne_id` bigint unsigned NOT NULL,
  `eleve_id` bigint unsigned NOT NULL,
  `matricule_desps` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moyenne_remontee` decimal(5,2) DEFAULT NULL,
  `statut` enum('ok','erreur_matricule','erreur_moyenne','non_trouve','en_attente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `message_erreur` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remontee_eleves_eleve_id_foreign` (`eleve_id`),
  KEY `remontee_eleves_remontee_sigfne_id_index` (`remontee_sigfne_id`),
  CONSTRAINT `remontee_eleves_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remontee_eleves_remontee_sigfne_id_foreign` FOREIGN KEY (`remontee_sigfne_id`) REFERENCES `remontees_sigfne` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `remontees_sigfne` ───────────
DROP TABLE IF EXISTS `remontees_sigfne`;
CREATE TABLE `remontees_sigfne` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `trimestre_id` bigint unsigned NOT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `plateforme` enum('agfne','agcp','agce_deco','agcepe_deco') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'agfne',
  `type` enum('moyennes_trimestrielles','moyennes_annuelles','dfa','fichier_examens') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moyennes_trimestrielles',
  `total_eleves` smallint unsigned NOT NULL DEFAULT '0',
  `eleves_remontes` smallint unsigned NOT NULL DEFAULT '0',
  `eleves_en_erreur` smallint unsigned NOT NULL DEFAULT '0',
  `eleves_sans_matricule` smallint unsigned NOT NULL DEFAULT '0',
  `statut` enum('preparation','en_cours','pret_envoi','envoye','termine','erreur','erreur_api','valide_drena') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'preparation',
  `fichier_export_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fichier CSV/Excel généré',
  `date_envoi` timestamp NULL DEFAULT NULL,
  `date_validation_drena` timestamp NULL DEFAULT NULL,
  `envoye_par` bigint unsigned DEFAULT NULL,
  `erreurs_detail` json DEFAULT NULL COMMENT 'JSON des erreurs rencontrées',
  `reponse_sigfne` json DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remontees_sigfne_trimestre_id_foreign` (`trimestre_id`),
  KEY `remontees_sigfne_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `remontees_sigfne_envoye_par_foreign` (`envoye_par`),
  KEY `remontees_sigfne_etablissement_id_trimestre_id_index` (`etablissement_id`,`trimestre_id`),
  KEY `remontees_sigfne_statut_index` (`statut`),
  CONSTRAINT `remontees_sigfne_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remontees_sigfne_envoye_par_foreign` FOREIGN KEY (`envoye_par`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `remontees_sigfne_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remontees_sigfne_trimestre_id_foreign` FOREIGN KEY (`trimestre_id`) REFERENCES `trimestres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `revenus_services` ───────────
DROP TABLE IF EXISTS `revenus_services`;
CREATE TABLE `revenus_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `centre_profit_id` bigint unsigned DEFAULT NULL,
  `exercice_id` bigint unsigned NOT NULL,
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(14,0) NOT NULL,
  `type` enum('recurrent','ponctuel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recurrent',
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Inscription, cantine, transport, etc.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `revenus_services_centre_profit_id_foreign` (`centre_profit_id`),
  KEY `revenus_services_exercice_id_foreign` (`exercice_id`),
  KEY `rs_etab_mois_idx` (`etablissement_id`,`mois`),
  CONSTRAINT `revenus_services_centre_profit_id_foreign` FOREIGN KEY (`centre_profit_id`) REFERENCES `centres_profit` (`id`) ON DELETE SET NULL,
  CONSTRAINT `revenus_services_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `revenus_services_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices_comptables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `role_permissions` ───────────
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `etablissement_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_permission_id_etablissement_id_unique` (`role`,`permission_id`,`etablissement_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  KEY `role_permissions_etablissement_id_foreign` (`etablissement_id`),
  CONSTRAINT `role_permissions_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `salles` ───────────
DROP TABLE IF EXISTS `salles`;
CREATE TABLE `salles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `nom` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Salle A1, Labo Physique',
  `batiment` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacite` smallint unsigned NOT NULL DEFAULT '60',
  `type` enum('classe','laboratoire','informatique','sport','amphitheatre','autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'classe',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salles_etablissement_id_nom_unique` (`etablissement_id`,`nom`),
  CONSTRAINT `salles_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `scores_financiers` ───────────
DROP TABLE IF EXISTS `scores_financiers`;
CREATE TABLE `scores_financiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `date_calcul` date NOT NULL,
  `score_global` decimal(5,2) NOT NULL,
  `indicateur` enum('vert','orange','rouge') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Basé sur score_global',
  `score_tresorerie` decimal(5,2) DEFAULT NULL COMMENT 'Liquidité, solvabilité',
  `score_recouvrement` decimal(5,2) DEFAULT NULL,
  `score_rentabilite` decimal(5,2) DEFAULT NULL,
  `score_budget` decimal(5,2) DEFAULT NULL COMMENT 'Respect du budget',
  `score_masse_salariale` decimal(5,2) DEFAULT NULL,
  `score_endettement` decimal(5,2) DEFAULT NULL,
  `ratio_liquidite` decimal(5,2) DEFAULT NULL COMMENT 'Actif court terme / Passif court terme',
  `ratio_ms_revenus` decimal(5,2) DEFAULT NULL COMMENT 'Masse salariale / Revenus',
  `ratio_charges_fixes` decimal(5,2) DEFAULT NULL COMMENT 'Charges fixes / Revenus',
  `fonds_roulement_mois` decimal(4,1) DEFAULT NULL COMMENT 'Combien de mois de fonctionnement en réserve',
  `risques_detectes` json DEFAULT NULL COMMENT 'Liste des risques identifiés',
  `recommandations` json DEFAULT NULL COMMENT 'Actions suggérées par IA',
  `tendances` json DEFAULT NULL COMMENT 'Évolution sur 3-6 mois',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sf_etab_date_idx` (`etablissement_id`,`date_calcul`),
  CONSTRAINT `scores_financiers_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `scores_sante` ───────────
DROP TABLE IF EXISTS `scores_sante`;
CREATE TABLE `scores_sante` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `date_calcul` date NOT NULL,
  `score_global` decimal(5,2) NOT NULL COMMENT 'Score 0-100',
  `score_pedagogie` decimal(5,2) DEFAULT NULL,
  `score_finances` decimal(5,2) DEFAULT NULL,
  `score_presence` decimal(5,2) DEFAULT NULL,
  `score_communication` decimal(5,2) DEFAULT NULL,
  `score_conformite_desps` decimal(5,2) DEFAULT NULL,
  `details` json DEFAULT NULL COMMENT 'Détail des facteurs',
  `recommandations` json DEFAULT NULL COMMENT 'Actions suggérées par IA',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scores_sante_etablissement_id_date_calcul_index` (`etablissement_id`,`date_calcul`),
  CONSTRAINT `scores_sante_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `series` ───────────
DROP TABLE IF EXISTS `series`;
CREATE TABLE `series` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A, C, D, etc.',
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Série A (Lettres), Série D (Sciences)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `series_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `sessions` ───────────
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` VALUES
('3Vn78qUQdYhqnkvicKci2RQSaxkxZmKhukuent9Y', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJmMWNFMXZzcERFYmpORlJITURFUTdwb1Zqa24wQm9NY3hSQ1hPZzlsIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJ1cmwiOnsiaW50ZW5kZWQiOiJodHRwOlwvXC9hdmlhc2Nob29scGF5LnRlc3RcL2Vuc2VpZ25hbnRzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC9hdmlhc2Nob29scGF5LnRlc3RcL2FkbWluXC9yaFwvcHJlc2VuY2VzXC9iaWxhbiIsInJvdXRlIjoiYWRtaW4ucmgucHJlc2VuY2VzLmJpbGFuIn0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjo1fQ==', 1779549682),
('kP3jMYc51dvXIzypz1Mvkb0IMQnYufHhrHw7v2xT', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJZbWxmSDdUTDMxeUVnandoNm1qMkRZVm4waUNmZ204emt4QXJwYVFPIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvYXZpYXNjaG9vbHBheS50ZXN0XC9hZG1pblwvZXRhYmxpc3NlbWVudHNcLzMiLCJyb3V0ZSI6ImFkbWluLmV0YWJsaXNzZW1lbnRzLnNob3cifSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjN9', 1779551490),
('KupFETo8itnu5cxrwqj9c2I132P3wZkIWSUwuhDe', 6, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', 'eyJfdG9rZW4iOiJBTXhET3U3VERiYkpOS1NLbnpjWm9SQ2h0Tk1FMjBMMWpDTVdQZUM2IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvYXZpYXNjaG9vbHBheS50ZXN0XC9hZG1pblwvYW5uZWVzLXNjb2xhaXJlcyIsInJvdXRlIjoiYWRtaW4uYW5uZWVzLmluZGV4In0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjo2fQ==', 1779551517);

-- ─────────── Table : `seuils_rentabilite` ───────────
DROP TABLE IF EXISTS `seuils_rentabilite`;
CREATE TABLE `seuils_rentabilite` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `exercice_id` bigint unsigned NOT NULL,
  `charges_fixes_totales` decimal(14,0) NOT NULL DEFAULT '0',
  `charges_variables_totales` decimal(14,0) NOT NULL DEFAULT '0',
  `revenu_moyen_par_eleve` decimal(12,0) NOT NULL DEFAULT '0',
  `cout_variable_par_eleve` decimal(12,0) NOT NULL DEFAULT '0',
  `marge_contribution_unitaire` decimal(12,0) NOT NULL DEFAULT '0',
  `nb_eleves_seuil` smallint unsigned NOT NULL DEFAULT '0' COMMENT 'Nombre minimum élèves',
  `revenu_seuil` decimal(14,0) NOT NULL DEFAULT '0' COMMENT 'Revenu minimum FCFA',
  `nb_eleves_actuels` smallint unsigned NOT NULL DEFAULT '0',
  `marge_securite_pourcent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `au_dessus_seuil` tinyint(1) NOT NULL DEFAULT '1',
  `details_calcul` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sr_etab_exo_unique` (`etablissement_id`,`exercice_id`),
  KEY `seuils_rentabilite_exercice_id_foreign` (`exercice_id`),
  CONSTRAINT `seuils_rentabilite_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seuils_rentabilite_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices_comptables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `simulations_financieres` ───────────
DROP TABLE IF EXISTS `simulations_financieres`;
CREATE TABLE `simulations_financieres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `cree_par` bigint unsigned NOT NULL,
  `nom` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Augmentation scolarité +10%, 50 élèves de plus',
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('augmentation_effectif','reduction_effectif','augmentation_tarif','reduction_tarif','ajout_service','suppression_service','recrutement','reduction_personnel','investissement','reduction_couts','scenario_libre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `horizon` enum('3_mois','6_mois','1_an','2_ans','3_ans') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1_an',
  `parametres` json NOT NULL COMMENT 'JSON: {nb_eleves_supplementaires: 50, hausse_scolarite_pourcent: 10}',
  `resultats` json DEFAULT NULL COMMENT 'JSON: revenus, depenses, marge, tresorerie par mois',
  `impact_revenus` decimal(14,0) DEFAULT NULL,
  `impact_depenses` decimal(14,0) DEFAULT NULL,
  `impact_marge` decimal(14,0) DEFAULT NULL,
  `impact_tresorerie` decimal(14,0) DEFAULT NULL,
  `roi_pourcent` decimal(7,2) DEFAULT NULL COMMENT 'Retour sur investissement',
  `delai_rentabilite_mois` smallint unsigned DEFAULT NULL,
  `statut` enum('brouillon','calcule','archive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `favori` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `simulations_financieres_cree_par_foreign` (`cree_par`),
  KEY `sim_etab_type_idx` (`etablissement_id`,`type`),
  CONSTRAINT `simulations_financieres_cree_par_foreign` FOREIGN KEY (`cree_par`) REFERENCES `users` (`id`),
  CONSTRAINT `simulations_financieres_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `sms_credits` ───────────
DROP TABLE IF EXISTS `sms_credits`;
CREATE TABLE `sms_credits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `solde` int unsigned NOT NULL DEFAULT '0' COMMENT 'Nombre de SMS disponibles',
  `cumul_recharge` int unsigned NOT NULL DEFAULT '0' COMMENT 'Cumul SMS rechargés',
  `cumul_envoye` int unsigned NOT NULL DEFAULT '0' COMMENT 'Cumul SMS envoyés',
  `cumul_paye_fcfa` decimal(14,0) NOT NULL DEFAULT '0' COMMENT 'Cumul versé à Avia',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_credits_etablissement_id_unique` (`etablissement_id`),
  CONSTRAINT `sms_credits_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sms_credits` VALUES
(1, 1, 0, 0, 0, '0', '2026-05-23 12:33:43', '2026-05-23 12:33:43');

-- ─────────── Table : `sms_envois` ───────────
DROP TABLE IF EXISTS `sms_envois`;
CREATE TABLE `sms_envois` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `envoye_par` bigint unsigned DEFAULT NULL,
  `destinataire` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Numéro normalisé E.164',
  `destinataire_nom` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('relance_impaye','annonce','note','absence','manuel','autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manuel',
  `statut` enum('en_attente','envoye','echec','recu','expire') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `infobip_message_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `infobip_response` text COLLATE utf8mb4_unicode_ci,
  `erreur` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nb_parties` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1 SMS = 160 car, sinon multi-parties',
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'paiement, inscription, etc',
  `reference_id` bigint unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_envois_envoye_par_foreign` (`envoye_par`),
  KEY `sms_envois_etablissement_id_created_at_index` (`etablissement_id`,`created_at`),
  KEY `sms_envois_etablissement_id_type_statut_index` (`etablissement_id`,`type`,`statut`),
  CONSTRAINT `sms_envois_envoye_par_foreign` FOREIGN KEY (`envoye_par`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_envois_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `sms_log` ───────────
DROP TABLE IF EXISTS `sms_log`;
CREATE TABLE `sms_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `telephone_destinataire` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('relance','pointage','note','annonce','pin_journalier','alerte','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('en_attente','envoye','delivre','echoue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: Twilio, Orange SMS API',
  `provider_message_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cout_fcfa` decimal(8,0) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_log_etablissement_id_created_at_index` (`etablissement_id`,`created_at`),
  CONSTRAINT `sms_log_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `sms_recharges` ───────────
DROP TABLE IF EXISTS `sms_recharges`;
CREATE TABLE `sms_recharges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `demandeur_id` bigint unsigned NOT NULL,
  `reference` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nb_sms` int unsigned NOT NULL,
  `montant_fcfa` decimal(14,0) NOT NULL,
  `prix_unitaire_fcfa` decimal(6,0) NOT NULL DEFAULT '50',
  `wave_checkout_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('en_attente_paiement','paye','credite','annule','expire') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente_paiement',
  `paye_at` timestamp NULL DEFAULT NULL,
  `credite_at` timestamp NULL DEFAULT NULL,
  `credite_par` bigint unsigned DEFAULT NULL,
  `notes_admin` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_recharges_reference_unique` (`reference`),
  KEY `sms_recharges_demandeur_id_foreign` (`demandeur_id`),
  KEY `sms_recharges_credite_par_foreign` (`credite_par`),
  KEY `sms_recharges_etablissement_id_statut_index` (`etablissement_id`,`statut`),
  CONSTRAINT `sms_recharges_credite_par_foreign` FOREIGN KEY (`credite_par`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_recharges_demandeur_id_foreign` FOREIGN KEY (`demandeur_id`) REFERENCES `users` (`id`),
  CONSTRAINT `sms_recharges_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `snapshots_financiers` ───────────
DROP TABLE IF EXISTS `snapshots_financiers`;
CREATE TABLE `snapshots_financiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `date_snapshot` date NOT NULL,
  `solde_caisse` decimal(14,0) NOT NULL DEFAULT '0',
  `solde_banque` decimal(14,0) NOT NULL DEFAULT '0',
  `solde_mobile_money` decimal(14,0) NOT NULL DEFAULT '0',
  `tresorerie_totale` decimal(14,0) NOT NULL DEFAULT '0',
  `revenus_jour` decimal(14,0) NOT NULL DEFAULT '0',
  `depenses_jour` decimal(14,0) NOT NULL DEFAULT '0',
  `revenus_mois_cumul` decimal(14,0) NOT NULL DEFAULT '0',
  `depenses_mois_cumul` decimal(14,0) NOT NULL DEFAULT '0',
  `revenus_exercice_cumul` decimal(14,0) NOT NULL DEFAULT '0',
  `depenses_exercice_cumul` decimal(14,0) NOT NULL DEFAULT '0',
  `resultat_exercice` decimal(14,0) NOT NULL DEFAULT '0',
  `creances_totales` decimal(14,0) NOT NULL DEFAULT '0',
  `nb_impayes` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `snap_etab_date_unique` (`etablissement_id`,`date_snapshot`),
  CONSTRAINT `snapshots_financiers_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `stats_ponctualite_mensuelles` ───────────
DROP TABLE IF EXISTS `stats_ponctualite_mensuelles`;
CREATE TABLE `stats_ponctualite_mensuelles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enseignant_id` bigint unsigned NOT NULL,
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: 2026-04',
  `jours_travailles` smallint unsigned NOT NULL DEFAULT '0',
  `presents` smallint unsigned NOT NULL DEFAULT '0',
  `retards` smallint unsigned NOT NULL DEFAULT '0',
  `absents` smallint unsigned NOT NULL DEFAULT '0',
  `absents_justifies` smallint unsigned NOT NULL DEFAULT '0',
  `score_ponctualite` decimal(5,2) NOT NULL DEFAULT '100.00',
  `heure_arrivee_moyenne` time DEFAULT NULL,
  `alertes_fraude` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stats_ponctualite_mensuelles_enseignant_id_mois_unique` (`enseignant_id`,`mois`),
  CONSTRAINT `stats_ponctualite_mensuelles_enseignant_id_foreign` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `transferts` ───────────
DROP TABLE IF EXISTS `transferts`;
CREATE TABLE `transferts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eleve_id` bigint unsigned NOT NULL,
  `etablissement_origine_id` bigint unsigned NOT NULL,
  `etablissement_destination_id` bigint unsigned DEFAULT NULL,
  `etablissement_destination_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Si hors réseau AviaSchoolPay',
  `etablissement_destination_code_desps` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `type` enum('transfert_sortant','transfert_entrant') COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('demande','quitus_emis','accepte','refuse','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'demande',
  `date_demande` date NOT NULL,
  `date_effectif` date DEFAULT NULL,
  `motif` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fiche_transfert_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fiche officielle DESPS PDF',
  `quitus_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_decision_sigfne` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transferts_etablissement_origine_id_foreign` (`etablissement_origine_id`),
  KEY `transferts_etablissement_destination_id_foreign` (`etablissement_destination_id`),
  KEY `transferts_annee_scolaire_id_foreign` (`annee_scolaire_id`),
  KEY `transferts_eleve_id_annee_scolaire_id_index` (`eleve_id`,`annee_scolaire_id`),
  CONSTRAINT `transferts_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`),
  CONSTRAINT `transferts_eleve_id_foreign` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transferts_etablissement_destination_id_foreign` FOREIGN KEY (`etablissement_destination_id`) REFERENCES `etablissements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transferts_etablissement_origine_id_foreign` FOREIGN KEY (`etablissement_origine_id`) REFERENCES `etablissements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `trimestres` ───────────
DROP TABLE IF EXISTS `trimestres`;
CREATE TABLE `trimestres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `annee_scolaire_id` bigint unsigned NOT NULL,
  `numero` tinyint unsigned NOT NULL COMMENT '1, 2 ou 3',
  `coefficient` decimal(4,1) NOT NULL DEFAULT '1.0',
  `libelle` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Trimestre 1',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `date_cloture_notes` date DEFAULT NULL COMMENT 'Date limite saisie des notes',
  `date_remontee_desps` date DEFAULT NULL COMMENT 'Date limite remontée SIGFNE',
  `en_cours` tinyint(1) NOT NULL DEFAULT '0',
  `notes_cloturees` tinyint(1) NOT NULL DEFAULT '0',
  `moyennes_remontees` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trimestres_annee_scolaire_id_numero_unique` (`annee_scolaire_id`,`numero`),
  CONSTRAINT `trimestres_annee_scolaire_id_foreign` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `trimestres` VALUES
(4, 2, 1, '1.0', 'Trimestre 1', '2026-09-01', '2026-12-20', NULL, NULL, 1, 0, 0, '2026-05-23 12:03:56', '2026-05-23 12:03:56'),
(5, 2, 2, '1.0', 'Trimestre 2', '2026-12-21', '2027-04-10', NULL, NULL, 0, 0, 0, '2026-05-23 12:03:56', '2026-05-23 12:03:56'),
(6, 2, 3, '1.0', 'Trimestre 3', '2027-04-11', '2027-07-31', NULL, NULL, 0, 0, 0, '2026-05-23 12:03:56', '2026-05-23 12:03:56'),
(7, 3, 1, '1.0', 'Trimestre 1', '2026-09-01', '2026-12-09', NULL, NULL, 1, 0, 0, '2026-05-23 12:07:29', '2026-05-23 12:07:29'),
(8, 3, 2, '1.0', 'Trimestre 2', '2026-12-10', '2027-03-19', NULL, NULL, 0, 0, 0, '2026-05-23 12:07:29', '2026-05-23 12:07:29'),
(9, 3, 3, '1.0', 'Trimestre 3', '2027-03-20', '2027-06-30', NULL, NULL, 0, 0, 0, '2026-05-23 12:07:29', '2026-05-23 12:07:29');

-- ─────────── Table : `types_evaluation` ───────────
DROP TABLE IF EXISTS `types_evaluation`;
CREATE TABLE `types_evaluation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Devoir, Interrogation, Composition, Examen blanc',
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `poids_pourcentage` decimal(5,2) NOT NULL DEFAULT '100.00' COMMENT 'Poids dans la moyenne du trimestre',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `types_evaluation_etablissement_id_foreign` (`etablissement_id`),
  CONSTRAINT `types_evaluation_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────── Table : `users` ───────────
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `active_etablissement_id` bigint unsigned DEFAULT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','directeur','sous_directeur','surveillant','comptable','secretaire','enseignant','parent','eleve','drena','ddena') COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sexe` enum('M','F') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `premiere_connexion` tinyint(1) NOT NULL DEFAULT '1',
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `langue` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fr',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_telephone_unique` (`telephone`),
  KEY `users_etablissement_id_role_index` (`etablissement_id`,`role`),
  KEY `users_telephone_index` (`telephone`),
  KEY `users_active_etablissement_id_foreign` (`active_etablissement_id`),
  CONSTRAINT `users_active_etablissement_id_foreign` FOREIGN KEY (`active_etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES
(1, 1, NULL, 'ADMIN', 'Super', 'superadmin@avia.ci', '0700000000', NULL, '$2y$12$vvsv.225K/j8mJN3GKu2YuOERi70/1i5oG7RfuNdBYxxfTBwSCNiO', 'super_admin', NULL, NULL, 1, 0, NULL, 'fr', NULL, '2026-05-23 10:46:06', '2026-05-23 10:46:06', NULL),
(3, 1, NULL, 'DECOCI', 'Super Admin', 'decoci20@gmail.com', '0700000001', NULL, '$2y$12$3ni2WNB9f4uiIV9Fan2H..WGttZDGeGAY./jZtB56ucXyfa2DUaRq', 'super_admin', NULL, NULL, 1, 0, '2026-05-23 10:56:38', 'fr', 'WZ9g6KdLBPKSq8370UpqjtC5EDrW3dP5d48gWrY8yHWry9Ep69dbEELjPZtp', '2026-05-23 10:55:15', '2026-05-23 10:56:38', NULL),
(5, 1, NULL, 'CONSTANT', 'GBAZARE', 'decoci@gmail.com', '0153463635', NULL, '$2y$12$SNTdad02nUu5JzTjwz.Si.0pDNu3FVStUlmAv3I8S92PVnVKsUHRC', 'directeur', NULL, NULL, 1, 0, '2026-05-23 11:25:08', 'fr', NULL, '2026-05-23 11:23:08', '2026-05-23 11:25:08', NULL),
(6, 3, NULL, 'TRAORE', 'KASSIM', 'trabikassim@gmail.com', '+225 0000000002', NULL, '$2y$12$bU7hZSocvyukrvBEDlu7runOG.wJC3x2RoUzDTXu2mIu9WR1sIF3C', 'directeur', NULL, NULL, 1, 0, '2026-05-23 12:11:23', 'fr', NULL, '2026-05-23 12:07:30', '2026-05-23 12:11:24', NULL);

-- ─────────── Table : `virements_internes` ───────────
DROP TABLE IF EXISTS `virements_internes`;
CREATE TABLE `virements_internes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etablissement_id` bigint unsigned NOT NULL,
  `compte_source_id` bigint unsigned NOT NULL,
  `compte_destination_id` bigint unsigned NOT NULL,
  `montant` decimal(14,0) NOT NULL,
  `date_virement` date NOT NULL,
  `motif` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effectue_par` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `virements_internes_etablissement_id_foreign` (`etablissement_id`),
  KEY `virements_internes_compte_source_id_foreign` (`compte_source_id`),
  KEY `virements_internes_compte_destination_id_foreign` (`compte_destination_id`),
  KEY `virements_internes_effectue_par_foreign` (`effectue_par`),
  CONSTRAINT `virements_internes_compte_destination_id_foreign` FOREIGN KEY (`compte_destination_id`) REFERENCES `comptes_tresorerie` (`id`),
  CONSTRAINT `virements_internes_compte_source_id_foreign` FOREIGN KEY (`compte_source_id`) REFERENCES `comptes_tresorerie` (`id`),
  CONSTRAINT `virements_internes_effectue_par_foreign` FOREIGN KEY (`effectue_par`) REFERENCES `users` (`id`),
  CONSTRAINT `virements_internes_etablissement_id_foreign` FOREIGN KEY (`etablissement_id`) REFERENCES `etablissements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



SET FOREIGN_KEY_CHECKS=1;
