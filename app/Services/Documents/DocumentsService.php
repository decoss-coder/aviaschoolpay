<?php

namespace App\Services\Documents;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\FichePaie;
use App\Models\Inscription;
use App\Models\MoyenneGenerale;
use App\Models\MoyenneMatiere;
use App\Models\Niveau;
use App\Models\Paiement;
use App\Models\Trimestre;
use Illuminate\Support\Collection;

class DocumentsService
{
    /**
     * Liste des élèves inscrits pour l'année en cours.
     */
    public function listeEleves(int $etabId, int $anneeId, ?int $classeId = null, ?int $niveauId = null): Collection
    {
        $query = Inscription::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with([
                'eleve:id,nom,prenom,sexe,date_naissance,matricule_interne,matricule_desps,contact_urgence_nom,contact_urgence_tel,redoublant',
                'classe:id,nom,niveau_id',
                'classe.niveau:id,libelle',
            ]);

        if ($classeId)  $query->where('classe_id', $classeId);
        if ($niveauId)  $query->whereHas('classe', fn($q) => $q->where('niveau_id', $niveauId));

        return $query->get()
            ->sortBy([
                fn($a, $b) => strcmp($a->classe?->nom ?? '', $b->classe?->nom ?? ''),
                fn($a, $b) => strcmp($a->eleve?->nom ?? '', $b->eleve?->nom ?? ''),
            ])
            ->values();
    }

    /**
     * Élèves non soldés (reste à payer > 0) pour l'année en cours.
     */
    public function elevesNonSoldes(int $etabId, int $anneeId, ?int $classeId = null): array
    {
        $query = Inscription::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with([
                'eleve:id,nom,prenom,sexe,matricule_interne,contact_urgence_nom,contact_urgence_tel',
                'classe:id,nom',
                'paiements',
            ]);

        if ($classeId) $query->where('classe_id', $classeId);

        $inscriptions = $query->get();

        $nonSoldees = $inscriptions->map(function ($i) {
            $paye = (int) $i->paiements->where('statut', 'confirme')->sum('montant');
            $reste = (int) $i->montant_net - $paye;
            $i->paye_calc = $paye;
            $i->reste_calc = max(0, $reste);
            $i->taux_calc = $i->montant_net > 0 ? round(($paye / $i->montant_net) * 100, 1) : 0;
            return $i;
        })->where('reste_calc', '>', 0)
          ->sortByDesc('reste_calc')
          ->values();

        return [
            'inscriptions'   => $nonSoldees,
            'nb_eleves'      => $nonSoldees->count(),
            'total_du'       => (int) $nonSoldees->sum('montant_net'),
            'total_paye'     => (int) $nonSoldees->sum('paye_calc'),
            'total_reste'    => (int) $nonSoldees->sum('reste_calc'),
        ];
    }

    /**
     * Annuaire parents : liste des contacts pour communication.
     */
    public function annuaireParents(int $etabId, int $anneeId, ?int $classeId = null): Collection
    {
        $query = Inscription::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with([
                'eleve:id,nom,prenom,sexe,contact_urgence_nom,contact_urgence_tel,matricule_interne',
                'classe:id,nom',
            ]);

        if ($classeId) $query->where('classe_id', $classeId);

        return $query->get()
            ->filter(fn($i) => $i->eleve?->contact_urgence_tel)
            ->sortBy([
                fn($a, $b) => strcmp($a->classe?->nom ?? '', $b->classe?->nom ?? ''),
                fn($a, $b) => strcmp($a->eleve?->nom ?? '', $b->eleve?->nom ?? ''),
            ])
            ->values();
    }

    /**
     * Nappe des moyennes pour une classe et un trimestre :
     * matrice élèves × matières + moyenne générale + rang.
     */
    public function nappeMoyennes(int $etabId, int $classeId, int $trimestreId): array
    {
        $classe = Classe::with('niveau:id,libelle')->findOrFail($classeId);
        abort_unless($classe->etablissement_id === $etabId, 403);

        $trimestre = Trimestre::findOrFail($trimestreId);

        // Élèves de la classe
        $inscriptions = Inscription::where('classe_id', $classeId)
            ->where('statut', 'validee')
            ->with('eleve:id,nom,prenom,matricule_interne,sexe')
            ->get();
        $eleveIds = $inscriptions->pluck('eleve_id')->toArray();

        // Moyennes par matière
        $moyMat = MoyenneMatiere::whereIn('eleve_id', $eleveIds)
            ->where('trimestre_id', $trimestreId)
            ->with('matiere:id,nom,code,coefficient_defaut')
            ->get();

        // Matières uniques (colonnes)
        $matieres = $moyMat->pluck('matiere')->unique('id')->sortBy('nom')->values();

        // Moyennes générales
        $moyGen = MoyenneGenerale::whereIn('eleve_id', $eleveIds)
            ->where('trimestre_id', $trimestreId)
            ->get()
            ->keyBy('eleve_id');

        // Construire la matrice
        $lignes = $inscriptions->map(function ($insc) use ($moyMat, $moyGen) {
            $moyennesParMatiere = $moyMat->where('eleve_id', $insc->eleve_id)
                ->keyBy('matiere_id');
            $gen = $moyGen[$insc->eleve_id] ?? null;
            return [
                'eleve'      => $insc->eleve,
                'moyennes'   => $moyennesParMatiere,
                'generale'   => $gen?->moyenne_generale,
                'rang'       => $gen?->rang,
                'mention'    => $gen?->mention,
            ];
        })->sortBy(fn($l) => $l['rang'] ?? 999)->values();

        // Stats classe
        $moyennes = $lignes->pluck('generale')->filter()->values();
        $stats = [
            'effectif'    => $inscriptions->count(),
            'avec_moyenne' => $moyennes->count(),
            'moyenne_classe' => $moyennes->count() > 0 ? round($moyennes->avg(), 2) : null,
            'note_max'    => $moyennes->max(),
            'note_min'    => $moyennes->min(),
            'taux_reussite' => $moyennes->count() > 0
                ? round(($moyennes->filter(fn($m) => $m >= 10)->count() / $moyennes->count()) * 100, 1)
                : 0,
        ];

        return [
            'classe'    => $classe,
            'trimestre' => $trimestre,
            'matieres'  => $matieres,
            'lignes'    => $lignes,
            'stats'     => $stats,
        ];
    }

    /**
     * Synthèse niveau : comparatif des moyennes générales par classe.
     */
    public function syntheseNiveau(int $etabId, int $anneeId, int $niveauId, int $trimestreId): array
    {
        $niveau = Niveau::findOrFail($niveauId);
        $trimestre = Trimestre::findOrFail($trimestreId);

        $classes = Classe::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('niveau_id', $niveauId)
            ->get();

        $synthese = $classes->map(function ($classe) use ($trimestreId) {
            $eleveIds = Inscription::where('classe_id', $classe->id)
                ->where('statut', 'validee')
                ->pluck('eleve_id');

            $moyennes = MoyenneGenerale::whereIn('eleve_id', $eleveIds)
                ->where('trimestre_id', $trimestreId)
                ->pluck('moyenne_generale');

            return [
                'classe'        => $classe->nom,
                'effectif'      => $eleveIds->count(),
                'avec_moyenne'  => $moyennes->count(),
                'moyenne'       => $moyennes->count() > 0 ? round($moyennes->avg(), 2) : null,
                'max'           => $moyennes->max(),
                'min'           => $moyennes->min(),
                'reussite'      => $moyennes->count() > 0
                    ? round(($moyennes->filter(fn($m) => $m >= 10)->count() / $moyennes->count()) * 100, 1)
                    : 0,
            ];
        });

        return compact('niveau', 'trimestre', 'synthese');
    }

    /**
     * Liste enseignants + leurs affectations.
     */
    public function listeEnseignants(int $etabId): Collection
    {
        return Enseignant::where('etablissement_id', $etabId)
            ->where('actif', true)
            ->with(['affectations' => fn($q) => $q->where('active', true)->with(['classe:id,nom', 'matiere:id,nom'])])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    /**
     * Bulletin individuel d'un élève pour un trimestre.
     */
    public function bulletinEleve(int $etabId, int $eleveId, int $trimestreId): array
    {
        $eleve = Eleve::with('classe.niveau')->findOrFail($eleveId);
        abort_unless($eleve->etablissement_id === $etabId, 403);
        $trimestre = Trimestre::findOrFail($trimestreId);

        $moyennes = MoyenneMatiere::where('eleve_id', $eleveId)
            ->where('trimestre_id', $trimestreId)
            ->with(['matiere:id,nom,code,coefficient_defaut'])
            ->get()
            ->sortBy('matiere.nom')
            ->values();

        $generale = MoyenneGenerale::where('eleve_id', $eleveId)
            ->where('trimestre_id', $trimestreId)
            ->first();

        // Effectif de la classe + statistiques classe
        $effectif = Inscription::where('classe_id', $eleve->classe_id)
            ->where('statut', 'validee')->count();

        $allMoyClasse = MoyenneGenerale::whereHas('eleve', fn($q) => $q->where('classe_id', $eleve->classe_id))
            ->where('trimestre_id', $trimestreId)
            ->pluck('moyenne_generale');

        $statsClasse = [
            'effectif'    => $effectif,
            'avec_note'   => $allMoyClasse->count(),
            'moy_classe'  => $allMoyClasse->count() ? round($allMoyClasse->avg(), 2) : null,
            'max'         => $allMoyClasse->max(),
            'min'         => $allMoyClasse->min(),
        ];

        return compact('eleve', 'trimestre', 'moyennes', 'generale', 'statsClasse');
    }

    /**
     * Tableau d'honneur : top N par mérite pour une classe.
     */
    public function tableauHonneur(int $etabId, int $classeId, int $trimestreId, int $topN = 5): array
    {
        $classe = Classe::with('niveau')->findOrFail($classeId);
        abort_unless($classe->etablissement_id === $etabId, 403);
        $trimestre = Trimestre::findOrFail($trimestreId);

        $eleveIds = Inscription::where('classe_id', $classeId)
            ->where('statut', 'validee')
            ->pluck('eleve_id');

        $top = MoyenneGenerale::whereIn('eleve_id', $eleveIds)
            ->where('trimestre_id', $trimestreId)
            ->with('eleve:id,nom,prenom,matricule_interne,sexe,photo_path')
            ->orderByDesc('moyenne_generale')
            ->limit($topN)
            ->get();

        return compact('classe', 'trimestre', 'top');
    }

    /**
     * Élèves en difficulté (moyenne < 10) avec recommandations.
     */
    public function elevesEnDifficulte(int $etabId, int $anneeId, int $trimestreId, ?int $classeId = null, float $seuil = 10): array
    {
        $query = Inscription::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with(['eleve:id,nom,prenom,matricule_interne,contact_urgence_nom,contact_urgence_tel', 'classe:id,nom']);

        if ($classeId) $query->where('classe_id', $classeId);
        $inscriptions = $query->get();

        $eleveIds = $inscriptions->pluck('eleve_id');

        $moyennes = MoyenneGenerale::whereIn('eleve_id', $eleveIds)
            ->where('trimestre_id', $trimestreId)
            ->get()
            ->keyBy('eleve_id');

        $endifficulte = $inscriptions
            ->filter(fn($i) => isset($moyennes[$i->eleve_id]) && $moyennes[$i->eleve_id]->moyenne_generale < $seuil)
            ->map(function ($i) use ($moyennes) {
                $g = $moyennes[$i->eleve_id];
                $i->moyenne = $g->moyenne_generale;
                $i->rang = $g->rang;
                $i->mention = $g->mention;
                return $i;
            })
            ->sortBy('moyenne')
            ->values();

        $trimestre = Trimestre::findOrFail($trimestreId);

        return [
            'inscriptions' => $endifficulte,
            'seuil'        => $seuil,
            'trimestre'    => $trimestre,
        ];
    }

    /**
     * Carnet de présence vide pour une semaine (ou un mois).
     */
    public function carnetPresence(int $etabId, int $anneeId, int $classeId, string $debutSemaine): array
    {
        $classe = Classe::findOrFail($classeId);
        abort_unless($classe->etablissement_id === $etabId, 403);

        $debut = \Carbon\Carbon::parse($debutSemaine)->startOfWeek();
        $jours = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $debut->copy()->addDays($i);
            $jours[] = ['date' => $d, 'libelle' => $d->locale('fr')->isoFormat('dddd D MMM')];
        }

        $inscriptions = Inscription::where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with('eleve:id,nom,prenom,matricule_interne,sexe')
            ->get()
            ->sortBy(fn($i) => $i->eleve?->nom)
            ->values();

        return compact('classe', 'debut', 'jours', 'inscriptions');
    }

    /**
     * Certificat de scolarité pour un élève.
     */
    public function certificatScolarite(int $etabId, int $eleveId, int $anneeId): array
    {
        $eleve = Eleve::with('classe.niveau')->findOrFail($eleveId);
        abort_unless($eleve->etablissement_id === $etabId, 403);

        $inscription = Inscription::where('eleve_id', $eleveId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with(['classe.niveau', 'anneeScolaire'])
            ->firstOrFail();

        $numero = 'CS-'.now()->format('Ymd').'-'.str_pad((string) $eleveId, 5, '0', STR_PAD_LEFT);

        return compact('eleve', 'inscription', 'numero');
    }

    /**
     * Bulletins en lot pour toute une classe (1 bulletin par élève).
     */
    public function bulletinsClasse(int $etabId, int $classeId, int $trimestreId): array
    {
        $classe = Classe::with('niveau')->findOrFail($classeId);
        abort_unless($classe->etablissement_id === $etabId, 403);
        $trimestre = Trimestre::findOrFail($trimestreId);

        $eleveIds = Inscription::where('classe_id', $classeId)
            ->where('statut', 'validee')
            ->orderBy('eleve_id')
            ->pluck('eleve_id');

        $bulletins = $eleveIds->map(fn($id) => $this->bulletinEleve($etabId, $id, $trimestreId))->values();

        return compact('classe', 'trimestre', 'bulletins');
    }

    /**
     * Attestation de paiement (preuve d'encaissement annuel) pour un élève.
     */
    public function attestationPaiement(int $etabId, int $eleveId, int $anneeId): array
    {
        $eleve = Eleve::with('classe.niveau')->findOrFail($eleveId);
        abort_unless($eleve->etablissement_id === $etabId, 403);

        $inscription = Inscription::where('eleve_id', $eleveId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with(['anneeScolaire'])
            ->firstOrFail();

        $paiements = Paiement::where('inscription_id', $inscription->id)
            ->where('statut', 'confirme')
            ->orderBy('date_paiement')
            ->get();

        $totalPaye = (int) $paiements->sum('montant');
        $reste = max(0, (int) $inscription->montant_net - $totalPaye);
        $solde = $reste === 0;

        $numero = 'AP-'.now()->format('Ymd').'-'.str_pad((string) $eleveId, 5, '0', STR_PAD_LEFT);

        return compact('eleve', 'inscription', 'paiements', 'totalPaye', 'reste', 'solde', 'numero');
    }

    /**
     * Récapitulatif annuel pour toute l'école.
     */
    public function recapAnnuelEcole(int $etabId, int $anneeId): array
    {
        $annee = \App\Models\AnneeScolaire::findOrFail($anneeId);

        // Élèves
        $inscriptions = Inscription::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with(['classe.niveau', 'paiements'])
            ->get();

        // Effectifs par niveau et par classe
        $parNiveau = $inscriptions->groupBy(fn($i) => $i->classe?->niveau?->libelle ?? '—')
            ->map(fn($items) => [
                'libelle'  => $items->first()->classe?->niveau?->libelle ?? '—',
                'effectif' => $items->count(),
                'filles'   => $items->filter(fn($i) => $i->eleve?->sexe === 'F')->count(),
                'garcons'  => $items->filter(fn($i) => $i->eleve?->sexe === 'M')->count(),
                'classes'  => $items->groupBy('classe_id')->count(),
            ])->values();

        // Finances
        $totalDu = (int) $inscriptions->sum('montant_net');
        $totalPaye = 0;
        $totalReste = 0;
        $nbSoldes = 0;
        foreach ($inscriptions as $i) {
            $paye = (int) $i->paiements->where('statut', 'confirme')->sum('montant');
            $totalPaye += $paye;
            $reste = max(0, (int) $i->montant_net - $paye);
            $totalReste += $reste;
            if ($reste === 0) $nbSoldes++;
        }
        $tauxRecouvrement = $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 1) : 0;

        // Dépenses année (toutes confondues, sur la période annuelle approximative)
        $debut = $annee->date_debut;
        $fin = $annee->date_fin;
        $totalDepenses = (int) \App\Models\Depense::where('etablissement_id', $etabId)
            ->where('statut', 'approuvee')
            ->whereBetween('date_depense', [$debut, $fin])
            ->sum('montant');

        $resultat = $totalPaye - $totalDepenses;

        // Enseignants
        $nbEnseignants = Enseignant::where('etablissement_id', $etabId)->where('actif', true)->count();
        $masseSalariale = (int) Enseignant::where('etablissement_id', $etabId)
            ->where('actif', true)->sum('salaire_base') * 12;

        // Taux de réussite trimestre 3 (si dispos)
        $tauxReussite = null;
        $trimestre3 = Trimestre::where('annee_scolaire_id', $anneeId)->where('numero', 3)->first();
        if ($trimestre3) {
            $moyennes = MoyenneGenerale::whereIn('eleve_id', $inscriptions->pluck('eleve_id'))
                ->where('trimestre_id', $trimestre3->id)
                ->pluck('moyenne_generale');
            if ($moyennes->count() > 0) {
                $tauxReussite = round(($moyennes->filter(fn($m) => $m >= 10)->count() / $moyennes->count()) * 100, 1);
            }
        }

        return [
            'annee'            => $annee,
            'nb_eleves'        => $inscriptions->count(),
            'nb_filles'        => $inscriptions->filter(fn($i) => $i->eleve?->sexe === 'F')->count(),
            'nb_garcons'       => $inscriptions->filter(fn($i) => $i->eleve?->sexe === 'M')->count(),
            'nb_redoublants'   => $inscriptions->filter(fn($i) => $i->eleve?->redoublant)->count(),
            'par_niveau'       => $parNiveau,
            'total_du'         => $totalDu,
            'total_paye'       => $totalPaye,
            'total_reste'      => $totalReste,
            'nb_soldes'        => $nbSoldes,
            'nb_non_soldes'    => $inscriptions->count() - $nbSoldes,
            'taux_recouvrement' => $tauxRecouvrement,
            'total_depenses'   => $totalDepenses,
            'resultat'         => $resultat,
            'nb_enseignants'   => $nbEnseignants,
            'masse_salariale'  => $masseSalariale,
            'ratio_ms_revenus' => $totalPaye > 0 ? round(($masseSalariale / $totalPaye) * 100, 1) : 0,
            'taux_reussite'    => $tauxReussite,
        ];
    }

    /**
     * Cartes d'élève (1 carte = 1 élève) — pour QR code matricule.
     */
    public function cartesEleves(int $etabId, int $anneeId, ?int $classeId = null): array
    {
        $query = Inscription::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with([
                'eleve:id,nom,prenom,sexe,date_naissance,matricule_interne,matricule_desps,photo_path,contact_urgence_nom,contact_urgence_tel',
                'classe:id,nom,niveau_id',
                'classe.niveau:id,libelle',
                'anneeScolaire:id,libelle',
            ]);

        if ($classeId) $query->where('classe_id', $classeId);

        $inscriptions = $query->get()
            ->sortBy([
                fn($a, $b) => strcmp($a->classe?->nom ?? '', $b->classe?->nom ?? ''),
                fn($a, $b) => strcmp($a->eleve?->nom ?? '', $b->eleve?->nom ?? ''),
            ])->values();

        return ['inscriptions' => $inscriptions];
    }

    /**
     * Calendrier scolaire annuel : événements groupés par mois.
     */
    public function calendrierAnnuel(int $etabId, int $anneeId): array
    {
        $annee = \App\Models\AnneeScolaire::findOrFail($anneeId);

        $evenements = \App\Models\EvenementScolaire::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('publie', true)
            ->orderBy('date_debut')->get();

        $parMois = $evenements->groupBy(fn($e) => $e->date_debut->format('Y-m'));

        $trimestres = \App\Models\Trimestre::where('annee_scolaire_id', $anneeId)
            ->orderBy('numero')->get();

        return compact('annee', 'evenements', 'parMois', 'trimestres');
    }

    /**
     * Convocation conseil de classe — données pour PDF.
     */
    public function convocationConseilClasse(int $etabId, int $conseilId): array
    {
        $conseil = \App\Models\ConseilClasse::with(['classe.niveau', 'trimestre'])
            ->where('etablissement_id', $etabId)
            ->findOrFail($conseilId);

        // Participants suggérés : enseignants de la classe + PP + parents délégués
        $eleves = Inscription::where('classe_id', $conseil->classe_id)
            ->where('statut', 'validee')
            ->with('eleve:id,nom,prenom,matricule_interne')
            ->get();

        return compact('conseil', 'eleves');
    }

    /**
     * Récapitulatif paie du mois pour tous les enseignants.
     */
    public function recapPaieMois(int $etabId, string $mois): array
    {
        $fiches = FichePaie::where('etablissement_id', $etabId)
            ->where('mois', $mois)
            ->with('enseignant:id,nom,prenom,matricule_mena,statut')
            ->orderBy('enseignant_id')
            ->get();

        return [
            'mois'        => $mois,
            'fiches'      => $fiches,
            'totaux' => [
                'nb_fiches'   => $fiches->count(),
                'brut'        => (int) $fiches->sum('salaire_brut'),
                'cotisations' => (int) $fiches->sum('cotisations_sociales'),
                'impots'      => (int) $fiches->sum('impots'),
                'net'         => (int) $fiches->sum('salaire_net'),
                'heures'      => (float) $fiches->sum('heures_travaillees'),
            ],
        ];
    }
}
