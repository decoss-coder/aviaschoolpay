<?php
// ══════════════════════════════════════════════════════════════
// app/Http/Controllers/Api/DashboardController.php
// MODULE 1 — TABLEAU DE BORD INTELLIGENT
// ══════════════════════════════════════════════════════════════
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Etablissement, Eleve, Enseignant, Inscription, Paiement, Pointage, MoyenneGenerale};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement;
        $annee = $etab->anneesScolaires()->enCours()->first();
        if (!$annee) return response()->json(['error' => 'Aucune année scolaire en cours'], 404);

        $trimestre = $annee->trimestres()->enCours()->first();

        // Stats élèves
        $totalEleves = Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)->where('statut', 'validee')->count();

        // Stats enseignants aujourd'hui
        $totalEnseignants = $etab->enseignants()->actif()->count();
        $presentsAujourdhui = Pointage::where('etablissement_id', $etab->id)
            ->where('date', today())->where('type_scan', 'arrivee')
            ->whereIn('statut', ['present', 'retard'])->distinct('enseignant_id')->count();

        // Stats financières
        $totalAttendu = Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)->where('statut', 'validee')->sum('montant_net');
        $totalPaye = Paiement::where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->whereHas('inscription', fn($q) => $q->where('annee_scolaire_id', $annee->id))->sum('montant');
        $tauxRecouvrement = $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 1) : 0;

        // Moyenne générale
        $moyenneGenerale = $trimestre
            ? MoyenneGenerale::where('trimestre_id', $trimestre->id)
                ->whereHas('classe', fn($q) => $q->where('etablissement_id', $etab->id))
                ->avg('moyenne_generale')
            : null;

        // Derniers paiements
        $derniersPaiements = Paiement::where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->with('eleve:id,nom,prenom')
            ->latest('date_paiement')->take(5)->get();

        // Pointages du jour
        $pointagesJour = Pointage::where('etablissement_id', $etab->id)
            ->where('date', today())->where('type_scan', 'arrivee')
            ->with('enseignant:id,nom,prenom,photo_path')
            ->orderBy('heure_scan')->get();

        // Alertes non lues
        $alertes = $etab->load(['alertesPointage' => fn($q) => $q->where('lue', false)->latest()->take(10)]);

        return response()->json([
            'annee_scolaire' => $annee->libelle,
            'trimestre' => $trimestre?->libelle,
            'stats' => [
                'eleves_inscrits' => $totalEleves,
                'enseignants_total' => $totalEnseignants,
                'enseignants_presents' => $presentsAujourdhui,
                'enseignants_absents' => $totalEnseignants - $presentsAujourdhui,
                'taux_recouvrement' => $tauxRecouvrement,
                'total_attendu_fcfa' => $totalAttendu,
                'total_paye_fcfa' => $totalPaye,
                'reste_a_recouvrer_fcfa' => $totalAttendu - $totalPaye,
                'moyenne_generale' => $moyenneGenerale ? round($moyenneGenerale, 2) : null,
            ],
            'derniers_paiements' => $derniersPaiements,
            'pointages_jour' => $pointagesJour,
            'jours_avant_cloture_notes' => $trimestre?->joursAvantCloture(),
        ]);
    }
}
