<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use App\Models\Matiere;
use App\Models\Pointage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\Scolarite\AnneeScolaireContext;

class EnseignantWebController extends Controller
{
    private const DISCIPLINES_BULLETIN = [
        'Français',
        'Histoire-Géographie',
        'Anglais',
        'Espagnol',
        'Mathématiques',
        'Physique-Chimie',
        'Sciences de la Vie et de la Terre',
        'EDHC',
        'Éducation Physique et Sportive',
        'Arts Plastiques / Éducation artistique',
        'Lecture',
        'Conduite',
    ];

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $query = Enseignant::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->with([
                'user',
                'classesPrincipales.niveau',
                'affectations' => fn ($q) => $q->where('active', true)->with(['classe.niveau', 'matiere']),
                'pointages' => fn ($q) => $q->whereDate('date', today())->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)->orderByDesc('heure_scan'),
            ])
            ->withCount([
                'alertesPointage as alertes_non_traitees_count' => fn ($q) => $q->where('traitee', false),
            ]);

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                    ->orWhere('prenom', 'like', "%{$s}%")
                    ->orWhere('matricule_mena', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('telephone', 'like', "%{$s}%")
                    ->orWhere('specialite', 'like', "%{$s}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('specialite')) {
            $query->where('specialite', 'like', '%' . $request->specialite . '%');
        }

        if ($request->filled('presence')) {
            if ($request->presence === 'present') {
                $query->whereHas('pointages', fn ($q) => $q->whereDate('date', today())->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)->whereIn('statut', [Pointage::STATUT_PRESENT, Pointage::STATUT_RETARD]));
            }

            if ($request->presence === 'absent') {
                $query->whereDoesntHave('pointages', fn ($q) => $q->whereDate('date', today())->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE));
            }

            if ($request->presence === 'retard') {
                $query->whereHas('pointages', fn ($q) => $q->whereDate('date', today())->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)->where('statut', Pointage::STATUT_RETARD));
            }

            if ($request->presence === 'anormal') {
                $query->whereHas('pointages', function ($q) {
                    $q->whereDate('date', today())
                        ->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)
                        ->where(function ($sub) {
                            $sub->where('statut', Pointage::STATUT_ANOMALIE)
                                ->orWhere('spoofing_detecte', true)
                                ->orWhere('gps_valide', false)
                                ->orWhere('token_valide', false)
                                ->orWhere('conforme_emploi_temps', false);
                        });
                });
            }
        }

        $statsBase = clone $query;
        $stats = [
            'total' => (clone $statsBase)->count(),
            'presents' => (clone $statsBase)->whereHas('pointages', fn ($q) => $q->whereDate('date', today())->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)->whereIn('statut', [Pointage::STATUT_PRESENT, Pointage::STATUT_RETARD]))->count(),
            'retards' => (clone $statsBase)->whereHas('pointages', fn ($q) => $q->whereDate('date', today())->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)->where('statut', Pointage::STATUT_RETARD))->count(),
            'absents' => (clone $statsBase)->whereDoesntHave('pointages', fn ($q) => $q->whereDate('date', today())->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE))->count(),
            'alertes' => (clone $statsBase)->withCount(['alertesPointage as tmp_alertes_count' => fn ($q) => $q->where('traitee', false)])->get()->sum('tmp_alertes_count'),
        ];

        $enseignants = $query->orderBy('nom')->orderBy('prenom')->paginate(20)->withQueryString();
        $statutsDisponibles = $this->statutsDisponibles($etab->id);
        $specialitesDisponibles = $this->specialitesDisponibles($etab->id);

        return view('enseignants.index', compact('enseignants', 'stats', 'annee', 'statutsDisponibles', 'specialitesDisponibles'));
    }

    public function create(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $statutsDisponibles = $this->statutsDisponibles($etab->id);
        $specialitesDisponibles = $this->specialitesDisponibles($etab->id);
        $matieresDisponibles = $this->matieresDisponibles($etab->id);
        $matieresSelectionnees = $this->selectedMatieres(old('matieres', []));

        return view('enseignants.create', compact('statutsDisponibles', 'specialitesDisponibles', 'matieresDisponibles', 'matieresSelectionnees'));
    }

    public function store(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $validated = $this->validateData($request);

        DB::transaction(function () use ($request, $validated, $etab) {
            if ($request->hasFile('photo')) {
                $validated['photo_path'] = $request->file('photo')->store('enseignants/photos', 'public');
            }

            $validated['specialite'] = $this->formatMatieres($validated['matieres'] ?? [], $validated['specialite'] ?? null);
            unset($validated['matieres']);

            $user = $this->ensureTeacherUser($validated, $etab);

            $validated['user_id'] = $user->id;
            $validated['etablissement_id'] = $etab->id;
            $validated['statut'] = $validated['statut'] ?: Enseignant::STATUT_TITULAIRE;
            $validated['score_ponctualite'] = 100.00;
            $validated['actif'] = true;

            Enseignant::create($validated);
        });

        return redirect()->route('enseignants.index')->with('success', 'Enseignant ajouté avec succès. Le compte utilisateur a été créé automatiquement.');
    }

    public function show(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $enseignant = Enseignant::query()
            ->where('etablissement_id', $etab->id)
            ->with([
                'user',
                'classesPrincipales.niveau',
                'affectations' => fn ($q) => $q->where('active', true)->with(['classe.niveau', 'matiere']),
                'pointages' => fn ($q) => $q->orderByDesc('date')->orderByDesc('heure_scan')->limit(30),
                'alertesPointage' => fn ($q) => $q->latest('date')->latest('id')->limit(20)->with('pointage'),
                'statsPonctualite' => fn ($q) => $q->latest('id')->limit(12),
                'paies' => fn ($q) => $q->latest('id')->limit(12),
            ])
            ->withCount(['alertesPointage as alertes_non_traitees_count' => fn ($q) => $q->where('traitee', false)])
            ->findOrFail($id);

        $pointages30j = $enseignant->pointages()->whereDate('date', '>=', now()->subDays(30)->toDateString())->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE);

        $stats30j = [
            'presents' => (clone $pointages30j)->whereIn('statut', [Pointage::STATUT_PRESENT, Pointage::STATUT_RETARD])->count(),
            'retards' => (clone $pointages30j)->where('statut', Pointage::STATUT_RETARD)->count(),
            'anomalies' => (clone $pointages30j)->where(function ($q) {
                $q->where('statut', Pointage::STATUT_ANOMALIE)->orWhere('spoofing_detecte', true)->orWhere('gps_valide', false)->orWhere('token_valide', false)->orWhere('conforme_emploi_temps', false);
            })->count(),
        ];

        return view('enseignants.show', compact('enseignant', 'stats30j'));
    }

    public function edit(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $enseignant = Enseignant::query()->where('etablissement_id', $etab->id)->findOrFail($id);
        $statutsDisponibles = $this->statutsDisponibles($etab->id);
        $specialitesDisponibles = $this->specialitesDisponibles($etab->id);
        $matieresDisponibles = $this->matieresDisponibles($etab->id);
        $matieresSelectionnees = $this->selectedMatieres(old('matieres', $enseignant->specialite));

        return view('enseignants.edit', compact('enseignant', 'statutsDisponibles', 'specialitesDisponibles', 'matieresDisponibles', 'matieresSelectionnees'));
    }

    public function update(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $enseignant = Enseignant::query()->where('etablissement_id', $etab->id)->with('user')->findOrFail($id);
        $validated = $this->validateData($request, $enseignant);

        DB::transaction(function () use ($request, $validated, $enseignant, $etab) {
            if ($request->hasFile('photo')) {
                if (!empty($enseignant->photo_path) && Storage::disk('public')->exists($enseignant->photo_path)) {
                    Storage::disk('public')->delete($enseignant->photo_path);
                }
                $validated['photo_path'] = $request->file('photo')->store('enseignants/photos', 'public');
            }

            $validated['specialite'] = $this->formatMatieres($validated['matieres'] ?? [], $validated['specialite'] ?? null);
            unset($validated['matieres']);

            $user = $this->ensureTeacherUser($validated, $etab, $enseignant);
            $validated['user_id'] = $user->id;
            $validated['statut'] = $validated['statut'] ?: Enseignant::STATUT_TITULAIRE;

            $enseignant->update($validated);
        });

        return redirect()->route('enseignants.show', $enseignant)->with('success', 'Enseignant mis à jour avec succès.');
    }

    public function destroy(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);
        $request->validate(['confirm_delete' => ['required', 'in:1']]);
        $enseignant = Enseignant::query()->where('etablissement_id', $etab->id)->findOrFail($id);

        if (!$enseignant->actif) {
            return redirect()->route('enseignants.index')->with('info', 'Cet enseignant est déjà inactif.');
        }

        DB::transaction(function () use ($enseignant) {
            $enseignant->update(['actif' => false]);
            $enseignant->delete();
        });

        return redirect()->route('enseignants.index')->with('success', 'Enseignant archivé avec succès.');
    }

    public function photo(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);
        $enseignant = Enseignant::query()->where('etablissement_id', $etab->id)->findOrFail($id);
        abort_if(empty($enseignant->photo_path), 404);
        abort_unless(Storage::disk('public')->exists($enseignant->photo_path), 404);
        return response()->file(Storage::disk('public')->path($enseignant->photo_path));
    }

    public function export(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);
        $query = Enseignant::where('etablissement_id', $etab->id);

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($w) use ($q) {
                $w->where('nom', 'like', "%{$q}%")->orWhere('prenom', 'like', "%{$q}%")->orWhere('matricule_mena', 'like', "%{$q}%")->orWhere('telephone', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('specialite', 'like', "%{$q}%");
            });
        }
        if ($request->filled('statut')) $query->where('statut', $request->statut);
        if (! $request->boolean('inclure_inactifs')) $query->where('actif', true);

        $filename = 'enseignants-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Matricule MENA', 'Nom', 'Prénom', 'Sexe', 'Date naissance', 'Téléphone', 'Email', 'Matières', 'Statut', 'Salaire base', 'Score ponctualité', 'Actif'], ';');
            $query->orderBy('nom')->orderBy('prenom')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    fputcsv($out, [$e->matricule_mena, $e->nom, $e->prenom, $e->sexe, optional($e->date_naissance)->format('Y-m-d'), $e->telephone, $e->email, $e->specialite, $e->statut, (int) ($e->salaire_base ?? 0), $e->score_ponctualite, $e->actif ? 'Oui' : 'Non'], ';');
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateData(Request $request, ?Enseignant $enseignant = null): array
    {
        $userIdToIgnore = $enseignant?->user_id;
        return $request->validate([
            'matricule_mena' => ['nullable', 'string', 'max:30'],
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', 'in:M,F'],
            'date_naissance' => ['nullable', 'date'],
            'telephone' => ['required', 'string', 'max:20'],
            'telephone_2' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userIdToIgnore), Rule::unique('enseignants', 'email')->ignore($enseignant?->id)],
            'adresse' => ['nullable', 'string'],
            'diplome_plus_eleve' => ['nullable', 'string', 'max:100'],
            'specialite' => ['nullable', 'string', 'max:255'],
            'matieres' => ['nullable', 'array'],
            'matieres.*' => ['nullable', 'string', 'max:100'],
            'statut' => ['required', 'string', 'max:50'],
            'date_prise_fonction' => ['nullable', 'date'],
            'salaire_base' => ['nullable', 'numeric', 'min:0'],
            'banque' => ['nullable', 'string', 'max:100'],
            'numero_compte' => ['nullable', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    private function ensureTeacherUser(array $data, $etab, ?Enseignant $enseignant = null): User
    {
        $email = trim((string) ($data['email'] ?? '')) ?: $this->buildTeacherEmail($data, (int) $etab->id, $enseignant?->id);
        $payload = [
            'etablissement_id' => $etab->id,
            'active_etablissement_id' => $etab->id,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $email,
            'telephone' => trim((string) ($data['telephone'] ?? '')),
            'role' => 'enseignant',
            'sexe' => $data['sexe'],
            'actif' => true,
        ];

        if ($enseignant?->user) {
            $enseignant->user->update($payload);
            return $enseignant->user->fresh();
        }

        $payload['password'] = Hash::make(Str::random(40));
        $payload['premiere_connexion'] = true;
        $payload['derniere_connexion'] = null;
        return User::create($payload);
    }

    private function buildTeacherEmail(array $data, int $etablissementId, ?int $enseignantId = null): string
    {
        $base = Str::slug(($data['prenom'] ?? 'enseignant') . '-' . ($data['nom'] ?? 'prof')) ?: 'enseignant';
        $suffix = $enseignantId ?: strtolower(Str::random(6));
        $candidate = "enseignant.{$etablissementId}.{$base}.{$suffix}@aviaschoolpay.local";
        $counter = 1;
        while (User::where('email', $candidate)->exists()) {
            $candidate = "enseignant.{$etablissementId}.{$base}.{$suffix}.{$counter}@aviaschoolpay.local";
            $counter++;
        }
        return $candidate;
    }

    private function formatMatieres(array $matieres, ?string $fallback = null): ?string
    {
        $values = collect($matieres)->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
        return $values->isNotEmpty() ? $values->implode(', ') : (trim((string) $fallback) ?: null);
    }

    private function selectedMatieres(mixed $value): array
    {
        if (is_array($value)) return array_values(array_filter(array_map('trim', $value)));
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    private function statutsDisponibles(int $etablissementId): array
    {
        $defaults = Enseignant::statutsParDefaut();
        $dbValues = Enseignant::query()->where('etablissement_id', $etablissementId)->whereNotNull('statut')->where('statut', '!=', '')->distinct()->orderBy('statut')->pluck('statut')->all();
        return array_values(array_unique(array_merge($defaults, $dbValues)));
    }

    private function specialitesDisponibles(int $etablissementId): array
    {
        $fromTeachers = Enseignant::query()->where('etablissement_id', $etablissementId)->whereNotNull('specialite')->where('specialite', '!=', '')->pluck('specialite')->all();
        $exploded = collect($fromTeachers)->flatMap(fn ($value) => array_map('trim', explode(',', (string) $value)))->filter()->all();
        return array_values(array_unique(array_merge(self::DISCIPLINES_BULLETIN, $exploded)));
    }

    private function matieresDisponibles(int $etablissementId): array
    {
        $fromMatieres = [];
        if (Schema::hasTable('matieres')) {
            $fromMatieres = Matiere::query()->where('etablissement_id', $etablissementId)->where('active', true)->whereNull('parent_matiere_id')->orderBy('ordre')->orderBy('nom')->pluck('nom')->all();
        }
        return array_values(array_unique(array_merge(self::DISCIPLINES_BULLETIN, $fromMatieres, $this->specialitesDisponibles($etablissementId))));
    }
}
