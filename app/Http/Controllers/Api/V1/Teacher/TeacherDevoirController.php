<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\Classe;
use App\Models\Devoir;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherDevoirController extends Controller
{
    use ResolvesTeacherContext;

    public function index(Request $request): JsonResponse
    {
        $ens = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee = $this->anneeCourante($etabId);

        $q = Devoir::where('enseignant_id', $ens->id)
            ->where('etablissement_id', $etabId)
            ->when($annee, fn ($qq) => $qq->where('annee_scolaire_id', $annee->id))
            ->when($request->filled('classe_id'), fn ($qq) => $qq->where('classe_id', (int) $request->classe_id))
            ->with(['classe:id,nom', 'matiere:id,nom,code'])
            ->orderByDesc('date_publication');

        $page = $q->paginate((int) $request->get('per_page', 20));

        // Enrichir avec les méta-fichiers
        $items = collect($page->items())->map(fn (Devoir $d) => $this->serialize($d))->all();

        return ApiEnvelope::success([
            'data' => $items,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ], 'Devoirs.');
    }

    public function store(Request $request): JsonResponse
    {
        $ens = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee = $this->anneeCourante($etabId);
        abort_if(! $annee, 422, 'Aucune année scolaire en cours.');

        $data = $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_limite' => 'nullable|date',
            'publie' => 'sometimes',
            'fichier_sujet' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:20480',
        ]);

        $classe = Classe::findOrFail($data['classe_id']);
        $this->assertClasseAssignable($request, $classe);
        $this->authorizeMatierePourClasse($request, $classe->id, (int) $data['matiere_id']);

        $publie = $this->parseBool($data['publie'] ?? true);

        $fichierPath = null;
        if ($request->hasFile('fichier_sujet')) {
            $fichierPath = $request->file('fichier_sujet')->store(
                "devoirs/{$etabId}/{$classe->id}",
                'public'
            );
        }

        $devoir = Devoir::create([
            'etablissement_id' => $etabId,
            'annee_scolaire_id' => $annee->id,
            'classe_id' => $data['classe_id'],
            'matiere_id' => $data['matiere_id'],
            'enseignant_id' => $ens->id,
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'type' => 'maison',
            'date_publication' => now()->toDateString(),
            'date_limite' => $data['date_limite'] ?? null,
            'publie' => $publie,
            'fichier_path' => $fichierPath,
        ]);

        return ApiEnvelope::success(
            $this->serialize($devoir->fresh()->load(['classe:id,nom', 'matiere:id,nom,code'])),
            'Devoir créé.',
            201
        );
    }

    public function show(Request $request, Devoir $devoir): JsonResponse
    {
        $this->assertDevoirOwned($request, $devoir);

        return ApiEnvelope::success(
            $this->serialize($devoir->load(['classe:id,nom', 'matiere:id,nom,code'])),
            'Détail du devoir.'
        );
    }

    public function update(Request $request, Devoir $devoir): JsonResponse
    {
        $this->assertDevoirOwned($request, $devoir);

        $data = $request->validate([
            'classe_id' => 'sometimes|exists:classes,id',
            'matiere_id' => 'sometimes|exists:matieres,id',
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'date_limite' => 'nullable|date',
            'publie' => 'sometimes|boolean',
        ]);

        if (isset($data['classe_id'])) {
            $classe = Classe::findOrFail($data['classe_id']);
            $this->assertClasseAssignable($request, $classe);
            $devoir->classe_id = $classe->id;
        }
        if (isset($data['matiere_id'])) {
            $cid = $devoir->classe_id;
            $this->authorizeMatierePourClasse($request, $cid, (int) $data['matiere_id']);
            $devoir->matiere_id = $data['matiere_id'];
        }
        if (isset($data['titre'])) {
            $devoir->titre = $data['titre'];
        }
        if (array_key_exists('description', $data)) {
            $devoir->description = $data['description'];
        }
        if (array_key_exists('date_limite', $data)) {
            $devoir->date_limite = $data['date_limite'];
        }
        if (isset($data['publie'])) {
            $devoir->publie = $data['publie'];
        }

        $devoir->save();

        return ApiEnvelope::success(
            $devoir->fresh()->load(['classe:id,nom', 'matiere:id,nom,code'])->toArray(),
            'Devoir mis à jour.'
        );
    }

    public function destroy(Request $request, Devoir $devoir): JsonResponse
    {
        $this->assertDevoirOwned($request, $devoir);

        if ($devoir->fichier_path) {
            Storage::disk('public')->delete($devoir->fichier_path);
        }
        if ($devoir->fichier_corrige_path) {
            Storage::disk('public')->delete($devoir->fichier_corrige_path);
        }

        $devoir->delete();

        return ApiEnvelope::success(new \stdClass, 'Devoir supprimé.');
    }

    /**
     * Téléchargement du sujet d'un devoir (enseignant ou élève autorisé).
     */
    public function downloadSujet(Request $request, Devoir $devoir): StreamedResponse
    {
        $user = $request->user();

        // Enseignant propriétaire ?
        $ens = $user->enseignantActif();
        $isOwner = $ens && (int) $devoir->enseignant_id === (int) $ens->id;

        if (! $isOwner) {
            // Sinon élève de la classe et devoir publié ?
            $eleve = $user->eleve;
            $isStudent = $eleve
                && (int) $eleve->classe_id === (int) $devoir->classe_id
                && $devoir->publie;
            abort_unless($isStudent, 403, 'Accès refusé.');
        }

        abort_unless($devoir->fichier_path && Storage::disk('public')->exists($devoir->fichier_path), 404);

        $filename = $this->buildFilename($devoir, $devoir->fichier_path);

        return Storage::disk('public')->download($devoir->fichier_path, $filename);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function serialize(Devoir $devoir): array
    {
        $arr = $devoir->toArray();

        if ($devoir->fichier_path) {
            $arr['fichier_url'] = url("/api/v1/devoirs/{$devoir->id}/sujet");
            $arr['fichier_nom'] = basename($devoir->fichier_path);
            $arr['fichier_size'] = Storage::disk('public')->exists($devoir->fichier_path)
                ? Storage::disk('public')->size($devoir->fichier_path)
                : null;
        } else {
            $arr['fichier_url'] = null;
            $arr['fichier_nom'] = null;
            $arr['fichier_size'] = null;
        }

        return $arr;
    }

    private function buildFilename(Devoir $devoir, string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $slug = \Illuminate\Support\Str::slug($devoir->titre, '-');
        return $slug . ($ext ? '.' . $ext : '');
    }

    private function parseBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
}
