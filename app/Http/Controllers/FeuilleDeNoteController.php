<?php

namespace App\Http\Controllers;

use App\Exports\FeuilleDeNoteExport;
use App\Imports\FeuilleDeNoteImport;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Trimestre;
use App\Models\TypeEvaluation;
use App\Services\FeuilleDeNote\FeuilleDeNoteOcrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class FeuilleDeNoteController extends Controller
{
    // ── helpers ────────────────────────────────────────────────────────────

    private function enseignant(Request $request): Enseignant
    {
        $ens = $request->user()->enseignantActif();
        abort_if(!$ens, 403, 'Compte enseignant introuvable pour cette école.');
        return $ens;
    }

    private function etablissementActif(Request $request)
    {
        $id = $request->user()->ecoleActiveId();
        return \App\Models\Etablissement::find($id);
    }

    private function annee(int $etabId): ?AnneeScolaire
    {
        return \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);
    }

    private function authorizeClasseMatiere(Enseignant $ens, Classe $classe, int $matiereId): void
    {
        $ok = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('matiere_id', $matiereId)
            ->where('active', true)
            ->exists();
        abort_if(!$ok, 403, "Vous n'êtes pas affecté à cette classe pour cette matière.");
    }

    private function elevesForClasse(int $classeId)
    {
        return Eleve::where('classe_id', $classeId)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get();
    }

    // ── UI : page de génération ────────────────────────────────────────────

    public function index(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);

        // matières de ce prof pour cette classe
        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->with('matiere')->get()->pluck('matiere');

        abort_if($matieres->isEmpty(), 403, "Vous n'êtes pas affecté à cette classe.");

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $eleves = $this->elevesForClasse($classe->id);

        return view('mon-espace.feuille-de-note.index',
            compact('ens','classe','annee','matieres','trimestres','eleves'));
    }

    // ── Export PDF ─────────────────────────────────────────────────────────

    public function pdf(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'matiere_id'   => 'required|exists:matieres,id',
            'nb_colonnes'  => 'nullable|integer|min:1|max:12',
            'titre_pdf'    => 'nullable|string|max:100',
            'orientation'  => 'nullable|in:portrait,landscape',
        ]);

        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);
        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

        $matiere = Matiere::findOrFail($data['matiere_id']);
        $eleves  = $this->elevesForClasse($classe->id);
        $nbCols  = (int) ($data['nb_colonnes'] ?? 6);
        $orientation = $data['orientation'] ?? 'landscape';

        $pdf = Pdf::loadView('mon-espace.feuille-de-note.pdf', [
            'etab'        => $etab,
            'annee'       => $annee,
            'classe'      => $classe,
            'matiere'     => $matiere,
            'enseignant'  => $ens,
            'eleves'      => $eleves,
            'nbCols'      => $nbCols,
            'titre'       => $data['titre_pdf'] ?? 'FEUILLE DE NOTE',
            'orientation' => $orientation,
        ])->setPaper('a4', $orientation);

        $fname = sprintf('feuille-notes_%s_%s.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            preg_replace('/[^a-zA-Z0-9]/', '-', $matiere->code ?? $matiere->nom));

        return $pdf->download($fname);
    }

    // ── Export Excel (template) ────────────────────────────────────────────

    public function excel(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'matiere_id'  => 'required|exists:matieres,id',
            'nb_colonnes' => 'nullable|integer|min:1|max:12',
        ]);

        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);
        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

        $matiere = Matiere::findOrFail($data['matiere_id']);
        $eleves  = $this->elevesForClasse($classe->id);
        $nbCols  = (int) ($data['nb_colonnes'] ?? 6);

        $fname = sprintf('feuille-notes_%s_%s.xlsx',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            preg_replace('/[^a-zA-Z0-9]/', '-', $matiere->code ?? $matiere->nom));

        return Excel::download(
            new FeuilleDeNoteExport($etab, $annee, $classe, $matiere, $ens, $eleves, $nbCols),
            $fname
        );
    }

    // ── Import Excel ───────────────────────────────────────────────────────

    public function importExcelForm(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);

        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)->where('active', true)
            ->with('matiere')->get()->pluck('matiere');

        return view('mon-espace.feuille-de-note.import-excel',
            compact('ens','classe','annee','matieres'));
    }

    public function importExcel(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'matiere_id' => 'required|exists:matieres,id',
            'fichier'    => 'required|file|mimes:xlsx,xls',
        ]);

        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);
        abort_if(!$annee, 422, 'Aucune année scolaire en cours.');
        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

        $import = new FeuilleDeNoteImport();
        Excel::import($import, $data['fichier']);
        $payload = $import->result();

        return $this->persistImportedColumns(
            $request, $classe, $etab, $annee, $ens, (int) $data['matiere_id'], $payload
        );
    }

    // ── Import OCR (photo) ─────────────────────────────────────────────────

    public function importOcrForm(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $annee = $this->annee((int) $request->user()->ecoleActiveId());

        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)->where('active', true)
            ->with('matiere')->get()->pluck('matiere');

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        return view('mon-espace.feuille-de-note.import-ocr',
            compact('ens','classe','annee','matieres','trimestres'));
    }

    public function importOcrPreview(Request $request, Classe $classe, FeuilleDeNoteOcrService $ocr)
    {
        $data = $request->validate([
            'matiere_id' => 'required|exists:matieres,id',
            'image'      => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $ens   = $this->enseignant($request);
        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

        $eleves = $this->elevesForClasse($classe->id);

        $path = $request->file('image')->store('ocr-feuilles-notes');
        $extracted = $ocr->extract('local', $path, $eleves);

        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)->where('active', true)
            ->with('matiere')->get()->pluck('matiere');
        $annee = $this->annee((int) $request->user()->ecoleActiveId());
        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        return view('mon-espace.feuille-de-note.import-ocr-preview', [
            'classe'     => $classe,
            'matieres'   => $matieres,
            'matiereId'  => (int) $data['matiere_id'],
            'eleves'     => $eleves,
            'extracted'  => $extracted,
            'imagePath'  => $path,
            'trimestres' => $trimestres,
        ]);
    }

    public function importOcrConfirm(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'matiere_id'      => 'required|exists:matieres,id',
            'image_path'      => 'required|string',
            'columns'         => 'required|array|min:1',
            'columns.*.titre' => 'required|string|max:200',
            'columns.*.type_evaluation_id' => 'required|exists:types_evaluation,id',
            'columns.*.trimestre_id' => 'required|exists:trimestres,id',
            'columns.*.date_evaluation' => 'required|date',
            'columns.*.note_sur' => 'required|numeric|min:1|max:100',
            'columns.*.coefficient' => 'required|numeric|min:0.5|max:10',
            'columns.*.notes' => 'array',
        ]);

        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);
        abort_if(!$annee, 422, 'Aucune année scolaire en cours.');
        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

        $payload = ['columns' => $data['columns']];
        $result = $this->persistImportedColumns(
            $request, $classe, $etab, $annee, $ens, (int) $data['matiere_id'], $payload
        );

        if (Storage::disk('local')->exists($data['image_path'])) {
            Storage::disk('local')->delete($data['image_path']);
        }

        return $result;
    }

    // ── Persistance commune Excel + OCR ────────────────────────────────────

    private function persistImportedColumns(
        Request $request, Classe $classe, $etab, AnneeScolaire $annee, Enseignant $ens,
        int $matiereId, array $payload
    ) {
        $columns = $payload['columns'] ?? [];
        $columns = array_filter($columns, fn ($c) => !empty($c['titre']));

        if (empty($columns)) {
            return back()->withErrors(['fichier' => 'Aucune colonne d\'évaluation valide trouvée.']);
        }

        // Map matricules -> eleve_id pour la classe
        $eleves = $this->elevesForClasse($classe->id);
        $matriculeMap = [];
        foreach ($eleves as $e) {
            if ($e->matricule_interne) $matriculeMap[strtoupper(trim($e->matricule_interne))] = $e->id;
            if ($e->matricule_desps)   $matriculeMap[strtoupper(trim($e->matricule_desps))]   = $e->id;
        }

        $totalEvals = 0;
        $totalNotes = 0;

        DB::transaction(function () use (
            $columns, $matriculeMap, $classe, $etab, $annee, $ens, $matiereId, $request, &$totalEvals, &$totalNotes
        ) {
            foreach ($columns as $col) {
                $trimId = $this->resolveTrimestreId($col, $annee);
                $typeId = $this->resolveTypeEvaluationId($col, $etab->id);

                $eval = Evaluation::create([
                    'etablissement_id'   => $etab->id,
                    'classe_id'          => $classe->id,
                    'matiere_id'         => $matiereId,
                    'enseignant_id'      => $ens->id,
                    'trimestre_id'       => $trimId,
                    'type_evaluation_id' => $typeId,
                    'titre'              => $col['titre'],
                    'date_evaluation'    => $col['date_evaluation'] ?? now()->toDateString(),
                    'note_sur'           => (float) ($col['note_sur'] ?? 20),
                    'coefficient'        => (float) ($col['coefficient'] ?? 1),
                    'description'        => 'Importé par feuille de note',
                    'statut'             => 'cloturee',
                ]);
                $totalEvals++;

                foreach (($col['notes'] ?? []) as $matricule => $valeur) {
                    $eleveId = $matriculeMap[strtoupper(trim((string) $matricule))] ?? null;
                    if (!$eleveId) continue;

                    $absent   = is_string($valeur) && in_array(strtoupper(trim($valeur)), ['ABS','ABSENT','A']);
                    $dispense = is_string($valeur) && in_array(strtoupper(trim($valeur)), ['DISP','DISPENSE','DISPENSÉ','D']);
                    $note = (!$absent && !$dispense && $valeur !== '' && $valeur !== null && is_numeric($valeur))
                        ? (float) $valeur : null;

                    if ($note === null && !$absent && !$dispense) continue;

                    Note::updateOrCreate(
                        ['evaluation_id' => $eval->id, 'eleve_id' => $eleveId],
                        [
                            'note'        => $note,
                            'absent'      => $absent,
                            'dispense'    => $dispense,
                            'saisie_par'  => $request->user()->id,
                            'date_saisie' => now(),
                        ]
                    );
                    $totalNotes++;
                }
            }
        });

        return redirect()->route('mon-espace.evaluations', $classe)
            ->with('success', "Import réussi : {$totalEvals} évaluation(s), {$totalNotes} note(s) enregistrée(s).");
    }

    private function resolveTrimestreId(array $col, AnneeScolaire $annee): int
    {
        if (!empty($col['trimestre_id'])) return (int) $col['trimestre_id'];

        $numero = (int) ($col['trimestre'] ?? 0);
        $trim = $numero > 0
            ? Trimestre::where('annee_scolaire_id', $annee->id)->where('numero', $numero)->first()
            : null;

        $trim = $trim ?? Trimestre::where('annee_scolaire_id', $annee->id)->where('en_cours', true)->first()
            ?? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->first();

        abort_if(!$trim, 422, 'Aucun trimestre disponible pour cette année.');
        return $trim->id;
    }

    private function resolveTypeEvaluationId(array $col, int $etabId): int
    {
        if (!empty($col['type_evaluation_id'])) return (int) $col['type_evaluation_id'];

        $code = strtoupper(trim((string) ($col['type'] ?? 'DEVOIR')));
        // alias courants
        $alias = ['I' => 'INTERRO', 'D' => 'DEVOIR', 'C' => 'COMPO', 'INTERROGATION' => 'INTERRO'];
        $code = $alias[$code] ?? $code;

        $type = TypeEvaluation::where('etablissement_id', $etabId)
            ->where(function ($q) use ($code) {
                $q->where('code', $code)->orWhere('nom', 'like', "%{$code}%");
            })->first();

        $type = $type ?? TypeEvaluation::where('etablissement_id', $etabId)->where('actif', true)->first();

        abort_if(!$type, 422, "Aucun type d'évaluation configuré.");
        return $type->id;
    }
}
