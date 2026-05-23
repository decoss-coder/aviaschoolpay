<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Devoir;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\MoyenneMatiere;
use App\Models\Note;
use App\Models\PresenceEleve;
use App\Models\Trimestre;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Portail élève — voit ses notes, devoirs/sujets à télécharger,
 * absences, prochaines évaluations.
 */
class ElevePortalController extends Controller
{
    private function eleve(Request $request): Eleve
    {
        $eleve = $request->user()->eleve;
        abort_if(!$eleve, 403, 'Compte élève introuvable.');
        return $eleve;
    }

    public function dashboard(Request $request)
    {
        $eleve = $this->eleve($request);
        $classe = $eleve->classe;
        abort_if(!$classe, 422, 'Aucune classe assignée.');

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);

        $trimestreActuel = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->where('en_cours', true)->first()
              ?? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->first()
            : null;

        // Dernières notes (5)
        $dernieresNotes = Note::where('eleve_id', $eleve->id)
            ->whereNotNull('note')
            ->with(['evaluation.matiere', 'evaluation.typeEvaluation'])
            ->latest('date_saisie')
            ->take(5)->get();

        // Devoirs récents publiés
        $devoirs = Devoir::where('classe_id', $classe->id)
            ->where('publie', true)
            ->where(function ($q) {
                $q->whereNull('date_limite')->orWhere('date_limite', '>=', today()->subDays(30));
            })
            ->with('matiere', 'enseignant')
            ->orderByDesc('date_publication')
            ->take(8)->get();

        // Moyenne générale courante
        $moyennes = $trimestreActuel
            ? MoyenneMatiere::where('eleve_id', $eleve->id)
                ->where('trimestre_id', $trimestreActuel->id)
                ->with('matiere')->get()
            : collect();

        $moyenneTotale = $moyennes->whereNotNull('moyenne')->avg('moyenne');

        // Stats absences
        $nbAbsences = PresenceEleve::where('eleve_id', $eleve->id)
            ->where('statut', 'absent')->count();

        return view('mon-espace-eleve.dashboard',
            compact('eleve','classe','annee','trimestreActuel','dernieresNotes','devoirs','moyennes','moyenneTotale','nbAbsences'));
    }

    public function notes(Request $request)
    {
        $eleve = $this->eleve($request);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $trimId = $request->input('trimestre_id', $trimestres->first(fn ($t) => $t->en_cours)?->id ?? $trimestres->first()?->id);

        // Notes détaillées
        $notes = Note::where('eleve_id', $eleve->id)
            ->whereHas('evaluation', fn ($q) => $q->where('trimestre_id', $trimId)->where('notes_publiees', true))
            ->with(['evaluation.matiere', 'evaluation.typeEvaluation'])
            ->get();

        // Moyennes par matière
        $moyennes = MoyenneMatiere::where('eleve_id', $eleve->id)
            ->where('trimestre_id', $trimId)
            ->with('matiere')
            ->get()
            ->keyBy('matiere_id');

        return view('mon-espace-eleve.notes',
            compact('eleve','trimestres','trimId','notes','moyennes'));
    }

    public function devoirs(Request $request)
    {
        $eleve = $this->eleve($request);
        $classe = $eleve->classe;
        abort_if(!$classe, 422);

        $devoirs = Devoir::where('classe_id', $classe->id)
            ->where('publie', true)
            ->with('matiere', 'enseignant')
            ->orderByDesc('date_publication')
            ->paginate(20);

        return view('mon-espace-eleve.devoirs', compact('eleve','devoirs'));
    }

    public function evaluations(Request $request)
    {
        $eleve = $this->eleve($request);
        $classe = $eleve->classe;
        abort_if(!$classe, 422);

        $evaluations = Evaluation::where('classe_id', $classe->id)
            ->whereDate('date_evaluation', '>=', today()->subDays(60))
            ->with('matiere', 'enseignant', 'typeEvaluation', 'trimestre')
            ->orderByDesc('date_evaluation')
            ->paginate(20);

        return view('mon-espace-eleve.evaluations', compact('eleve','evaluations'));
    }

    public function downloadDevoirSujet(Request $request, Devoir $devoir)
    {
        $eleve = $this->eleve($request);
        abort_unless($devoir->classe_id === $eleve->classe_id, 403);
        abort_unless($devoir->publie, 403);
        return $this->downloadFile($devoir->fichier_path, $devoir->titre . '-sujet');
    }

    public function downloadDevoirCorrige(Request $request, Devoir $devoir)
    {
        $eleve = $this->eleve($request);
        abort_unless($devoir->classe_id === $eleve->classe_id, 403);
        abort_unless($devoir->publie, 403);
        return $this->downloadFile($devoir->fichier_corrige_path, $devoir->titre . '-corrige');
    }

    public function downloadEvalSujet(Request $request, Evaluation $evaluation)
    {
        $eleve = $this->eleve($request);
        abort_unless($evaluation->classe_id === $eleve->classe_id, 403);
        return $this->downloadFile($evaluation->fichier_sujet_path, $evaluation->titre . '-sujet');
    }

    public function downloadEvalCorrige(Request $request, Evaluation $evaluation)
    {
        $eleve = $this->eleve($request);
        abort_unless($evaluation->classe_id === $eleve->classe_id, 403);
        abort_unless($evaluation->notes_publiees, 403, 'Corrigé non encore disponible.');
        return $this->downloadFile($evaluation->fichier_corrige_path, $evaluation->titre . '-corrige');
    }

    private function downloadFile(?string $path, string $downloadName)
    {
        abort_if(!$path, 404, 'Fichier indisponible.');
        abort_unless(Storage::disk('public')->exists($path), 404);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '-', $downloadName);
        return Storage::disk('public')->download($path, "{$safe}.{$ext}");
    }
}
