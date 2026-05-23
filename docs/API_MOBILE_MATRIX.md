# Matrice portails web → API JSON `/api/v1`

Référence pour les équipes mobile : écran Blade / contrôleur web → données principales → endpoint REST cible.

## Enseignant (`mon-espace`)

| Écran / route web | Contrôleur | Données (Eloquent / logique) | Endpoint API v1 |
|-------------------|-------------|------------------------------|-------------------|
| Dashboard | `EnseignantPortalController@dashboard` | `Enseignant`, `Affectation`, `Evaluation`, `Devoir`, `MoyenneMatiere`, `Trimestre` | `GET /api/v1/teacher/dashboard` |
| Mes classes | `EnseignantPortalController@classes` | `Affectation` + `Classe`, `Matiere` | `GET /api/v1/teacher/classes` |
| Élèves d’une classe | `EnseignantPortalController@eleves` | `Eleve` par `classe_id` | `GET /api/v1/teacher/classes/{id}/students` |
| Évaluations | `EnseignantPortalController@evaluations` | `Evaluation` | `GET /api/v1/teacher/classes/{id}/evaluations` |
| Notes (saisie) | `EnseignantPortalController@storeNotes` | `Note` | `POST /api/v1/teacher/notes/evaluations/{evaluation}/saisir` (alias `NoteController`) |
| Devoirs | `EnseignantPortalController@devoirs`, `storeDevoir` | `Devoir` | `GET/POST /api/v1/teacher/devoirs` |
| Pointage QR | `MonPointageController` | `Pointage`, `QrCode` | `POST /api/v1/teacher/pointage/scan-qr` |
| Cahier d’appel jour | `CahierAppelController@appelJour` | `PresenceEleve`, `EmploiDuTemps`, `Creneau` | `GET /api/v1/teacher/classes/{id}/presences` |
| Marquer présence | `CahierAppelController@appelJourMark` | `PresenceEleve::updateOrCreate` | `POST /api/v1/teacher/classes/{id}/presences/mark` |
| Emploi du temps (prof) | `EmploiDuTempsWebController@professeur` | `EmploiDuTemps` | `GET /api/v1/teacher/emploi-du-temps` |
| Multi-école | `EcoleSwitcherController`, session | `active_etablissement_id` | `PUT /api/v1/context/etablissement` |

## Élève (`mon-espace-eleve`)

| Écran | Contrôleur | Données | Endpoint API v1 |
|-------|------------|---------|-----------------|
| Dashboard | `ElevePortalController@dashboard` | `Note`, `Devoir`, `MoyenneMatiere`, `PresenceEleve` | `GET /api/v1/student/dashboard` |
| Notes | `ElevePortalController@notes` | `Note`, `MoyenneMatiere`, `Trimestre` | `GET /api/v1/student/notes` |
| Devoirs | `ElevePortalController@devoirs` | `Devoir` | `GET /api/v1/student/devoirs` |
| Évaluations | `ElevePortalController@evaluations` | `Evaluation` | `GET /api/v1/student/evaluations` |
| Téléchargements | `downloadDevoirSujet`, etc. | `Storage::disk('public')` | `GET /api/v1/student/files/...` |

## Parent (`mon-espace-parent`)

| Écran | Contrôleur | Données | Endpoint API v1 |
|-------|------------|---------|-----------------|
| Dashboard | `ParentPortalController@dashboard` | `ParentTuteur::eleves`, stats par enfant | `GET /api/v1/parent/dashboard` |
| Notes enfant | `ParentPortalController@notes` | `Note`, `MoyenneMatiere` | `GET /api/v1/parent/children/{eleve}/notes` |
| Paiements | `ParentPortalController@paiements` | `Inscription`, `Paiement`, `Echeance` | `GET /api/v1/parent/children/{eleve}/paiements` |
| Présences | `ParentPortalController@presences` | `PresenceEleve` | `GET /api/v1/parent/children/{eleve}/presences` |
| Paiement Mobile | (nouveau, scoping parent) | `Inscription`, `PayDunyaService` | `POST /api/v1/parent/payments/mobile` |

## Commun

| Fonction | Web | API v1 |
|----------|-----|--------|
| Connexion | `LoginController@login` | `POST /api/v1/auth/login` |
| Déconnexion | `logout` | `POST /api/v1/auth/logout` |
| Profil | — | `GET/PUT /api/v1/me` |
