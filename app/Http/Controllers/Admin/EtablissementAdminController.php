<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\Platform\PlatformStatsService;
use App\Services\Scolarite\AnneeScolaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EtablissementAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $q = trim((string) $request->get('q', ''));
        $filtre = $request->get('filtre', 'tous');

        $query = Etablissement::query()->orderBy('nom');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nom', 'like', "%{$q}%")
                    ->orWhere('code_desps', 'like', "%{$q}%")
                    ->orWhere('sigle', 'like', "%{$q}%")
                    ->orWhere('ville', 'like', "%{$q}%");
            });
        }

        if ($filtre === 'actifs') {
            $query->where('actif', true);
        } elseif ($filtre === 'bloques') {
            $query->where('actif', false);
        }

        $etablissements = $query->get()->map(fn ($e) => PlatformStatsService::statsPourEtablissement($e));

        return view('admin.etablissements.index', compact('etablissements', 'q', 'filtre'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        return view('admin.etablissements.form', ['etablissement' => new Etablissement]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        // ── Validation supplémentaire pour le directeur (création obligatoire) ──
        $request->validate([
            'directeur_email'     => ['required', 'email', 'max:120', 'unique:users,email'],
            'directeur_telephone' => ['required', 'string', 'max:30', 'unique:users,telephone'],
            'directeur_nom'       => ['required', 'string', 'max:100'],
            'directeur_prenom'    => ['required', 'string', 'max:100'],
            'directeur_role'      => ['nullable', Rule::in(['fondateur', 'directeur', 'directeur_adjoint', 'gestionnaire'])],
        ], [
            'directeur_email.required'     => "L'email du directeur est obligatoire (pour qu'il puisse se connecter).",
            'directeur_email.unique'       => 'Cet email est déjà utilisé par un autre utilisateur.',
            'directeur_telephone.required' => 'Le téléphone du directeur est obligatoire.',
            'directeur_telephone.unique'   => 'Ce numéro est déjà utilisé par un autre utilisateur.',
            'directeur_nom.required'       => 'Le nom du directeur est obligatoire.',
            'directeur_prenom.required'    => 'Le prénom du directeur est obligatoire.',
        ]);

        $data = $this->validated($request);
        $data['actif'] = $request->boolean('actif', true);

        $result = DB::transaction(function () use ($data, $request) {
            $etab = Etablissement::create($data);

            // ── 1) Année scolaire active ──
            $libelle = $request->input('annee_libelle')
                ?: (now()->year.'-'.(now()->year + 1));

            $annee = AnneeScolaireService::creer(
                $etab->id,
                $libelle,
                $request->input('annee_date_debut', now()->format('Y').'-09-01'),
                $request->input('annee_date_fin', (now()->year + 1).'-06-30'),
                true
            );

            // ── 2) Compte directeur (login : email, password : 0000, à changer à 1re connexion) ──
            $directeur = User::create([
                'etablissement_id'   => $etab->id,
                'nom'                => mb_strtoupper(trim($request->input('directeur_nom'))),
                'prenom'             => trim($request->input('directeur_prenom')),
                'email'              => mb_strtolower(trim($request->input('directeur_email'))),
                'telephone'          => trim($request->input('directeur_telephone')),
                'password'           => Hash::make('0000'),
                'role'               => $request->input('directeur_role', 'directeur'),
                'actif'              => true,
                'premiere_connexion' => true, // ← force changement password à la 1re connexion
            ]);

            return ['etab' => $etab, 'directeur' => $directeur, 'annee' => $annee];
        });

        $etab = $result['etab'];
        $directeur = $result['directeur'];

        $rolesLabels = $this->rolesLabels();

        return redirect()
            ->route('admin.etablissements.show', $etab)
            ->with('success', "Établissement « {$etab->nom} » créé avec succès.")
            ->with('compte_directeur_cree', [
                'email'    => $directeur->email,
                'password' => '0000',
                'nom'      => $directeur->prenom.' '.$directeur->nom,
                'role'     => $rolesLabels[$directeur->role] ?? ucfirst($directeur->role),
            ]);
    }

    public function show(Request $request, Etablissement $etablissement)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $detail = PlatformStatsService::detailEtablissement($etablissement);

        return view('admin.etablissements.show', $detail);
    }

    public function edit(Request $request, Etablissement $etablissement)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        return view('admin.etablissements.form', ['etablissement' => $etablissement]);
    }

    public function update(Request $request, Etablissement $etablissement)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $this->validated($request, $etablissement);
        $data['actif'] = $request->boolean('actif', (bool) $etablissement->actif);
        $etablissement->update($data);

        return redirect()
            ->route('admin.etablissements.show', $etablissement)
            ->with('success', 'Établissement mis à jour.');
    }

    public function destroy(Request $request, Etablissement $etablissement)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $request->validate(['confirm_delete' => ['required', 'accepted']]);

        if (Eleve::where('etablissement_id', $etablissement->id)->exists()) {
            return back()->withErrors([
                'delete' => 'Impossible de supprimer : des élèves sont encore rattachés. Bloquez l\'accès à la place.',
            ]);
        }

        $etablissement->delete();

        return redirect()
            ->route('admin.etablissements.index')
            ->with('success', 'Établissement supprimé.');
    }

    public function toggleAccess(Request $request, Etablissement $etablissement)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $activer = $request->has('bloquer')
            ? ! $request->boolean('bloquer')
            : ! $etablissement->actif;

        DB::transaction(function () use ($etablissement, $activer) {
            $etablissement->update(['actif' => $activer]);

            User::query()
                ->where('etablissement_id', $etablissement->id)
                ->where('role', '!=', 'super_admin')
                ->update(['actif' => $activer]);
        });

        $msg = $activer
            ? "Accès rétabli pour « {$etablissement->nom} » et ses utilisateurs."
            : "Accès suspendu pour « {$etablissement->nom} » et tous ses utilisateurs.";

        return back()->with('success', $msg);
    }

    /**
     * Crée un compte utilisateur (fondateur/direction/gestionnaire/comptable...) pour un établissement existant.
     * Password initial = 0000, premiere_connexion = true (changement obligatoire).
     */
    public function storeUser(Request $request, Etablissement $etablissement)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:100'],
            'prenom'    => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:120', 'unique:users,email'],
            'telephone' => ['required', 'string', 'max:30', 'unique:users,telephone'],
            'role'      => ['required', Rule::in(['fondateur', 'directeur', 'directeur_adjoint', 'gestionnaire', 'secretaire', 'comptable', 'censeur'])],
        ], [
            'email.unique'     => 'Cet email est déjà utilisé.',
            'telephone.unique' => 'Ce téléphone est déjà utilisé.',
        ]);

        $user = User::create([
            'etablissement_id'   => $etablissement->id,
            'nom'                => mb_strtoupper(trim($data['nom'])),
            'prenom'             => trim($data['prenom']),
            'email'              => mb_strtolower(trim($data['email'])),
            'telephone'          => trim($data['telephone']),
            'password'           => Hash::make('0000'),
            'role'               => $data['role'],
            'actif'              => true,
            'premiere_connexion' => true,
        ]);

        $rolesLabels = $this->rolesLabels();

        return redirect()
            ->route('admin.etablissements.show', $etablissement)
            ->with('success', "Compte « {$user->prenom} {$user->nom} » créé.")
            ->with('compte_directeur_cree', [
                'email'    => $user->email,
                'password' => '0000',
                'nom'      => $user->prenom.' '.$user->nom,
                'role'     => $rolesLabels[$user->role] ?? ucfirst($user->role),
            ]);
    }

    /**
     * Réinitialise le mot de passe d'un utilisateur à 0000 (premiere_connexion = true).
     */
    public function resetUserPassword(Request $request, Etablissement $etablissement, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_unless($user->etablissement_id === $etablissement->id, 403);
        abort_if($user->isSuperAdmin(), 403);

        $user->update([
            'password' => Hash::make('0000'),
            'premiere_connexion' => true,
        ]);

        return back()->with('success', "Mot de passe de {$user->email} réinitialisé à 0000 (changement obligatoire à la prochaine connexion).");
    }

    public function toggleUser(Request $request, Etablissement $etablissement, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_unless($user->etablissement_id === $etablissement->id, 403);
        abort_if($user->isSuperAdmin(), 403);

        $user->update(['actif' => ! $user->actif]);

        return back()->with('success', "Compte {$user->email} ".($user->actif ? 'activé' : 'désactivé').'.');
    }

    public function ouvrirEspace(Request $request, Etablissement $etablissement)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_unless($etablissement->actif, 422, 'Établissement bloqué.');

        $user = $request->user();

        // Sauvegarder l'établissement original si pas déjà fait (pour pouvoir y revenir)
        if (! session()->has('super_admin_original_etab_id')) {
            session(['super_admin_original_etab_id' => $user->etablissement_id]);
        }

        session(['super_admin_impersonate_etab_id' => $etablissement->id]);

        $user->forceFill(['etablissement_id' => $etablissement->id])->save();
        AnneeScolaireService::initialiserContexte($etablissement->id);

        return redirect()->route('dashboard')->with(
            'success',
            "Vous consultez l'espace de « {$etablissement->nom} » (mode super admin)."
        );
    }

    public function quitterEspace(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $user = $request->user();

        // Restaurer l'établissement original (ou fallback sur le premier disponible)
        $originalEtabId = session('super_admin_original_etab_id')
            ?? Etablissement::query()->orderBy('id')->value('id');

        if ($originalEtabId) {
            $user->forceFill(['etablissement_id' => $originalEtabId])->save();
        }

        session()->forget(['super_admin_impersonate_etab_id', 'super_admin_original_etab_id']);

        return redirect()->route('admin.platform.dashboard')
            ->with('success', 'Vous êtes revenu au cockpit Avia.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Etablissement $etab = null): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'code_desps' => [
                'required', 'string', 'max:20',
                Rule::unique('etablissements', 'code_desps')->ignore($etab?->id),
            ],
            'sigle' => ['nullable', 'string', 'max:20'],
            'type' => ['required', Rule::in(['prescolaire', 'primaire', 'secondaire', 'lycee', 'mixte'])],
            'statut_juridique' => ['required', Rule::in(['public', 'prive_laic', 'prive_confessionnel', 'communautaire'])],
            'adresse' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'drena' => ['nullable', 'string', 'max:100'],
            'ddena' => ['nullable', 'string', 'max:100'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'directeur_nom' => ['nullable', 'string', 'max:120'],
            'directeur_telephone' => ['nullable', 'string', 'max:30'],
            'actif' => ['nullable', 'boolean'],
        ]);
    }

    /** @return array<string, string> */
    private function rolesLabels(): array
    {
        return [
            'fondateur' => 'Fondateur',
            'directeur' => 'Directeur',
            'directeur_adjoint' => 'Directeur adjoint',
            'gestionnaire' => 'Gestionnaire',
            'secretaire' => 'Secrétaire',
            'comptable' => 'Comptable',
            'censeur' => 'Censeur',
        ];
    }
}
