<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Exports\FeuilleDeNoteExport;
use App\Exports\FicheClasseExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\Eleve;
use App\Models\EmploiDuTemps;
use App\Models\Enseignant;
use App\Models\Matiere;
use App\Models\Trimestre;
use App\Support\ApiEnvelope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherPdfController extends Controller
{
    use ResolvesTeacherContext;

    // â”â‚¬â”â‚¬ Emploi du temps : donnÃ©es JSON â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬

    public function scheduleGrid(Request $request): JsonResponse
    {
        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee  = $this->anneeCourante($etabId);
        abort_if(!$annee, 422, 'Aucune annÃ©e scolaire en cours.');

        $jours    = EmploiDuTemps::jours();
        $creneaux = $this->creneaux($etabId);

        $emplois = EmploiDuTemps::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $annee->id)
            ->where('enseignant_id', $ens->id)
            ->where('actif', true)
            ->with(['classe:id,nom', 'matiere:id,nom,code', 'salle:id,nom', 'creneau:id,heure_debut,heure_fin,ordre,type,libelle'])
            ->get();

        // Construire la grille jour Ãƒâ€” crÃ©neau
        $grid = [];
        foreach ($jours as $jour) {
            $grid[$jour] = [];
            foreach ($creneaux as $creneau) {
                $grid[$jour][$creneau->id] = null;
            }
        }
        foreach ($emplois as $s) {
            $grid[$s->jour][$s->creneau_id] = [
                'id'          => $s->id,
                'matiere'     => $s->matiere ? ['nom' => $s->matiere->nom, 'code' => $s->matiere->code] : null,
                'classe'      => $s->classe ? ['nom' => $s->classe->nom] : null,
                'salle'       => $s->salle ? ['nom' => $s->salle->nom] : null,
            ];
        }

        $creneauxData = $creneaux->map(fn ($c) => [
            'id'          => $c->id,
            'libelle'     => $c->libelle ?? (substr((string)$c->heure_debut, 0, 5).' - '.substr((string)$c->heure_fin, 0, 5)),
            'heure_debut' => substr((string)$c->heure_debut, 0, 5),
            'heure_fin'   => substr((string)$c->heure_fin, 0, 5),
            'type'        => $c->type ?? 'cours',
            'est_pause'   => in_array($c->type ?? '', ['recreation', 'pause_dejeuner']),
        ]);

        return ApiEnvelope::success([
            'jours'           => $jours,
            'creneaux'        => $creneauxData,
            'grid'            => $grid,
            'enseignant'      => ['nom' => $ens->nom, 'prenom' => $ens->prenom, 'corps' => $ens->corps ?? ''],
            'etablissement'   => ['nom' => $ens->etablissement->nom ?? ''],
            'annee'           => ['libelle' => $annee->libelle],
        ], 'Grille EDT enseignant.');
    }

    // â”â‚¬â”â‚¬ Emploi du temps : PDF â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬

    public function schedulePdf(Request $request): HttpResponse
    {
        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee  = $this->anneeCourante($etabId);
        abort_if(!$annee, 422, 'Aucune annÃ©e scolaire en cours.');

        $etab     = $ens->etablissement ?? \App\Models\Etablissement::find($etabId);
        $jours    = EmploiDuTemps::jours();
        $creneaux = $this->creneaux($etabId);

        $emplois = EmploiDuTemps::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $annee->id)
            ->where('enseignant_id', $ens->id)
            ->where('actif', true)
            ->with(['classe', 'matiere', 'salle', 'creneau'])
            ->orderBy('jour')->orderBy('creneau_id')
            ->get();

        $grid = [];
        foreach ($jours as $jour) {
            foreach ($creneaux as $creneau) {
                $grid[$jour][$creneau->id] = null;
            }
        }
        foreach ($emplois as $seance) {
            $grid[$seance->jour][$seance->creneau_id] = $seance;
        }

        $parClasse = $emplois
            ->groupBy(fn ($item) => $item->classe_id ?: ('x-' . $item->id))
            ->map(function (Collection $items) {
                $first   = $items->first();
                $classe  = $first?->classe;
                $nbH     = $items->count();
                return [
                    'classe'       => $classe?->nom ?? 'Ã¢â‚¬â€',
                    'effectif'     => '',
                    'discipline'   => $items->pluck('matiere.code')->filter()->unique()->implode('/'),
                    'heures_a'     => $nbH,
                    'heures_a_label' => $nbH . 'H',
                ];
            })->values();

        $slotCount = max(8, $parClasse->count());
        $slots = collect(range(1, $slotCount))->map(function ($i) use ($parClasse) {
            return $parClasse->get($i - 1) ?? ['classe' => '', 'effectif' => '', 'discipline' => '', 'heures_a' => 0, 'heures_a_label' => ''];
        })->all();

        $totalA    = (int) $parClasse->sum('heures_a');
        $decharges = ['PP' => 0, 'CE' => 0, 'LABO' => 0, 'BIBLIO/CDI' => 0, 'UP' => 0];
        $maxService = $this->resolveMaxService($ens);

        $document = [
            'type'       => 'professeur',
            'enseignant' => $ens,
            'jours'      => $jours,
            'creneaux'   => $creneaux,
            'grid'       => $grid,
            'emplois'    => $emplois,
            'discipline' => $emplois->pluck('matiere.code')->filter()->unique()->sort()->implode('/') ?: ($ens->specialite ?? 'Ã¢â‚¬â€'),
            'corps'      => $ens->corps ?? ($ens->statut ?? 'ENSEIGNANT'),
            'matricule'  => $ens->matricule_mena ?? 'Ã¢â‚¬â€',
            'recap'      => [
                'slots'             => $slots,
                'total_a'           => $totalA . 'H',
                'total_b'           => '',
                'total_c'           => '',
                'total_d'           => '',
                'service_total'     => $totalA . 'H',
                'max_service'       => $maxService . 'H',
                'heures_sup'        => max(0, $totalA - $maxService) > 0 ? (max(0, $totalA - $maxService) . 'H') : '',
                'decharges_labels'  => ['PP', 'CE', 'LABO', 'BIBLIO/CDI', 'UP'],
                'decharges_values'  => ['', '', '', '', ''],
                'vacataires'        => ['Prof.Agr' => '04H', 'PL' => '06H', 'PC' => '08H'],
                'permanents'        => '25H',
                'signature_place'   => strtoupper($etab->ville ?? 'ABIDJAN'),
                'signature_date'    => now()->locale('fr')->translatedFormat('d F Y'),
            ],
        ];

        $fname = 'edt-' . Str::slug($ens->nom . '-' . $ens->prenom) . '.pdf';

        return Pdf::setOption(['defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false, 'dpi' => 120])
            ->loadView('emploi-du-temps.pdf.professeurs', [
                'documents'      => [$document],
                'annee'          => $annee,
                'etablissement'  => $etab,
            ])
            ->setPaper('a4', 'portrait')
            ->download($fname);
    }

    // â”â‚¬â”â‚¬ Feuille de note : liste matiÃ¨res â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬

    public function feuilleIndex(Request $request, Classe $classe): JsonResponse
    {
        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee  = $this->anneeCourante($etabId);

        $this->assertClasseAssignable($request, $classe);

        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->with('matiere:id,nom,code')
            ->get()
            ->pluck('matiere')
            ->filter()
            ->unique('id')
            ->values();

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get(['id', 'libelle', 'numero'])
            : collect();

        return ApiEnvelope::success([
            'classe'     => $classe->only(['id', 'nom']),
            'matieres'   => $matieres,
            'trimestres' => $trimestres,
        ], 'DonnÃ©es feuille de note.');
    }

    // â”â‚¬â”â‚¬ Feuille de note : PDF â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬

    public function feuillePdf(Request $request, Classe $classe): HttpResponse
    {
        $data = $request->validate([
            'matiere_id'  => 'required|exists:matieres,id',
            'nb_colonnes' => 'nullable|integer|min:1|max:12',
            'titre_pdf'   => 'nullable|string|max:100',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee  = $this->anneeCourante($etabId);
        $etab   = \App\Models\Etablissement::find($etabId);

        $this->authorizeMatierePourClasse($request, $classe->id, (int) $data['matiere_id']);

        $matiere = Matiere::findOrFail($data['matiere_id']);
        $eleves  = Eleve::where('classe_id', $classe->id)->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')->get();
        $nbCols  = (int) ($data['nb_colonnes'] ?? 6);
        $orientation = $data['orientation'] ?? 'landscape';

        $fname = sprintf('feuille-notes_%s_%s.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            preg_replace('/[^a-zA-Z0-9]/', '-', $matiere->code ?? $matiere->nom));

        return Pdf::loadView('mon-espace.feuille-de-note.pdf', [
            'etab'        => $etab,
            'annee'       => $annee,
            'classe'      => $classe,
            'matiere'     => $matiere,
            'enseignant'  => $ens,
            'eleves'      => $eleves,
            'nbCols'      => $nbCols,
            'titre'       => $data['titre_pdf'] ?? 'FEUILLE DE NOTE',
            'orientation' => $orientation,
        ])->setPaper('a4', $orientation)->download($fname);
    }

    // â”â‚¬â”â‚¬ Feuille de note : Excel â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬

    public function feuilleExcel(Request $request, Classe $classe): BinaryFileResponse
    {
        $data = $request->validate([
            'matiere_id'  => 'required|exists:matieres,id',
            'nb_colonnes' => 'nullable|integer|min:1|max:12',
        ]);

        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee  = $this->anneeCourante($etabId);
        $etab   = \App\Models\Etablissement::find($etabId);

        $this->authorizeMatierePourClasse($request, $classe->id, (int) $data['matiere_id']);

        $matiere = Matiere::findOrFail($data['matiere_id']);
        $eleves  = Eleve::where('classe_id', $classe->id)->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')->get();
        $nbCols  = (int) ($data['nb_colonnes'] ?? 6);

        $fname = sprintf('feuille-notes_%s_%s.xlsx',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            preg_replace('/[^a-zA-Z0-9]/', '-', $matiere->code ?? $matiere->nom));

        return Excel::download(
            new FeuilleDeNoteExport($etab, $annee, $classe, $matiere, $ens, $eleves, $nbCols),
            $fname
        );
    }

    // â”â‚¬â”â‚¬ Fiche classe (liste Ã©lÃ¨ves) : PDF â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬

    public function ficheClassePdf(Request $request, Classe $classe): HttpResponse
    {
        $this->assertClasseAssignable($request, $classe);

        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $etab   = \App\Models\Etablissement::find($etabId);
        $annee  = $this->anneeCourante($etabId);

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        $orientation = $request->input('orientation', 'landscape');
        if (!in_array($orientation, ['portrait', 'landscape'])) {
            $orientation = 'landscape';
        }

        $pdf = Pdf::loadView('mon-espace.fiche-classe.pdf', [
            'etab'        => $etab,
            'annee'       => $annee,
            'classe'      => $classe,
            'ens'         => $ens,
            'eleves'      => $eleves,
            'orientation' => $orientation,
        ])->setPaper('a4', $orientation);

        $fname = sprintf('fiche-classe_%s.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom)
        );

        return $pdf->download($fname);
    }

    // â”â‚¬â”â‚¬ Fiche classe : Excel â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬

    public function ficheClasseExcel(Request $request, Classe $classe): BinaryFileResponse
    {
        $this->assertClasseAssignable($request, $classe);

        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $etab   = \App\Models\Etablissement::find($etabId);
        $annee  = $this->anneeCourante($etabId);

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        $fname = sprintf('fiche-classe_%s.xlsx',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom)
        );

        return Excel::download(
            new FicheClasseExport($etab, $annee, $classe, $ens, $eleves),
            $fname
        );
    }

    // â”â‚¬â”â‚¬ Helpers privÃ©s â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬â”â‚¬

    private function creneaux(int $etabId): Collection
    {
        $query = Creneau::query();
        if (Schema::hasColumn('creneaux', 'etablissement_id')) {
            $query->where('etablissement_id', $etabId);
        }
        if (Schema::hasColumn('creneaux', 'actif')) {
            $query->where('actif', true);
        }
        if (Schema::hasColumn('creneaux', 'ordre')) {
            $query->orderBy('ordre');
        } else {
            $query->orderBy('heure_debut');
        }
        return $query->get();
    }

    private function resolveMaxService(Enseignant $enseignant): int
    {
        $corps = Str::lower(Str::ascii(trim(($enseignant->corps ?? '') . ' ' . ($enseignant->statut ?? ''))));
        if (str_contains($corps, 'vacataire') || str_contains($corps, 'vacat')) {
            if (str_contains($corps, 'agr')) return 4;
            if (preg_match('/\bpl\b/', $corps)) return 6;
            return 8;
        }
        return 25;
    }
}
