<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Eleve, Inscription, ParentTuteur};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Validation\Rule;

class EleveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;
        $query = Eleve::where('etablissement_id', $etab)->with(['inscriptionEnCours.classe.niveau']);

        if ($request->filled('classe_id'))  $query->whereHas('inscriptionEnCours', fn($q) => $q->where('classe_id', $request->classe_id));
        if ($request->filled('statut'))     $query->where('statut', $request->statut);
        if ($request->filled('sexe'))       $query->where('sexe', $request->sexe);
        if ($request->filled('search'))     $query->where(fn($q) => $q->where('nom', 'like', "%{$request->search}%")->orWhere('prenom', 'like', "%{$request->search}%")->orWhere('matricule_interne', 'like', "%{$request->search}%")->orWhere('matricule_desps', 'like', "%{$request->search}%"));

        return response()->json($query->orderBy('nom')->paginate($request->get('per_page', 25)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'nullable|string|max:100',
            'nationalite' => 'nullable|string|max:50',
            'matricule_desps' => 'nullable|string|max:20',
            'numero_extrait_naissance' => 'nullable|string|max:50',
            'adresse' => 'nullable|string',
            'groupe_sanguin' => 'nullable|string|max:5',
            'allergies' => 'nullable|string',
            'contact_urgence_nom' => 'nullable|string|max:100',
            'contact_urgence_tel' => 'nullable|string|max:20',
            'ecole_precedente' => 'nullable|string|max:200',
            // Inscription
            'classe_id' => 'required|exists:classes,id',
            'montant_scolarite' => 'required|integer|min:0',
            'reduction' => 'nullable|integer|min:0',
            // Parent
            'parent_nom' => 'required|string|max:255',
            'parent_prenom' => 'required|string|max:255',
            'parent_telephone' => 'required|string|max:20',
            'parent_sexe' => 'required|in:M,F',
            'parent_lien' => 'required|string',
        ]);

        $etab = $request->user()->etablissement_id;

        $eleve = Eleve::create([
            ...$request->only([
                'nom', 'prenom', 'sexe', 'date_naissance', 'lieu_naissance', 'nationalite',
                'matricule_desps', 'numero_extrait_naissance', 'adresse', 'groupe_sanguin',
                'allergies', 'contact_urgence_nom', 'contact_urgence_tel', 'ecole_precedente',
            ]),
            'etablissement_id' => $etab,
            'matricule_interne' => Eleve::genererMatricule($etab),
            'statut' => 'inscrit',
            'date_premiere_inscription' => now(),
            'actif' => true,
        ]);

        // Créer ou trouver le parent
        $parent = ParentTuteur::firstOrCreate(
            ['etablissement_id' => $etab, 'telephone' => $request->parent_telephone],
            [
                'nom' => $request->parent_nom,
                'prenom' => $request->parent_prenom,
                'sexe' => $request->parent_sexe,
                'lien_parente' => $request->parent_lien,
            ]
        );
        $eleve->parents()->attach($parent->id, ['est_contact_principal' => true]);

        // Créer l'inscription
        $annee = $request->user()->etablissement->anneesScolaires()->enCours()->firstOrFail();
        $reduction = $request->input('reduction', 0);
        $montantNet = $request->montant_scolarite - $reduction;

        Inscription::create([
            'eleve_id' => $eleve->id,
            'classe_id' => $request->classe_id,
            'annee_scolaire_id' => $annee->id,
            'etablissement_id' => $etab,
            'date_inscription' => now(),
            'type' => 'nouvelle',
            'statut' => 'validee',
            'montant_scolarite' => $request->montant_scolarite,
            'reduction' => $reduction,
            'montant_net' => $montantNet,
        ]);

        return response()->json($eleve->load(['inscriptionEnCours.classe', 'parents']), 201);
    }

    public function show(Eleve $eleve): JsonResponse
    {
        $eleve->load([
            'inscriptionEnCours.classe.niveau', 'parents', 'documents',
            'moyennesGenerales' => fn($q) => $q->latest('trimestre_id')->take(3),
            'paiements' => fn($q) => $q->confirmes()->latest('date_paiement')->take(10),
        ]);

        return response()->json([
            'eleve' => $eleve,
            'solde_scolarite' => $eleve->soldeScolarite(),
            'classe_actuelle' => $eleve->classeActuelle(),
        ]);
    }

    public function update(Request $request, Eleve $eleve): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'matricule_desps' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'photo_path' => 'nullable|string',
            'observations' => 'nullable|string',
        ]);

        $eleve->update($validated);
        return response()->json($eleve->fresh());
    }

    public function destroy(Eleve $eleve): JsonResponse
    {
        $eleve->update(['statut' => 'radie', 'actif' => false]);
        return response()->json(['message' => 'Élève radié avec succès.']);
    }

    public function ficheComplete(Eleve $eleve): JsonResponse
    {
        $eleve->load([
            'inscriptions.classe', 'parents', 'documents', 'presences',
            'moyennesGenerales.trimestre', 'moyennesAnnuelles', 'bulletins',
            'paiements', 'decisionsFinAnnee', 'transferts',
        ]);
        return response()->json($eleve);
    }

    public function rechercheDESPS(Request $request): JsonResponse
    {
        $request->validate(['matricule_desps' => 'required|string|max:20']);
        $eleve = Eleve::where('matricule_desps', $request->matricule_desps)->with('inscriptionEnCours.classe')->first();
        return response()->json($eleve ?: ['message' => 'Aucun élève trouvé avec ce matricule DESPS.'], $eleve ? 200 : 404);
    }
}
