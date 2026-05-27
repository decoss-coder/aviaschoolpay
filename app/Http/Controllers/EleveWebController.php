<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Services\Eleve\EleveInscriptionService;
use App\Services\Finance\TarificationService;
use App\Services\Parent\ParentAccountService;
use App\Services\Parent\ParentEleveLinkService;
use App\Services\Scolarite\AnneeScolaireContext;
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
                'classe:id,nom,niveau_id,frais_inscription,frais_reinscription,scolarite_annuelle',
                'classe.niveau:id,libelle,code,frais_inscription_defaut,frais_scolarite_defaut,frais_reinscription_defaut',
                'inscriptionEnCours.classe:id,nom,niveau_id,frais_inscription,frais_reinscription,scolarite_annuelle',
                'inscriptionEnCours.classe.niveau:id,libelle,code,frais_inscription_defaut,frais_scolarite_defaut,frais_reinscription_defaut',
                'moyennesGenerales' => fn ($q) => $q->orderByDesc('trimestre_id'),
                'paiements' => fn ($q) => $q->where('statut', 'confirme')->select('id', 'eleve_id', 'inscription_id', 'montant', 'statut'),
            ]);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(fn ($q) => $q->where('nom', 'like', "%{$s}%")
                ->orWhere('prenom', 'like', "%{$s}%")
                ->orWhere('matricule_interne', 'like', "%{$s}%")
                ->orWhere('matricule_desps', 'like', "%{$s}%"));
        }

        if ($request->filled('classe_id')) {
            $classeId = $request->integer('classe_id');
            $query->where(function ($q) use ($classeId, $annee) {
                $q->where('classe_id', $classeId);
                if ($annee) {
                    $q->orWhereHas('inscriptions', fn ($sub) => $sub->where('annee_scolaire_id', $annee->id)->where('classe_id', $classeId));
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
            'garcons_aff' => (clone $statsQuery)->where('sexe', 'M')->where('statut_eleve', 'AFF')->count(),
            'filles_aff' => (clone $statsQuery)->where('sexe', 'F')->where('statut_eleve', 'AFF')->count(),
            'garcons_naff' => (clone $statsQuery)->where('sexe', 'M')->where('statut_eleve', 'NAFF')->count(),
            'filles_naff' => (clone $statsQuery)->where('sexe', 'F')->where('statut_eleve', 'NAFF')->count(),
        ];

        $eleves = $query->orderBy('nom')->orderBy('prenom')->paginate(25)->withQueryString();
        $finances = [];
        foreach ($eleves as $e) {
            $finances[$e->id] = $this->calculerFinancesEleve($e);
        }

        $classes = $this->getClassesPourAnneeEnCours($etab, $annee);
        return view('eleves.index', compact('eleves', 'classes', 'stats', 'annee', 'finances'));
    }

    private function calculerFinancesEleve(Eleve $eleve): array
    {
        $classe = $eleve->classe ?? $eleve->inscriptionEnCours?->classe;
        $niveau = $classe?->niveau;
        $fraisIns = (int) ($classe?->frais_inscription ?? 0);
        if ($fraisIns <= 0 && $niveau) $fraisIns = (int) ($niveau->frais_inscription_defaut ?? 0);
        $scolarite = (int) ($classe?->scolarite_annuelle ?? 0);
        if ($scolarite <= 0 && $niveau) $scolarite = (int) ($niveau->frais_scolarite_defaut ?? 0);
        $duInscription = $fraisIns;
        $duScolarite = strtoupper((string) $eleve->statut_eleve) === 'NAFF' ? $scolarite : 0;
        $totalDu = $duInscription + $duScolarite;
        $paye = (int) ($eleve->paiements?->sum('montant') ?? 0);
        $reste = max(0, $totalDu - $paye);
        return [
            'du_inscription' => $duInscription,
            'du_scolarite' => $duScolarite,
            'total_du' => $totalDu,
            'paye' => $paye,
            'reste' => $reste,
            'taux' => $totalDu > 0 ? (int) round(($paye / $totalDu) * 100) : 0,
            'statut' => $totalDu <= 0 ? 'indefini' : ($paye <= 0 ? 'impaye' : ($reste <= 0 ? 'a_jour' : 'partiel')),
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
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $validated = $this->validateEleveForm($request, $etab, $annee, null);

        $eleve = DB::transaction(function () use ($request, $validated, $etab, $annee) {
            $parentData = $this->buildParentData($validated, $etab);
            $classeId = $validated['classe_id'] ?? null;

            $eleveData = $this->buildEleveData($validated, $etab, $annee, null);
            $eleveData['matricule_interne'] = Eleve::genererMatricule($etab->id, $annee);
            $eleveData['date_premiere_inscription'] = now()->toDateString();
            $eleveData['statut'] = $classeId ? 'inscrit' : 'pre_inscrit';

            if ($request->hasFile('photo')) {
                $eleveData['photo_path'] = $request->file('photo')->store('eleves/photos', 'public');
            }

            $eleve = Eleve::create($eleveData);
            $this->syncParentIfPossible($eleve, $etab, $parentData, $validated);
            $this->syncInscriptionFromTarifs($eleve, $etab, $annee, $classeId, 'nouvelle');
            $this->refreshClasseEffectif($classeId);
            return $eleve;
        });

        return redirect()->route('eleves.show', $eleve)->with('success', 'Élève enregistré. Les montants sont calculés depuis Finances → Tarifs.');
    }

    public function show(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);
        $eleve = Eleve::query()->where('etablissement_id', $etab->id)->with(['classe.niveau', 'inscriptionEnCours.classe.niveau', 'parents', 'moyennesGenerales.trimestre', 'paiements'])->findOrFail($id);
        return view('eleves.show', compact('eleve'));
    }

    public function edit(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $eleve = Eleve::query()->where('etablissement_id', $etab->id)->with(['parents', 'classe.niveau'])->findOrFail($id);
        $classes = $this->getClassesPourAnneeEnCours($etab, $annee);
        $nationalites = $this->nationalites();
        return view('eleves.edit', compact('eleve', 'classes', 'nationalites'));
    }

    public function update(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $eleve = Eleve::query()->where('etablissement_id', $etab->id)->with(['parents', 'classe.niveau'])->findOrFail($id);

        $validated = $this->validateEleveForm($request, $etab, $annee, $eleve);

        DB::transaction(function () use ($request, $validated, $eleve, $etab, $annee) {
            $ancienneClasseId = $eleve->classe_id;
            $classeId = $validated['classe_id'] ?? null;
            $parentData = $this->buildParentData($validated, $etab);
            $eleveData = $this->buildEleveData($validated, $etab, $annee, $eleve);
            $eleveData['statut'] = $classeId ? 'inscrit' : 'pre_inscrit';

            if ($request->hasFile('photo')) {
                if (!empty($eleve->photo_path) && Storage::disk('public')->exists($eleve->photo_path)) {
                    Storage::disk('public')->delete($eleve->photo_path);
                }
                $eleveData['photo_path'] = $request->file('photo')->store('eleves/photos', 'public');
            }

            $eleve->update($eleveData);
            $this->syncParentIfPossible($eleve, $etab, $parentData, $validated);
            $this->syncInscriptionFromTarifs($eleve->fresh(), $etab, $annee, $classeId, 'renouvellement');
            if ((int) $ancienneClasseId !== (int) $classeId) $this->refreshClasseEffectif($ancienneClasseId);
            $this->refreshClasseEffectif($classeId);
        });

        return redirect()->route('eleves.show', $eleve)->with('success', 'Élève mis à jour. Les montants ont été resynchronisés depuis Finances → Tarifs.');
    }

    public function destroy(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);
        $request->validate(['confirm_delete' => ['required', 'in:1']]);
        $eleve = Eleve::query()->where('etablissement_id', $etab->id)->findOrFail($id);
        if (!$eleve->actif) return redirect()->route('eleves.index')->with('info', 'Cet élève est déjà inactif.');
        DB::transaction(function () use ($eleve) {
            $ancienneClasseId = $eleve->classe_id;
            $eleve->update(['actif' => false, 'statut' => 'radie', 'statut_eleve' => 'NAFF', 'classe_id' => null]);
            $this->refreshClasseEffectif($ancienneClasseId);
        });
        return redirect()->route('eleves.index')->with('success', 'Élève radié avec succès.');
    }

    public function export(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);
        $query = Eleve::query()->where('etablissement_id', $etab->id)->with(['classe:id,nom', 'contactPrincipal']);
        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(fn ($w) => $w->where('nom', 'like', "%{$q}%")->orWhere('prenom', 'like', "%{$q}%")->orWhere('matricule_interne', 'like', "%{$q}%")->orWhere('matricule_desps', 'like', "%{$q}%"));
        }
        if ($request->filled('classe_id')) $query->where('classe_id', $request->classe_id);
        if ($request->filled('statut')) $query->where('statut_eleve', $request->statut);
        if ($request->filled('sexe')) $query->where('sexe', $request->sexe);
        if (! $request->boolean('inclure_inactifs')) $query->where('actif', true);
        $filename = 'eleves-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Matricule interne', 'Matricule DESPS', 'Nom', 'Prénom', 'Sexe', 'Date naissance', 'Âge', 'Classe', 'Statut', 'Redoublant', 'Nationalité', 'LV2', 'Option arts', 'Contact (nom)', 'Téléphone', 'Email', 'Actif'], ';');
            $query->orderBy('nom')->orderBy('prenom')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    fputcsv($out, [$e->matricule_interne, $e->matricule_desps, $e->nom, $e->prenom, $e->sexe, optional($e->date_naissance)->format('Y-m-d'), $e->age ?? '', $e->classe?->nom ?? '', $e->statut_eleve, $e->redoublant ? 'Oui' : 'Non', $e->nationalite, $e->lv2, $e->option_arts, trim(($e->contactPrincipal?->prenom ?? '') . ' ' . ($e->contactPrincipal?->nom ?? '')), $e->contactPrincipal?->telephone ?? '', $e->contactPrincipal?->email ?? '', $e->actif ? 'Oui' : 'Non'], ';');
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateEleveForm(Request $request, $etab, $annee, ?Eleve $eleve): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'prenom' => ['required', 'string', 'max:120'],
            'sexe' => ['required', Rule::in(['M', 'F'])],
            'statut_eleve' => ['required', Rule::in(['AFF', 'NAFF'])],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:120'],
            'nationalite' => ['nullable', 'string', 'max:80'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'parent_nom' => ['nullable', 'string', 'max:150'],
            'parent_lien' => ['nullable', 'string', 'max:50'],
            'parent_telephone' => ['nullable', 'string', 'max:30'],
            'parent_email' => ['nullable', 'email', 'max:120'],
            'parent_profession' => ['nullable', 'string', 'max:120'],
            'parent_cni' => ['nullable', 'string', 'max:100'],
            'classe_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where(function ($q) use ($etab, $annee) {
                $q->where('etablissement_id', $etab->id);
                if ($annee) $q->where('annee_scolaire_id', $annee->id);
            })],
            // Les matricules MENA/DESPS réels peuvent varier selon les imports : 2038044Y, 233052P, 19602121M, etc.
            // On accepte donc les codes alphanumériques usuels au lieu d'imposer strictement 8 chiffres + lettre.
            'matricule_desps' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\/]+$/'],
        ], [
            'matricule_desps.regex' => 'Le matricule MENA/DESPS doit contenir uniquement des lettres, chiffres, tirets ou slashs, sans espace.',
        ]);
    }

    private function buildEleveData(array $validated, $etab, $annee, ?Eleve $eleve): array
    {
        $telParent = filled($validated['parent_telephone'] ?? null) ? EleveInscriptionService::normalize($validated['parent_telephone']) : null;
        $contactTel = filled($validated['telephone'] ?? null) ? EleveInscriptionService::normalize($validated['telephone']) : $telParent;
        return [
            'etablissement_id' => $etab->id,
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'sexe' => $validated['sexe'],
            'statut_eleve' => $validated['statut_eleve'],
            'date_naissance' => $validated['date_naissance'] ?? null,
            'lieu_naissance' => $validated['lieu_naissance'] ?? null,
            'nationalite' => $validated['nationalite'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
            'matricule_desps' => filled($validated['matricule_desps'] ?? null) ? strtoupper(preg_replace('/\s+/', '', trim($validated['matricule_desps']))) : null,
            'classe_id' => $validated['classe_id'] ?? null,
            'contact_urgence_tel' => $contactTel,
            'contact_urgence_nom' => trim((string) ($validated['parent_nom'] ?? '')) ?: null,
            'actif' => true,
        ];
    }

    private function buildParentData(array $validated, $etab): array
    {
        $nomCompletParent = trim((string) ($validated['parent_nom'] ?? ''));
        [$parentPrenom, $parentNom] = $this->splitNomComplet($nomCompletParent);
        $lienParente = $validated['parent_lien'] ?? 'tuteur';
        $telParent = filled($validated['parent_telephone'] ?? null) ? EleveInscriptionService::normalize($validated['parent_telephone']) : null;
        return [
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
    }

    private function syncParentIfPossible(Eleve $eleve, $etab, array $parentData, array $validated): void
    {
        $hasParentData = collect([$validated['parent_nom'] ?? null, $validated['parent_telephone'] ?? null, $validated['parent_email'] ?? null, $validated['parent_profession'] ?? null, $validated['parent_lien'] ?? null])->filter(fn ($value) => filled($value))->isNotEmpty();
        if ($hasParentData && filled($parentData['telephone'] ?? null)) {
            ParentEleveLinkService::synchroniser($eleve, $etab, $parentData);
        }
    }

    private function syncInscriptionFromTarifs(Eleve $eleve, $etab, $annee, ?int $classeId, string $type): void
    {
        if (!$annee || !$classeId) return;
        $inscription = Inscription::updateOrCreate(
            ['eleve_id' => $eleve->id, 'annee_scolaire_id' => $annee->id],
            ['classe_id' => $classeId, 'etablissement_id' => $etab->id, 'date_inscription' => now()->toDateString(), 'type' => $type, 'statut' => 'validee', 'reduction' => 0, 'montant_inscription' => 0, 'montant_scolarite' => 0, 'montant_net' => 0]
        );
        TarificationService::synchroniserInscription($inscription, $eleve);
    }

    private function getClassesPourAnneeEnCours($etab, $annee)
    {
        if (!$etab || !$annee) return collect();
        return Classe::query()->where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->with('niveau')->orderBy('niveau_id')->orderBy('nom')->get();
    }

    private function refreshClasseEffectif(?int $classeId): void
    {
        if (!$classeId) return;
        $classe = Classe::find($classeId);
        if (!$classe) return;
        $classe->update(['effectif' => Eleve::query()->where('etablissement_id', $classe->etablissement_id)->where('classe_id', $classeId)->where('actif', true)->count()]);
    }

    private function splitNomComplet(?string $nomComplet): array
    {
        $nomComplet = trim((string) $nomComplet);
        if ($nomComplet === '') return [null, null];
        $parts = preg_split('/\s+/', $nomComplet);
        if (!$parts || count($parts) === 1) return [null, $nomComplet];
        $nom = array_pop($parts);
        $prenom = implode(' ', $parts);
        return [$prenom ?: null, $nom ?: null];
    }

    private function nationalites(): array
    {
        return ['Ivoirienne', 'Française', 'Burkinabé', 'Malienne', 'Ghanéenne', 'Autre'];
    }
}
