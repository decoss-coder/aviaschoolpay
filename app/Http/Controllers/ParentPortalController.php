<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Bulletin;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MoyenneMatiere;
use App\Models\Note;
use App\Models\ParentTuteur;
use App\Models\PresenceEleve;
use App\Models\Trimestre;
use App\Services\Eleve\EleveScolariteService;
use App\Services\Finance\ParentScopeService;
use App\Services\Finance\WavePaymentLinkService;
use Illuminate\Http\Request;

class ParentPortalController extends Controller
{
    private function profilPrincipal(Request $request): ParentTuteur
    {
        $parent = ParentScopeService::profilPrincipal($request->user());
        abort_if(! $parent, 403, 'Compte parent introuvable.');

        return $parent;
    }

    private function enfant(Request $request, Eleve $eleve): void
    {
        abort_unless(ParentScopeService::userPeutVoirEleve($request->user(), $eleve), 403);
    }

    public function dashboard(Request $request)
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

            $nbAbsences = PresenceEleve::where('eleve_id', $eleve->id)
                ->where('statut', 'absent')->count();

            $finances = EleveScolariteService::resumePourEleve($eleve, $annee?->id);
            $reste = (int) ($finances['resume']['reste_a_payer'] ?? 0);

            return [$eleve->id => [
                'trimestre' => $trimestre,
                'moyenneTotale' => $moyenneTotale,
                'nbAbsences' => $nbAbsences,
                'resteAPayer' => $reste,
                'statut_eleve' => $eleve->statut_eleve,
                'finances' => $finances,
            ]];
        });

        return view('mon-espace-parent.dashboard', compact('parent', 'enfants', 'statsParEnfant', 'profils'));
    }

    public function notes(Request $request, Eleve $eleve)
    {
        $this->enfant($request, $eleve);

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $trimId = $request->input(
            'trimestre_id',
            $trimestres->first(fn ($t) => $t->en_cours)?->id ?? $trimestres->first()?->id
        );

        $notes = Note::where('eleve_id', $eleve->id)
            ->whereHas('evaluation', fn ($q) =>
                $q->where('trimestre_id', $trimId)->where('notes_publiees', true)
            )
            ->with(['evaluation.matiere', 'evaluation.typeEvaluation'])
            ->get();

        $moyennes = MoyenneMatiere::where('eleve_id', $eleve->id)
            ->where('trimestre_id', $trimId)
            ->matierePrincipaleOnly()
            ->with('matiere')
            ->get()
            ->keyBy('matiere_id');

        return view('mon-espace-parent.notes', compact('eleve', 'trimestres', 'trimId', 'notes', 'moyennes'));
    }

    public function paiements(Request $request, Eleve $eleve)
    {
        $this->enfant($request, $eleve);

        $finances = EleveScolariteService::resumePourEleve($eleve);
        $etab = $eleve->etablissement;
        $waveActif = $etab && WavePaymentLinkService::etablissementPeutEncaisser($etab);
        $resteWave = (int) ($finances['resume']['reste_a_payer'] ?? 0);

        return view('mon-espace-parent.paiements', compact('eleve', 'finances', 'waveActif', 'resteWave'));
    }

    public function genererLienWave(Request $request, Eleve $eleve)
    {
        $this->enfant($request, $eleve);

        $etab = $eleve->etablissement;
        abort_unless($etab && WavePaymentLinkService::etablissementPeutEncaisser($etab), 422, 'Paiement Wave non disponible pour cette école.');

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);

        $inscription = Inscription::query()
            ->where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $annee?->id)
            ->where('statut', 'validee')
            ->latest('date_inscription')
            ->first();

        if (! $inscription) {
            return back()->withErrors(['montant' => 'Aucune inscription validée.']);
        }

        $montant = (int) $request->validate([
            'montant' => ['required', 'integer', 'min:100'],
        ])['montant'];

        $result = WavePaymentLinkService::preparerPaiement($etab, $eleve, $inscription, $montant);

        return back()
            ->with('wave_url', $result['url'])
            ->with('wave_message', $result['message_partage']);
    }

    public function presences(Request $request, Eleve $eleve)
    {
        $this->enfant($request, $eleve);

        $absences = PresenceEleve::where('eleve_id', $eleve->id)
            ->orderByDesc('date')
            ->paginate(30);

        $stats = [
            'absences' => PresenceEleve::where('eleve_id', $eleve->id)->where('statut', 'absent')->count(),
            'retards' => PresenceEleve::where('eleve_id', $eleve->id)->where('statut', 'retard')->count(),
            'justifies' => PresenceEleve::where('eleve_id', $eleve->id)->where('justifie', true)->count(),
        ];

        return view('mon-espace-parent.presences', compact('eleve', 'absences', 'stats'));
    }
}
