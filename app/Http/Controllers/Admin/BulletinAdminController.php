<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\MoyenneGenerale;
use App\Models\MoyenneMatiere;
use App\Models\Note;
use App\Models\PresenceEleve;
use App\Models\Trimestre;
use App\Services\Pedagogie\MoyenneAnnuelleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BulletinAdminController extends Controller
{
    private array $columnsCache = [];

    private function etabId(Request $request): int
    {
        return (int) $request->user()->etablissement_id;
    }

    public function index(Request $request)
    {
        $etabId = $this->etabId($request);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);
        $trimestres = $annee ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get() : collect();
        $classes = $annee
            ? Classe::where('etablissement_id', $etabId)
                ->where('annee_scolaire_id', $annee->id)
                ->where('active', true)
                ->orderBy('nom')
                ->get()
            : collect();

        $classeId = $request->input('classe_id');
        $trimestreId = $request->input('trimestre_id', $trimestres->first(fn ($t) => $t->en_cours)?->id ?? $trimestres->first()?->id);

        $eleves = collect();
        if ($classeId && $trimestreId && $annee) {
            $eleves = Eleve::where('classe_id', $classeId)
                ->where('actif', true)
                ->inscritsCetteAnnee($annee->id)
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get();
        }

        $moyennesGenerales = $classeId && $trimestreId
            ? MoyenneGenerale::where('classe_id', $classeId)
                ->where('trimestre_id', $trimestreId)
                ->get()
                ->keyBy('eleve_id')
            : collect();

        $moyennesAnnuelles = $classeId && $annee && Schema::hasTable('moyennes_annuelles')
            ? \App\Models\MoyenneAnnuelle::where('classe_id', $classeId)
                ->where('annee_scolaire_id', $annee->id)
                ->get()
                ->keyBy('eleve_id')
            : collect();

        return view('admin.rh.bulletins.index', compact(
            'annee',
            'classes',
            'trimestres',
            'classeId',
            'trimestreId',
            'eleves',
            'moyennesGenerales',
            'moyennesAnnuelles'
        ));
    }

    public function calculer(Request $request)
    {
        $data = $request->validate([
            'classe_id'    => 'required|exists:classes,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $etabId = $this->etabId($request);
        $classe = Classe::where('etablissement_id', $etabId)->findOrFail($data['classe_id']);
        $trimestre = Trimestre::findOrFail($data['trimestre_id']);
        $annee = AnneeScolaire::findOrFail($trimestre->annee_scolaire_id);
        $eleves = Eleve::where('classe_id', $classe->id)->where('actif', true)->get();

        DB::transaction(function () use ($classe, $trimestre, $annee, $eleves) {
            foreach ($eleves as $eleve) {
                $this->calculerPourEleve($eleve, $classe, $trimestre, $annee);
            }

            $this->calculerRangs($classe, $trimestre);
        });

        try {
            MoyenneAnnuelleService::calculerPourClasse($classe, $annee);
        } catch (\Throwable $e) {
            Log::warning('Calcul moyenne annuelle ignoré pendant calcul bulletin', [
                'classe_id' => $classe->id,
                'annee_id' => $annee->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.rh.bulletins.index', [
            'classe_id' => $classe->id,
            'trimestre_id' => $trimestre->id,
        ])->with('success', 'Moyennes calculées et rangs établis.');
    }

    private function calculerPourEleve(Eleve $eleve, Classe $classe, Trimestre $trimestre, AnneeScolaire $annee): void
    {
        $matieres = DB::table('affectations')
            ->join('matieres', 'matieres.id', '=', 'affectations.matiere_id')
            ->where('affectations.classe_id', $classe->id)
            ->where('affectations.active', true)
            ->where('affectations.annee_scolaire_id', $annee->id)
            ->whereNull('matieres.parent_matiere_id')
            ->select('matieres.id', 'matieres.coefficient_defaut')
            ->distinct()
            ->get();

        $totalPoints = 0.0;
        $totalCoefs = 0.0;

        foreach ($matieres as $matRow) {
            $matiereId = (int) $matRow->id;
            $coefMat = max(0.0, (float) ($matRow->coefficient_defaut ?? 1));
            if ($coefMat <= 0) {
                $coefMat = 1.0;
            }

            $moyDirectQuery = MoyenneMatiere::where('eleve_id', $eleve->id)
                ->where('matiere_id', $matiereId)
                ->where('trimestre_id', $trimestre->id);

            if ($this->hasColumn('moyennes_matieres', 'saisie_directe')) {
                $moyDirectQuery->where('saisie_directe', true);
            }

            $moyDirect = $moyDirectQuery->first();

            if ($moyDirect) {
                $moyenne = (float) $moyDirect->moyenne;
            } else {
                $notesQuery = Note::join('evaluations', 'evaluations.id', '=', 'notes.evaluation_id')
                    ->where('notes.eleve_id', $eleve->id)
                    ->where('evaluations.matiere_id', $matiereId)
                    ->where('evaluations.trimestre_id', $trimestre->id)
                    ->whereNotNull('notes.note');

                if ($this->hasColumn('notes', 'absent')) {
                    $notesQuery->where(function ($q) {
                        $q->where('notes.absent', false)->orWhereNull('notes.absent');
                    });
                }

                if ($this->hasColumn('notes', 'dispense')) {
                    $notesQuery->where(function ($q) {
                        $q->where('notes.dispense', false)->orWhereNull('notes.dispense');
                    });
                }

                $select = ['notes.note'];
                $select[] = $this->hasColumn('evaluations', 'note_sur') ? 'evaluations.note_sur' : DB::raw('20 as note_sur');
                $select[] = $this->hasColumn('evaluations', 'coefficient') ? 'evaluations.coefficient' : DB::raw('1 as coefficient');

                $notes = $notesQuery->select($select)->get();
                if ($notes->isEmpty()) {
                    continue;
                }

                $sommePoints = 0.0;
                $sommeCoefs = 0.0;
                foreach ($notes as $note) {
                    $bareme = (float) ($note->note_sur ?: 20);
                    $coef = (float) ($note->coefficient ?: 1);
                    $noteSur20 = $bareme > 0 ? ((float) $note->note / $bareme) * 20 : (float) $note->note;
                    $sommePoints += $noteSur20 * $coef;
                    $sommeCoefs += $coef;
                }

                if ($sommeCoefs <= 0) {
                    continue;
                }

                $moyenne = $sommePoints / $sommeCoefs;

                $this->upsertMoyenneMatiere($eleve->id, $matiereId, $trimestre->id, [
                    'classe_id' => $classe->id,
                    'moyenne' => round($moyenne, 2),
                    'moyenne_ponderee' => round($moyenne * $coefMat, 2),
                    'saisie_directe' => false,
                    'publie' => true,
                ]);
            }

            $totalPoints += $moyenne * $coefMat;
            $totalCoefs += $coefMat;
        }

        $moyenneGenerale = $totalCoefs > 0 ? round($totalPoints / $totalCoefs, 2) : null;
        [$totalAbs, $absJust, $totalRet] = $this->presenceStats($eleve, $trimestre);
        $mention = $this->calculerMention($moyenneGenerale);

        $this->upsertMoyenneGenerale($eleve->id, $trimestre->id, [
            'classe_id' => $classe->id,
            'annee_scolaire_id' => $annee->id,
            'moyenne_generale' => $moyenneGenerale,
            'total_points' => round($totalPoints, 2),
            'total_coefficients' => $totalCoefs,
            'mention' => $mention,
            'total_absences' => $totalAbs,
            'absences_justifiees' => $absJust,
            'total_retards' => $totalRet,
        ]);
    }

    private function calculerMention(?float $moy): string
    {
        if ($moy === null) return 'aucune';
        if ($moy >= 16) return 'felicitations';
        if ($moy >= 14) return 'tableau_honneur';
        if ($moy >= 12) return 'encouragements';
        if ($moy < 8) return 'avertissement';
        return 'aucune';
    }

    private function calculerRangs(Classe $classe, Trimestre $trimestre): void
    {
        $generales = MoyenneGenerale::where('classe_id', $classe->id)
            ->where('trimestre_id', $trimestre->id)
            ->whereNotNull('moyenne_generale')
            ->orderByDesc('moyenne_generale')
            ->get();

        $effectif = $generales->count();
        if ($effectif === 0) {
            return;
        }

        $moyMax = $generales->first()->moyenne_generale;
        $moyMin = $generales->last()->moyenne_generale;
        $moyClasse = round($generales->avg('moyenne_generale'), 2);

        foreach ($generales as $i => $g) {
            $payload = [
                'rang' => $i + 1,
                'effectif_classe' => $effectif,
                'moyenne_premier' => $moyMax,
                'moyenne_dernier' => $moyMin,
                'moyenne_classe' => $moyClasse,
            ];

            DB::table('moyennes_generales')
                ->where('id', $g->id)
                ->update($this->filterColumns('moyennes_generales', $payload));
        }
    }

    public function pdf(Request $request, Eleve $eleve, Trimestre $trimestre)
    {
        abort_unless($eleve->etablissement_id === $this->etabId($request), 404);

        $classe = $eleve->classe;
        $annee = AnneeScolaire::find($trimestre->annee_scolaire_id);
        $etab = $eleve->etablissement;

        $generale = MoyenneGenerale::where('eleve_id', $eleve->id)
            ->where('trimestre_id', $trimestre->id)
            ->first();

        abort_if(!$generale, 422, 'La moyenne générale n\'a pas encore été calculée. Lancez le calcul d\'abord.');

        $moyennes = MoyenneMatiere::where('eleve_id', $eleve->id)
            ->where('trimestre_id', $trimestre->id)
            ->with('matiere')
            ->get();

        $pdf = Pdf::loadView('admin.rh.bulletins.pdf', compact('etab', 'annee', 'trimestre', 'classe', 'eleve', 'generale', 'moyennes'))
            ->setPaper('a4', 'portrait');

        $fname = sprintf('bulletin_%s_%s_T%d.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $eleve->nom),
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe?->nom ?? ''),
            $trimestre->numero
        );

        return $pdf->download($fname);
    }

    public function pdfClasse(Request $request)
    {
        $data = $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $classe = Classe::where('etablissement_id', $this->etabId($request))->findOrFail($data['classe_id']);
        $trimestre = Trimestre::findOrFail($data['trimestre_id']);
        $eleves = Eleve::where('classe_id', $classe->id)->where('actif', true)->get();
        $annee = AnneeScolaire::find($trimestre->annee_scolaire_id);
        $etab = $classe->etablissement;

        $bulletins = [];
        foreach ($eleves as $eleve) {
            $generale = MoyenneGenerale::where('eleve_id', $eleve->id)
                ->where('trimestre_id', $trimestre->id)
                ->first();
            if (!$generale) {
                continue;
            }

            $moyennes = MoyenneMatiere::where('eleve_id', $eleve->id)
                ->where('trimestre_id', $trimestre->id)
                ->with('matiere')
                ->get();

            $bulletins[] = compact('eleve', 'generale', 'moyennes');
        }

        $pdf = Pdf::loadView('admin.rh.bulletins.pdf-classe', compact('etab', 'annee', 'trimestre', 'classe', 'bulletins'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("bulletins_{$classe->nom}_T{$trimestre->numero}.pdf");
    }

    public function pdfMasse(Request $request)
    {
        $data = $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'trimestre_id' => 'required|exists:trimestres,id',
            'disposition' => 'required|in:1,2,3,4',
            'eleve_ids' => 'required|array|min:1',
            'eleve_ids.*' => 'exists:eleves,id',
        ]);

        $etabId = $this->etabId($request);
        $classe = Classe::where('etablissement_id', $etabId)->findOrFail($data['classe_id']);
        $trimestre = Trimestre::findOrFail($data['trimestre_id']);
        $annee = AnneeScolaire::findOrFail($trimestre->annee_scolaire_id);
        $etab = $classe->etablissement;
        $disposition = (int) $data['disposition'];
        $bulletins = [];

        DB::transaction(function () use ($data, $classe, $trimestre, $annee, &$bulletins) {
            foreach ($data['eleve_ids'] as $eleveId) {
                $eleve = Eleve::findOrFail($eleveId);
                $generale = MoyenneGenerale::where('eleve_id', $eleve->id)
                    ->where('trimestre_id', $trimestre->id)
                    ->first();

                if (!$generale) {
                    $this->calculerPourEleve($eleve, $classe, $trimestre, $annee);
                    $this->calculerRangs($classe, $trimestre);
                    $generale = MoyenneGenerale::where('eleve_id', $eleve->id)
                        ->where('trimestre_id', $trimestre->id)
                        ->first();
                }

                if (!$generale) {
                    continue;
                }

                $moyennes = MoyenneMatiere::where('eleve_id', $eleve->id)
                    ->where('trimestre_id', $trimestre->id)
                    ->with('matiere')
                    ->get()
                    ->sortBy('matiere.nom')
                    ->values();

                $bulletins[] = compact('eleve', 'generale', 'moyennes');
            }
        });

        if (empty($bulletins)) {
            return back()->withErrors(['msg' => 'Aucun bulletin disponible. Vérifiez que des moyennes ont été saisies.']);
        }

        $pdf = Pdf::loadView('admin.rh.bulletins.pdf-masse', compact('etab', 'annee', 'trimestre', 'classe', 'bulletins', 'disposition'))
            ->setPaper('a4', 'portrait');

        $fname = sprintf('bulletins_%s_T%d_%deleves.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            $trimestre->numero,
            count($bulletins)
        );

        return $pdf->download($fname);
    }

    private function presenceStats(Eleve $eleve, Trimestre $trimestre): array
    {
        if (!Schema::hasTable('presence_eleves') || !$this->hasColumn('presence_eleves', 'date') || !$this->hasColumn('presence_eleves', 'statut')) {
            return [0, 0, 0];
        }

        $base = PresenceEleve::where('eleve_id', $eleve->id)
            ->whereBetween('date', [$trimestre->date_debut, $trimestre->date_fin]);

        $totalAbs = (clone $base)->where('statut', 'absent')->count();
        $absJust = $this->hasColumn('presence_eleves', 'justifie')
            ? (clone $base)->where('statut', 'absent')->where('justifie', true)->count()
            : 0;
        $totalRet = (clone $base)->where('statut', 'retard')->count();

        return [$totalAbs, $absJust, $totalRet];
    }

    private function upsertMoyenneMatiere(int $eleveId, int $matiereId, int $trimestreId, array $values): void
    {
        $keys = $this->filterColumns('moyennes_matieres', [
            'eleve_id' => $eleveId,
            'matiere_id' => $matiereId,
            'trimestre_id' => $trimestreId,
        ]);

        $values = $this->filterColumns('moyennes_matieres', $values);
        if (empty($keys) || empty($values)) {
            return;
        }

        DB::table('moyennes_matieres')->updateOrInsert($keys, $values);
    }

    private function upsertMoyenneGenerale(int $eleveId, int $trimestreId, array $values): void
    {
        $keys = $this->filterColumns('moyennes_generales', [
            'eleve_id' => $eleveId,
            'trimestre_id' => $trimestreId,
        ]);

        $values = $this->filterColumns('moyennes_generales', $values);
        if (empty($keys) || empty($values)) {
            return;
        }

        DB::table('moyennes_generales')->updateOrInsert($keys, $values);
    }

    private function filterColumns(string $table, array $payload): array
    {
        return array_intersect_key($payload, array_flip($this->columns($table)));
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    private function columns(string $table): array
    {
        if (!array_key_exists($table, $this->columnsCache)) {
            $this->columnsCache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        return $this->columnsCache[$table];
    }
}
