<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClasseRequest;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\Niveau;
use App\Models\Serie;
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClasseWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        if (!$annee) {
            return redirect()->route('dashboard')
                ->with('error', 'Aucune année scolaire en cours. Veuillez en créer une avant de créer des classes.');
        }

        $query = Classe::query()
            ->where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->with(['niveau', 'serie', 'professeurPrincipal']);

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('statut')) {
            if ($request->statut === 'pleine') {
                $query->whereColumn('effectif', '>=', 'capacite');
            } elseif ($request->statut === 'disponible') {
                $query->whereColumn('effectif', '<', 'capacite');
            } elseif ($request->statut === 'vide') {
                $query->where('effectif', 0);
            }
        }

        $classes = $query
            ->orderBy('niveau_id')
            ->orderBy('nom')
            ->get();

        $classesParNiveau = $classes->groupBy(function ($classe) {
            return $classe->niveau->libelle ?? $classe->niveau->code ?? 'Sans niveau';
        });

        $stats = [
            'total_classes' => $classes->count(),
            'total_capacite' => $classes->sum('capacite'),
            'total_effectif' => $classes->sum('effectif'),
            'taux_remplissage' => $classes->sum('capacite') > 0
                ? round(($classes->sum('effectif') / $classes->sum('capacite')) * 100)
                : 0,
            'classes_pleines' => $classes->filter(fn ($c) => (int) $c->effectif >= (int) $c->capacite)->count(),
            'scolarite_moyenne' => round($classes->avg('scolarite_annuelle') ?? 0),
        ];

        $niveaux = Niveau::where('etablissement_id', $etab->id)
            ->orderBy('ordre')
            ->get();

        return view('classes.index', compact('classes', 'classesParNiveau', 'stats', 'niveaux', 'annee'));
    }

    public function create(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        if (!$annee) {
            return redirect()->route('classes.index')
                ->with('error', 'Aucune année scolaire en cours.');
        }

        $niveaux = Niveau::where('etablissement_id', $etab->id)->orderBy('ordre')->get();
        $series = Serie::orderBy('libelle')->get();
        $enseignants = Enseignant::where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        $niveauPreselect = $request->input('niveau_id');

        return view('classes.create', compact('niveaux', 'series', 'enseignants', 'annee', 'niveauPreselect'));
    }

    public function store(ClasseRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->etablissement_id;
        $data['effectif'] = 0;
        $data['active'] = $request->boolean('active', true);

        $niveau = Niveau::where('id', $data['niveau_id'])
            ->where('etablissement_id', $request->user()->etablissement_id)
            ->first();

        if ($niveau) {
            $data['scolarite_annuelle'] = (int) ($niveau->frais_scolarite_defaut ?? 0);
            $data['frais_inscription'] = (int) ($niveau->frais_inscription_defaut ?? 0);
            $data['frais_reinscription'] = (int) ($niveau->frais_reinscription_defaut ?? 0);
        }

        $classe = Classe::create($data);

        return redirect()->route('classes.show', $classe)
            ->with('success', "Classe « {$classe->nom} » créée avec succès. Vous pouvez maintenant y inscrire des élèves.");
    }

    public function show(Request $request, Classe $classe)
    {
        $this->authorizeEtab($request, $classe);

        $classe->load([
            'niveau',
            'serie',
            'professeurPrincipal',
            'anneeScolaire',
        ]);

        $eleves = $classe->eleves()
            ->where('actif', true)
            ->with([
                'parents',
                'moyennesGenerales' => fn ($q) => $q->with('trimestre')->orderByDesc('trimestre_id'),
            ])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $moyennes = $classe->moyennesGenerales()
            ->with('trimestre')
            ->latest('trimestre_id')
            ->get();

        $moyenneClasse = $moyennes->avg('moyenne_generale');

        $stats = [
            'effectif' => $eleves->count(),
            'capacite' => (int) $classe->capacite,
            'taux_remplissage' => (int) $classe->capacite > 0
                ? round(($eleves->count() / $classe->capacite) * 100)
                : 0,
            'places_restantes' => max(0, (int) $classe->capacite - $eleves->count()),
            'garcons' => $eleves->filter(fn ($e) => $e->sexe === 'M')->count(),
            'filles' => $eleves->filter(fn ($e) => $e->sexe === 'F')->count(),
            'moyenne_classe' => $moyenneClasse ? round($moyenneClasse, 2) : null,
            'eleves_en_difficulte' => $moyennes->filter(fn ($m) => $m->moyenne_generale < 10)->count(),
        ];

        return view('classes.show', compact('classe', 'eleves', 'stats'));
    }

    public function edit(Request $request, Classe $classe)
    {
        $this->authorizeEtab($request, $classe);

        $niveaux = Niveau::where('etablissement_id', $request->user()->etablissement_id)
            ->orderBy('ordre')
            ->get();

        $series = Serie::orderBy('libelle')->get();

        $enseignants = Enseignant::where('etablissement_id', $request->user()->etablissement_id)
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        return view('classes.edit', compact('classe', 'niveaux', 'series', 'enseignants'));
    }

    public function update(ClasseRequest $request, Classe $classe)
    {
        $this->authorizeEtab($request, $classe);

        $classe->update($request->validated());

        return redirect()->route('classes.show', $classe)
            ->with('success', "Classe « {$classe->nom} » mise à jour.");
    }

    public function destroy(Request $request, Classe $classe)
    {
        $this->authorizeEtab($request, $classe);

        $nbEleves = $classe->eleves()->where('actif', true)->count();

        if ($nbEleves > 0) {
            return back()->with(
                'error',
                "Impossible de supprimer « {$classe->nom} » : {$nbEleves} élève(s) y sont rattaché(s). Déplacez-les d'abord vers d'autres classes."
            );
        }

        $nom = $classe->nom;
        $classe->delete();

        return redirect()->route('classes.index')
            ->with('success', "Classe « {$nom} » supprimée.");
    }

    public function duplicate(Request $request, Classe $classe)
    {
        $this->authorizeEtab($request, $classe);

        $nouvelle = $classe->replicate();
        $nouvelle->nom = $classe->nom . ' (copie)';
        $nouvelle->effectif = 0;
        $nouvelle->save();

        return redirect()->route('classes.edit', $nouvelle)
            ->with('success', "Classe dupliquée. Modifiez le nom avant d'y inscrire des élèves.");
    }

    public function quickCreate(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        if (!$annee) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année scolaire en cours.',
            ], 422);
        }

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'niveau_id' => [
                'required',
                'integer',
                Rule::exists('niveaux', 'id')->where(fn ($q) => $q->where('etablissement_id', $etab->id)),
            ],
            'capacite' => ['nullable', 'integer', 'min:1', 'max:200'],
            'serie_id' => ['nullable', 'integer', 'exists:series,id'],
            'scolarite_annuelle' => ['nullable', 'integer', 'min:0'],
            'frais_inscription' => ['nullable', 'integer', 'min:0'],
            'frais_reinscription' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'nom.required' => 'Le nom de la classe est obligatoire.',
            'niveau_id.required' => 'Le niveau est obligatoire.',
            'niveau_id.exists' => 'Niveau invalide.',
            'capacite.max' => 'Capacité maximum : 200 élèves.',
        ]);

        $niveau = Niveau::where('id', $validated['niveau_id'])
            ->where('etablissement_id', $etab->id)
            ->first();

        if (!$niveau) {
            return response()->json([
                'success' => false,
                'message' => 'Ce niveau n\'appartient pas à votre établissement.',
            ], 403);
        }

        $existe = Classe::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('niveau_id', $validated['niveau_id'])
            ->where('nom', $validated['nom'])
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => "Une classe « {$validated['nom']} » existe déjà dans ce niveau.",
            ], 422);
        }

        try {
            $classe = Classe::create([
                'etablissement_id' => $etab->id,
                'annee_scolaire_id' => $annee->id,
                'niveau_id' => $validated['niveau_id'],
                'serie_id' => $validated['serie_id'] ?? null,
                'nom' => $validated['nom'],
                'capacite' => $validated['capacite'] ?? 30,
                'effectif' => 0,
                'scolarite_annuelle' => $validated['scolarite_annuelle'] ?? ($niveau->frais_scolarite_defaut ?? 0),
                'frais_inscription' => $validated['frais_inscription'] ?? ($niveau->frais_inscription_defaut ?? 0),
                'frais_reinscription' => $validated['frais_reinscription'] ?? ($niveau->frais_reinscription_defaut ?? 0),
                'description' => $validated['description'] ?? null,
                'active' => true,
            ]);

            $classe->load('niveau', 'serie');

            return response()->json([
                'success' => true,
                'message' => "Classe « {$classe->nom} » créée avec succès.",
                'classe' => [
                    'id' => $classe->id,
                    'nom' => $classe->nom,
                    'niveau_id' => $classe->niveau_id,
                    'niveau_libelle' => $classe->niveau->libelle ?? $classe->niveau->code ?? '',
                    'serie_id' => $classe->serie_id,
                    'serie_libelle' => $classe->serie->libelle ?? null,
                    'capacite' => $classe->capacite,
                    'effectif' => $classe->effectif,
                    'scolarite_annuelle' => $classe->scolarite_annuelle,
                    'frais_inscription' => $classe->frais_inscription,
                    'frais_reinscription' => $classe->frais_reinscription,
                    'label' => $classe->nom . ' (0/' . $classe->capacite . ')',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function ajusterTarifs(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        if (!$annee) {
            return back()->with('error', 'Aucune année scolaire en cours.');
        }

        $validated = $request->validate([
            'mode_ciblage' => ['required', Rule::in(['niveau', 'intervalle', 'selection'])],

            'niveau_id' => ['nullable', 'integer'],
            'classe_debut_id' => ['nullable', 'integer'],
            'classe_fin_id' => ['nullable', 'integer'],
            'classe_ids' => ['nullable', 'array'],
            'classe_ids.*' => ['integer'],

            'scolarite_annuelle' => ['required', 'integer', 'min:0'],
            'frais_inscription' => ['nullable', 'integer', 'min:0'],
            'frais_reinscription' => ['nullable', 'integer', 'min:0'],
        ]);

        $baseQuery = Classe::query()
            ->where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id);

        if ($validated['mode_ciblage'] === 'niveau') {
            if (empty($validated['niveau_id'])) {
                return back()->with('error', 'Le niveau est obligatoire pour ce mode.');
            }

            $baseQuery->where('niveau_id', $validated['niveau_id']);
        }

        if ($validated['mode_ciblage'] === 'selection') {
            $ids = collect($validated['classe_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();

            if ($ids->isEmpty()) {
                return back()->with('error', 'Sélectionnez au moins une classe.');
            }

            $baseQuery->whereIn('id', $ids);
        }

        if ($validated['mode_ciblage'] === 'intervalle') {
            $debutId = (int) ($validated['classe_debut_id'] ?? 0);
            $finId = (int) ($validated['classe_fin_id'] ?? 0);

            if (!$debutId || !$finId) {
                return back()->with('error', 'Choisissez la classe de début et la classe de fin.');
            }

            $classesIntervalle = Classe::query()
                ->where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with('niveau')
                ->orderBy('niveau_id')
                ->orderBy('nom')
                ->get();

            $ids = $classesIntervalle->pluck('id')->values();
            $startIndex = $ids->search($debutId);
            $endIndex = $ids->search($finId);

            if ($startIndex === false || $endIndex === false) {
                return back()->with('error', 'Intervalle invalide.');
            }

            if ($startIndex > $endIndex) {
                [$startIndex, $endIndex] = [$endIndex, $startIndex];
            }

            $idsIntervalle = $ids->slice($startIndex, $endIndex - $startIndex + 1)->all();

            $baseQuery->whereIn('id', $idsIntervalle);
        }

        $classes = $baseQuery->get();

        if ($classes->isEmpty()) {
            return back()->with('error', 'Aucune classe trouvée pour ce ciblage.');
        }

        DB::transaction(function () use ($classes, $validated) {
            foreach ($classes as $classe) {
                $classe->update([
                    'scolarite_annuelle' => $validated['scolarite_annuelle'],
                    'frais_inscription' => $validated['frais_inscription'] ?? 0,
                    'frais_reinscription' => $validated['frais_reinscription'] ?? 0,
                ]);
            }
        });

        return back()->with('success', count($classes) . ' classe(s) mise(s) à jour avec succès.');
    }

    private function authorizeEtab(Request $request, Classe $classe): void
    {
        if ($classe->etablissement_id !== $request->user()->etablissement_id) {
            abort(403, 'Cette classe ne vous appartient pas.');
        }
    }
}
