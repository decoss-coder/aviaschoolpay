<?php

namespace App\Http\Controllers;

use App\Models\AlertePointage;
use App\Models\Enseignant;
use App\Models\Pointage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PointageWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $date = $request->date ?: today()->toDateString();

        $query = Pointage::query()
            ->where('etablissement_id', $etab->id)
            ->with([
                'enseignant.user',
                'salle',
                'qrCode',
                'alertes',
                'emploiDuTemps.creneau',
                'emploiDuTemps.matiere',
                'emploiDuTemps.classe',
            ])
            ->whereDate('date', $date);

        if ($request->filled('search')) {
            $s = trim((string) $request->search);

            $query->where(function ($q) use ($s) {
                $q->whereHas('enseignant', function ($sub) use ($s) {
                    $sub->where('nom', 'like', "%{$s}%")
                        ->orWhere('prenom', 'like', "%{$s}%")
                        ->orWhere('matricule_mena', 'like', "%{$s}%")
                        ->orWhere('telephone', 'like', "%{$s}%")
                        ->orWhere('specialite', 'like', "%{$s}%");
                })
                ->orWhere('token_validation', 'like', "%{$s}%")
                ->orWhere('observations', 'like', "%{$s}%");
            });
        }

        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', (int) $request->enseignant_id);
        }

        if ($request->filled('type_scan')) {
            $query->where('type_scan', $request->type_scan);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('methode')) {
            $query->where('methode', $request->methode);
        }

        if ($request->filled('gps')) {
            if ($request->gps === 'valide') {
                $query->where('gps_valide', true);
            } elseif ($request->gps === 'invalide') {
                $query->where('gps_valide', false);
            }
        }

        if ($request->filled('edt')) {
            if ($request->edt === 'conforme') {
                $query->where('conforme_emploi_temps', true);
            } elseif ($request->edt === 'non_conforme') {
                $query->where('conforme_emploi_temps', false);
            }
        }

        if ($request->filled('spoofing')) {
            if ($request->spoofing === 'oui') {
                $query->where('spoofing_detecte', true);
            } elseif ($request->spoofing === 'non') {
                $query->where('spoofing_detecte', false);
            }
        }

        if ($request->filled('alertes')) {
            if ($request->alertes === 'avec') {
                $query->whereHas('alertes');
            } elseif ($request->alertes === 'sans') {
                $query->whereDoesntHave('alertes');
            }
        }

        if ($request->filled('anomalie') && $request->anomalie === 'oui') {
            $query->where(function ($q) {
                $q->where('statut', Pointage::STATUT_HORS_ZONE)
                    ->orWhere('spoofing_detecte', true)
                    ->orWhere('gps_valide', false)
                    ->orWhere('token_valide', false)
                    ->orWhere('conforme_emploi_temps', false);
            });
        }

        if ($request->filled('cahier')) {
            match ($request->cahier) {
                'valide' => $query->where('cahier_texte_validated', true),
                'non_valide' => $query->whereNotNull('cahier_texte_path')->where('cahier_texte_validated', false),
                'manquant' => $query->where('cahier_texte_status', Pointage::CAHIER_EN_ATTENTE),
                default => null,
            };
        }

        if ($request->filled('validation_finale')) {
            $query->where('validation_finale', $request->validation_finale);
        }

        $statsBase = clone $query;

        $stats = [
            'total' => (clone $statsBase)->count(),
            'arrivees' => (clone $statsBase)->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)->count(),
            'departs' => (clone $statsBase)->where('type_scan', Pointage::TYPE_SCAN_DEPART)->count(),
            'presents' => (clone $statsBase)->where('statut', Pointage::STATUT_PRESENT)->count(),
            'retards' => (clone $statsBase)->where('statut', Pointage::STATUT_RETARD)->count(),
            'absents' => (clone $statsBase)->where('statut', Pointage::STATUT_ABSENT)->count(),
            'hors_zone' => (clone $statsBase)->where('statut', Pointage::STATUT_HORS_ZONE)->count(),
            'anomalies' => (clone $statsBase)->where(function ($q) {
                $q->where('statut', Pointage::STATUT_HORS_ZONE)
                    ->orWhere('spoofing_detecte', true)
                    ->orWhere('gps_valide', false)
                    ->orWhere('token_valide', false)
                    ->orWhere('conforme_emploi_temps', false);
            })->count(),
            'alertes' => (clone $statsBase)->whereHas('alertes')->count(),
            'cahier_envoyes' => (clone $statsBase)->whereNotNull('cahier_texte_path')->count(),
            'cahier_valides' => (clone $statsBase)->where('cahier_texte_validated', true)->count(),
            'cahier_non_valides' => (clone $statsBase)->whereNotNull('cahier_texte_path')->where('cahier_texte_validated', false)->count(),
        ];

        $pointages = $query
            ->orderByDesc('date')
            ->orderByDesc('heure_scan')
            ->paginate(25)
            ->withQueryString();

        $enseignants = Enseignant::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $dateCarbon = \Carbon\Carbon::parse($date);
        $filterKeys = ['search', 'enseignant_id', 'type_scan', 'statut', 'methode', 'anomalie', 'cahier', 'validation_finale', 'gps', 'edt', 'spoofing', 'alertes'];
        $filterQuery = request()->only($filterKeys);
        $activeFilterCount = collect($filterQuery)->filter(fn ($v) => $v !== null && $v !== '')->count();

        $tauxCahier = ($stats['cahier_envoyes'] ?? 0) > 0
            ? (int) round((($stats['cahier_valides'] ?? 0) / $stats['cahier_envoyes']) * 100)
            : null;

        return view('pointage.index', compact(
            'pointages',
            'stats',
            'enseignants',
            'date',
            'dateCarbon',
            'filterQuery',
            'activeFilterCount',
            'tauxCahier',
        ));
    }

    public function show(Request $request, int $pointage)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $pointage = Pointage::query()
            ->where('etablissement_id', $etab->id)
            ->with([
                'enseignant.user',
                'salle',
                'qrCode',
                'emploiDuTemps.creneau',
                'emploiDuTemps.matiere',
                'emploiDuTemps.classe',
                'alertes.traiteePar',
                'alertes.enseignant',
            ])
            ->findOrFail($pointage);

        $validation = $pointage->cahierValidation();
        $scoreIa = $validation['score'] ?? null;

        return view('pointage.show', compact('pointage', 'validation', 'scoreIa'));
    }

    public function traiterAlerte(Request $request, int $alerte)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $validated = $request->validate([
            'commentaire_traitement' => ['nullable', 'string', 'max:1000'],
        ]);

        $alerte = AlertePointage::query()
            ->where('etablissement_id', $etab->id)
            ->findOrFail($alerte);

        $alerte->update([
            'lue' => true,
            'traitee' => true,
            'traitee_par' => $request->user()->id,
            'commentaire_traitement' => $validated['commentaire_traitement'] ?? null,
        ]);

        return back()->with('success', 'Alerte marquée comme traitée.');
    }

    public function selfie(Request $request, int $pointage)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $pointage = Pointage::query()
            ->where('etablissement_id', $etab->id)
            ->findOrFail($pointage);

        abort_if(empty($pointage->selfie_path), 404);
        abort_unless(Storage::disk('public')->exists($pointage->selfie_path), 404);

        return response()->file(Storage::disk('public')->path($pointage->selfie_path));
    }

    public function cahierTexte(Request $request, int $pointage)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $pointage = Pointage::query()
            ->where('etablissement_id', $etab->id)
            ->findOrFail($pointage);

        abort_if(blank($pointage->cahier_texte_path), 404);
        abort_unless(Storage::disk('public')->exists($pointage->cahier_texte_path), 404);

        return response()->file(Storage::disk('public')->path($pointage->cahier_texte_path));
    }

    public function rapport(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $periode = (int) $request->input('periode', 30);
        if (! in_array($periode, [7, 30, 90], true)) {
            $periode = 30;
        }

        $debut = today()->subDays($periode - 1);
        $fin = today();

        $baseQuery = Pointage::query()
            ->where('etablissement_id', $etab->id)
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()]);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'arrivees' => (clone $baseQuery)->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)->count(),
            'presents' => (clone $baseQuery)->where('statut', Pointage::STATUT_PRESENT)->count(),
            'retards' => (clone $baseQuery)->where('statut', Pointage::STATUT_RETARD)->count(),
            'hors_zone' => (clone $baseQuery)->where('statut', Pointage::STATUT_HORS_ZONE)->count(),
            'anomalies' => (clone $baseQuery)->where(function ($q) {
                $q->where('statut', Pointage::STATUT_HORS_ZONE)
                    ->orWhere('spoofing_detecte', true)
                    ->orWhere('gps_valide', false)
                    ->orWhere('conforme_emploi_temps', false);
            })->count(),
            'cahier_valides' => (clone $baseQuery)->where('cahier_texte_validated', true)->count(),
            'alertes' => AlertePointage::query()
                ->where('etablissement_id', $etab->id)
                ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
                ->count(),
        ];

        $parJourSemaine = (clone $baseQuery)
            ->selectRaw('DAYOFWEEK(date) as dow, COUNT(*) as total')
            ->groupBy('dow')
            ->orderBy('dow')
            ->pluck('total', 'dow');

        $joursLabels = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        $chartJourSemaine = [
            'labels' => $joursLabels,
            'data' => collect(range(1, 7))->map(fn ($dow) => (int) ($parJourSemaine[$dow] ?? 0))->values()->all(),
        ];

        $parDate = (clone $baseQuery)
            ->selectRaw('date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartEvolution = [
            'labels' => $parDate->map(fn ($r) => Carbon::parse($r->date)->format('d/m'))->all(),
            'data' => $parDate->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];

        $parStatut = (clone $baseQuery)
            ->select('statut', DB::raw('COUNT(*) as total'))
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $chartStatuts = [
            'labels' => $parStatut->keys()->map(fn ($s) => match ($s) {
                Pointage::STATUT_PRESENT => 'Présent',
                Pointage::STATUT_RETARD => 'Retard',
                Pointage::STATUT_ABSENT => 'Absent',
                Pointage::STATUT_HORS_ZONE => 'Hors zone',
                Pointage::STATUT_ANOMALIE => 'Anomalie',
                default => ucfirst((string) $s),
            })->all(),
            'data' => $parStatut->values()->map(fn ($v) => (int) $v)->all(),
        ];

        $arriveesParEnseignant = (clone $baseQuery)
            ->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)
            ->selectRaw('enseignant_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) as presents", [Pointage::STATUT_PRESENT])
            ->selectRaw("SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) as retards", [Pointage::STATUT_RETARD])
            ->selectRaw("SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) as hors_zone", [Pointage::STATUT_HORS_ZONE])
            ->groupBy('enseignant_id')
            ->get()
            ->keyBy('enseignant_id');

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $enseignants = Enseignant::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->when($annee, fn ($q) => $q->affectesCetteAnnee($annee->id))
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $classement = $enseignants->map(function (Enseignant $ens) use ($arriveesParEnseignant) {
            $row = $arriveesParEnseignant->get($ens->id);
            $total = (int) ($row->total ?? 0);
            $presents = (int) ($row->presents ?? 0);
            $retards = (int) ($row->retards ?? 0);
            $horsZone = (int) ($row->hors_zone ?? 0);

            $score = $total > 0
                ? max(0, min(100, (int) round(($presents / $total) * 100 - ($retards * 5) - ($horsZone * 15))))
                : null;

            return [
                'enseignant' => $ens,
                'total' => $total,
                'presents' => $presents,
                'retards' => $retards,
                'hors_zone' => $horsZone,
                'score' => $score,
            ];
        })->sortByDesc(fn ($r) => $r['score'] ?? -1)->values();

        $moyenneScore = $classement->filter(fn ($r) => $r['score'] !== null)->avg('score');

        return view('pointage.rapport', compact(
            'periode',
            'debut',
            'fin',
            'stats',
            'chartJourSemaine',
            'chartEvolution',
            'chartStatuts',
            'classement',
            'moyenneScore',
            'annee',
        ));
    }
}