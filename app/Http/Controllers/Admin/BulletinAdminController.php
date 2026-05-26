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

/**
 * Génération des bulletins par la direction.
 *
 *  - calcule les moyennes par matière à partir des notes ET/OU des moyennes saisies directement
 *  - calcule la moyenne générale pondérée par les coefficients matière
 *  - détermine le rang et la mention
 *  - rend un PDF par élève ou bulletin de classe
 */
class BulletinAdminController extends Controller
{
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
                ->where('active', true)->orderBy('nom')->get()
            : collect();

        $classeId = $request->input('classe_id');
        $trimestreId = $request->input('trimestre_id', $trimestres->first(fn($t) => $t->en_cours)?->id ?? $trimestres->first()?->id);

        $eleves = collect();
        if ($classeId && $trimestreId && $annee) {
            $eleves = Eleve::where('classe_id', $classeId)
                ->where('actif', true)
                ->inscritsCetteAnnee($annee->id)
                ->orderBy('nom')->orderBy('prenom')
                ->get();
        }

        // Récupérer les moyennes générales si déjà calculées
        $moyennesGenerales = $classeId && $trimestreId
            ? MoyenneGenerale::where('classe_id', $classeId)
                ->where('trimestre_id', $trimestreId)
                ->get()->keyBy('eleve_id')
            : collect();

        // Moyennes annuelles (si calculées)
        $moyennesAnnuelles = $classeId && $annee
            ? \App\Models\MoyenneAnnuelle::where('classe_id', $classeId)
                ->where('annee_scolaire_id', $annee->id)
                ->get()->keyBy('eleve_id')
            : collect();

        return view('admin.rh.bulletins.index',
            compact('annee', 'classes', 'trimestres', 'classeId', 'trimestreId', 'eleves', 'moyennesGenerales', 'moyennesAnnuelles'));
    }

    /**
     * Lance le calcul/recalcul des moyennes pour une classe + trimestre.
     */
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

            // Recalcul automatique de la moyenne annuelle (intègre coefs trimestres)
            MoyenneAnnuelleService::calculerPourClasse($classe, $annee);
        });

        return redirect()->route('admin.rh.bulletins.index', [
            'classe_id' => $classe->id,
            'trimestre_id' => $trimestre->id,
        ])->with('success', 'Moyennes calculées + rangs établis + moyennes annuelles mises à jour (coefs trimestres pris en compte).');
    }

    /**
     * Pour un élève : calcule moyenne par matière puis moyenne générale.
     */
    private function calculerPourEleve(Eleve $eleve, Classe $classe, Trimestre $trimestre, AnneeScolaire $annee): void
    {
        $matieres = DB::table('affectations')
            ->join('matieres', 'matieres.id', '=', 'affectations.matiere_id')
            ->where('affectations.classe_id', $classe->id)
            ->where('affectations.active', true)
            ->where('affectations.annee_scolaire_id', $annee->id)
            ->select('matieres.id', 'matieres.coefficient_defaut')
            ->distinct()
            ->get();

        $totalPoints = 0;
        $totalCoefs  = 0;

        foreach ($matieres as $matRow) {
            $matiereId = $matRow->id;
            $coefMat = (float) ($matRow->coefficient_defaut ?? 1);

            // 1) Moyenne déjà saisie directement ?
            $moyDirect = MoyenneMatiere::where('eleve_id', $eleve->id)
                ->where('matiere_id', $matiereId)
                ->where('trimestre_id', $trimestre->id)
                ->where('saisie_directe', true)
                ->first();

            if ($moyDirect) {
                $moyenne = (float) $moyDirect->moyenne;
            } else {
                // 2) Sinon : moyenne calculée à partir des notes individuelles
                $notes = Note::join('evaluations', 'evaluations.id', '=', 'notes.evaluation_id')
                    ->where('notes.eleve_id', $eleve->id)
                    ->where('evaluations.matiere_id', $matiereId)
                    ->where('evaluations.trimestre_id', $trimestre->id)
                    ->whereNotNull('notes.note')
                    ->where('notes.absent', false)
                    ->where('notes.dispense', false)
                    ->select(
                        'notes.note', 'evaluations.note_sur', 'evaluations.coefficient'
                    )->get();

                if ($notes->isEmpty()) continue;

                $sommePoints = 0; $sommeCoefs = 0;
                foreach ($notes as $n) {
                    $bareme = (float) ($n->note_sur ?: 20);
                    $coef   = (float) ($n->coefficient ?: 1);
                    $n20    = $bareme > 0 ? ((float) $n->note / $bareme) * 20 : (float) $n->note;
                    $sommePoints += $n20 * $coef;
                    $sommeCoefs  += $coef;
                }
                if ($sommeCoefs <= 0) continue;
                $moyenne = $sommePoints / $sommeCoefs;

                // Upsert moyenne_matieres
                MoyenneMatiere::updateOrCreate(
                    ['eleve_id' => $eleve->id, 'matiere_id' => $matiereId, 'trimestre_id' => $trimestre->id],
                    [
                        'classe_id'        => $classe->id,
                        'moyenne'          => round($moyenne, 2),
                        'moyenne_ponderee' => round($moyenne * $coefMat, 2),
                        'saisie_directe'   => false,
                    ]
                );
            }

            $totalPoints += $moyenne * $coefMat;
            $totalCoefs  += $coefMat;
        }

        $moyGen = $totalCoefs > 0 ? round($totalPoints / $totalCoefs, 2) : null;

        // Absences/retards du trimestre
        $totalAbs = PresenceEleve::where('eleve_id', $eleve->id)
            ->whereBetween('date', [$trimestre->date_debut, $trimestre->date_fin])
            ->where('statut', 'absent')->count();
        $absJust = PresenceEleve::where('eleve_id', $eleve->id)
            ->whereBetween('date', [$trimestre->date_debut, $trimestre->date_fin])
            ->where('statut', 'absent')->where('justifie', true)->count();
        $totalRet = PresenceEleve::where('eleve_id', $eleve->id)
            ->whereBetween('date', [$trimestre->date_debut, $trimestre->date_fin])
            ->where('statut', 'retard')->count();

        $mention = $this->calculerMention($moyGen);

        MoyenneGenerale::updateOrCreate(
            ['eleve_id' => $eleve->id, 'trimestre_id' => $trimestre->id],
            [
                'classe_id'         => $classe->id,
                'annee_scolaire_id' => $annee->id,
                'moyenne_generale'  => $moyGen,
                'total_points'      => round($totalPoints, 2),
                'total_coefficients'=> $totalCoefs,
                'mention'           => $mention,
                'total_absences'    => $totalAbs,
                'absences_justifiees' => $absJust,
                'total_retards'     => $totalRet,
            ]
        );
    }

    private function calculerMention(?float $moy): string
    {
        if ($moy === null) return 'aucune';
        if ($moy >= 16)  return 'felicitations';
        if ($moy >= 14)  return 'tableau_honneur';
        if ($moy >= 12)  return 'encouragements';
        if ($moy < 8)    return 'avertissement';
        return 'aucune';
    }

    /**
     * Calcule les rangs (1er, 2e...) pour la classe.
     */
    private function calculerRangs(Classe $classe, Trimestre $trimestre): void
    {
        $generales = MoyenneGenerale::where('classe_id', $classe->id)
            ->where('trimestre_id', $trimestre->id)
            ->whereNotNull('moyenne_generale')
            ->orderByDesc('moyenne_generale')
            ->get();

        $effectif = $generales->count();
        if ($effectif === 0) return;

        $moyMax = $generales->first()->moyenne_generale;
        $moyMin = $generales->last()->moyenne_generale;
        $moyClasse = round($generales->avg('moyenne_generale'), 2);

        foreach ($generales as $i => $g) {
            $g->update([
                'rang'             => $i + 1,
                'effectif_classe'  => $effectif,
                'moyenne_premier'  => $moyMax,
                'moyenne_dernier'  => $moyMin,
                'moyenne_classe'   => $moyClasse,
            ]);
        }
    }

    /**
     * Génère le PDF du bulletin pour un élève.
     */
    public function pdf(Request $request, Eleve $eleve, Trimestre $trimestre)
    {
        abort_unless($eleve->etablissement_id === $this->etabId($request), 404);

        $classe = $eleve->classe;
        $annee  = AnneeScolaire::find($trimestre->annee_scolaire_id);
        $etab   = $eleve->etablissement;

        $generale = MoyenneGenerale::where('eleve_id', $eleve->id)
            ->where('trimestre_id', $trimestre->id)->first();

        abort_if(!$generale, 422, 'La moyenne générale n\'a pas encore été calculée. Lancez le calcul d\'abord.');

        $moyennes = MoyenneMatiere::where('eleve_id', $eleve->id)
            ->where('trimestre_id', $trimestre->id)
            ->with('matiere')
            ->get();

        $pdf = Pdf::loadView('admin.rh.bulletins.pdf', compact(
            'etab', 'annee', 'trimestre', 'classe', 'eleve', 'generale', 'moyennes'
        ))->setPaper('a4', 'portrait');

        $fname = sprintf('bulletin_%s_%s_T%d.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $eleve->nom),
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe?->nom ?? ''),
            $trimestre->numero);

        return $pdf->download($fname);
    }

    /**
     * Génère un ZIP de tous les bulletins de la classe.
     */
    public function pdfClasse(Request $request)
    {
        $data = $request->validate([
            'classe_id'    => 'required|exists:classes,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $classe = Classe::where('etablissement_id', $this->etabId($request))->findOrFail($data['classe_id']);
        $trimestre = Trimestre::findOrFail($data['trimestre_id']);
        $eleves = Eleve::where('classe_id', $classe->id)->where('actif', true)->get();
        $annee  = AnneeScolaire::find($trimestre->annee_scolaire_id);
        $etab   = $classe->etablissement;

        $bulletins = [];
        foreach ($eleves as $eleve) {
            $generale = MoyenneGenerale::where('eleve_id', $eleve->id)
                ->where('trimestre_id', $trimestre->id)->first();
            if (!$generale) continue;

            $moyennes = MoyenneMatiere::where('eleve_id', $eleve->id)
                ->where('trimestre_id', $trimestre->id)
                ->with('matiere')->get();

            $bulletins[] = compact('eleve', 'generale', 'moyennes');
        }

        $pdf = Pdf::loadView('admin.rh.bulletins.pdf-classe', compact(
            'etab', 'annee', 'trimestre', 'classe', 'bulletins'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("bulletins_{$classe->nom}_T{$trimestre->numero}.pdf");
    }

    /**
     * Génération en masse avec disposition configurable (1/2/3/4 bulletins par page A4).
     * Auto-calcule les moyennes générales si elles ne l'ont pas encore été.
     */
    public function pdfMasse(Request $request)
    {
        $data = $request->validate([
            'classe_id'    => 'required|exists:classes,id',
            'trimestre_id' => 'required|exists:trimestres,id',
            'disposition'  => 'required|in:1,2,3,4',
            'eleve_ids'    => 'required|array|min:1',
            'eleve_ids.*'  => 'exists:eleves,id',
        ]);

        $etabId    = $this->etabId($request);
        $classe    = Classe::where('etablissement_id', $etabId)->findOrFail($data['classe_id']);
        $trimestre = Trimestre::findOrFail($data['trimestre_id']);
        $annee     = AnneeScolaire::findOrFail($trimestre->annee_scolaire_id);
        $etab      = $classe->etablissement;
        $disposition = (int) $data['disposition'];

        $bulletins = [];

        DB::transaction(function () use ($data, $classe, $trimestre, $annee, &$bulletins) {
            foreach ($data['eleve_ids'] as $eleveId) {
                $eleve = Eleve::findOrFail($eleveId);

                // Auto-calculer si pas encore fait
                $generale = MoyenneGenerale::where('eleve_id', $eleve->id)
                    ->where('trimestre_id', $trimestre->id)->first();

                if (!$generale) {
                    $this->calculerPourEleve($eleve, $classe, $trimestre, $annee);
                    $this->calculerRangs($classe, $trimestre);
                    $generale = MoyenneGenerale::where('eleve_id', $eleve->id)
                        ->where('trimestre_id', $trimestre->id)->first();
                }

                if (!$generale) return;

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

        $pdf = Pdf::loadView('admin.rh.bulletins.pdf-masse', compact(
            'etab', 'annee', 'trimestre', 'classe', 'bulletins', 'disposition'
        ))->setPaper('a4', 'portrait');

        $fname = sprintf(
            'bulletins_%s_T%d_%deleves.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            $trimestre->numero,
            count($bulletins)
        );

        return $pdf->download($fname);
    }
}
