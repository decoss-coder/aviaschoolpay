-- ════════════════════════════════════════════════════════════════
-- AviaSchoolPay — Insertion super_admin + établissement siège
-- Généré le : 2026-05-23 16:11:25
-- IDEMPOTENT : peut être exécuté plusieurs fois sans erreur
-- ════════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS=0;

-- ─── 1) Établissement siège Avia ───
INSERT INTO `etablissements` (`id`, `groupe_scolaire_id`, `nom`, `code_desps`, `sigfne_actif`, `sigfne_login`, `sigfne_token`, `sigfne_plateforme`, `sigfne_derniere_sync`, `sigle`, `type`, `statut_juridique`, `adresse`, `ville`, `commune`, `region`, `drena`, `ddena`, `telephone`, `email`, `site_web`, `logo_path`, `gps_latitude`, `gps_longitude`, `gps_rayon_metres`, `directeur_nom`, `directeur_telephone`, `actif`, `wave_actif`, `paiements_manuels_actifs`, `wave_libelle`, `wave_lien_base`, `wave_configured_at`, `wave_configured_by`, `systeme_evaluation`, `created_at`, `updated_at`) VALUES
  (1, NULL, 'Avia Technologie — Siège', 'AVIA-SIEGE', 0, NULL, NULL, NULL, NULL, 'AVIA', 'mixte', 'prive_laic', 'Siège Avia', 'Abidjan', NULL, NULL, NULL, NULL, '+225 0000000000', 'contact@avia.ci', NULL, NULL, NULL, NULL, 100, 'Deco', '+225 0000000001', 1, 0, 1, NULL, NULL, NULL, NULL, 'trimestre', '2026-05-23 10:46:03', '2026-05-23 11:14:44')
ON DUPLICATE KEY UPDATE
  `groupe_scolaire_id` = VALUES(`groupe_scolaire_id`),
  `nom` = VALUES(`nom`),
  `code_desps` = VALUES(`code_desps`),
  `sigfne_actif` = VALUES(`sigfne_actif`),
  `sigfne_login` = VALUES(`sigfne_login`),
  `sigfne_token` = VALUES(`sigfne_token`),
  `sigfne_plateforme` = VALUES(`sigfne_plateforme`),
  `sigfne_derniere_sync` = VALUES(`sigfne_derniere_sync`),
  `sigle` = VALUES(`sigle`),
  `type` = VALUES(`type`),
  `statut_juridique` = VALUES(`statut_juridique`),
  `adresse` = VALUES(`adresse`),
  `ville` = VALUES(`ville`),
  `commune` = VALUES(`commune`),
  `region` = VALUES(`region`),
  `drena` = VALUES(`drena`),
  `ddena` = VALUES(`ddena`),
  `telephone` = VALUES(`telephone`),
  `email` = VALUES(`email`),
  `site_web` = VALUES(`site_web`),
  `logo_path` = VALUES(`logo_path`),
  `gps_latitude` = VALUES(`gps_latitude`),
  `gps_longitude` = VALUES(`gps_longitude`),
  `gps_rayon_metres` = VALUES(`gps_rayon_metres`),
  `directeur_nom` = VALUES(`directeur_nom`),
  `directeur_telephone` = VALUES(`directeur_telephone`),
  `actif` = VALUES(`actif`),
  `wave_actif` = VALUES(`wave_actif`),
  `paiements_manuels_actifs` = VALUES(`paiements_manuels_actifs`),
  `wave_libelle` = VALUES(`wave_libelle`),
  `wave_lien_base` = VALUES(`wave_lien_base`),
  `wave_configured_at` = VALUES(`wave_configured_at`),
  `wave_configured_by` = VALUES(`wave_configured_by`),
  `systeme_evaluation` = VALUES(`systeme_evaluation`),
  `created_at` = VALUES(`created_at`),
  `updated_at` = VALUES(`updated_at`);

-- ─── 2) Comptes super_admin ───
INSERT INTO `users` (`id`, `etablissement_id`, `active_etablissement_id`, `nom`, `prenom`, `email`, `telephone`, `email_verified_at`, `password`, `role`, `avatar_path`, `sexe`, `actif`, `premiere_connexion`, `derniere_connexion`, `langue`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
  (1, 1, NULL, 'ADMIN', 'Super', 'superadmin@avia.ci', '0700000000', NULL, '$2y$12$vvsv.225K/j8mJN3GKu2YuOERi70/1i5oG7RfuNdBYxxfTBwSCNiO', 'super_admin', NULL, NULL, 1, 0, NULL, 'fr', NULL, '2026-05-23 10:46:06', '2026-05-23 10:46:06', NULL)
ON DUPLICATE KEY UPDATE
  `etablissement_id` = VALUES(`etablissement_id`),
  `active_etablissement_id` = VALUES(`active_etablissement_id`),
  `nom` = VALUES(`nom`),
  `prenom` = VALUES(`prenom`),
  `email` = VALUES(`email`),
  `telephone` = VALUES(`telephone`),
  `email_verified_at` = VALUES(`email_verified_at`),
  `password` = VALUES(`password`),
  `role` = VALUES(`role`),
  `avatar_path` = VALUES(`avatar_path`),
  `sexe` = VALUES(`sexe`),
  `actif` = VALUES(`actif`),
  `premiere_connexion` = VALUES(`premiere_connexion`),
  `derniere_connexion` = VALUES(`derniere_connexion`),
  `langue` = VALUES(`langue`),
  `remember_token` = VALUES(`remember_token`),
  `created_at` = VALUES(`created_at`),
  `updated_at` = VALUES(`updated_at`),
  `deleted_at` = VALUES(`deleted_at`);

INSERT INTO `users` (`id`, `etablissement_id`, `active_etablissement_id`, `nom`, `prenom`, `email`, `telephone`, `email_verified_at`, `password`, `role`, `avatar_path`, `sexe`, `actif`, `premiere_connexion`, `derniere_connexion`, `langue`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
  (3, 1, NULL, 'DECOCI', 'Super Admin', 'decoci20@gmail.com', '0700000001', NULL, '$2y$12$3ni2WNB9f4uiIV9Fan2H..WGttZDGeGAY./jZtB56ucXyfa2DUaRq', 'super_admin', NULL, NULL, 1, 0, '2026-05-23 10:56:38', 'fr', 'WZ9g6KdLBPKSq8370UpqjtC5EDrW3dP5d48gWrY8yHWry9Ep69dbEELjPZtp', '2026-05-23 10:55:15', '2026-05-23 10:56:38', NULL)
ON DUPLICATE KEY UPDATE
  `etablissement_id` = VALUES(`etablissement_id`),
  `active_etablissement_id` = VALUES(`active_etablissement_id`),
  `nom` = VALUES(`nom`),
  `prenom` = VALUES(`prenom`),
  `email` = VALUES(`email`),
  `telephone` = VALUES(`telephone`),
  `email_verified_at` = VALUES(`email_verified_at`),
  `password` = VALUES(`password`),
  `role` = VALUES(`role`),
  `avatar_path` = VALUES(`avatar_path`),
  `sexe` = VALUES(`sexe`),
  `actif` = VALUES(`actif`),
  `premiere_connexion` = VALUES(`premiere_connexion`),
  `derniere_connexion` = VALUES(`derniere_connexion`),
  `langue` = VALUES(`langue`),
  `remember_token` = VALUES(`remember_token`),
  `created_at` = VALUES(`created_at`),
  `updated_at` = VALUES(`updated_at`),
  `deleted_at` = VALUES(`deleted_at`);

-- ─── 3) PlatformSettings (config Wave Avia) ───
INSERT INTO `platform_settings` (`cle`, `valeur`, `description`, `created_at`, `updated_at`) VALUES
  ('wave_libelle_restauration', 'Avia Technologie', 'Libellé paiement restauration', '2026-05-23 10:47:35', '2026-05-23 10:47:35')
ON DUPLICATE KEY UPDATE
  `valeur` = VALUES(`valeur`),
  `description` = VALUES(`description`),
  `updated_at` = VALUES(`updated_at`);

INSERT INTO `platform_settings` (`cle`, `valeur`, `description`, `created_at`, `updated_at`) VALUES
  ('wave_lien_restauration_500', 'https://pay.wave.com/m/M_ci_1Onagr26EsBs/c/ci/', 'Lien Wave restauration archive (500 FCFA)', '2026-05-23 10:47:35', '2026-05-23 10:47:35')
ON DUPLICATE KEY UPDATE
  `valeur` = VALUES(`valeur`),
  `description` = VALUES(`description`),
  `updated_at` = VALUES(`updated_at`);


SET FOREIGN_KEY_CHECKS=1;
