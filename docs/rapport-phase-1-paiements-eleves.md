# Rapport Phase 1 — Paiements élèves

**Date :** 19 mai 2026

---

## Objectif

Rendre professionnel le flux **Paiements → Nouveau paiement** : classe → liste élèves → élève → grille **inscription / scolarité séparées** → formulaire (manuel + Wave).

---

## Fichiers créés

| Fichier | Description |
|---------|-------------|
| `database/migrations/2026_05_19_100000_enrich_paiements_eleves.php` | Colonnes poste, canal, répartition, annulation |
| `app/Services/Finance/PaiementService.php` | Grille, répartition, résolution établissement/année/inscription |
| `docs/audit-systeme-scolaire.md` | Audit phase 0 |
| `docs/rapport-phase-1-paiements-eleves.md` | Ce rapport |

---

## Fichiers modifiés

| Fichier | Modifications |
|---------|-----------------|
| `app/Http/Controllers/PaiementWebController.php` | Refonte : année courante, poste cible, auto-inscription, filtres |
| `app/Models/Paiement.php` | Fillable, scopes, libellé poste |
| `app/Services/Finance/WavePaymentLinkService.php` | Répartition inscription/scolarité sur paiement Wave |
| `resources/views/paiements/create.blade.php` | Choix poste (auto / inscription / scolarité) |
| `resources/views/paiements/index.blade.php` | Stats séparées, colonnes inscription/scolarité, filtres canal/classe |
| `resources/views/paiements/show.blade.php` | Répartition, annulation avec motif obligatoire |
| `routes/api_v1.php` | Alias `/api/v1/mobile/*` pour l’app |

---

## Migration

Exécuter :

```bash
php artisan migrate
```

Colonnes ajoutées sur `paiements` : `poste_cible`, `canal_paiement`, `montant_inscription`, `montant_scolarite`, `motif_annulation`, `date_validation`, `reference_transaction`.

---

## Routes web (inchangées, enrichies)

- `GET /paiements` — liste + stats inscription/scolarité
- `GET /paiements/create?classe_id=&eleve_id=` — sélection progressive
- `POST /paiements` — enregistrement
- `POST /paiements/{id}/confirmer` — validation Wave
- `POST /paiements/{id}/annuler` — motif obligatoire, pas d’annulation si reçu confirmé

---

## API mobile (alias ajoutés)

| Méthode | URL | Équivalent |
|---------|-----|------------|
| GET | `/api/v1/mobile/annee-scolaire-active` | `context/annee-scolaire` |
| GET | `/api/v1/mobile/eleves/{eleve}/paiements` | `parent/children/{eleve}/paiements` |
| GET | `/api/v1/mobile/eleves/{eleve}/solde` | idem (synthèse finances) |
| POST | `/api/v1/mobile/eleves/{eleve}/paiements/wave/initier` | `parent/.../paiements/wave` |
| GET | `/api/v1/mobile/recus/{paiement}` | `parent/paiements/{paiement}/recu` |

Règles respectées : `annee.courante`, `etab.access`, scope parent.

---

## Tests réalisés (à valider manuellement)

1. Direction : **Paiements → Nouveau paiement** — choisir classe, puis élève.
2. Vérifier grille : lignes **Inscription** et **Scolarité** + total.
3. Paiement espèces sur poste « Inscription » uniquement.
4. Paiement Wave → statut `en_attente` → **Confirmer** → reçu PDF.
5. Annuler un paiement en attente avec motif.
6. Tenter d’annuler un paiement confirmé avec reçu → refus.
7. Export CSV : colonnes inscription/scolarité.
8. API : `GET /api/v1/mobile/annee-scolaire-active` (token parent).

---

## Limites restantes

- Pas de webhook Wave (confirmation manuelle).
- Pas de table `journaux_actions`.
- Pas de module super admin « tous les paiements » filtrable.
- PayDunya legacy non branché sur v1.
- Période / mois de paiement : non modélisé (hors `echeances` existantes).

---

## Prochaine phase recommandée

**Phase 2 — Paramètres direction** : page unique année courante + toggles manuel/Wave + lien Wave éditable + message si année inactive ; puis **Phase 4** supervision paiements super admin.
