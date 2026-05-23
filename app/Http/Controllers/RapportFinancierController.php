<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Services\Rapports\RapportFinancierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RapportFinancierController extends Controller
{
    public function __construct(private RapportFinancierService $service) {}

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annees = $etab->anneesScolaires()->orderByDesc('id')->get();
        $anneeCourante = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $classes = Classe::where('etablissement_id', $etab->id)
            ->when($anneeCourante, fn($q) => $q->where('annee_scolaire_id', $anneeCourante->id))
            ->orderBy('nom')->get();

        return view('rapports.index', compact('etab', 'annees', 'anneeCourante', 'classes'));
    }

    public function paiementsPdf(Request $request): Response
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin'   => 'nullable|date|after_or_equal:date_debut',
            'classe_id'  => 'nullable|exists:classes,id',
        ]);

        $etab = $request->user()->etablissement;
        $data = $this->service->paiements(
            $etab,
            $request->date_debut,
            $request->date_fin,
            $request->classe_id ? (int) $request->classe_id : null
        );
        $data['etablissement'] = $etab;
        $data['classe'] = $request->classe_id
            ? Classe::find($request->classe_id)
            : null;

        $pdf = Pdf::loadView('rapports.pdf.paiements', $data)->setPaper('a4', 'portrait');
        $nom = "rapport-paiements-{$data['debut']}-au-{$data['fin']}.pdf";

        return $request->boolean('download')
            ? $pdf->download($nom)
            : $pdf->stream($nom);
    }

    public function bilanScolaritePdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $data = $this->service->bilanScolarite($etab, $request->annee_id ? (int) $request->annee_id : null);
        $data['etablissement'] = $etab;

        $pdf = Pdf::loadView('rapports.pdf.bilan-scolarite', $data)->setPaper('a4', 'portrait');
        $nom = "bilan-scolarite-{$data['annee']?->libelle}.pdf";

        return $request->boolean('download')
            ? $pdf->download($nom)
            : $pdf->stream($nom);
    }

    public function mensuelPdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $data = $this->service->mensuel($etab, $request->mois);
        $data['etablissement'] = $etab;

        $pdf = Pdf::loadView('rapports.pdf.periode', $data)->setPaper('a4', 'portrait');
        $nom = "rapport-mensuel-{$data['periode_id']}.pdf";

        return $request->boolean('download')
            ? $pdf->download($nom)
            : $pdf->stream($nom);
    }

    public function trimestrielPdf(Request $request): Response
    {
        $etab = $request->user()->etablissement;
        $data = $this->service->trimestriel(
            $etab,
            $request->annee ? (int) $request->annee : null,
            $request->trimestre ? (int) $request->trimestre : null,
        );
        $data['etablissement'] = $etab;

        $pdf = Pdf::loadView('rapports.pdf.periode', $data)->setPaper('a4', 'portrait');
        $nom = "rapport-trimestriel-{$data['periode_id']}.pdf";

        return $request->boolean('download')
            ? $pdf->download($nom)
            : $pdf->stream($nom);
    }
}
