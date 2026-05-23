# Audit système scolaire — AviaSchoolPay

**Date :** 19 mai 2026  
**Périmètre :** Phase 0 avant correction paiements / mobile / super admin

---

## 1. Ce qui existe déjà

### Routes web actives (`routes/web.php`)
- **Élèves** : CRUD `eleves.*`, imports
- **Finances** : `finances.*` (tableau de bord, tarifs, Wave, fiche élève)
- **Paiements** : `paiements.*` (liste, création, détail, reçu PDF, confirmer, annuler, export CSV)
- **Années scolaires** : `admin.annees.*` (création, activation, clôture, restauration)
- **Super admin** : `admin.platform.dashboard`, `admin.etablissements.*`, `admin.platform.parametres`, `admin.wave.*`
- **Parent web** : `mon-espace-parent.paiements`

### API mobile (`routes/api_v1.php` → `/api/v1/...`)
- Contexte année : `GET context/annee-scolaire`
- Parent : paiements, historique, initier, Wave, reçu
- Élève : `GET student/paiements`
- Middleware : `auth:sanctum`, `annee.courante`, `etab.access`

### Modèles & services clés
| Composant | Rôle |
|-----------|------|
| `EleveScolariteService` | AFF = inscription seule, NAFF = inscription + scolarité |
| `TarificationService` | Grilles tarifaires, recouvrement, sync inscriptions |
| `WavePaymentLinkService` | Lien Wave dynamique (`?amount=`) |
| `AnneeScolaireContext` | Année courante par requête |
| `Paiement`, `Inscription`, `AnneeScolaire` | Données financières |

### Tables MySQL (migrations exécutées)
- `annees_scolaires`, `inscriptions`, `paiements`, `echeances`
- `etablissements` : `wave_*`, `paiements_manuels_actifs`
- `platform_settings`, `annee_scolaire_restauration_demandes`
- Archives année : colonnes sur `annees_scolaires`

---

## 2. Ce qui est incomplet

| Zone | État |
|------|------|
| Webhook Wave | Absent — confirmation manuelle par la direction |
| PayDunya (`routes/api.php`) | Fichier présent mais **non chargé** dans `bootstrap/app.php` |
| `ParentPaiementApiController` | Orphelin (pas de route v1) |
| Journal d’actions (`journaux_actions`) | Non implémenté |
| `modes_paiement`, `parametres_paiement`, `recus_paiement` | Tables dédiées absentes (logique dans `paiements` + PDF) |
| Paramètres paiement centralisés par école | Partiel (`etablissements` + `finances.wave`) |
| Super admin — supervision paiements globale | Dashboard stats basiques, pas de module paiements filtré |

---

## 3. Ce qui est cassé ou mal relié

- **Clés recouvrement** : `TarificationService` renvoie `total_du` / `taux` ; certaines vues utilisaient `total_attendu` / `taux_recouvrement` → corrigé sur `paiements/index`.
- **Super admin sans établissement** : `PaiementWebController` utilisait `$user->etablissement` → corrigé via `ecoleActiveId()` / impersonation.
- **Inscription manquante** : paiement refusé si pas d’inscription validée → création auto + sync tarifs ajoutée.
- **Réactivation école** : bug `toggleAccess` corrigé (session précédente).

---

## 4. Vues à refaire / complétées (phase 1)

| Vue | Statut |
|-----|--------|
| `paiements/create` | Flux classe → élève → grille inscription/scolarité + formulaire |
| `paiements/index` | Stats séparées inscription/scolarité, colonnes détaillées |
| `paiements/show` | Répartition + annulation avec motif |
| `admin/platform/dashboard` | Existe (session précédente) |
| Menus divers (cockpit, compta…) | Hors périmètre phase 1 |

---

## 5. Risques techniques

1. **Pas de webhook Wave** : risque de fraude / oublis si confirmation manuelle négligée.
2. **Double config Wave** : direction (`finances.wave`) vs super admin (`admin.wave`).
3. **Inscription virtuelle vs BDD** : montants affichés avant sync tarifs — dépend de `finances.synchroniser`.
4. **Annulation paiement confirmé** : bloquée si reçu émis (conforme spec).
5. **Routes legacy** : confusion possible avec `api.php` / `routes_financieres.php`.

---

## 6. Plan de correction par étapes

| Phase | Contenu | Statut |
|-------|---------|--------|
| **0** | Audit (ce document) | Fait |
| **1** | Paiements manuel + Wave, grille AFF/NAFF, mobile alias | **En cours / livré** |
| **2** | Paramètres direction (année, toggles, pénalités) | À faire |
| **3** | Clôture / archive / restauration 500 F (durcir sécurité clés) | Partiel existant |
| **4** | Super admin paiements + journal actions | À faire |
| **5** | Webhook Wave + PayDunya (décision produit) | À faire |

---

## 7. Tables cible vs existant

| Table demandée | Existant |
|----------------|----------|
| `annees_scolaires` | Oui |
| `paiements_eleves` | `paiements` |
| `ecoles` | `etablissements` |
| `eleves`, `classes`, `inscriptions`, `users` | Oui |
| `archives_annees_scolaires` | Colonnes sur `annees_scolaires` + service archive |
| `demandes_restauration_archives` | `annee_scolaire_restauration_demandes` |
| `modes_paiement` | Enum `mode` sur `paiements` |
| `parametres_paiement` | `platform_settings` + colonnes `etablissements` |
| `recus_paiement` | `numero_recu`, PDF à la volée |
| `journaux_actions` | Non |

**Enrichissement phase 1 sur `paiements` :** `poste_cible`, `canal_paiement`, `montant_inscription`, `montant_scolarite`, `motif_annulation`, `date_validation`, `reference_transaction`.
