<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\EleveImportJob;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Services\Scolarite\AnneeScolaireService;
use App\Services\EleveParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EleveImportController extends Controller
{
    public function __construct(private EleveParserService $parser) {}

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->with('niveau')->orderBy('nom')->get()
            : collect();

        $jobsRecents = EleveImportJob::where('etablissement_id', $etab->id)
            ->with(['user', 'classeCible.niveau'])->latest()->take(10)->get();

        $statsGlobales = [
            'total_imports' => EleveImportJob::where('etablissement_id', $etab->id)->count(),
            'eleves_importes_total' => EleveImportJob::where('etablissement_id', $etab->id)->where('statut', 'completed')->sum('lignes_importees'),
            'imports_en_cours' => EleveImportJob::where('etablissement_id', $etab->id)->whereIn('statut', ['upload', 'parsing', 'preview', 'importing'])->count(),
        ];

        return view('eleves.import.index', compact('classes', 'annee', 'jobsRecents', 'statsGlobales'));
    }

    public function preview(Request $request, EleveImportJob $job)
    {
        $this->authorizeJob($request, $job);

        if (!in_array($job->statut, ['preview', 'failed'], true)) {
            return redirect()->route('eleves.import.index')->with('error', 'Cet import n\'est plus modifiable.');
        }

        $etab = $request->user()->etablissement;
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $niveaux = Niveau::where('etablissement_id', $etab->id)->where('actif', true)->orderBy('ordre')->get();
        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->with(['niveau', 'serie'])->orderBy('niveau_id')->orderBy('nom')->get()
            : collect();
        $classesParNiveau = $classes->groupBy(fn ($c) => $c->niveau->libelle ?? $c->niveau->code ?? 'Sans niveau');

        $donnees = $this->preparerLignesPreview($job->donnees_normalisees ?? [], (int) $etab->id);

        foreach ($donnees as &$ligne) {
            if (!isset($ligne['classe_id']) || $ligne['classe_id'] === '') {
                $ligne['classe_id'] = $job->classe_cible_id;
            }
            $ligne['raw_statut'] = $this->sanitizeRawStatut($ligne['raw_statut'] ?? '');
            $ligne['statut_eleve'] = $this->resolveStatutEleve($ligne['statut_eleve'] ?? '', $ligne['raw_statut'] ?? '');
        }
        unset($ligne);

        $job->donnees_normalisees = $donnees;

        return view('eleves.import.preview', compact('job', 'classes', 'classesParNiveau', 'niveaux', 'annee'));
    }

    public function updatePreview(Request $request, EleveImportJob $job)
    {
        $this->authorizeJob($request, $job);

        $validated = $request->validate([
            'donnees' => ['required', 'array'],
            'donnees.*.classe_id' => ['nullable', 'integer', 'exists:classes,id'],
            'donnees.*.matricule_desps' => ['nullable', 'string', 'max:20'],
            'donnees.*.matricule_desps_original' => ['nullable', 'string', 'max:20'],
            'donnees.*.matricule_interne' => ['nullable', 'string', 'max:30'],
            'donnees.*.matricule_interne_auto' => ['nullable', 'boolean'],
            'donnees.*.matricule_desps_invalide' => ['nullable', 'boolean'],
            'donnees.*.matricule_remplacement_label' => ['nullable', 'string', 'max:80'],
            'donnees.*.nom' => ['nullable', 'string', 'max:120'],
            'donnees.*.prenom' => ['nullable', 'string', 'max:120'],
            'donnees.*.sexe' => ['nullable', 'string', 'in:M,F'],
            'donnees.*.date_naissance' => ['nullable', 'date'],
            'donnees.*.lieu_naissance' => ['nullable', 'string', 'max:120'],
            'donnees.*.raw_statut' => ['nullable', 'string', 'max:10'],
            'donnees.*.statut_eleve' => ['nullable', 'string', 'max:10'],
            'niveau_id' => ['nullable', 'integer', 'exists:niveaux,id'],
            'classe_cible_id' => ['nullable', 'integer', 'exists:classes,id'],
        ]);

        $etab = $request->user()->etablissement;
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $classeIdsAutorises = $annee ? Classe::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
        $niveauIdsAutorises = Niveau::where('etablissement_id', $etab->id)->where('actif', true)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $niveauId = !empty($validated['niveau_id']) ? (int) $validated['niveau_id'] : null;
        $classeCibleId = !empty($validated['classe_cible_id']) ? (int) $validated['classe_cible_id'] : null;

        if ($niveauId && !in_array($niveauId, $niveauIdsAutorises, true)) {
            return response()->json(['success' => false, 'message' => 'Le niveau sélectionné n’appartient pas à votre établissement.'], 422);
        }
        if ($classeCibleId && !in_array($classeCibleId, $classeIdsAutorises, true)) {
            return response()->json(['success' => false, 'message' => 'La classe par défaut sélectionnée n’est pas autorisée pour l’année en cours.'], 422);
        }

        $lignesNettoyees = [];
        foreach ($validated['donnees'] as $ligne) {
            $ligneNettoyee = $ligne;
            unset($ligneNettoyee['_selectionne']);

            $matriculeSaisi = isset($ligne['matricule_desps']) ? mb_strtoupper(preg_replace('/\s+/', '', trim((string) $ligne['matricule_desps']))) : '';
            $despsValide = $matriculeSaisi !== '' && preg_match(EleveParserService::REGEX_MATRICULE_DESPS, $matriculeSaisi);
            $ligneNettoyee['matricule_desps'] = $despsValide ? $matriculeSaisi : null;
            $ligneNettoyee['matricule_desps_original'] = $matriculeSaisi ?: ($ligne['matricule_desps_original'] ?? null);
            $ligneNettoyee['matricule_desps_invalide'] = $matriculeSaisi !== '' && !$despsValide;
            $ligneNettoyee['matricule_interne'] = isset($ligne['matricule_interne']) ? trim((string) $ligne['matricule_interne']) : null;
            $ligneNettoyee['matricule_interne_auto'] = !$despsValide;
            $ligneNettoyee['matricule_remplacement_label'] = !$despsValide ? 'Remplacé par matricule interne' : null;
            $ligneNettoyee['nom'] = isset($ligne['nom']) ? mb_strtoupper(trim((string) $ligne['nom'])) : null;
            $ligneNettoyee['prenom'] = isset($ligne['prenom']) ? trim((string) $ligne['prenom']) : null;
            $ligneNettoyee['sexe'] = in_array(($ligne['sexe'] ?? null), ['M', 'F'], true) ? $ligne['sexe'] : null;
            $ligneNettoyee['sexe_a_corriger'] = empty($ligneNettoyee['sexe']);
            $ligneNettoyee['date_naissance'] = !empty($ligne['date_naissance']) ? $ligne['date_naissance'] : null;
            $ligneNettoyee['lieu_naissance'] = isset($ligne['lieu_naissance']) ? trim((string) $ligne['lieu_naissance']) : null;

            $classeIdLigne = !empty($ligne['classe_id']) ? (int) $ligne['classe_id'] : null;
            if ($classeIdLigne && !in_array($classeIdLigne, $classeIdsAutorises, true)) {
                return response()->json(['success' => false, 'message' => 'Une des classes sélectionnées n’est pas autorisée pour l’année en cours.'], 422);
            }

            $ligneNettoyee['classe_id'] = $classeIdLigne ?: ($classeCibleId ?: null);
            $ligneNettoyee['raw_statut'] = $this->sanitizeRawStatut($ligne['raw_statut'] ?? '');
            $ligneNettoyee['statut_eleve'] = $this->resolveStatutEleve($ligne['statut_eleve'] ?? '', $ligneNettoyee['raw_statut']);
            $lignesNettoyees[] = $ligneNettoyee;
        }

        $lignesNettoyees = $this->preparerLignesPreview($lignesNettoyees, (int) $etab->id);
        $erreursCourantes = is_array($job->erreurs) ? $job->erreurs : [];

        $job->update([
            'donnees_normalisees' => $lignesNettoyees,
            'niveau_id' => $niveauId,
            'classe_cible_id' => $classeCibleId,
            'lignes_valides' => count($lignesNettoyees),
            'lignes_erreur' => count($erreursCourantes),
            'total_lignes' => count($lignesNettoyees) + count($erreursCourantes),
            'statut' => 'preview',
        ]);

        return response()->json(['success' => true, 'donnees' => $lignesNettoyees, 'stats' => ['total' => count($lignesNettoyees) + count($erreursCourantes), 'valides' => count($lignesNettoyees), 'erreurs' => count($erreursCourantes)]]);
    }

    public function confirmer(Request $request, EleveImportJob $job)
    {
        $this->authorizeJob($request, $job);
        if ($job->statut !== 'preview') return back()->with('error', 'Cet import n\'est plus en phase de preview.');
        @set_time_limit(300);

        $etab = $request->user()->etablissement;
        if (!$etab) return back()->with('error', 'Impossible de retrouver l’établissement connecté.');

        $donnees = $this->preparerLignesPreview($job->donnees_normalisees ?? [], (int) $etab->id);
        if (empty($donnees)) return back()->with('error', 'Aucune ligne valide à importer.');

        $job->update(['statut' => 'importing', 'started_at' => now(), 'progression' => 0]);

        try {
            DB::beginTransaction();
            $annee = AnneeScolaireService::couranteOuEchec($etab->id);
            $importes = 0; $reinscrits = 0; $nouveaux = 0; $classesMAJ = [];

            foreach ($donnees as $donnee) {
                unset($donnee['_ligne']);
                $classeId = $donnee['classe_id'] ?? $job->classe_cible_id;
                if ($classeId) $classesMAJ[$classeId] = true;

                $parentData = ['parent_nom' => $donnee['parent_nom'] ?? null, 'parent_telephone' => $donnee['parent_telephone'] ?? null, 'parent_lien' => $donnee['parent_lien'] ?? null];
                unset($donnee['parent_nom'], $donnee['parent_telephone'], $donnee['parent_lien']);
                $rawStatut = $this->sanitizeRawStatut($donnee['raw_statut'] ?? '');
                unset($donnee['raw_statut'], $donnee['matricule_desps_original'], $donnee['matricule_desps_invalide'], $donnee['matricule_interne_auto'], $donnee['matricule_remplacement_label'], $donnee['matricule_provenances'], $donnee['sexe_a_corriger']);

                $donnee['etablissement_id'] = $etab->id; $donnee['user_id'] = null; $donnee['actif'] = true;
                if ($classeId) { $donnee['classe_id'] = $classeId; $donnee['statut'] = 'inscrit'; } else { $donnee['classe_id'] = null; $donnee['statut'] = 'pre_inscrit'; }
                $donnee['statut_eleve'] = $this->resolveStatutEleve($donnee['statut_eleve'] ?? '', $rawStatut);

                $matriculeInterne = trim((string) ($donnee['matricule_interne'] ?? ''));
                $matriculeDesps = trim((string) ($donnee['matricule_desps'] ?? ''));
                $eleveExistant = Eleve::trouverParMatricule($etab->id, $matriculeInterne ?: null, $matriculeDesps ?: null);
                $estReinscription = $eleveExistant !== null; $eleve = $eleveExistant;

                if ($eleve) {
                    $reinscrits++;
                    unset($donnee['matricule_interne'], $donnee['matricule_desps'], $donnee['date_premiere_inscription'], $donnee['etablissement_id'], $donnee['user_id']);
                    $eleve->update($donnee);
                } else {
                    $nouveaux++;
                    $donnee['matricule_interne'] = $matriculeInterne !== '' ? $matriculeInterne : Eleve::genererMatricule($etab->id, $annee);
                    $donnee['date_premiere_inscription'] = now()->toDateString();
                    $eleve = Eleve::create($donnee);
                }

                if ($classeId) {
                    $classe = Classe::find($classeId); $montantScolarite = (int) ($classe?->scolarite_annuelle ?? 0);
                    Inscription::updateOrCreate(['eleve_id' => $eleve->id, 'annee_scolaire_id' => $annee->id], ['classe_id' => $classeId, 'etablissement_id' => $etab->id, 'date_inscription' => now()->toDateString(), 'type' => $estReinscription ? 'renouvellement' : 'nouvelle', 'statut' => 'validee', 'montant_scolarite' => $montantScolarite, 'reduction' => 0, 'montant_net' => $montantScolarite]);
                }
                if (array_filter($parentData)) {
                    $info = 'Parent importé : ' . ($parentData['parent_nom'] ?? '');
                    if (!empty($parentData['parent_telephone'])) $info .= " ({$parentData['parent_telephone']})";
                    $eleve->update(['observations' => trim(($eleve->observations ? $eleve->observations . ' | ' : '') . $info)]);
                }
                $importes++;
                if ($importes % 10 === 0) $job->update(['progression' => round(($importes / count($donnees)) * 100), 'message_progression' => "Import : {$importes} / " . count($donnees), 'lignes_importees' => $importes]);
            }

            foreach (array_keys($classesMAJ) as $classeId) { $classe = Classe::find($classeId); if ($classe) $classe->updateEffectif(); }
            $job->update(['statut' => 'completed', 'lignes_importees' => $importes, 'progression' => 100, 'message_progression' => "{$importes} élève(s) importé(s)", 'completed_at' => now()]);
            DB::commit();

            $message = "{$importes} élève(s) traité(s) pour l'année {$annee->libelle}.";
            if ($reinscrits > 0) $message .= " {$reinscrits} réinscription(s) (matricule existant).";
            if ($nouveaux > 0) $message .= " {$nouveaux} nouvel(le)(s).";
            if (count($classesMAJ) > 1) $message .= ' Répartis dans '.count($classesMAJ).' classe(s).';
            return redirect()->route('eleves.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $job->update(['statut' => 'failed', 'message_progression' => 'Erreur : ' . $e->getMessage(), 'completed_at' => now()]);
            return back()->with('error', "Échec de l'import : " . $e->getMessage());
        }
    }

    public function annuler(Request $request, EleveImportJob $job)
    {
        $this->authorizeJob($request, $job);
        if ($job->estTermine()) return back()->with('error', 'Cet import est déjà terminé.');
        if ($job->fichier_path && Storage::exists($job->fichier_path)) Storage::delete($job->fichier_path);
        $job->update(['statut' => 'cancelled', 'completed_at' => now(), 'message_progression' => 'Annulé par l\'utilisateur']);
        return redirect()->route('eleves.import.index')->with('success', 'Import annulé.');
    }

    public function telechargerTemplate(Request $request)
    {
        $structure = $this->parser->getTemplateStructure();
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) return $this->telechargerTemplateCSV($structure);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet(); $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle('Élèves');
        $col = 1; foreach ($structure['headers'] as $header) { $sheet->setCellValueByColumnAndRow($col, 1, $header); $sheet->getStyleByColumnAndRow($col, 1)->getFont()->setBold(true); $sheet->getColumnDimensionByColumn($col)->setAutoSize(true); $col++; }
        $row = 2; foreach ($structure['exemples'] as $exemple) { $col = 1; foreach ($exemple as $valeur) { $sheet->setCellValueByColumnAndRow($col, $row, $valeur); $col++; } $row++; }
        $row += 2; $sheet->setCellValue("A{$row}", 'NOTES :'); $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        foreach ($structure['notes'] as $note) { $row++; $sheet->setCellValue("A{$row}", '• ' . $note); }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet); $path = tempnam(sys_get_temp_dir(), 'template_eleves_'); $writer->save($path);
        return response()->download($path, 'template_import_eleves.xlsx')->deleteFileAfterSend();
    }

    private function telechargerTemplateCSV(array $structure)
    {
        $csv = fopen('php://temp', 'r+'); fputcsv($csv, array_values($structure['headers'])); foreach ($structure['exemples'] as $exemple) fputcsv($csv, $exemple); rewind($csv); $content = stream_get_contents($csv); fclose($csv);
        return response($content, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="template_import_eleves.csv"']);
    }

    private function authorizeJob(Request $request, EleveImportJob $job): void
    {
        $userEtabId = $request->user()->etablissement_id ?? $request->user()->etablissement?->id;
        if ((int) $job->etablissement_id !== (int) $userEtabId) abort(403, 'Cet import ne vous appartient pas.');
    }

    private function preparerLignesPreview(array $donnees, int $etablissementId): array
    {
        foreach ($donnees as &$ligne) {
            $matriculeSaisi = isset($ligne['matricule_desps']) ? mb_strtoupper(preg_replace('/\s+/', '', trim((string) $ligne['matricule_desps']))) : '';
            $despsValide = $matriculeSaisi !== '' && preg_match(EleveParserService::REGEX_MATRICULE_DESPS, $matriculeSaisi);
            $ligne['matricule_desps'] = $despsValide ? $matriculeSaisi : null;
            $ligne['matricule_desps_original'] = $matriculeSaisi ?: ($ligne['matricule_desps_original'] ?? null);
            $ligne['matricule_desps_invalide'] = $matriculeSaisi !== '' && !$despsValide;
            $ligne['matricule_interne'] = trim((string) ($ligne['matricule_interne'] ?? ''));
            $ligne['matricule_interne_auto'] = !$despsValide;
            $ligne['matricule_remplacement_label'] = !$despsValide ? 'Remplacé par matricule interne' : null;
            $ligne['sexe'] = in_array(($ligne['sexe'] ?? null), ['M', 'F'], true) ? $ligne['sexe'] : null;
            $ligne['sexe_a_corriger'] = empty($ligne['sexe']);
        }
        unset($ligne);

        $donnees = $this->parser->attribuerMatriculesInternesPreview($donnees, $etablissementId);

        foreach ($donnees as &$ligne) {
            $lookup = $ligne['matricule_desps'] ?? $ligne['matricule_desps_original'] ?? $ligne['matricule_interne'] ?? null;
            $ligne['matricule_provenances'] = $this->provenancesMatricule($lookup, $etablissementId);
        }
        unset($ligne);

        return $donnees;
    }

    /** @return array<int, array<string, string|int|null|bool>> */
    private function provenancesMatricule(?string $matricule, int $etablissementId): array
    {
        $matricule = strtoupper(preg_replace('/\s+/', '', trim((string) $matricule)));
        if ($matricule === '') {
            return [];
        }

        return Eleve::query()
            ->with('etablissement:id,nom,code_desps,sigle')
            ->where(function ($q) use ($matricule) {
                $q->where('matricule_desps', $matricule)
                  ->orWhere('matricule_interne', $matricule);
            })
            ->where('actif', true)
            ->limit(5)
            ->get()
            ->map(fn (Eleve $e) => [
                'eleve_id' => $e->id,
                'nom' => trim(($e->prenom ?? '').' '.($e->nom ?? '')),
                'ecole' => $e->etablissement?->nom,
                'code' => $e->etablissement?->code_desps ?: $e->etablissement?->sigle,
                'matricule_desps' => $e->matricule_desps,
                'matricule_interne' => $e->matricule_interne,
                'meme_ecole' => (int) $e->etablissement_id === (int) $etablissementId,
            ])->values()->all();
    }

    private function sanitizeRawStatut(?string $value): string { $value = strtoupper(trim((string) $value)); return in_array($value, ['AFF', 'NAFF'], true) ? $value : ''; }
    private function sanitizeStatutEleve(?string $value): string { $value = strtoupper(trim((string) $value)); return in_array($value, ['AFF', 'NAFF'], true) ? $value : ''; }
    private function resolveStatutEleve(?string $statutEleve, ?string $rawStatut): string
    {
        $statutEleve = $this->sanitizeStatutEleve($statutEleve); if ($statutEleve !== '') return $statutEleve;
        $rawStatut = $this->sanitizeRawStatut($rawStatut); if ($rawStatut !== '') return $rawStatut;
        return '';
    }
}
