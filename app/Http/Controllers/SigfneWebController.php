<?php

namespace App\Http\Controllers;

use App\Models\RemonteeSigfne;
use App\Models\Trimestre;
use App\Services\Scolarite\AnneeScolaireService;
use App\Services\Sigfne\SigfneSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SigfneWebController extends Controller
{
    public function __construct(private SigfneSyncService $sync) {}

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::courantePourEtablissement($etab->id);

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $remontees = RemonteeSigfne::where('etablissement_id', $etab->id)
            ->with(['trimestre', 'anneeScolaire', 'envoyePar:id,nom,prenom'])
            ->latest()->take(20)->get();

        $plateforme = $this->sync->plateformePourEtablissement($etab);
        $urlPlateforme = $this->sync->urlPlateforme($etab);

        return view('sigfne.index', compact(
            'etab', 'annee', 'trimestres', 'remontees', 'plateforme', 'urlPlateforme'
        ));
    }

    public function preparer(Request $request)
    {
        $request->validate(['trimestre_id' => 'required|exists:trimestres,id']);

        $etab = $request->user()->etablissement;
        $trimestre = Trimestre::findOrFail($request->trimestre_id);

        $data = $this->sync->preparer($etab, $trimestre);

        return response()->json([
            'plateforme'     => $data['plateforme'],
            'total'          => $data['total'],
            'prets'          => $data['prets'],
            'sans_matricule' => $data['sans_matricule'],
            'sans_moyenne'   => $data['sans_moyenne'],
            'taux'           => $data['taux'],
            'apercu'         => $data['eleves']->take(10)->values(),
            'erreurs'        => $data['eleves']->filter(fn($e) => ! $e['pret'])->take(20)->values(),
        ]);
    }

    public function executer(Request $request)
    {
        $validated = $request->validate([
            'trimestre_id' => 'required|exists:trimestres,id',
            'push_api'     => 'nullable|boolean',
        ]);

        $etab = $request->user()->etablissement;
        $trimestre = Trimestre::findOrFail($validated['trimestre_id']);

        $remontee = $this->sync->executer($etab, $trimestre, $request->user(),
            $request->boolean('push_api'));

        $msg = match ($remontee->statut) {
            'envoye'     => "✓ Remontée envoyée à SIGFNE avec succès ! {$remontee->eleves_remontes} élèves transmis.",
            'pret_envoi' => "✓ Fichier généré ({$remontee->eleves_remontes} élèves). Téléchargez-le pour upload manuel sur la plateforme.",
            'erreur_api' => "⚠ Fichier généré mais l'envoi API a échoué. Téléchargez et uploadez manuellement.",
            default      => "⚠ Fichier généré avec erreurs : {$remontee->eleves_en_erreur} élève(s) non transmissibles.",
        };

        return redirect()->route('sigfne.index')->with('success', $msg);
    }

    public function telechargerFichier(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $remontee = RemonteeSigfne::where('etablissement_id', $etab)->findOrFail($id);
        abort_unless($remontee->fichier_export_path && Storage::disk('local')->exists($remontee->fichier_export_path), 404);

        return Storage::disk('local')->download(
            $remontee->fichier_export_path,
            'sigfne-'.($remontee->trimestre?->libelle ?? 't').'-'.now()->format('Ymd').'.csv'
        );
    }

    public function parametrer(Request $request)
    {
        $validated = $request->validate([
            'sigfne_actif'      => 'nullable|boolean',
            'sigfne_login'      => 'nullable|string|max:100',
            'sigfne_token'      => 'nullable|string|max:1000',
            'sigfne_plateforme' => 'nullable|in:agfne,agcp',
        ]);

        $etab = $request->user()->etablissement;
        $etab->update([
            'sigfne_actif'      => ! empty($validated['sigfne_actif']),
            'sigfne_login'      => $validated['sigfne_login'] ?? null,
            'sigfne_token'      => ! empty($validated['sigfne_token']) ? $validated['sigfne_token'] : $etab->sigfne_token,
            'sigfne_plateforme' => $validated['sigfne_plateforme'] ?? null,
        ]);

        return back()->with('success', 'Paramètres SIGFNE enregistrés.');
    }

    public function dfa()
    {
        return view('sigfne.dfa');
    }
}
