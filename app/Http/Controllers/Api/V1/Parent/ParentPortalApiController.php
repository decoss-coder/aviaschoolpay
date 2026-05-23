<?php

namespace App\Http\Controllers\Api\V1\Parent;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoyenneMatiere;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\ParentTuteur;
use App\Models\PresenceEleve;
use App\Models\Trimestre;
use App\Services\Eleve\EleveScolariteService;
use App\Services\Finance\PaiementService;
use App\Services\Finance\ParentScopeService;
use App\Services\Finance\WavePaymentLinkService;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParentPortalApiController extends Controller
{
    private function profilPrincipal(Request $request): ParentTuteur
    {
        $p = ParentScopeService::profilPrincipal($request->user());
        abort_if(! $p, 403, 'Compte parent introuvable.');

        return $p;
    }

    private function enfant(Request $request, Eleve $eleve): void
    {
        abort_unless(ParentScopeService::userPeutVoirEleve($request->user(), $eleve), 403);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $parent = $this->profilPrincipal($request);
        $enfants = ParentScopeService::enfantsPourUser($request->user());
        $profils = ParentScopeService::profilsPourUser($request->user());

        $statsParEnfant = $enfants->mapWithKeys(function (Eleve $eleve) {
            $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);

            $trimestre = $annee
                ? Trimestre::where('annee_scolaire_id', $annee->id)->where('en_cours', true)->first()
                  ?? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->first()
                : null;

            $moyenneTotale = $trimestre
                ? MoyenneMatiere::where('eleve_id', $eleve->id)
                    ->where('trimestre_id', $trimestre->id)
                    ->matierePrincipaleOnly()
                    ->whereNotNull('moyenne')
                    ->avg('moyenne')
                : null;

            // Absences uniquement pour l'année courante de l'enfant
            $nbAbsences = $annee
                ? PresenceEleve::where('eleve_id', $eleve->id)
                    ->where('statut', 'absent')
                    ->whereHas('trimestre', fn ($q) => $q->where('annee_scolaire_id', $annee->id))
                    ->count()
                : 0;

            $finances = EleveScolariteService::resumePourEleve($eleve, $annee?->id);

            return [$eleve->id => [
                'trimestre' => $trimestre?->only(['id', 'libelle']),
                'moyenne_totale' => $moyenneTotale !== null ? round((float) $moyenneTotale, 2) : null,
                'nb_absences' => $nbAbsences,
                'reste_a_payer_fcfa' => (int) ($finances['resume']['reste_a_payer'] ?? 0),
                'finances' => $finances,
            ]];
        });

        $enfantsPayload = $enfants->map(fn (Eleve $e) => array_merge(
            $e->only(['id', 'nom', 'prenom', 'sexe', 'statut_eleve', 'etablissement_id', 'classe_id', 'matricule_interne', 'actif']),
            [
                'classe' => $e->classe?->only(['id', 'nom']),
                'etablissement' => $e->etablissement?->only(['id', 'nom', 'sigle']),
            ]
        ));

        $parEcole = $enfants->groupBy('etablissement_id')->map(function ($groupe, $etabId) use ($statsParEnfant) {
            $etab = $groupe->first()?->etablissement;

            return [
                'etablissement_id' => (int) $etabId,
                'etablissement' => $etab?->only(['id', 'nom', 'sigle']),
                'nb_enfants' => $groupe->count(),
                'enfants' => $groupe->map(fn (Eleve $e) => array_merge(
                    $e->only(['id', 'nom', 'prenom', 'statut_eleve']),
                    [
                        'classe' => $e->classe?->only(['id', 'nom']),
                        'stats' => $statsParEnfant[$e->id] ?? null,
                    ]
                ))->values(),
            ];
        })->values();

        return ApiEnvelope::success([
            'parent' => $parent->only(['id', 'nom', 'prenom', 'telephone']),
            'profils' => $profils->map(fn ($p) => $p->only(['id', 'etablissement_id', 'telephone']) + [
                'etablissement' => $p->etablissement?->only(['id', 'nom', 'sigle']),
            ]),
            'enfants' => $enfantsPayload,
            'enfants_par_ecole' => $parEcole,
            'stats_par_enfant' => $statsParEnfant,
            'nb_ecoles' => $parEcole->count(),
            'nb_enfants' => $enfants->count(),
        ], 'Tableau de bord parent.');
    }

    public function children(Request $request): JsonResponse
    {
        $this->profilPrincipal($request);
        $enfants = ParentScopeService::enfantsPourUser($request->user());

        return ApiEnvelope::success(['enfants' => $enfants], 'Enfants liés au compte.');
    }

    public function notes(Request $request, Eleve $eleve): JsonResponse
    {
        $this->enfant($request, $eleve);

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $trimId = (int) $request->input(
            'trimestre_id',
            $trimestres->first(fn ($t) => $t->en_cours)?->id ?? $trimestres->first()?->id
        );

        $notes = Note::where('eleve_id', $eleve->id)
            ->whereHas('evaluation', fn ($q) => $q->where('trimestre_id', $trimId)->where('notes_publiees', true))
            ->with(['evaluation.matiere', 'evaluation.typeEvaluation'])
            ->get();

        $moyennes = MoyenneMatiere::where('eleve_id', $eleve->id)
            ->where('trimestre_id', $trimId)
            ->matierePrincipaleOnly()
            ->with('matiere:id,nom,code,coefficient_defaut')
            ->get();

        return ApiEnvelope::success([
            'eleve' => $eleve->only(['id', 'nom', 'prenom']),
            'trimestres' => $trimestres,
            'trimestre_id' => $trimId,
            'notes' => $notes,
            'moyennes_matieres' => $moyennes,
        ], "Notes de l'enfant.");
    }

    public function paiements(Request $request, Eleve $eleve): JsonResponse
    {
        $this->enfant($request, $eleve);

        $eleve->loadMissing('etablissement:id,nom,wave_actif,wave_lien_base,paiements_manuels_actifs');
        $etab = $eleve->etablissement;

        $finances = EleveScolariteService::resumePourEleve($eleve);
        $grille = PaiementService::grilleDepuisResume($finances);

        return ApiEnvelope::success(array_merge(
            ['eleve' => $eleve->only(['id', 'nom', 'prenom', 'statut_eleve', 'etablissement_id'])],
            $finances,
            [
                'grille' => $grille,
                'options_paiement' => [
                    'wave_actif' => $etab ? WavePaymentLinkService::etablissementPeutEncaisser($etab) : false,
                    'paiement_manuel_ecole' => (bool) ($etab->paiements_manuels_actifs ?? true),
                    'mobile_wave_uniquement' => true,
                ],
            ]
        ), 'Paiements et inscriptions.');
    }

    public function presences(Request $request, Eleve $eleve): JsonResponse
    {
        $this->enfant($request, $eleve);

        // Filtres optionnels : période + statut
        $dateDebut = $request->get('date_debut');
        $dateFin = $request->get('date_fin');
        $statutFilter = $request->get('statut'); // present | absent | retard | exclu

        $query = PresenceEleve::where('eleve_id', $eleve->id);
        if ($dateDebut) {
            $query->whereDate('date', '>=', $dateDebut);
        }
        if ($dateFin) {
            $query->whereDate('date', '<=', $dateFin);
        }
        if ($statutFilter) {
            $query->where('statut', $statutFilter);
        }

        $presences = (clone $query)
            ->with([
                'classe:id,nom',
                'matiere:id,nom,code',
                'enseignant:id,nom,prenom',
                'creneau:id,libelle,heure_debut,heure_fin',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 30));

        // Stats globales (non filtrées) pour l'écran récapitulatif
        $baseQuery = PresenceEleve::where('eleve_id', $eleve->id);
        $totalRecords = (clone $baseQuery)->count();
        $presentsCount = (clone $baseQuery)->where('statut', 'present')->count();
        $absencesCount = (clone $baseQuery)->where('statut', 'absent')->count();
        $retardsCount = (clone $baseQuery)->where('statut', 'retard')->count();
        $exclusCount = (clone $baseQuery)->where('statut', 'exclu')->count();
        $justifiesCount = (clone $baseQuery)->where('justifie', true)->count();

        $tauxPresence = $totalRecords > 0
            ? round(($presentsCount / $totalRecords) * 100, 1)
            : 0;
        $tauxAbsenteisme = $totalRecords > 0
            ? round((($absencesCount + $retardsCount + $exclusCount) / $totalRecords) * 100, 1)
            : 0;

        // 5 derniers événements anormaux (pour widget alerte)
        $dernieresAnomalies = (clone $baseQuery)
            ->whereIn('statut', ['absent', 'retard', 'exclu'])
            ->with(['matiere:id,nom', 'creneau:id,heure_debut,heure_fin'])
            ->orderByDesc('date')
            ->limit(5)
            ->get();

        return ApiEnvelope::success([
            'eleve' => $eleve->only(['id', 'nom', 'prenom', 'matricule_interne']),
            'periode' => [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ],
            'stats' => [
                'total' => $totalRecords,
                'presents' => $presentsCount,
                'absences' => $absencesCount,
                'retards' => $retardsCount,
                'exclus' => $exclusCount,
                'justifies' => $justifiesCount,
                'taux_presence' => $tauxPresence,
                'taux_absenteisme' => $tauxAbsenteisme,
            ],
            'dernieres_anomalies' => $dernieresAnomalies,
            'presences' => $presences->toArray(),
        ], "Présences de l'enfant.");
    }

    public function initierPaiement(Request $request, Eleve $eleve): JsonResponse
    {
        $this->enfant($request, $eleve);

        if (! $eleve->estNonAffecte() && ! $eleve->estAffecte()) {
            return ApiEnvelope::fail('Statut élève (AFF/NAFF) requis pour un paiement.', [], 422);
        }

        $validator = Validator::make($request->all(), [
            'montant' => 'required|integer|min:100',
            'mode' => 'required|in:wave,orange_money,mtn_money,moov_money',
            'poste_cible' => 'nullable|in:inscription,scolarite,auto',
        ]);

        if ($validator->fails()) {
            return ApiEnvelope::fail($validator->errors()->first(), $validator->errors()->toArray(), 422);
        }

        $etab = $eleve->etablissement;
        abort_unless($etab, 422, 'Établissement introuvable.');

        $annee = PaiementService::resolveAnneeCourante($etab);
        if (! $annee) {
            return ApiEnvelope::fail('Aucune année scolaire active.', [], 422);
        }

        $inscription = PaiementService::resolveInscription($etab, $annee, $eleve);
        if (! $inscription) {
            return ApiEnvelope::fail('Aucune inscription trouvée pour cet élève.', [], 422);
        }

        $resume = EleveScolariteService::resumePourEleve($eleve, $annee->id);
        $grille = PaiementService::grilleDepuisResume($resume);
        $posteCible = (string) ($request->poste_cible ?? 'auto');

        $maxMontant = match ($posteCible) {
            'inscription' => (int) $grille['inscription']['reste'],
            'scolarite' => (int) $grille['scolarite']['reste'],
            default => (int) $grille['total']['reste'],
        };

        if ($maxMontant <= 0) {
            return ApiEnvelope::fail('Aucun montant restant sur ce poste.', [], 422);
        }

        $montant = min((int) $request->montant, $maxMontant);
        $repartition = PaiementService::repartirMontant($grille, $montant, $posteCible);
        $mode = (string) $request->mode;

        if ($mode === 'wave') {
            if (! WavePaymentLinkService::etablissementPeutEncaisser($etab)) {
                return ApiEnvelope::fail("Le paiement Wave n'est pas activé pour cet établissement.", [], 422);
            }

            try {
                $result = WavePaymentLinkService::preparerPaiement($etab, $eleve, $inscription, $montant);
                $result['paiement']->update([
                    'poste_cible' => $posteCible,
                    'montant_inscription' => $repartition['montant_inscription'],
                    'montant_scolarite' => $repartition['montant_scolarite'],
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return ApiEnvelope::fail(collect($e->errors())->flatten()->first() ?? 'Erreur Wave.', [], 422);
            }

            return ApiEnvelope::success([
                'paiement' => $result['paiement']->only([
                    'id', 'reference', 'montant', 'montant_inscription', 'montant_scolarite',
                    'poste_cible', 'mode', 'statut', 'date_paiement',
                ]),
                'grille' => $grille,
                'lien_paiement' => $result['url'],
                'lien_wave' => $result['url'],
                'libelle' => $result['libelle'],
                'message_partage' => $result['message_partage'],
                'reste_apres' => $result['reste_apres'],
                'action' => 'open_url',
            ], 'Lien Wave généré — ouvrez-le pour finaliser le paiement.');
        }

        $paiement = Paiement::create([
            'etablissement_id' => $etab->id,
            'inscription_id' => $inscription->id,
            'eleve_id' => $eleve->id,
            'reference' => Paiement::genererReference($etab->id),
            'montant' => $montant,
            'montant_inscription' => $repartition['montant_inscription'],
            'montant_scolarite' => $repartition['montant_scolarite'],
            'poste_cible' => $posteCible,
            'canal_paiement' => 'manuel',
            'date_paiement' => today()->toDateString(),
            'mode' => $mode,
            'statut' => 'en_attente',
            'observations' => 'Initié depuis l\'application mobile parent.',
        ]);

        return ApiEnvelope::success([
            'paiement' => $paiement->only([
                'id', 'reference', 'montant', 'montant_inscription', 'montant_scolarite',
                'poste_cible', 'mode', 'statut', 'date_paiement',
            ]),
            'grille' => $grille,
            'reste_apres' => max(0, $maxMontant - $montant),
            'action' => 'pending_confirmation',
            'instruction' => 'Paiement en attente de validation par l\'établissement.',
        ], 'Paiement initié — en attente de confirmation.');
    }

    /**
     * Liste les paiements d'un enfant (historique) pour l'app mobile.
     */
    public function paiementsList(Request $request, Eleve $eleve): JsonResponse
    {
        $this->enfant($request, $eleve);

        $rows = Paiement::where('eleve_id', $eleve->id)
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 30));

        $items = collect($rows->items())->map(fn (Paiement $p) => array_merge(
            $p->only([
                'id', 'reference', 'montant', 'montant_inscription', 'montant_scolarite',
                'poste_cible', 'canal_paiement', 'mode', 'statut', 'date_paiement', 'numero_recu',
            ]),
            ['libelle_poste' => $p->libellePoste()]
        ));

        return ApiEnvelope::success([
            'eleve' => $eleve->only(['id', 'nom', 'prenom']),
            'paiements' => [
                'data' => $items,
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
            ],
        ], 'Historique paiements.');
    }

    /**
     * Téléchargement du reçu d'un paiement par le parent (PDF).
     */
    public function recuPaiement(Request $request, Paiement $paiement)
    {
        $eleve = $paiement->eleve;
        if (! $eleve) {
            return ApiEnvelope::fail('Élève introuvable.', [], 404);
        }
        $this->enfant($request, $eleve);

        if (! $paiement->estConfirme()) {
            return ApiEnvelope::fail('Reçu disponible uniquement pour les paiements confirmés.', [], 422);
        }

        $paiement->load([
            'eleve:id,nom,prenom,matricule_interne',
            'inscription.classe:id,nom',
            'encaissePar:id,nom,prenom',
            'etablissement:id,nom,sigle,adresse,telephone',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('paiements.recu', compact('paiement'))
            ->setPaper('A5', 'portrait');

        return $pdf->download("recu-{$paiement->numero_recu}.pdf");
    }

    /**
     * Pré-reçu (voucher) — disponible dès que le paiement est créé (statut "en_attente").
     * Le parent l'imprime/télécharge et le présente à la direction pour confirmation.
     */
    public function preRecuPaiement(Request $request, Paiement $paiement)
    {
        $eleve = $paiement->eleve;
        if (! $eleve) {
            return ApiEnvelope::fail('Élève introuvable.', [], 404);
        }
        $this->enfant($request, $eleve);

        // Bloque uniquement si déjà confirmé : dans ce cas, c'est le reçu officiel qu'il faut
        if ($paiement->estConfirme()) {
            return ApiEnvelope::fail(
                'Ce paiement est confirmé : utilisez le reçu officiel.',
                ['use' => 'recu'],
                409
            );
        }

        $paiement->load([
            'eleve:id,nom,prenom,matricule_interne,matricule_desps',
            'inscription.classe:id,nom',
            'etablissement:id,nom,sigle,adresse,telephone',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('paiements.pre-recu', compact('paiement'))
            ->setPaper('A5', 'portrait');

        $filename = 'pre-recu-' . str_replace(['/', ' '], '-', $paiement->reference) . '.pdf';

        return $pdf->download($filename);
    }

    public function genererLienWave(Request $request, Eleve $eleve): JsonResponse
    {
        $this->enfant($request, $eleve);

        $etab = $eleve->etablissement;
        if (! $etab || ! WavePaymentLinkService::etablissementPeutEncaisser($etab)) {
            return ApiEnvelope::fail('Le paiement Wave n\'est pas configuré pour cet établissement.', 422);
        }

        $validator = Validator::make($request->all(), [
            'montant' => 'required|integer|min:100',
        ]);

        if ($validator->fails()) {
            return ApiEnvelope::fail($validator->errors()->first(), 422);
        }

        $annee = PaiementService::resolveAnneeCourante($etab);
        if (! $annee) {
            return ApiEnvelope::fail('Aucune année scolaire active.', [], 422);
        }

        $inscription = PaiementService::resolveInscription($etab, $annee, $eleve);
        if (! $inscription) {
            return ApiEnvelope::fail('Aucune inscription validée pour cet élève.', [], 422);
        }

        try {
            $result = WavePaymentLinkService::preparerPaiement(
                $etab,
                $eleve,
                $inscription,
                (int) $request->montant
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiEnvelope::fail(collect($e->errors())->flatten()->first() ?? 'Montant invalide.', 422);
        }

        return ApiEnvelope::success([
            'lien_wave' => $result['url'],
            'montant' => $result['montant'],
            'reste_apres' => $result['reste_apres'],
            'libelle' => $result['libelle'],
            'message_partage' => $result['message_partage'],
            'paiement' => $result['paiement']->only(['id', 'reference', 'statut', 'mode']),
        ], 'Lien Wave généré.');
    }
}
