<?php

namespace App\Http\Controllers;

use App\Exports\CahierAppelExport;
use App\Imports\CahierAppelImport;
use App\Models\Affectation;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\Eleve;
use App\Models\EmploiDuTemps;
use App\Models\Enseignant;
use App\Models\PresenceEleve;
use App\Services\FeuilleDeNote\CahierAppelOcrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CahierAppelController extends Controller
{
    private function enseignant(Request $request): Enseignant
    {
        $ens = $request->user()->enseignantActif();
        abort_if(!$ens, 403, 'Compte enseignant introuvable pour cette école.');
        return $ens;
    }

    private function authorizeClasse(Request $request, Classe $classe, Enseignant $ens): void
    {
        $ok = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->exists();
        abort_if(!$ok, 403, "Vous n'êtes pas affecté à cette classe.");
    }

    private function elevesForClasse(int $classeId)
    {
        return Eleve::where('classe_id', $classeId)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get();
    }

    /**
     * Retourne les séances du prof pour cette classe sur la semaine donnée,
     * sous forme de colonnes (jour, creneau, date, matiere).
     *
     * Si le prof n'a pas d'EDT enregistré (cas seeder partiel), fallback sur
     * tous les créneaux 'cours' configurés × jours lundi-samedi.
     */
    private function seancesSemaine(Enseignant $ens, Classe $classe, Carbon $semaine): array
    {
        $jours = [
            'lundi' => 0, 'mardi' => 1, 'mercredi' => 2,
            'jeudi' => 3, 'vendredi' => 4, 'samedi' => 5,
        ];

        $edt = EmploiDuTemps::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('actif', true)
            ->with(['creneau', 'matiere'])
            ->get();

        // Fallback : aucun EDT → utiliser tous les créneaux 'cours' de l'établissement
        if ($edt->isEmpty()) {
            $creneaux = Creneau::where('etablissement_id', $classe->etablissement_id)
                ->where('type', 'cours')
                ->orderBy('ordre')
                ->get();

            $seances = [];
            foreach ($jours as $jour => $offset) {
                $d = $semaine->copy()->addDays($offset);
                foreach ($creneaux as $cr) {
                    $seances[] = [
                        'jour'    => $jour,
                        'date'    => $d->toDateString(),
                        'creneau_id' => $cr->id,
                        'libelle_jour' => $d->locale('fr')->isoFormat('ddd D/MM'),
                        'libelle_creneau' => substr($cr->heure_debut, 0, 5) . '–' . substr($cr->heure_fin, 0, 5),
                        'matiere' => null,
                    ];
                }
            }
            return $seances;
        }

        $seances = $edt->map(function ($e) use ($semaine, $jours) {
            $offset = $jours[$e->jour] ?? null;
            if ($offset === null) return null;
            $d = $semaine->copy()->addDays($offset);
            return [
                'jour'    => $e->jour,
                'date'    => $d->toDateString(),
                'creneau_id' => $e->creneau_id,
                'libelle_jour' => $d->locale('fr')->isoFormat('ddd D/MM'),
                'libelle_creneau' => $e->creneau
                    ? substr($e->creneau->heure_debut, 0, 5) . '–' . substr($e->creneau->heure_fin, 0, 5)
                    : '—',
                'matiere' => $e->matiere?->code,
            ];
        })->filter()->values()->all();

        // Tri par date puis par heure_debut du créneau
        usort($seances, function ($a, $b) use ($edt) {
            if ($a['date'] !== $b['date']) return strcmp($a['date'], $b['date']);
            $ha = $edt->firstWhere('creneau_id', $a['creneau_id'])?->creneau?->heure_debut ?? '';
            $hb = $edt->firstWhere('creneau_id', $b['creneau_id'])?->creneau?->heure_debut ?? '';
            return strcmp($ha, $hb);
        });

        return $seances;
    }

    /**
     * Hub principal : choix semaine, génération PDF/Excel, imports.
     * Colonnes = séances du prof sur la semaine (EDT-driven).
     */
    public function index(Request $request, Classe $classe)
    {
        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $eleves = $this->elevesForClasse($classe->id);

        $semaine = $request->input('semaine')
            ? Carbon::parse($request->semaine)->startOfWeek()
            : now()->startOfWeek();

        $seances = $this->seancesSemaine($ens, $classe, $semaine);

        $presences = PresenceEleve::where('classe_id', $classe->id)
            ->whereBetween('date', [$semaine->toDateString(), $semaine->copy()->addDays(5)->toDateString()])
            ->get()
            ->keyBy(fn ($p) => $p->eleve_id . '_' . $p->date->toDateString() . '_' . ($p->creneau_id ?? 'NULL'));

        return view('mon-espace.cahier-appel.index',
            compact('ens', 'classe', 'eleves', 'semaine', 'seances', 'presences'));
    }

    /**
     * PDF imprimable de la semaine — affiche les séances du prof
     * et pré-remplit les présences déjà saisies.
     */
    public function pdf(Request $request, Classe $classe)
    {
        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $semaine = $request->input('semaine')
            ? Carbon::parse($request->semaine)->startOfWeek()
            : now()->startOfWeek();

        $seances = $this->seancesSemaine($ens, $classe, $semaine);
        $eleves  = $this->elevesForClasse($classe->id);
        $etab    = \App\Models\Etablissement::find($request->user()->ecoleActiveId());

        $presences = PresenceEleve::where('classe_id', $classe->id)
            ->whereBetween('date', [$semaine->toDateString(), $semaine->copy()->addDays(5)->toDateString()])
            ->get()
            ->keyBy(fn ($p) => $p->eleve_id . '_' . $p->date->toDateString() . '_' . ($p->creneau_id ?? 'NULL'));

        $pdf = Pdf::loadView('mon-espace.cahier-appel.pdf', compact(
            'etab', 'classe', 'ens', 'eleves', 'semaine', 'seances', 'presences'
        ))->setPaper('a4', 'landscape');

        $fname = sprintf('cahier-appel_%s_%s.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            $semaine->format('Y-m-d'));

        return $pdf->download($fname);
    }

    /**
     * Excel template pour saisie informatique.
     */
    public function excel(Request $request, Classe $classe)
    {
        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $semaine = $request->input('semaine')
            ? Carbon::parse($request->semaine)->startOfWeek()
            : now()->startOfWeek();

        $eleves = $this->elevesForClasse($classe->id);
        $etab = \App\Models\Etablissement::find($request->user()->ecoleActiveId());

        $fname = sprintf('cahier-appel_%s_%s.xlsx',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            $semaine->format('Y-m-d'));

        return Excel::download(
            new CahierAppelExport($etab, $classe, $ens, $eleves, $semaine),
            $fname
        );
    }

    /**
     * Import Excel rempli.
     */
    public function importExcel(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'semaine' => 'required|date',
            'fichier' => 'required|file|mimes:xlsx,xls',
        ]);

        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $semaine = Carbon::parse($data['semaine'])->startOfWeek();
        $eleves  = $this->elevesForClasse($classe->id);

        $import = new CahierAppelImport($semaine, $eleves);
        Excel::import($import, $data['fichier']);

        return $this->persistPresences(
            $request, $classe, $ens, $semaine, $import->result()
        );
    }

    /**
     * Form upload photo + OCR.
     */
    public function importOcrForm(Request $request, Classe $classe)
    {
        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        return view('mon-espace.cahier-appel.import-ocr', compact('classe', 'ens'));
    }

    public function importOcrPreview(Request $request, Classe $classe, CahierAppelOcrService $ocr)
    {
        $data = $request->validate([
            'semaine' => 'required|date',
            'image'   => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $semaine = Carbon::parse($data['semaine'])->startOfWeek();
        $eleves  = $this->elevesForClasse($classe->id);

        $path = $request->file('image')->store('ocr-cahier-appel');
        $extracted = $ocr->extract('local', $path, $eleves, $semaine);

        return view('mon-espace.cahier-appel.import-ocr-preview', [
            'classe'    => $classe,
            'eleves'    => $eleves,
            'semaine'   => $semaine,
            'extracted' => $extracted,
            'imagePath' => $path,
        ]);
    }

    public function importOcrConfirm(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'semaine'           => 'required|date',
            'image_path'        => 'required|string',
            'presences'         => 'required|array',
            'presences.*'       => 'array',  // par matricule
            'presences.*.*'     => 'in:present,absent,retard,excuse,dispense,',
        ]);

        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $semaine = Carbon::parse($data['semaine'])->startOfWeek();

        // Convertir presences[matricule][date] = statut → format payload
        $rows = [];
        foreach ($data['presences'] as $matricule => $jours) {
            foreach ($jours as $date => $statut) {
                if ($statut === '') continue;
                $rows[] = [
                    'matricule' => $matricule,
                    'date'      => $date,
                    'periode'   => 'journee',
                    'statut'    => $statut,
                ];
            }
        }

        $result = $this->persistPresences($request, $classe, $ens, $semaine, $rows);

        if (Storage::disk('local')->exists($data['image_path'])) {
            Storage::disk('local')->delete($data['image_path']);
        }

        return $result;
    }

    /**
     * Persiste les entrées présence dans presences_eleves.
     * @param array $rows Format : [['matricule'=>..., 'date'=>..., 'periode'=>..., 'statut'=>...], ...]
     */
    private function persistPresences(
        Request $request, Classe $classe, Enseignant $ens, Carbon $semaine, array $rows
    ) {
        $eleves = $this->elevesForClasse($classe->id);

        $matriculeMap = [];
        foreach ($eleves as $e) {
            if ($e->matricule_interne) $matriculeMap[strtoupper(trim($e->matricule_interne))] = $e->id;
            if ($e->matricule_desps)   $matriculeMap[strtoupper(trim($e->matricule_desps))]   = $e->id;
        }

        $count = 0;

        DB::transaction(function () use ($rows, $matriculeMap, $classe, $ens, $request, &$count) {
            foreach ($rows as $row) {
                $eleveId = $matriculeMap[strtoupper(trim((string) $row['matricule']))] ?? null;
                if (!$eleveId) continue;

                $statut = $row['statut'] ?? '';
                if (!in_array($statut, ['present', 'absent', 'retard', 'excuse', 'dispense'])) continue;

                PresenceEleve::updateOrCreate(
                    [
                        'eleve_id' => $eleveId,
                        'date'     => $row['date'],
                        'periode'  => $row['periode'] ?? 'journee',
                    ],
                    [
                        'classe_id'     => $classe->id,
                        'enseignant_id' => $ens->id,
                        'statut'        => $statut,
                        'saisie_par'    => $request->user()->id,
                    ]
                );
                $count++;
            }
        });

        return redirect()->route('mon-espace.cahier-appel.index', [
            'classe'  => $classe->id,
            'semaine' => $semaine->toDateString(),
        ])->with('success', "{$count} présence(s) enregistrée(s).");
    }

    /**
     * Appel du jour : focus sur LES SÉANCES du prof pour ce jour
     * (1 colonne par créneau enseigné).
     */
    public function appelJour(Request $request, Classe $classe)
    {
        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $date = $request->input('date')
            ? Carbon::parse($request->date)
            : today();

        // Semaine de la date pour réutiliser seancesSemaine()
        $semaine = $date->copy()->startOfWeek();
        $toutes  = $this->seancesSemaine($ens, $classe, $semaine);

        // Filtrer sur la date du jour uniquement
        $seances = array_values(array_filter($toutes, fn ($s) => $s['date'] === $date->toDateString()));

        $eleves = $this->elevesForClasse($classe->id);

        $creneauxIds = array_column($seances, 'creneau_id');

        $presences = PresenceEleve::where('classe_id', $classe->id)
            ->whereDate('date', $date->toDateString())
            ->whereIn('creneau_id', $creneauxIds)
            ->get()
            ->keyBy(fn ($p) => $p->eleve_id . '_' . $p->creneau_id);

        $stats = $this->computeStatsJour($classe->id, $date->toDateString(), $creneauxIds, $eleves->count(), count($seances));

        return view('mon-espace.cahier-appel.appel-jour',
            compact('ens', 'classe', 'eleves', 'date', 'seances', 'presences', 'stats', 'semaine'));
    }

    /**
     * Calcule les stats agrégées (sur l'ensemble des séances du jour pour cette classe).
     */
    private function computeStatsJour(int $classeId, string $dateStr, array $creneauxIds, int $totalEleves, int $nbSeances): array
    {
        if (empty($creneauxIds) || $nbSeances === 0) {
            return [
                'present' => 0, 'absent' => 0, 'retard' => 0, 'excuse' => 0, 'dispense' => 0,
                'non_saisi' => $totalEleves * max(1, $nbSeances),
                'total'     => $totalEleves * max(1, $nbSeances),
            ];
        }

        $counts = PresenceEleve::where('classe_id', $classeId)
            ->whereDate('date', $dateStr)
            ->whereIn('creneau_id', $creneauxIds)
            ->selectRaw('statut, COUNT(*) as n')
            ->groupBy('statut')
            ->pluck('n', 'statut');

        $totalCellules = $totalEleves * $nbSeances;
        $saisies = (int) $counts->sum();

        return [
            'present'   => (int) ($counts['present']  ?? 0),
            'absent'    => (int) ($counts['absent']   ?? 0),
            'retard'    => (int) ($counts['retard']   ?? 0),
            'excuse'    => (int) ($counts['excuse']   ?? 0),
            'dispense'  => (int) ($counts['dispense'] ?? 0),
            'non_saisi' => max(0, $totalCellules - $saisies),
            'total'     => $totalCellules,
        ];
    }

    /**
     * Endpoint AJAX : marquer un élève sur une séance (créneau précis).
     */
    public function appelJourMark(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'eleve_id'   => 'required|integer|exists:eleves,id',
            'date'       => 'required|date',
            'creneau_id' => 'required|integer|exists:creneaux,id',
            'statut'     => 'required|in:present,absent,retard,excuse,dispense',
        ]);

        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        // L'élève doit appartenir à la classe
        $eleveOk = Eleve::where('id', $data['eleve_id'])
            ->where('classe_id', $classe->id)
            ->where('actif', true)
            ->exists();
        abort_if(!$eleveOk, 422, 'Élève introuvable dans cette classe.');

        // Le prof doit enseigner cette classe à ce créneau (jour, créneau)
        $dateCarbon = Carbon::parse($data['date']);
        $jourFr = strtolower($dateCarbon->locale('fr')->isoFormat('dddd'));

        $autorise = EmploiDuTemps::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('creneau_id', $data['creneau_id'])
            ->where('jour', $jourFr)
            ->where('actif', true)
            ->exists();

        // Fallback : si pas d'EDT configuré POUR CETTE CLASSE, autoriser tant que
        // l'affectation existe (alignement avec seancesSemaine() qui utilise le même fallback).
        if (!$autorise) {
            $hasEdtForClasse = EmploiDuTemps::where('enseignant_id', $ens->id)
                ->where('classe_id', $classe->id)
                ->where('actif', true)
                ->exists();
            $autorise = !$hasEdtForClasse; // pas d'EDT pour cette classe → mode fallback OK
        }
        abort_if(!$autorise, 403, 'Vous n\'enseignez pas à ce créneau pour cette classe.');

        $presence = PresenceEleve::updateOrCreate(
            [
                'eleve_id'   => $data['eleve_id'],
                'date'       => $data['date'],
                'creneau_id' => $data['creneau_id'],
            ],
            [
                'classe_id'     => $classe->id,
                'enseignant_id' => $ens->id,
                'periode'       => 'journee',
                'statut'        => $data['statut'],
                'saisie_par'    => $request->user()->id,
            ]
        );

        // Réponse minimale → latence client réduite (les stats sont calculées côté JS).
        return response()->json([
            'success'     => true,
            'presence_id' => $presence->id,
            'statut'      => $presence->statut,
        ]);
    }

    /**
     * Saisie directe en ligne (depuis index, sans import).
     */
    public function storeDirect(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'semaine'           => 'required|date',
            'presences'         => 'required|array',
            'presences.*'       => 'array',
            'presences.*.*'     => 'nullable|in:present,absent,retard,excuse,dispense',
        ]);

        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $semaine = Carbon::parse($data['semaine'])->startOfWeek();
        $eleves  = $this->elevesForClasse($classe->id);

        $count = 0;
        DB::transaction(function () use ($data, $eleves, $classe, $ens, $request, &$count) {
            foreach ($data['presences'] as $eleveId => $jours) {
                if (!$eleves->contains('id', (int) $eleveId)) continue;
                foreach ($jours as $date => $statut) {
                    if ($statut === null || $statut === '') continue;

                    PresenceEleve::updateOrCreate(
                        [
                            'eleve_id' => (int) $eleveId,
                            'date'     => $date,
                            'periode'  => 'journee',
                        ],
                        [
                            'classe_id'     => $classe->id,
                            'enseignant_id' => $ens->id,
                            'statut'        => $statut,
                            'saisie_par'    => $request->user()->id,
                        ]
                    );
                    $count++;
                }
            }
        });

        return redirect()->route('mon-espace.cahier-appel.index', [
            'classe'  => $classe->id,
            'semaine' => $semaine->toDateString(),
        ])->with('success', "{$count} présence(s) enregistrée(s).");
    }
}
