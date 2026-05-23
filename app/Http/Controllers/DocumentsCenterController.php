<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Niveau;
use App\Models\Trimestre;
use App\Services\Documents\DocumentsService;
use App\Services\Scolarite\AnneeScolaireService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentsCenterController extends Controller
{
    public function __construct(private DocumentsService $service) {}

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::courantePourEtablissement($etab->id);

        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->orderBy('nom')->get()
            : collect();

        $niveaux = Niveau::where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->orderBy('ordre')->get();

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        return view('documents.index', compact('etab', 'annee', 'classes', 'niveaux', 'trimestres'));
    }

    // ─── 1. Liste élèves ───────────────────────────────────────
    public function listeElevesPdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);

        $inscriptions = $this->service->listeEleves(
            $etab->id, $annee->id,
            $request->classe_id ? (int) $request->classe_id : null,
            $request->niveau_id ? (int) $request->niveau_id : null
        );

        $filtre = $this->labelFiltre($request);
        $pdf = Pdf::loadView('documents.pdf.eleves', compact('etab', 'annee', 'inscriptions', 'filtre'))
            ->setPaper('a4', 'portrait');

        return $this->respondPdf($request, $pdf, 'liste-eleves');
    }

    // ─── 2. Élèves non soldés ──────────────────────────────────
    public function elevesNonSoldesPdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);

        $data = $this->service->elevesNonSoldes($etab->id, $annee->id,
            $request->classe_id ? (int) $request->classe_id : null);

        $pdf = Pdf::loadView('documents.pdf.eleves-non-soldes', array_merge($data, [
            'etab' => $etab, 'annee' => $annee, 'filtre' => $this->labelFiltre($request),
        ]))->setPaper('a4', 'portrait');

        return $this->respondPdf($request, $pdf, 'eleves-non-soldes');
    }

    // ─── 3. Annuaire parents ───────────────────────────────────
    public function annuaireParentsPdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);

        $inscriptions = $this->service->annuaireParents($etab->id, $annee->id,
            $request->classe_id ? (int) $request->classe_id : null);

        $pdf = Pdf::loadView('documents.pdf.annuaire', [
            'etab' => $etab, 'annee' => $annee, 'inscriptions' => $inscriptions,
            'filtre' => $this->labelFiltre($request),
        ])->setPaper('a4', 'portrait');

        return $this->respondPdf($request, $pdf, 'annuaire-parents');
    }

    // ─── 4. Nappe des moyennes (classe) ────────────────────────
    public function nappeMoyennesPdf(Request $request): Response
    {
        $request->validate([
            'classe_id'    => 'required|exists:classes,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $etab = $request->user()->etablissement;
        $data = $this->service->nappeMoyennes($etab->id, (int) $request->classe_id, (int) $request->trimestre_id);

        $pdf = Pdf::loadView('documents.pdf.nappe-moyennes', array_merge($data, ['etab' => $etab]))
            ->setPaper('a4', 'landscape');

        return $this->respondPdf($request, $pdf, 'nappe-moyennes-'.$data['classe']->nom);
    }

    // ─── 5. Synthèse niveau ────────────────────────────────────
    public function syntheseNiveauPdf(Request $request): Response
    {
        $request->validate([
            'niveau_id'    => 'required|exists:niveaux,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);

        $data = $this->service->syntheseNiveau($etab->id, $annee->id, (int) $request->niveau_id, (int) $request->trimestre_id);

        $pdf = Pdf::loadView('documents.pdf.synthese-niveau', array_merge($data, ['etab' => $etab]))
            ->setPaper('a4', 'portrait');

        return $this->respondPdf($request, $pdf, 'synthese-niveau-'.$data['niveau']->libelle);
    }

    // ─── 6. Liste enseignants ──────────────────────────────────
    public function listeEnseignantsPdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $enseignants = $this->service->listeEnseignants($etab->id);

        $pdf = Pdf::loadView('documents.pdf.enseignants', compact('etab', 'enseignants'))
            ->setPaper('a4', 'portrait');

        return $this->respondPdf($request, $pdf, 'liste-enseignants');
    }

    // ─── 7. Récap paie du mois ─────────────────────────────────
    public function recapPaiePdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $mois = $request->get('mois', now()->format('Y-m'));

        $data = $this->service->recapPaieMois($etab->id, $mois);
        $pdf = Pdf::loadView('documents.pdf.recap-paie', array_merge($data, ['etab' => $etab]))
            ->setPaper('a4', 'portrait');

        return $this->respondPdf($request, $pdf, 'recap-paie-'.$mois);
    }

    // ─── 8. Bulletin individuel ────────────────────────────────
    public function bulletinElevePdf(Request $request): Response
    {
        $request->validate([
            'eleve_id'     => 'required|exists:eleves,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $etab = $request->user()->etablissement;
        $data = $this->service->bulletinEleve($etab->id, (int) $request->eleve_id, (int) $request->trimestre_id);
        $data['etab'] = $etab;

        $pdf = Pdf::loadView('documents.pdf.bulletin', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'bulletin-'.($data['eleve']->matricule_interne ?? $data['eleve']->id));
    }

    // ─── 9. Tableau d'honneur ──────────────────────────────────
    public function tableauHonneurPdf(Request $request): Response
    {
        $request->validate([
            'classe_id'    => 'required|exists:classes,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $etab = $request->user()->etablissement;
        $top = (int) ($request->top ?? 5);
        $data = $this->service->tableauHonneur($etab->id, (int) $request->classe_id, (int) $request->trimestre_id, $top);
        $data['etab'] = $etab;

        $pdf = Pdf::loadView('documents.pdf.tableau-honneur', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'honneur-'.$data['classe']->nom);
    }

    // ─── 10. Élèves en difficulté ─────────────────────────────
    public function elevesDifficultePdf(Request $request): Response
    {
        $request->validate(['trimestre_id' => 'required|exists:trimestres,id']);

        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);
        $seuil = (float) ($request->seuil ?? 10);
        $data = $this->service->elevesEnDifficulte($etab->id, $annee->id, (int) $request->trimestre_id,
            $request->classe_id ? (int) $request->classe_id : null, $seuil);
        $data['etab'] = $etab;
        $data['annee'] = $annee;
        $data['filtre'] = $this->labelFiltre($request);

        $pdf = Pdf::loadView('documents.pdf.eleves-difficulte', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'eleves-difficulte');
    }

    // ─── 11. Carnet de présence vide ───────────────────────────
    public function carnetPresencePdf(Request $request): Response
    {
        $request->validate([
            'classe_id'      => 'required|exists:classes,id',
            'debut_semaine'  => 'nullable|date',
        ]);

        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);
        $debut = $request->debut_semaine ?: now()->startOfWeek()->toDateString();

        $data = $this->service->carnetPresence($etab->id, $annee->id, (int) $request->classe_id, $debut);
        $data['etab'] = $etab;

        $pdf = Pdf::loadView('documents.pdf.carnet-presence', $data)->setPaper('a4', 'landscape');
        return $this->respondPdf($request, $pdf, 'carnet-presence-'.$data['classe']->nom);
    }

    // ─── 12. Certificat de scolarité ───────────────────────────
    public function certificatScolaritePdf(Request $request): Response
    {
        $request->validate(['eleve_id' => 'required|exists:eleves,id']);

        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);
        $data = $this->service->certificatScolarite($etab->id, (int) $request->eleve_id, $annee->id);
        $data['etab'] = $etab;

        $pdf = Pdf::loadView('documents.pdf.certificat-scolarite', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'certificat-'.($data['eleve']->matricule_interne ?? $data['eleve']->id));
    }

    // ─── 13. Bulletins en lot par classe ──────────────────────
    public function bulletinsClassePdf(Request $request): Response
    {
        $request->validate([
            'classe_id'    => 'required|exists:classes,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $etab = $request->user()->etablissement;
        $data = $this->service->bulletinsClasse($etab->id, (int) $request->classe_id, (int) $request->trimestre_id);
        $data['etab'] = $etab;

        $pdf = Pdf::loadView('documents.pdf.bulletins-classe', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'bulletins-'.$data['classe']->nom.'-'.$data['trimestre']->numero);
    }

    // ─── 14. Attestation de paiement ──────────────────────────
    public function attestationPaiementPdf(Request $request): Response
    {
        $request->validate(['eleve_id' => 'required|exists:eleves,id']);

        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);
        $data = $this->service->attestationPaiement($etab->id, (int) $request->eleve_id, $annee->id);
        $data['etab'] = $etab;

        $pdf = Pdf::loadView('documents.pdf.attestation-paiement', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'attestation-'.($data['eleve']->matricule_interne ?? $data['eleve']->id));
    }

    // ─── 15. Récapitulatif annuel école ───────────────────────
    public function recapAnnuelPdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $annee = $request->annee_id
            ? \App\Models\AnneeScolaire::where('etablissement_id', $etab->id)->find($request->annee_id)
            : AnneeScolaireService::couranteOuEchec($etab->id);

        $data = $this->service->recapAnnuelEcole($etab->id, $annee->id);
        $data['etab'] = $etab;

        $pdf = Pdf::loadView('documents.pdf.recap-annuel', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'recap-annuel-'.$annee->libelle);
    }

    // ─── 16. Cartes élève PDF avec QR ────────────────────────
    public function cartesElevesPdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);
        $data = $this->service->cartesEleves($etab->id, $annee->id,
            $request->classe_id ? (int) $request->classe_id : null);
        $data['etab'] = $etab;
        $data['annee'] = $annee;

        $pdf = Pdf::loadView('documents.pdf.cartes-eleves', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'cartes-eleves-'.($request->classe_id ? 'classe' : 'toutes'));
    }

    // ─── 17. Calendrier scolaire annuel ───────────────────────
    public function calendrierAnnuelPdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $annee = $request->annee_id
            ? \App\Models\AnneeScolaire::where('etablissement_id', $etab->id)->find($request->annee_id)
            : AnneeScolaireService::couranteOuEchec($etab->id);

        $data = $this->service->calendrierAnnuel($etab->id, $annee->id);
        $data['etab'] = $etab;

        $pdf = Pdf::loadView('documents.pdf.calendrier-annuel', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'calendrier-'.$annee->libelle);
    }

    // ─── 18. Convocation conseil de classe ────────────────────
    public function convocationConseilPdf(Request $request): Response
    {
        $request->validate(['conseil_id' => 'required|exists:conseils_classe,id']);

        $etab = $request->user()->etablissement;
        $data = $this->service->convocationConseilClasse($etab->id, (int) $request->conseil_id);
        $data['etab'] = $etab;

        $pdf = Pdf::loadView('documents.pdf.convocation-conseil', $data)->setPaper('a4', 'portrait');
        return $this->respondPdf($request, $pdf, 'convocation-conseil-'.$data['conseil']->classe?->nom);
    }

    // ═══════════════ EXPORTS CSV ═══════════════════════════════

    /** Liste élèves au format CSV */
    public function listeElevesCsv(Request $request): StreamedResponse
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);
        $inscriptions = $this->service->listeEleves($etab->id, $annee->id,
            $request->classe_id ? (int) $request->classe_id : null,
            $request->niveau_id ? (int) $request->niveau_id : null);

        return $this->streamCsv("liste-eleves-{$annee->libelle}.csv",
            ['Matricule', 'Nom', 'Prénom', 'Sexe', 'Date naissance', 'Classe', 'Niveau', 'Redoublant', 'Contact urgence', 'Téléphone urgence'],
            $inscriptions->map(fn($i) => [
                $i->eleve?->matricule_interne ?? '',
                $i->eleve?->nom ?? '',
                $i->eleve?->prenom ?? '',
                $i->eleve?->sexe ?? '',
                $i->eleve?->date_naissance?->format('d/m/Y') ?? '',
                $i->classe?->nom ?? '',
                $i->classe?->niveau?->libelle ?? '',
                $i->eleve?->redoublant ? 'Oui' : 'Non',
                $i->eleve?->contact_urgence_nom ?? '',
                $i->eleve?->contact_urgence_tel ?? '',
            ]));
    }

    /** Élèves non soldés CSV */
    public function elevesNonSoldesCsv(Request $request): StreamedResponse
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);
        $data = $this->service->elevesNonSoldes($etab->id, $annee->id,
            $request->classe_id ? (int) $request->classe_id : null);

        return $this->streamCsv("eleves-non-soldes-{$annee->libelle}.csv",
            ['Matricule', 'Nom', 'Prénom', 'Classe', 'Contact', 'Téléphone', 'Dû', 'Payé', 'Reste', 'Taux %'],
            $data['inscriptions']->map(fn($i) => [
                $i->eleve?->matricule_interne ?? '',
                $i->eleve?->nom ?? '',
                $i->eleve?->prenom ?? '',
                $i->classe?->nom ?? '',
                $i->eleve?->contact_urgence_nom ?? '',
                $i->eleve?->contact_urgence_tel ?? '',
                (int) $i->montant_net,
                (int) $i->paye_calc,
                (int) $i->reste_calc,
                $i->taux_calc,
            ]));
    }

    /** Récap paie CSV */
    public function recapPaieCsv(Request $request): StreamedResponse
    {
        $etab = $request->user()->etablissement;
        $mois = $request->get('mois', now()->format('Y-m'));
        $data = $this->service->recapPaieMois($etab->id, $mois);

        return $this->streamCsv("recap-paie-{$mois}.csv",
            ['Réf', 'Matricule', 'Nom', 'Prénom', 'Statut', 'Type rém.', 'Heures', 'Salaire base', 'Taux horaire', 'Brut', 'CNPS', 'IUTS', 'Net', 'Statut paie'],
            $data['fiches']->map(fn($f) => [
                $f->reference,
                $f->enseignant?->matricule_mena ?? '',
                $f->enseignant?->nom ?? '',
                $f->enseignant?->prenom ?? '',
                $f->enseignant?->statut ?? '',
                $f->type_remuneration,
                number_format($f->heures_travaillees, 2, '.', ''),
                (int) $f->salaire_base,
                (int) $f->taux_horaire,
                (int) $f->salaire_brut,
                (int) $f->cotisations_sociales,
                (int) $f->impots,
                (int) $f->salaire_net,
                $f->statut,
            ]));
    }

    // ─── Helpers ───────────────────────────────────────────────
    private function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return new StreamedResponse(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function respondPdf(Request $request, $pdf, string $nomBase): Response
    {
        $nom = $nomBase.'-'.now()->format('Ymd-His').'.pdf';
        return $request->boolean('download') ? $pdf->download($nom) : $pdf->stream($nom);
    }

    private function labelFiltre(Request $request): string
    {
        if ($request->classe_id) {
            $c = Classe::find($request->classe_id);
            return $c ? "Classe : {$c->nom}" : '';
        }
        if ($request->niveau_id) {
            $n = Niveau::find($request->niveau_id);
            return $n ? "Niveau : {$n->libelle}" : '';
        }
        return 'Toutes classes';
    }
}
