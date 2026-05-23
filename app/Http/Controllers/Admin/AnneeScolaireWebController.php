<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\AnneeScolaireRestaurationDemande;
use App\Services\Scolarite\AnneeScolaireArchiveService;
use App\Services\Scolarite\AnneeScolaireContext;
use App\Services\Scolarite\AnneeScolaireService;
use App\Services\Scolarite\AnneeScolaireDonneesService;
use App\Services\Scolarite\AnneeScolaireTransitionService;
use Illuminate\Http\Request;

class AnneeScolaireWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annees = AnneeScolaire::query()
            ->where('etablissement_id', $etab->id)
            ->orderByDesc('date_debut')
            ->get();

        // Années archivées disponibles à la restauration (panneau dédié + modale)
        $anneesArchivees = $annees->where('archivee', true)->values();

        $courante = AnneeScolaireContext::courante()
            ?? AnneeScolaireService::courantePourEtablissement($etab->id);

        $demandes = AnneeScolaireRestaurationDemande::query()
            ->where('etablissement_id', $etab->id)
            ->with(['anneeScolaire', 'demandeur'])
            ->latest()
            ->limit(10)
            ->get();

        $lienWaveRestauration = AnneeScolaireArchiveService::lienWaveRestauration();

        $edtEnBase = \App\Models\EmploiDuTemps::query()
            ->where('etablissement_id', $etab->id)
            ->selectRaw('annee_scolaire_id, COUNT(*) as total')
            ->groupBy('annee_scolaire_id')
            ->pluck('total', 'annee_scolaire_id');

        $edtDansArchive = [];
        foreach ($annees as $anneeRow) {
            if ($anneeRow->archive_path && $anneeRow->restoration_key_vault) {
                $edtDansArchive[$anneeRow->id] = AnneeScolaireArchiveService::compterEmploiDansArchive($anneeRow);
            }
        }

        return view('admin.annees-scolaires.index', compact(
            'etab',
            'annees',
            'anneesArchivees',
            'courante',
            'demandes',
            'lienWaveRestauration',
            'edtEnBase',
            'edtDansArchive'
        ))->with([
            'peutVoirCle' => $request->user()->peutVoirCleArchive(),
        ]);
    }

    public function store(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $data = $request->validate([
            'libelle'    => ['required', 'string', 'max:20'],
            'date_debut' => ['required', 'date'],
            'date_fin'   => ['required', 'date', 'after:date_debut'],
            'activer'    => ['nullable', 'boolean'],
        ], [
            'libelle.required'    => 'Le libellé est obligatoire (ex: 2026-2027).',
            'libelle.max'         => 'Le libellé ne doit pas dépasser 20 caractères.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date'     => 'La date de début est invalide.',
            'date_fin.required'   => 'La date de fin est obligatoire.',
            'date_fin.date'       => 'La date de fin est invalide.',
            'date_fin.after'      => 'La date de fin doit être strictement postérieure à la date de début.',
        ]);

        AnneeScolaireService::creer(
            $etab->id,
            $data['libelle'],
            $data['date_debut'],
            $data['date_fin'],
            $request->boolean('activer', true)
        );

        return back()->with('success', 'Année scolaire créée.');
    }

    public function activer(Request $request, AnneeScolaire $annee)
    {
        abort_unless($annee->etablissement_id === $request->user()->etablissement_id, 403);

        $annee = AnneeScolaireService::activer($annee);

        $message = "« {$annee->libelle} » est maintenant l'année en cours. Toute l'application affiche cette année.";
        if ($annee->estArchiveConsultation()) {
            $message .= ' Mode consultation uniquement : les données restaurées sont visibles sans modification.';
        }

        return back()->with('success', $message);
    }

    public function resynchroniser(Request $request, AnneeScolaire $annee)
    {
        abort_unless($annee->etablissement_id === $request->user()->etablissement_id, 403);

        $stats = AnneeScolaireDonneesService::synchroniserDepuisInscriptions($annee);

        return back()->with(
            'success',
            "Synchronisation terminée : {$stats['eleves']} élève(s) relié(s) aux classes, {$stats['classes']} classe(s) recalculée(s)."
        );
    }

    public function diagnosticBd(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['super_admin', 'directeur', 'directeur_adjoint', 'gestionnaire'], true), 403);

        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annees = AnneeScolaire::query()
            ->where('etablissement_id', $etab->id)
            ->orderByDesc('date_debut')
            ->get();

        $lignes = $annees->map(function (AnneeScolaire $a) {
            $edtBase = \App\Models\EmploiDuTemps::where('annee_scolaire_id', $a->id)->count();
            $classes = \App\Models\Classe::where('annee_scolaire_id', $a->id)->count();
            $inscriptions = \App\Models\Inscription::where('annee_scolaire_id', $a->id)->count();
            $edtArchive = null;
            $archiveLisible = false;

            if ($a->archive_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($a->archive_path)) {
                if ($a->restoration_key_vault) {
                    try {
                        $edtArchive = AnneeScolaireArchiveService::compterEmploiDansArchive($a);
                        $archiveLisible = true;
                    } catch (\Throwable $e) {
                        $edtArchive = 'erreur: '.$e->getMessage();
                    }
                } else {
                    $edtArchive = 'cle_vault_absente';
                }
            }

            return [
                'id' => $a->id,
                'libelle' => $a->libelle,
                'en_cours' => (bool) $a->en_cours,
                'cloturee' => (bool) $a->cloturee,
                'archivee' => (bool) $a->archivee,
                'restaurer_le' => $a->archive_meta['restaurer_le'] ?? null,
                'counts_archive_meta' => $a->archive_meta['counts'] ?? null,
                'classes' => $classes,
                'inscriptions' => $inscriptions,
                'edt_en_base' => $edtBase,
                'edt_dans_fichier_enc' => $edtArchive,
                'fichier_archive' => $a->archive_path,
            ];
        });

        return response()->json([
            'etablissement_id' => $etab->id,
            'etablissement' => $etab->nom,
            'edt_total_etablissement' => \App\Models\EmploiDuTemps::where('etablissement_id', $etab->id)->count(),
            'annees' => $lignes,
            'conclusion' => $this->conclusionDiagnosticEdt($lignes),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /** @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $lignes */
    private function conclusionDiagnosticEdt($lignes): string
    {
        $totalBase = $lignes->sum('edt_en_base');
        if ($totalBase > 0) {
            return 'Des créneaux EDT existent en base. Utilisez le filtre année sur la page Emploi du temps.';
        }

        $maxArchive = $lignes
            ->filter(fn ($l) => is_int($l['edt_dans_fichier_enc'] ?? null))
            ->max('edt_dans_fichier_enc');

        if ($maxArchive > 0) {
            return "Aucun EDT en base, mais {$maxArchive} créneau(x) dans un fichier .enc : utilisez « Récupérer l'emploi du temps » sur Années scolaires.";
        }

        return 'Aucun EDT en base ni dans les archives lisibles : données perdues à l\'archivage initial — ressaisie ou génération IA nécessaire.';
    }

    public function reimporterEdt(Request $request, AnneeScolaire $annee)
    {
        abort_unless($annee->etablissement_id === $request->user()->etablissement_id, 403);

        $cle = $request->input('cle_restauration');
        if (! $cle && ! $request->user()->peutVoirCleArchive()) {
            $request->validate([
                'cle_restauration' => ['required', 'string', 'min:8'],
            ]);
        }

        $result = AnneeScolaireArchiveService::reimporterEmploiDuTemps($annee, $cle);

        $msg = "Emploi du temps récupéré : {$result['imported']}/{$result['dans_archive']} créneau(x) importé(s) ({$result['classes_mappees']} classes reliées";
        if (! empty($result['classes_recreees'])) {
            $msg .= ", dont {$result['classes_recreees']} recréée(s) depuis l'archive";
        }
        $msg .= ').';

        return back()->with('success', $msg);
    }

    public function update(Request $request, AnneeScolaire $annee)
    {
        abort_unless($annee->etablissement_id === $request->user()->etablissement_id, 403);
        abort_if($annee->archivee, 422, 'Année archivée : modification impossible.');

        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:20'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
        ]);

        $annee->update($data);

        return back()->with('success', 'Dates et libellé mis à jour.');
    }

    public function cloturer(Request $request, AnneeScolaire $annee)
    {
        abort_unless($annee->etablissement_id === $request->user()->etablissement_id, 403);

        if ($annee->en_cours) {
            return back()->withErrors([
                'annee' => 'Activez d\'abord une autre année, puis clôturez celle-ci.',
            ]);
        }

        $request->validate([
            'confirm_cloture' => ['required', 'accepted'],
            'purger_donnees' => ['nullable', 'boolean'],
        ]);

        $result = AnneeScolaireTransitionService::cloturerEtBasculer(
            $annee,
            $request->user(),
            $request->boolean('purger_donnees', true)
        );

        $anneeCourante = $result['annee_courante'];
        $libelleCourante = $anneeCourante?->libelle ?? '—';
        $classesProvisionnees = (int) ($result['classes_provisionnees'] ?? 0);

        $success = "« {$annee->libelle} » est archivée. L'application affiche désormais l'année « {$libelleCourante} ».";
        if ($classesProvisionnees > 0) {
            $success .= " {$classesProvisionnees} classe(s) ont été recréées pour la nouvelle année.";
        }
        $success .= ' Réinscrivez les élèves avec leurs matricules existants (import ou saisie).';

        $redirect = back()->with('success', $success)
            ->with('annee_basculee', $libelleCourante)
            ->with('archive_meta', $result['meta']);

        if ($request->user()->peutVoirCleArchive()) {
            $redirect->with('archive_restoration_key', $result['restoration_key'])
                ->with('archive_key_visible', true);
        } else {
            $redirect->with('archive_key_stored', true);
        }

        return $redirect;
    }

    public function demanderRestauration(Request $request, AnneeScolaire $annee)
    {
        abort_unless($annee->etablissement_id === $request->user()->etablissement_id, 403);
        abort_unless($annee->archivee, 422, 'Seules les années archivées peuvent être restaurées.');

        $demande = AnneeScolaireRestaurationDemande::create([
            'etablissement_id' => $annee->etablissement_id,
            'annee_scolaire_id' => $annee->id,
            'demandeur_id' => $request->user()->id,
            'montant_fcfa' => AnneeScolaireArchiveService::FRAIS_RESTAURATION_FCFA,
            'statut' => 'en_attente_paiement',
            'reference' => AnneeScolaireRestaurationDemande::genererReference(),
            'wave_checkout_url' => AnneeScolaireArchiveService::lienWaveRestauration(),
        ]);

        return back()->with([
            'success' => 'Demande de restauration enregistrée. Payez 500 FCFA à Avia Technologie via Wave pour recevoir la clé.',
            'restauration_demande_id' => $demande->id,
            'wave_url' => $demande->wave_checkout_url,
        ]);
    }

    public function consulter(Request $request, AnneeScolaire $annee)
    {
        abort_unless($annee->etablissement_id === $request->user()->etablissement_id, 403);
        abort_unless($annee->archivee, 404, 'Cette année n\'est pas archivée.');

        return response()->json(
            AnneeScolaireArchiveService::metaArchive($annee)
        );
    }

    public function restaurer(Request $request, AnneeScolaire $annee)
    {
        abort_unless($annee->etablissement_id === $request->user()->etablissement_id, 403);
        abort_unless($annee->archivee, 422, 'Seules les années archivées peuvent être restaurées avec une clé.');

        session()->flash('restaurer_annee_id', $annee->id);

        $data = $request->validate([
            'cle_restauration' => ['required', 'string', 'min:8'],
            'confirm_restauration' => ['required', 'accepted'],
        ], [
            'cle_restauration.required' => 'La clé de déchiffrement est obligatoire.',
            'confirm_restauration.accepted' => 'Vous devez confirmer la restauration des données.',
        ]);

        $result = AnneeScolaireArchiveService::restaurer(
            $annee,
            $data['cle_restauration']
        );

        $counts = $result['counts'];
        $details = collect($counts)
            ->filter(fn ($n) => $n > 0)
            ->map(fn ($n, $type) => "{$n} {$type}")
            ->implode(', ');

        $message = "« {$result['libelle']} » a été déchiffrée et restaurée.";
        if ($details !== '') {
            $message .= " Données réimportées : {$details}.";
        }
        $message .= ' L\'année est disponible : cliquez sur « Activer cette année » pour l\'afficher dans tout le système.';

        return back()
            ->with('success', $message)
            ->with('annee_restauree', $result['libelle']);
    }

    public function restaurerFichier(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $data = $request->validate([
            'annee_id' => ['nullable', 'integer', 'exists:annees_scolaires,id'],
            'cle_restauration' => ['required', 'string', 'min:8'],
            'fichier_archive' => ['required', 'file', 'max:51200'],
            'confirm_restauration' => ['required', 'accepted'],
        ]);

        $annee = null;
        if (! empty($data['annee_id'])) {
            $annee = AnneeScolaire::query()
                ->where('etablissement_id', $etab->id)
                ->findOrFail($data['annee_id']);
        }

        $contenu = file_get_contents($data['fichier_archive']->getRealPath());
        if ($contenu === false || $contenu === '') {
            return back()->withErrors([
                'fichier_archive' => 'Impossible de lire le fichier archive.',
            ]);
        }

        $annee = AnneeScolaireArchiveService::restaurerDepuisFichier(
            $contenu,
            $data['cle_restauration'],
            $etab->id,
            $annee
        );

        return back()->with('success', "Archive importée : « {$annee->libelle} » restaurée depuis le fichier .enc.")
            ->with('annee_restauree', $annee->libelle);
    }
}
