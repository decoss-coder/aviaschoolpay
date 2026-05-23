<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Support\ApiEnvelope;
use App\Models\AnneeScolaire;
use App\Models\Devoir;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\MoyenneMatiere;
use App\Models\Note;
use App\Models\PresenceEleve;
use App\Models\Trimestre;
use App\Services\Eleve\EleveScolariteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentPortalApiController extends Controller
{
    private function eleve(Request $request): Eleve
    {
        $eleve = $request->user()->eleve;
        abort_if(!$eleve, 403, 'Compte élève introuvable.');

        return $eleve;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $eleve = $this->eleve($request)->loadMissing('etablissement:id,nom', 'inscriptionEnCours.classe:id,nom');
        $classe = $eleve->classeEffective();
        abort_if(!$classe, 422, 'Aucune classe assignée.');

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);

        $trimestreActuel = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->where('en_cours', true)->first()
              ?? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->first()
            : null;

        $dernieresNotes = Note::where('eleve_id', $eleve->id)
            ->whereNotNull('note')
            ->with(['evaluation.matiere', 'evaluation.typeEvaluation'])
            ->latest('date_saisie')
            ->take(5)->get();

        $devoirs = Devoir::where('classe_id', $classe->id)
            ->where('publie', true)
            ->where(function ($q) {
                $q->whereNull('date_limite')->orWhere('date_limite', '>=', today()->subDays(30));
            })
            ->with('matiere:id,nom,code', 'enseignant:id,nom,prenom')
            ->orderByDesc('date_publication')
            ->take(8)->get();

        $moyennes = $trimestreActuel
            ? MoyenneMatiere::where('eleve_id', $eleve->id)
                ->where('trimestre_id', $trimestreActuel->id)
                ->with('matiere:id,nom,code')->get()
            : collect();

        $moyenneTotale = $moyennes->whereNotNull('moyenne')->avg('moyenne');
        // Absences uniquement pour l'année courante de l'élève
        $nbAbsences = $annee
            ? PresenceEleve::where('eleve_id', $eleve->id)
                ->where('statut', 'absent')
                ->whereHas('trimestre', fn ($q) => $q->where('annee_scolaire_id', $annee->id))
                ->count()
            : 0;

        $finances = EleveScolariteService::resumePourEleve($eleve, $annee?->id);

        return ApiEnvelope::success([
            'eleve' => $eleve->only(['id', 'nom', 'prenom', 'matricule_interne', 'matricule_desps', 'statut_eleve']),
            'classe' => $classe->only(['id', 'nom']),
            'etablissement' => $eleve->etablissement?->only(['id', 'nom']),
            'trimestre' => $trimestreActuel?->only(['id', 'libelle', 'numero']),
            'moyenne_trimestre' => $moyenneTotale !== null ? round((float) $moyenneTotale, 2) : null,
            'nb_absences' => $nbAbsences,
            'finances' => $finances,
            'dernieres_notes' => $dernieresNotes,
            'devoirs_recents' => $devoirs,
        ], 'Tableau de bord élève.');
    }

    public function paiements(Request $request): JsonResponse
    {
        $eleve = $this->eleve($request);

        return ApiEnvelope::success(
            EleveScolariteService::resumePourEleve($eleve),
            'Situation financière scolarité.'
        );
    }

    public function notes(Request $request): JsonResponse
    {
        $eleve = $this->eleve($request);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $trimId = (int) $request->input('trimestre_id', $trimestres->first(fn ($t) => $t->en_cours)?->id ?? $trimestres->first()?->id);

        $notes = Note::where('eleve_id', $eleve->id)
            ->whereHas('evaluation', fn ($q) => $q->where('trimestre_id', $trimId)->where('notes_publiees', true))
            ->with(['evaluation.matiere', 'evaluation.typeEvaluation'])
            ->get();

        // Enrichir chaque note → evaluation avec URL fichier sujet si présent
        $notesArr = $notes->map(function ($n) {
            $arr = $n->toArray();
            $ev = $n->evaluation;
            if ($ev && $ev->fichier_sujet_path) {
                $arr['evaluation']['fichier_url'] = url("/api/v1/evaluations/{$ev->id}/sujet");
                $arr['evaluation']['fichier_nom'] = basename($ev->fichier_sujet_path);
            } else {
                $arr['evaluation']['fichier_url'] = null;
                $arr['evaluation']['fichier_nom'] = null;
            }
            return $arr;
        });

        $moyennes = MoyenneMatiere::where('eleve_id', $eleve->id)
            ->where('trimestre_id', $trimId)
            ->with('matiere:id,nom,code')
            ->get();

        return ApiEnvelope::success([
            'trimestres' => $trimestres,
            'trimestre_id' => $trimId,
            'notes' => $notesArr,
            'moyennes_matieres' => $moyennes,
        ], 'Notes et moyennes.');
    }

    public function devoirs(Request $request): JsonResponse
    {
        $eleve = $this->eleve($request);
        $classe = $eleve->classe;
        abort_if(!$classe, 422);

        $devoirs = Devoir::where('classe_id', $classe->id)
            ->where('publie', true)
            ->with('matiere:id,nom,code', 'enseignant:id,nom,prenom')
            ->orderByDesc('date_publication')
            ->paginate((int) $request->get('per_page', 20));

        // Enrichir avec URL de téléchargement
        $items = collect($devoirs->items())->map(function ($d) {
            $arr = $d->toArray();
            if ($d->fichier_path) {
                $arr['fichier_url'] = url("/api/v1/devoirs/{$d->id}/sujet");
                $arr['fichier_nom'] = basename($d->fichier_path);
            } else {
                $arr['fichier_url'] = null;
                $arr['fichier_nom'] = null;
            }
            return $arr;
        })->all();

        return ApiEnvelope::success([
            'data' => $items,
            'meta' => [
                'current_page' => $devoirs->currentPage(),
                'last_page' => $devoirs->lastPage(),
                'total' => $devoirs->total(),
            ],
        ], 'Liste des devoirs.');
    }

    public function schedule(Request $request): JsonResponse
    {
        $eleve = $this->eleve($request);
        $classe = $eleve->classe;
        abort_if(!$classe, 422, 'Aucune classe assignée.');

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);
        abort_if(!$annee, 422, 'Aucune année scolaire en cours.');

        $emploiDuTemps = \App\Models\EmploiDuTemps::where('classe_id', $classe->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('actif', true)
            ->with(['matiere:id,nom,code', 'enseignant:id,nom,prenom', 'salle:id,nom', 'creneau:id,heure_debut,heure_fin,ordre'])
            ->orderByRaw("CASE jour WHEN 'lundi' THEN 1 WHEN 'mardi' THEN 2 WHEN 'mercredi' THEN 3 WHEN 'jeudi' THEN 4 WHEN 'vendredi' THEN 5 WHEN 'samedi' THEN 6 ELSE 7 END")
            ->orderBy('creneau_id')
            ->get()
            ->map(fn ($e) => [
                'id'          => $e->id,
                'jour'        => $e->jour,
                'heure_debut' => $e->creneau ? substr((string) $e->creneau->heure_debut, 0, 5) : null,
                'heure_fin'   => $e->creneau ? substr((string) $e->creneau->heure_fin, 0, 5) : null,
                'matiere'     => $e->matiere?->only(['id', 'nom', 'code']),
                'enseignant'  => $e->enseignant ? ['nom' => $e->enseignant->nom, 'prenom' => $e->enseignant->prenom] : null,
                'salle'       => $e->salle?->only(['id', 'nom']),
            ]);

        return ApiEnvelope::success(['emploi_du_temps' => $emploiDuTemps], 'Emploi du temps de la classe.');
    }

    public function presences(Request $request): JsonResponse
    {
        $eleve = $this->eleve($request);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courante();

        // Filtrage strict année courante (via trimestres de l'année active)
        $trimIds = $annee
            ? \App\Models\Trimestre::where('annee_scolaire_id', $annee->id)->pluck('id')
            : collect();

        $absences = PresenceEleve::where('eleve_id', $eleve->id)
            ->when($trimIds->isNotEmpty(), fn($q) => $q->whereIn('trimestre_id', $trimIds))
            ->orderByDesc('date')
            ->paginate((int) $request->get('per_page', 30));

        $base = fn() => PresenceEleve::where('eleve_id', $eleve->id)
            ->when($trimIds->isNotEmpty(), fn($q) => $q->whereIn('trimestre_id', $trimIds));

        $stats = [
            'absences'  => (clone $base())->where('statut', 'absent')->count(),
            'retards'   => (clone $base())->where('statut', 'retard')->count(),
            'justifies' => (clone $base())->where('justifie', true)->count(),
        ];

        return ApiEnvelope::success([
            'eleve'     => $eleve->only(['id', 'nom', 'prenom']),
            'stats'     => $stats,
            'presences' => $absences->toArray(),
        ], 'Présences de l\'élève.');
    }

    public function evaluations(Request $request): JsonResponse
    {
        $eleve = $this->eleve($request);
        $classe = $eleve->classe;
        abort_if(!$classe, 422);

        $evaluations = Evaluation::where('classe_id', $classe->id)
            ->whereDate('date_evaluation', '>=', today()->subDays(60))
            ->with('matiere:id,nom,code', 'enseignant:id,nom,prenom', 'typeEvaluation:id,nom', 'trimestre:id,libelle')
            ->orderByDesc('date_evaluation')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json($evaluations);
    }

    public function downloadDevoirSujet(Request $request, Devoir $devoir): StreamedResponse|JsonResponse
    {
        $eleve = $this->eleve($request);
        abort_unless($devoir->classe_id === $eleve->classe_id, 403);
        abort_unless($devoir->publie, 403);

        return $this->downloadFile($devoir->fichier_path, $devoir->titre.'-sujet');
    }

    public function downloadDevoirCorrige(Request $request, Devoir $devoir): StreamedResponse|JsonResponse
    {
        $eleve = $this->eleve($request);
        abort_unless($devoir->classe_id === $eleve->classe_id, 403);
        abort_unless($devoir->publie, 403);

        return $this->downloadFile($devoir->fichier_corrige_path, $devoir->titre.'-corrige');
    }

    public function downloadEvalSujet(Request $request, Evaluation $evaluation): StreamedResponse|JsonResponse
    {
        $eleve = $this->eleve($request);
        abort_unless($evaluation->classe_id === $eleve->classe_id, 403);

        return $this->downloadFile($evaluation->fichier_sujet_path, $evaluation->titre.'-sujet');
    }

    public function downloadEvalCorrige(Request $request, Evaluation $evaluation): StreamedResponse|JsonResponse
    {
        $eleve = $this->eleve($request);
        abort_unless($evaluation->classe_id === $eleve->classe_id, 403);
        abort_unless($evaluation->notes_publiees, 403, 'Corrigé non encore disponible.');

        return $this->downloadFile($evaluation->fichier_corrige_path, $evaluation->titre.'-corrige');
    }

    private function downloadFile(?string $path, string $downloadName): StreamedResponse|JsonResponse
    {
        abort_if(!$path, 404, 'Fichier indisponible.');
        abort_unless(Storage::disk('public')->exists($path), 404);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '-', $downloadName);

        return Storage::disk('public')->download($path, "{$safe}.{$ext}");
    }
}
