<?php

namespace App\Http\Controllers;

use App\Exports\FicheClasseExport;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Enseignant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Fiche de classe — liste administrative imprimable (PDF + Excel).
 * Pas d'écriture en base, juste un export visuel.
 */
class FicheClasseController extends Controller
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

    public function pdf(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $etabId = (int) $request->user()->ecoleActiveId();
        $etab   = \App\Models\Etablissement::find($etabId);
        $annee  = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        $orientation = $request->input('orientation', 'landscape');
        if (!in_array($orientation, ['portrait', 'landscape'])) $orientation = 'landscape';

        $pdf = Pdf::loadView('mon-espace.fiche-classe.pdf', compact(
            'etab', 'annee', 'classe', 'ens', 'eleves', 'orientation'
        ))->setPaper('a4', $orientation);

        $fname = sprintf('fiche-classe_%s.pdf', preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom));
        return $pdf->download($fname);
    }

    public function excel(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $etab  = \App\Models\Etablissement::find($request->user()->ecoleActiveId());
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        $fname = sprintf('fiche-classe_%s.xlsx', preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom));
        return Excel::download(new FicheClasseExport($etab, $annee, $classe, $ens, $eleves), $fname);
    }
}
