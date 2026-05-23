<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Paiement, Inscription, Eleve, Echeance};
use App\Services\PayDunyaService;
use Illuminate\Http\{JsonResponse, Request};

class PaiementController extends Controller
{
    public function __construct(private PayDunyaService $payDunya) {}

    public function index(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;
        $query = Paiement::where('etablissement_id', $etab)->with('eleve:id,nom,prenom,matricule_interne');

        if ($request->filled('statut'))    $query->where('statut', $request->statut);
        if ($request->filled('mode'))      $query->where('mode', $request->mode);
        if ($request->filled('date_debut'))$query->where('date_paiement', '>=', $request->date_debut);
        if ($request->filled('date_fin'))  $query->where('date_paiement', '<=', $request->date_fin);

        return response()->json($query->latest('date_paiement')->paginate(25));
    }

    /**
     * INITIER UN PAIEMENT VIA PAYDUNYA
     */
    public function initierPaiementMobile(Request $request): JsonResponse
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'montant' => 'required|integer|min:100',
            'mode' => 'required|in:orange_money,mtn_money,moov_money,wave,carte_bancaire',
        ]);

        $inscription = Inscription::with('eleve')->findOrFail($request->inscription_id);
        $reference = Paiement::genererReference($request->user()->etablissement_id);

        // Créer le paiement en attente
        $paiement = Paiement::create([
            'etablissement_id' => $request->user()->etablissement_id,
            'inscription_id' => $inscription->id,
            'eleve_id' => $inscription->eleve_id,
            'reference' => $reference,
            'montant' => $request->montant,
            'date_paiement' => today(),
            'mode' => $request->mode,
            'statut' => 'en_attente',
        ]);

        // Appeler PayDunya pour créer la facture
        $resultat = $this->payDunya->creerFacture(
            montant: $request->montant,
            description: "Scolarité {$inscription->eleve->nom_complet} - {$reference}",
            reference: $reference,
            callbackUrl: route('api.paiements.callback.paydunya'),
        );

        if ($resultat['success']) {
            $paiement->update([
                'paydunya_token' => $resultat['token'],
                'paydunya_invoice_url' => $resultat['invoice_url'],
            ]);

            return response()->json([
                'success' => true,
                'paiement_id' => $paiement->id,
                'reference' => $reference,
                'lien_paiement' => $resultat['invoice_url'],
                'token' => $resultat['token'],
                'message' => 'Lien de paiement généré. Redirigez le parent vers ce lien.',
            ]);
        }

        $paiement->update(['statut' => 'echoue', 'paydunya_response_text' => $resultat['error'] ?? 'Erreur']);
        return response()->json(['error' => 'Erreur PayDunya: ' . ($resultat['error'] ?? 'Inconnue')], 500);
    }

    /**
     * Initiation paiement pour un parent (établissement = celui de l'inscription).
     */
    public function initierPaiementMobileForParent(Request $request, Inscription $inscription): JsonResponse
    {
        $request->validate([
            'montant' => 'required|integer|min:100',
            'mode' => 'required|in:orange_money,mtn_money,moov_money,wave,carte_bancaire',
        ]);

        $etabId = $inscription->etablissement_id;
        $reference = Paiement::genererReference($etabId);

        $paiement = Paiement::create([
            'etablissement_id' => $etabId,
            'inscription_id' => $inscription->id,
            'eleve_id' => $inscription->eleve_id,
            'reference' => $reference,
            'montant' => $request->montant,
            'date_paiement' => today(),
            'mode' => $request->mode,
            'statut' => 'en_attente',
        ]);

        $resultat = $this->payDunya->creerFacture(
            montant: (int) $request->montant,
            description: "Scolarité {$inscription->eleve->nom_complet} - {$reference}",
            reference: $reference,
            callbackUrl: route('api.paiements.callback.paydunya'),
        );

        if ($resultat['success']) {
            $paiement->update([
                'paydunya_token' => $resultat['token'],
                'paydunya_invoice_url' => $resultat['invoice_url'],
            ]);

            return response()->json([
                'success' => true,
                'paiement_id' => $paiement->id,
                'reference' => $reference,
                'lien_paiement' => $resultat['invoice_url'],
                'token' => $resultat['token'],
                'message' => 'Lien de paiement généré.',
            ]);
        }

        $paiement->update(['statut' => 'echoue', 'paydunya_response_text' => $resultat['error'] ?? 'Erreur']);

        return response()->json(['error' => 'Erreur PayDunya: ' . ($resultat['error'] ?? 'Inconnue')], 500);
    }

    /**
     * CALLBACK PAYDUNYA (webhook appelé par PayDunya)
     */
    public function callbackPayDunya(Request $request): JsonResponse
    {
        $data = $request->all();
        $token = $data['data']['invoice']['token'] ?? null;

        if (!$token) return response()->json(['error' => 'Token manquant'], 400);

        $paiement = Paiement::where('paydunya_token', $token)->first();
        if (!$paiement) return response()->json(['error' => 'Paiement non trouvé'], 404);

        // Vérifier le statut auprès de PayDunya
        $verification = $this->payDunya->verifierFacture($token);

        $paiement->update([
            'statut' => $verification['status'] === 'completed' ? 'confirme' : 'echoue',
            'paydunya_response_code' => $verification['response_code'] ?? null,
            'paydunya_response_text' => $verification['response_text'] ?? null,
            'paydunya_metadata' => $data,
            'paydunya_callback_at' => now(),
        ]);

        if ($verification['status'] === 'completed') {
            // Mettre à jour l'échéance si liée
            if ($paiement->echeance_id) {
                $echeance = $paiement->echeance;
                $echeance->increment('montant_paye', $paiement->montant);
                $echeance->update([
                    'reste_a_payer' => max(0, $echeance->montant - $echeance->montant_paye),
                    'statut' => $echeance->montant_paye >= $echeance->montant ? 'paye' : 'partiellement_paye',
                ]);
            }

            // Générer le numéro de reçu
            $numRecu = sprintf('REC-%s-%04d', now()->format('Y-m'), Paiement::where('etablissement_id', $paiement->etablissement_id)->confirmes()->whereMonth('date_paiement', now()->month)->count());
            $paiement->update(['numero_recu' => $numRecu]);

            // TODO: Envoyer SMS de confirmation au parent
            // TODO: Générer PDF du reçu
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * ENREGISTRER UN PAIEMENT EN ESPÈCES
     */
    public function enregistrerEspeces(Request $request): JsonResponse
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'montant' => 'required|integer|min:100',
            'observations' => 'nullable|string',
        ]);

        $inscription = Inscription::findOrFail($request->inscription_id);
        $reference = Paiement::genererReference($request->user()->etablissement_id);

        $paiement = Paiement::create([
            'etablissement_id' => $request->user()->etablissement_id,
            'inscription_id' => $inscription->id,
            'eleve_id' => $inscription->eleve_id,
            'encaisse_par' => $request->user()->id,
            'reference' => $reference,
            'montant' => $request->montant,
            'date_paiement' => today(),
            'mode' => 'especes',
            'statut' => 'confirme',
            'numero_recu' => sprintf('REC-%s-%04d', now()->format('Y-m'), rand(1, 9999)),
            'observations' => $request->observations,
        ]);

        return response()->json($paiement->load('eleve:id,nom,prenom'), 201);
    }

    /**
     * TABLEAU DE RECOUVREMENT
     */
    public function recouvrement(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;
        $annee = $request->user()->etablissement->anneesScolaires()->enCours()->first();
        if (!$annee) return response()->json(['error' => 'Aucune année en cours'], 404);

        $inscriptions = Inscription::where('etablissement_id', $etab)
            ->where('annee_scolaire_id', $annee->id)
            ->where('statut', 'validee')
            ->with(['eleve:id,nom,prenom,matricule_interne', 'classe:id,nom'])
            ->get();

        $totalAttendu = $inscriptions->sum('montant_net');
        $details = $inscriptions->map(function ($insc) {
            $paye = $insc->montantPaye();
            return [
                'eleve' => $insc->eleve->nom_complet,
                'matricule' => $insc->eleve->matricule_interne,
                'classe' => $insc->classe->nom,
                'scolarite' => $insc->montant_net,
                'paye' => $paye,
                'reste' => $insc->montant_net - $paye,
                'taux' => $insc->tauxPaiement(),
                'statut' => $paye >= $insc->montant_net ? 'a_jour' : ($paye > 0 ? 'partiel' : 'impaye'),
            ];
        });

        $totalPaye = $details->sum('paye');

        return response()->json([
            'annee' => $annee->libelle,
            'resume' => [
                'total_attendu' => $totalAttendu,
                'total_paye' => $totalPaye,
                'reste' => $totalAttendu - $totalPaye,
                'taux_recouvrement' => $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 1) : 0,
                'eleves_a_jour' => $details->where('statut', 'a_jour')->count(),
                'eleves_partiels' => $details->where('statut', 'partiel')->count(),
                'eleves_impayes' => $details->where('statut', 'impaye')->count(),
            ],
            'details' => $details->sortBy('reste')->values(),
        ]);
    }
}
