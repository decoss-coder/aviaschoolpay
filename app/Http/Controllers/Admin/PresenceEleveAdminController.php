<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Niveau;
use App\Models\PresenceEleve;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord direction / éducateur pour la gestion des présences élèves.
 *
 * Workflow :
 *  - Vue d'ensemble (dashboard) : stats + classes les plus impactées + alertes
 *  - Liste filtrable de toutes les présences/absences
 *  - Vue par classe / par élève
 *  - Justifier / traiter une absence
 */
class PresenceEleveAdminController extends Controller
{
    private function etabId(Request $request): int
    {
        return (int) $request->user()->etablissement_id;
    }

    /**
     * Tableau de bord global du jour.
     */
    public function dashboard(Request $request)
    {
        $etabId = $this->etabId($request);
        $date   = $request->input('date', today()->toDateString());
        $dateC  = Carbon::parse($date);
        $semaine= $dateC->copy()->startOfWeek();

        // Base : présences du jour pour cet établissement
        $baseJour = PresenceEleve::join('classes', 'classes.id', '=', 'presences_eleves.classe_id')
            ->where('classes.etablissement_id', $etabId)
            ->whereDate('presences_eleves.date', $date);

        $statsJour = (clone $baseJour)
            ->selectRaw('presences_eleves.statut, COUNT(*) as n')
            ->groupBy('presences_eleves.statut')
            ->pluck('n', 'statut');

        // Absences par classe (top 10)
        $absencesParClasse = PresenceEleve::join('classes', 'classes.id', '=', 'presences_eleves.classe_id')
            ->where('classes.etablissement_id', $etabId)
            ->whereBetween('presences_eleves.date', [$semaine->toDateString(), $semaine->copy()->addDays(5)->toDateString()])
            ->where('presences_eleves.statut', 'absent')
            ->selectRaw('classes.id as classe_id, classes.nom as classe_nom, COUNT(*) as nb_abs')
            ->groupBy('classes.id', 'classes.nom')
            ->orderByDesc('nb_abs')
            ->limit(10)
            ->get();

        // Top élèves avec le plus d'absences sur la semaine
        $topElevesAbsents = PresenceEleve::join('eleves', 'eleves.id', '=', 'presences_eleves.eleve_id')
            ->join('classes', 'classes.id', '=', 'presences_eleves.classe_id')
            ->where('classes.etablissement_id', $etabId)
            ->whereBetween('presences_eleves.date', [$semaine->toDateString(), $semaine->copy()->addDays(5)->toDateString()])
            ->where('presences_eleves.statut', 'absent')
            ->selectRaw('eleves.id, eleves.nom, eleves.prenom, eleves.matricule_desps, eleves.matricule_interne, classes.nom as classe_nom, COUNT(*) as nb_abs, SUM(CASE WHEN presences_eleves.justifie THEN 1 ELSE 0 END) as nb_justifiees')
            ->groupBy('eleves.id', 'eleves.nom', 'eleves.prenom', 'eleves.matricule_desps', 'eleves.matricule_interne', 'classes.nom')
            ->orderByDesc('nb_abs')
            ->limit(12)
            ->get();

        // Absences non traitées (à justifier)
        $aTraiter = PresenceEleve::join('classes', 'classes.id', '=', 'presences_eleves.classe_id')
            ->where('classes.etablissement_id', $etabId)
            ->where('presences_eleves.statut', 'absent')
            ->whereNull('presences_eleves.traite_at')
            ->where('presences_eleves.justifie', false)
            ->count();

        // Évolution sur 7 jours
        $derniers7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = today()->subDays($i);
            $derniers7[] = [
                'date'    => $d->toDateString(),
                'libelle' => $d->locale('fr')->isoFormat('ddd D/MM'),
                'absents' => PresenceEleve::join('classes', 'classes.id', '=', 'presences_eleves.classe_id')
                    ->where('classes.etablissement_id', $etabId)
                    ->whereDate('presences_eleves.date', $d->toDateString())
                    ->where('presences_eleves.statut', 'absent')->count(),
                'retards' => PresenceEleve::join('classes', 'classes.id', '=', 'presences_eleves.classe_id')
                    ->where('classes.etablissement_id', $etabId)
                    ->whereDate('presences_eleves.date', $d->toDateString())
                    ->where('presences_eleves.statut', 'retard')->count(),
            ];
        }

        return view('admin.rh.presences.dashboard', compact(
            'date', 'semaine', 'statsJour', 'absencesParClasse',
            'topElevesAbsents', 'aTraiter', 'derniers7'
        ));
    }

    /**
     * Liste filtrable de toutes les présences (page admin).
     */
    public function index(Request $request)
    {
        $etabId = $this->etabId($request);

        $query = PresenceEleve::query()
            ->join('classes', 'classes.id', '=', 'presences_eleves.classe_id')
            ->join('eleves',  'eleves.id',  '=', 'presences_eleves.eleve_id')
            ->where('classes.etablissement_id', $etabId)
            ->with(['eleve:id,nom,prenom,matricule_desps,matricule_interne,classe_id',
                    'classe:id,nom', 'enseignant:id,nom,prenom',
                    'creneau:id,heure_debut,heure_fin', 'saisiePar:id,nom,prenom'])
            ->select('presences_eleves.*');

        if ($d = $request->input('date'))           $query->whereDate('presences_eleves.date', $d);
        if ($d1 = $request->input('date_debut'))    $query->whereDate('presences_eleves.date', '>=', $d1);
        if ($d2 = $request->input('date_fin'))      $query->whereDate('presences_eleves.date', '<=', $d2);
        if ($s = $request->input('statut'))         $query->where('presences_eleves.statut', $s);
        if ($c = $request->input('classe_id'))      $query->where('presences_eleves.classe_id', $c);
        if ($e = $request->input('eleve_id'))       $query->where('presences_eleves.eleve_id', $e);
        if ($request->input('justifie') === '1')    $query->where('presences_eleves.justifie', true);
        if ($request->input('justifie') === '0')    $query->where('presences_eleves.justifie', false);
        if ($request->input('traite') === '1')      $query->whereNotNull('presences_eleves.traite_at');
        if ($request->input('traite') === '0')      $query->whereNull('presences_eleves.traite_at');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('eleves.nom', 'like', "%{$search}%")
                  ->orWhere('eleves.prenom', 'like', "%{$search}%")
                  ->orWhere('eleves.matricule_desps', 'like', "%{$search}%")
                  ->orWhere('eleves.matricule_interne', 'like', "%{$search}%");
            });
        }

        $presences = $query->orderByDesc('presences_eleves.date')
            ->orderByDesc('presences_eleves.created_at')
            ->paginate(40)
            ->withQueryString();

        $classes = Classe::where('etablissement_id', $etabId)
            ->where('active', true)
            ->orderBy('nom')->get();

        return view('admin.rh.presences.index', compact('presences', 'classes'));
    }

    /**
     * Détail historique d'un élève (toutes ses présences + cumul).
     */
    public function showEleve(Request $request, Eleve $eleve)
    {
        abort_unless($eleve->etablissement_id === $this->etabId($request), 404);

        $semaine = $request->input('semaine')
            ? Carbon::parse($request->semaine)->startOfWeek()
            : now()->startOfWeek();

        $presences = PresenceEleve::where('eleve_id', $eleve->id)
            ->with(['classe:id,nom', 'creneau:id,heure_debut,heure_fin',
                    'enseignant:id,nom,prenom', 'matiere:id,nom,code',
                    'saisiePar:id,nom,prenom', 'traitePar:id,nom,prenom'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();

        $cumul = [
            'absent'   => PresenceEleve::where('eleve_id', $eleve->id)->where('statut', 'absent')->count(),
            'retard'   => PresenceEleve::where('eleve_id', $eleve->id)->where('statut', 'retard')->count(),
            'present'  => PresenceEleve::where('eleve_id', $eleve->id)->where('statut', 'present')->count(),
            'excuse'   => PresenceEleve::where('eleve_id', $eleve->id)->where('statut', 'excuse')->count(),
            'dispense' => PresenceEleve::where('eleve_id', $eleve->id)->where('statut', 'dispense')->count(),
            'absences_justifiees' => PresenceEleve::where('eleve_id', $eleve->id)
                ->where('statut', 'absent')->where('justifie', true)->count(),
            'absences_non_justifiees' => PresenceEleve::where('eleve_id', $eleve->id)
                ->where('statut', 'absent')->where('justifie', false)->count(),
        ];

        return view('admin.rh.presences.eleve', compact('eleve', 'presences', 'cumul'));
    }

    /**
     * Justifier une absence + marquer traité.
     */
    public function justifier(Request $request, PresenceEleve $presence)
    {
        $presence->loadMissing('classe');
        abort_unless($presence->classe?->etablissement_id === $this->etabId($request), 404);

        $data = $request->validate([
            'justifie'      => 'required|boolean',
            'motif'         => 'nullable|string|max:255',
            'justification' => 'nullable|string|max:2000',
        ]);

        $presence->update([
            'justifie'      => (bool) $data['justifie'],
            'motif'         => $data['motif'] ?? $presence->motif,
            'justification' => $data['justification'] ?? $presence->justification,
            'traite_par'    => $request->user()->id,
            'traite_at'     => now(),
        ]);

        return back()->with('success', 'Absence traitée.');
    }

    /**
     * Marquer comme traité sans justifier (refus de justification).
     */
    public function traiter(Request $request, PresenceEleve $presence)
    {
        $presence->loadMissing('classe');
        abort_unless($presence->classe?->etablissement_id === $this->etabId($request), 404);

        $request->validate([
            'observation' => 'nullable|string|max:2000',
        ]);

        $presence->update([
            'observation' => $request->input('observation', $presence->observation),
            'traite_par'  => $request->user()->id,
            'traite_at'   => now(),
        ]);

        return back()->with('success', 'Marqué comme traité.');
    }
}
