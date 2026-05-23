<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Trimestre;
use App\Services\Presence\PresenceBilanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur de bilan des présences pour la direction.
 *
 * Endpoints web :
 *   GET /presences/bilan                          → dashboard bilans (sélection période + classe)
 *   GET /presences/bilan/classe/{classe}          → bilan détaillé d'une classe
 *   GET /presences/bilan/eleve/{eleve}            → bilan détaillé d'un élève
 *
 * Endpoints JSON (pour AJAX et exports) :
 *   GET /presences/bilan/api/classe/{classe}      → JSON bilan classe
 *   GET /presences/bilan/api/eleve/{eleve}        → JSON bilan élève
 *   GET /presences/bilan/api/etablissement        → JSON bilan global
 */
class PresenceBilanController extends Controller
{
    public function __construct(private PresenceBilanService $service) {}

    // ── Vues web ─────────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        $etabId = (int) $request->user()->ecoleActiveId();
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $trimestreId = (int) $request->input('trimestre_id', $trimestres->firstWhere('en_cours', true)?->id ?? $trimestres->first()?->id);
        $trimestre = $trimestres->firstWhere('id', $trimestreId);

        [$debut, $fin, $label] = $this->resolvePeriode($request, $annee, $trimestre);

        $classes = Classe::where('etablissement_id', $etabId)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->orderBy('nom')
            ->get(['id', 'nom']);

        $bilan = $this->service->bilanEtablissement($etabId, $debut, $fin, $label);

        return view('admin.rh.presences.bilan', [
            'annee'       => $annee,
            'trimestres'  => $trimestres,
            'trimestre_id' => $trimestreId,
            'classes'     => $classes,
            'periode'     => ['debut' => $debut, 'fin' => $fin, 'label' => $label],
            'bilan'       => $bilan,
        ]);
    }

    public function bilanClasse(Request $request, Classe $classe)
    {
        $etabId = (int) $request->user()->ecoleActiveId();
        abort_unless((int) $classe->etablissement_id === $etabId, 404);

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);
        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $trimestreId = (int) $request->input('trimestre_id', $trimestres->firstWhere('en_cours', true)?->id ?? $trimestres->first()?->id);
        $trimestre = $trimestres->firstWhere('id', $trimestreId);

        [$debut, $fin, $label] = $this->resolvePeriode($request, $annee, $trimestre);

        $bilan = $this->service->bilanClasse($classe, $debut, $fin, $label);

        return view('admin.rh.presences.bilan-classe', [
            'classe'      => $classe,
            'annee'       => $annee,
            'trimestres'  => $trimestres,
            'trimestre_id' => $trimestreId,
            'periode'     => ['debut' => $debut, 'fin' => $fin, 'label' => $label],
            'bilan'       => $bilan,
        ]);
    }

    public function bilanEleve(Request $request, Eleve $eleve)
    {
        $etabId = (int) $request->user()->ecoleActiveId();
        abort_unless((int) $eleve->etablissement_id === $etabId, 404);

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);
        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $trimestreId = (int) $request->input('trimestre_id', $trimestres->firstWhere('en_cours', true)?->id ?? $trimestres->first()?->id);
        $trimestre = $trimestres->firstWhere('id', $trimestreId);

        [$debut, $fin, $label] = $this->resolvePeriode($request, $annee, $trimestre);

        $bilan = $this->service->bilanEleve($eleve, $debut, $fin, $label);

        // Bilans par trimestre pour comparaison
        $bilansTrimestres = $trimestres->map(function ($t) use ($eleve) {
            $b = $this->service->bilanEleveTrimestre($eleve, $t);
            return [
                'trimestre'      => $t->only(['id', 'numero', 'libelle']),
                'absents'        => $b['absents'],
                'retards'        => $b['retards'],
                'justifies'      => $b['justifies'],
                'heures_absence' => $b['heures_absence'],
            ];
        });

        // Bilan annuel
        $bilanAnnee = $annee ? $this->service->bilanEleveAnnee($eleve, $annee) : null;

        return view('admin.rh.presences.bilan-eleve', [
            'eleve'            => $eleve,
            'annee'            => $annee,
            'trimestres'       => $trimestres,
            'trimestre_id'     => $trimestreId,
            'periode'          => ['debut' => $debut, 'fin' => $fin, 'label' => $label],
            'bilan'            => $bilan,
            'bilans_trimestres' => $bilansTrimestres,
            'bilan_annee'      => $bilanAnnee,
        ]);
    }

    // ── JSON / API ───────────────────────────────────────────────────────────

    public function apiBilanClasse(Request $request, Classe $classe): JsonResponse
    {
        $etabId = (int) $request->user()->ecoleActiveId();
        abort_unless((int) $classe->etablissement_id === $etabId, 404);

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);
        $trimestre = $request->filled('trimestre_id')
            ? Trimestre::find($request->trimestre_id)
            : null;

        [$debut, $fin, $label] = $this->resolvePeriode($request, $annee, $trimestre);

        return response()->json([
            'success' => true,
            'data'    => $this->service->bilanClasse($classe, $debut, $fin, $label),
        ]);
    }

    public function apiBilanEleve(Request $request, Eleve $eleve): JsonResponse
    {
        $etabId = (int) $request->user()->ecoleActiveId();
        abort_unless((int) $eleve->etablissement_id === $etabId, 404);

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);
        $trimestre = $request->filled('trimestre_id')
            ? Trimestre::find($request->trimestre_id)
            : null;

        [$debut, $fin, $label] = $this->resolvePeriode($request, $annee, $trimestre);

        return response()->json([
            'success' => true,
            'data'    => $this->service->bilanEleve($eleve, $debut, $fin, $label),
        ]);
    }

    public function apiBilanEtablissement(Request $request): JsonResponse
    {
        $etabId = (int) $request->user()->ecoleActiveId();
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);
        $trimestre = $request->filled('trimestre_id')
            ? Trimestre::find($request->trimestre_id)
            : null;

        [$debut, $fin, $label] = $this->resolvePeriode($request, $annee, $trimestre);

        return response()->json([
            'success' => true,
            'data'    => $this->service->bilanEtablissement($etabId, $debut, $fin, $label),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Détermine la période de calcul depuis :
     *   - date_debut + date_fin (priorité haute)
     *   - trimestre_id
     *   - sinon année scolaire en cours (par défaut)
     */
    private function resolvePeriode(Request $request, ?AnneeScolaire $annee, ?Trimestre $trimestre): array
    {
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            return [
                Carbon::parse($request->date_debut),
                Carbon::parse($request->date_fin),
                'Période ' . $request->date_debut . ' → ' . $request->date_fin,
            ];
        }

        if ($trimestre) {
            return [
                Carbon::parse($trimestre->date_debut),
                Carbon::parse($trimestre->date_fin),
                $trimestre->libelle ?? "Trimestre {$trimestre->numero}",
            ];
        }

        if ($annee) {
            return [
                Carbon::parse($annee->date_debut),
                Carbon::parse($annee->date_fin),
                "Année {$annee->libelle}",
            ];
        }

        // Fallback : derniers 30 jours
        return [now()->subDays(30), now(), '30 derniers jours'];
    }
}
