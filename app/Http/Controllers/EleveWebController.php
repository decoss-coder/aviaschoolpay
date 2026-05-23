<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\ParentTuteur;
use App\Services\Eleve\EleveInscriptionService;
use App\Services\Finance\TarificationService;
use App\Services\Scolarite\AnneeScolaireContext;
use App\Services\Parent\ParentAccountService;
use App\Services\Parent\ParentEleveLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EleveWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $query = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->when($annee, fn ($q) => $q->pourAnneeScolaire($annee))
            ->with([
                // Classe directe + niveau avec tarifs (pour fallback si classe à 0)
                'classe:id,nom,niveau_id,frais_inscription,frais_reinscription,scolarite_annuelle',
                'classe.niveau:id,libelle,code,frais_inscription_defaut,frais_scolarite_defaut,frais_reinscription_defaut',
                // Idem via inscription si pas de classe directe
                'inscriptionEnCours.classe:id,nom,niveau_id,frais_inscription,frais_reinscription,scolarite_annuelle',
                'inscriptionEnCours.classe.niveau:id,libelle,code,frais_inscription_defaut,frais_scolarite_defaut,frais_reinscription_defaut',
                'moyennesGenerales' => fn ($q) => $q->orderByDesc('trimestre_id'),
                // Paiements confirmés pour calcul du montant payé (sans dépendre de l'inscription synchronisée)
                'paiements' => fn ($q) => $q->where('statut', 'confirme')
                    ->select('id', 'eleve_id', 'inscription_id', 'montant', 'statut'),
            ]);

        if ($request->filled('search')) {
            $s = trim($request->search);

            $query->where(function ($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                    ->orWhere('prenom', 'like', "%{$s}%")
                    ->orWhere('matricule_interne', 'like', "%{$s}%")
                    ->orWhere('matricule_desps', 'like', "%{$s}%");
            });
        }

        if ($request->filled('classe_id')) {
            $classeId = $request->integer('classe_id');

            $query->where(function ($q) use ($classeId, $annee) {
                $q->where('classe_id', $classeId);
                if ($annee) {
                    $q->orWhereHas('inscriptions', fn ($sub) => $sub
                        ->where('annee_scolaire_id', $annee->id)
                        ->where('classe_id', $classeId));
                } else {
                    $q->orWhereHas('inscriptionEnCours', fn ($sub) => $sub->where('classe_id', $classeId));
                }
            });
        }

        if ($request->filled('statut_eleve')) {
            $statutEleve = strtoupper(trim((string) $request->statut_eleve));

            if (in_array($statutEleve, ['AFF', 'NAFF'], true)) {
                $query->where('statut_eleve', $statutEleve);
            }
        }

        $statsQuery = clone $query;

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'affectes' => (clone $statsQuery)->where('statut_eleve', 'AFF')->count(),
            'non_affectes' => (clone $statsQuery)->where('statut_eleve', 'NAFF')->count(),

            'garcons_aff' => (clone $statsQuery)
                ->where('sexe', 'M')
                ->where('statut_eleve', 'AFF')
                ->count(),

            'filles_aff' => (clone $statsQuery)
                ->where('sexe', 'F')
                ->where('statut_eleve', 'AFF')
                ->count(),

            'garcons_naff' => (clone $statsQuery)
                ->where('sexe', 'M')
                ->where('statut_eleve', 'NAFF')
                ->count(),

            'filles_naff' => (clone $statsQuery)
                ->where('sexe', 'F')
                ->where('statut_eleve', 'NAFF')
                ->count(),
        ];

        $eleves = $query
            ->orderBy('nom')
            ->orderBy('prenom')
            ->paginate(25)
            ->withQueryString();

        // Calcul du résumé financier par élève (basé sur tarifs classe/niveau + paiements confirmés)
        $finances = [];
        foreach ($eleves as $e) {
            $finances[$e->id] = $this->calculerFinancesEleve($e);
        }

        $classes = $this->getClassesPourAnneeEnCours($etab, $annee);

        return view('eleves.index', compact('eleves', 'classes', 'stats', 'annee', 'finances'));
    }

    /**
     * Calcule le résumé financier d'un élève — utilise les tarifs classe avec fallback niveau,
     * et somme les paiements confirmés. Indépendant de la synchronisation des inscriptions.
     *
     * @return array{
     *   du_inscription:int, du_scolarite:int, total_du:int,
     *   paye:int, reste:int, taux:int,
     *   statut:string  // a_jour | partiel | impaye | indefini
     * }
     */
    private function calculerFinancesEleve(Eleve $eleve): array
    {
        $classe = $eleve->classe ?? $eleve->inscriptionEnCours?->classe;
        $niveau = $classe?->niveau;

        $fraisIns = (int) ($classe?->frais_inscription ?? 0);
        if ($fraisIns <= 0 && $niveau) {
            $fraisIns = (int) ($niveau->frais_inscription_defaut ?? 0);
        }
        $scolarite = (int) ($classe?->scolarite_annuelle ?? 0);
        if ($scolarite <= 0 && $niveau) {
            $scolarite = (int) ($niveau->frais_scolarite_defaut ?? 0);
        }

        $statutEleve = strtoupper((string) ($eleve->statut_eleve ?? ''));
        $duInscription = $fraisIns;
        $duScolarite = $statutEleve === 'NAFF' ? $scolarite : 0;
        $totalDu = $duInscription + $duScolarite;

        $paye = (int) ($eleve->paiements?->sum('montant') ?? 0);
        $reste = max(0, $totalDu - $paye);
        $taux = $totalDu > 0 ? (int) round(($paye / $totalDu) * 100) : 0;

        $statut = 'indefini';
        if ($totalDu > 0) {
            if ($paye <= 0) {
                $statut = 'impaye';
            } elseif ($reste <= 0) {
                $statut = 'a_jour';
            } else {
                $statut = 'partiel';
            }
        }

        return [
            'du_inscription' => $duInscription,
            'du_scolarite' => $duScolarite,
            'total_du' => $totalDu,
            'paye' => $paye,
            'reste' => $reste,
            'taux' => $taux,
            'statut' => $statut,
        ];
    }

    public function create(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $classes = $this->getClassesPourAnneeEnCours($etab, $annee);
        $nationalites = $this->nationalites();

        return view('eleves.create', compact('classes', 'nationalites'));
    }

    public function store(Request $request)
    {
        return redirect()
            ->route('eleves.index')
            ->with('success', 'Élève inscrit.');
    }

    public function show(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $eleve = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->with([
                'classe.niveau',
                'inscriptionEnCours.classe.niveau',
                'parents',
                'moyennesGenerales.trimestre',
                'paiements',
            ])
            ->findOrFail($id);

        return view('eleves.show', compact('eleve'));
    }

    public function edit(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $eleve = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->with([
                'parents',
                'classe.niveau',
            ])
            ->findOrFail($id);

        $classes = $this->getClassesPourAnneeEnCours($etab, $annee);
        $nationalites = $this->nationalites();

        return view('eleves.edit', compact('eleve', 'classes', 'nationalites'));
    }

    public function update(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $eleve = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->with([
                'parents',
                'classe.niveau',
            ])
            ->findOrFail($id);

        $estPreInscrit = ($eleve->statut ?? null) === 'pre_inscrit';

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'prenom' => ['required', 'string', 'max:120'],
            'sexe' => ['required', Rule::in(['M', 'F'])],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:120'],
            'nationalite' => ['nullable', 'string', 'max:80'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'photo' => ['nullable', 'image', 'max:2048'],

            'parent_nom' => [$estPreInscrit ? 'nullable' : 'required', 'nullable', 'string', 'max:150'],
            'parent_lien' => [$estPreInscrit ? 'nullable' : 'required', 'nullable', 'string', 'max:50'],
            'parent_telephone' => [$estPreInscrit ? 'nullable' : 'required', 'nullable', 'string', 'max:30'],
            'parent_email' => ['nullable', 'email', 'max:120'],
            'parent_profession' => ['nullable', 'string', 'max:120'],
            'parent_cni' => ['nullable', 'string', 'max:100'],

            'classe_id' => [
                $estPreInscrit ? 'nullable' : 'required',
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->where(function ($q) use ($etab, $annee) {
                    $q->where('etablissement_id', $etab->id);

                    if ($annee) {
                        $q->where('annee_scolaire_id', $annee->id);
                    }
                }),
            ],

            'matricule_desps' => ['nullable', 'string', 'max:20', 'regex:/^\d{8}[A-Z]?$/'],
        ], [
            'matricule_desps.regex' => 'Le matricule DESPS doit contenir 8 chiffres suivis éventuellement d’une lettre majuscule.',
        ]);

        DB::transaction(function () use ($request, $validated, $eleve, $estPreInscrit, $etab) {
            $ancienneClasseId = $eleve->classe_id;

            $nomCompletParent = trim((string) ($validated['parent_nom'] ?? ''));
            [$parentPrenom, $parentNom] = $this->splitNomComplet($nomCompletParent);
            $lienParente = $validated['parent_lien'] ?? 'tuteur';
            $telParent = filled($validated['parent_telephone'] ?? null)
                ? EleveInscriptionService::normalize($validated['parent_telephone'])
                : null;

            $eleveData = [
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
                'sexe' => $validated['sexe'],
                'date_naissance' => $validated['date_naissance'] ?? null,
                'lieu_naissance' => $validated['lieu_naissance'] ?? null,
                'nationalite' => $validated['nationalite'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'matricule_desps' => $validated['matricule_desps'] ?? null,
                'classe_id' => $validated['classe_id'] ?? null,
            ];

            if (filled($validated['telephone'] ?? null)) {
                $eleveData['contact_urgence_tel'] = EleveInscriptionService::normalize($validated['telephone']);
            } elseif ($telParent) {
                $eleveData['contact_urgence_tel'] = $telParent;
            }

            if ($nomCompletParent) {
                $eleveData['contact_urgence_nom'] = $nomCompletParent;
            }

            if (!empty($validated['classe_id']) && $estPreInscrit) {
                $eleveData['statut'] = 'inscrit';
            }

            if ($request->hasFile('photo')) {
                if (!empty($eleve->photo_path) && Storage::disk('public')->exists($eleve->photo_path)) {
                    Storage::disk('public')->delete($eleve->photo_path);
                }

                $eleveData['photo_path'] = $request->file('photo')->store('eleves/photos', 'public');
            }

            $eleve->update($eleveData);

            $parentData = [
                'etablissement_id' => $etab->id,
                'nom' => $parentNom ?: $nomCompletParent,
                'prenom' => $parentPrenom ?: 'Parent',
                'sexe' => ParentAccountService::sexeFromLien($lienParente),
                'telephone' => $telParent ?? '',
                'email' => $validated['parent_email'] ?? null,
                'profession' => $validated['parent_profession'] ?? null,
                'lien_parente' => $lienParente,
                'actif' => true,
            ];

            $hasParentData = collect([
                $nomCompletParent,
                $validated['parent_telephone'] ?? null,
                $validated['parent_email'] ?? null,
                $validated['parent_profession'] ?? null,
                $validated['parent_lien'] ?? null,
            ])->filter(fn ($value) => filled($value))->isNotEmpty();

            if ($hasParentData && $telParent) {
                try {
                    ParentEleveLinkService::synchroniser($eleve, $etab, $parentData);
                } catch (\InvalidArgumentException $e) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'parent_telephone' => [$e->getMessage()],
                    ]);
                }
            }

            $nouvelleClasseId = $validated['classe_id'] ?? null;

            if ($ancienneClasseId && (int) $ancienneClasseId !== (int) $nouvelleClasseId) {
                $this->refreshClasseEffectif($ancienneClasseId);
            }

            if ($nouvelleClasseId) {
                $this->refreshClasseEffectif($nouvelleClasseId);
            }

            $eleve->refresh();
            $inscription = Inscription::query()
                ->where('eleve_id', $eleve->id)
                ->where('statut', 'validee')
                ->latest('date_inscription')
                ->first();

            if ($inscription) {
                TarificationService::synchroniserInscription($inscription, $eleve);
            }
        });

        return redirect()
            ->route('eleves.show', $eleve)
            ->with('success', 'Élève mis à jour.');
    }
    
    public function destroy(Request $request, $id)
{
    $etab = $request->user()->etablissement;
    abort_unless($etab, 403);

    $request->validate([
        'confirm_delete' => ['required', 'in:1'],
    ]);

    $eleve = Eleve::query()
        ->where('etablissement_id', $etab->id)
        ->findOrFail($id);

    if (!$eleve->actif) {
        return redirect()
            ->route('eleves.index')
            ->with('info', 'Cet élève est déjà inactif.');
    }

    DB::transaction(function () use ($eleve) {
        $ancienneClasseId = $eleve->classe_id;

        $eleve->update([
            'actif' => false,
            'statut' => 'radie',
            'statut_eleve' => 'NAFF',
            'classe_id' => null,
        ]);

        if ($ancienneClasseId) {
            $this->refreshClasseEffectif($ancienneClasseId);
        }
    });

    return redirect()
        ->route('eleves.index')
        ->with('success', 'Élève radié avec succès.');
}

    /*public function destroy(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $eleve = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->findOrFail($id);

        DB::transaction(function () use ($eleve) {
            $ancienneClasseId = $eleve->classe_id;

            $eleve->update([
                'actif' => false,
                'statut' => 'radie',
            ]);

            if ($ancienneClasseId) {
                $this->refreshClasseEffectif($ancienneClasseId);
            }
        });

        return redirect()
            ->route('eleves.index')
            ->with('success', 'Élève radié.');
    }*/

    public function export(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $query = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->with(['classe:id,nom', 'contactPrincipal']);

        // Réutilise les filtres de l'index
        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($w) use ($q) {
                $w->where('nom', 'like', "%{$q}%")
                  ->orWhere('prenom', 'like', "%{$q}%")
                  ->orWhere('matricule_interne', 'like', "%{$q}%")
                  ->orWhere('matricule_desps', 'like', "%{$q}%");
            });
        }
        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }
        if ($request->filled('statut')) {
            $query->where('statut_eleve', $request->statut);
        }
        if ($request->filled('sexe')) {
            $query->where('sexe', $request->sexe);
        }
        if (! $request->boolean('inclure_inactifs')) {
            $query->where('actif', true);
        }

        $filename = 'eleves-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, [
                'Matricule interne', 'Matricule DESPS', 'Nom', 'Prénom',
                'Sexe', 'Date naissance', 'Âge', 'Classe', 'Statut',
                'Redoublant', 'Nationalité', 'LV2', 'Option arts',
                'Contact (nom)', 'Téléphone', 'Email', 'Actif',
            ], ';');

            $query->orderBy('nom')->orderBy('prenom')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $e) {
                        fputcsv($out, [
                            $e->matricule_interne,
                            $e->matricule_desps,
                            $e->nom,
                            $e->prenom,
                            $e->sexe,
                            optional($e->date_naissance)->format('Y-m-d'),
                            $e->age ?? '',
                            $e->classe?->nom ?? '',
                            $e->statut_eleve,
                            $e->redoublant ? 'Oui' : 'Non',
                            $e->nationalite,
                            $e->lv2,
                            $e->option_arts,
                            trim(($e->contactPrincipal?->prenom ?? '') . ' ' . ($e->contactPrincipal?->nom ?? '')),
                            $e->contactPrincipal?->telephone ?? '',
                            $e->contactPrincipal?->email ?? '',
                            $e->actif ? 'Oui' : 'Non',
                        ], ';');
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function getClassesPourAnneeEnCours($etab, $annee)
    {
        if (!$etab || !$annee) {
            return collect();
        }

        return Classe::query()
            ->where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->with('niveau')
            ->orderBy('niveau_id')
            ->orderBy('nom')
            ->get();
    }

    private function refreshClasseEffectif(?int $classeId): void
    {
        if (!$classeId) {
            return;
        }

        $classe = Classe::find($classeId);

        if (!$classe) {
            return;
        }

        $effectif = Eleve::query()
            ->where('etablissement_id', $classe->etablissement_id)
            ->where('classe_id', $classeId)
            ->where('actif', true)
            ->count();

        $classe->update([
            'effectif' => $effectif,
        ]);
    }

    private function splitNomComplet(?string $nomComplet): array
    {
        $nomComplet = trim((string) $nomComplet);

        if ($nomComplet === '') {
            return [null, null];
        }

        $parts = preg_split('/\s+/', $nomComplet);

        if (!$parts || count($parts) === 1) {
            return [null, $nomComplet];
        }

        $nom = array_pop($parts);
        $prenom = implode(' ', $parts);

        return [$prenom ?: null, $nom ?: null];
    }

    private function nationalites(): array
    {
        return [
            'Ivoirienne',
            'Française',
            'Burkinabé',
            'Malienne',
            'Ghanéenne',
            'Autre',
        ];
    }
}