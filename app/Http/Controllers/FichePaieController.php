<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use App\Models\FichePaie;
use App\Services\Salaire\SalaireService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FichePaieController extends Controller
{
    public function __construct(private SalaireService $service) {}

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement_id;
        $mois = $request->get('mois', now()->format('Y-m'));

        $fiches = FichePaie::where('etablissement_id', $etab)
            ->where('mois', $mois)
            ->with(['enseignant:id,nom,prenom,matricule_mena', 'generePar:id,nom,prenom', 'validePar:id,nom,prenom'])
            ->orderBy('enseignant_id')
            ->get();

        // Enseignants actifs sans fiche pour ce mois
        $enseignantsAvecFiche = $fiches->pluck('enseignant_id')->toArray();
        $enseignantsSansFiche = Enseignant::where('etablissement_id', $etab)
            ->where('actif', true)
            ->whereNotIn('id', $enseignantsAvecFiche)
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        $stats = [
            'nb_total'          => $fiches->count(),
            'nb_brouillon'      => $fiches->where('statut', 'brouillon')->count(),
            'nb_validees'       => $fiches->where('statut', 'validee')->count(),
            'nb_payees'         => $fiches->where('statut', 'payee')->count(),
            'total_brut'        => (int) $fiches->sum('salaire_brut'),
            'total_net'         => (int) $fiches->sum('salaire_net'),
            'total_cotisations' => (int) $fiches->sum('cotisations_sociales'),
            'total_impots'      => (int) $fiches->sum('impots'),
            'total_heures'      => (float) $fiches->sum('heures_travaillees'),
        ];

        return view('fiches-paie.index', compact('fiches', 'mois', 'stats', 'enseignantsSansFiche'));
    }

    public function previsualiser(Request $request, Enseignant $enseignant)
    {
        abort_unless($enseignant->etablissement_id === $request->user()->etablissement_id, 403);
        $mois = $request->get('mois', now()->format('Y-m'));
        return response()->json($this->service->previsualiser($enseignant, $mois));
    }

    public function generer(Request $request)
    {
        $validated = $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'mois'          => 'required|regex:/^\d{4}-\d{2}$/',
            'primes'        => 'nullable|integer|min:0',
            'indemnites'    => 'nullable|integer|min:0',
            'avances'       => 'nullable|integer|min:0',
            'retenues'      => 'nullable|integer|min:0',
            'observations'  => 'nullable|string',
        ]);

        $etab = $request->user()->etablissement_id;
        $enseignant = Enseignant::where('etablissement_id', $etab)->findOrFail($validated['enseignant_id']);

        // Vérifier qu'il n'existe pas déjà une fiche pour ce mois
        $existe = FichePaie::where('enseignant_id', $enseignant->id)->where('mois', $validated['mois'])->exists();
        abort_if($existe, 422, 'Une fiche existe déjà pour ce mois.');

        $calc = $this->service->calculerPourEnseignant($enseignant, $validated['mois']);

        $primes      = (int) ($validated['primes'] ?? 0);
        $indemnites  = (int) ($validated['indemnites'] ?? 0);
        $avances     = (int) ($validated['avances'] ?? 0);
        $retenuesExt = (int) ($validated['retenues'] ?? 0);

        $brut = $calc['salaire_brut'] + $primes + $indemnites;
        $cnps = (int) round($brut * SalaireService::TAUX_COTISATIONS_SOCIALES / 100);
        $iuts = (int) round($brut * SalaireService::TAUX_IUTS / 100);
        $net  = $brut - $cnps - $iuts - $avances - $retenuesExt;

        $fiche = FichePaie::create([
            'etablissement_id'      => $etab,
            'enseignant_id'         => $enseignant->id,
            'reference'             => FichePaie::genererReference($etab),
            'mois'                  => $validated['mois'],
            'periode_debut'         => $calc['periode_debut'],
            'periode_fin'           => $calc['periode_fin'],
            'type_remuneration'     => $calc['type_remuneration'],
            'salaire_base'          => $calc['salaire_base'],
            'taux_horaire'          => $calc['taux_horaire'],
            'heures_travaillees'    => $calc['heures_travaillees'],
            'heures_contractuelles' => $calc['heures_contractuelles'],
            'montant_horaire'       => $calc['montant_horaire'],
            'primes'                => $primes,
            'indemnites'            => $indemnites,
            'avances'               => $avances,
            'retenues'              => $retenuesExt,
            'salaire_brut'          => $brut,
            'cotisations_sociales'  => $cnps,
            'impots'                => $iuts,
            'salaire_net'           => $net,
            'nb_jours_travailles'   => $calc['nb_jours_travailles'],
            'nb_jours_absents'      => $calc['nb_jours_absents'],
            'nb_retards'            => $calc['nb_retards'],
            'statut'                => 'brouillon',
            'generee_par'           => $request->user()->id,
            'observations'          => $validated['observations'] ?? null,
        ]);

        return redirect()->route('fiches-paie.show', $fiche->id)
            ->with('success', "Fiche {$fiche->reference} générée.");
    }

    public function genererPourTous(Request $request)
    {
        $validated = $request->validate(['mois' => 'required|regex:/^\d{4}-\d{2}$/']);
        $etab = $request->user()->etablissement_id;

        $sansFiche = Enseignant::where('etablissement_id', $etab)
            ->where('actif', true)
            ->whereNotIn('id', FichePaie::where('etablissement_id', $etab)->where('mois', $validated['mois'])->pluck('enseignant_id'))
            ->get();

        $count = 0;
        foreach ($sansFiche as $ens) {
            $calc = $this->service->calculerPourEnseignant($ens, $validated['mois']);
            $brut = $calc['salaire_brut'];
            $cnps = (int) round($brut * SalaireService::TAUX_COTISATIONS_SOCIALES / 100);
            $iuts = (int) round($brut * SalaireService::TAUX_IUTS / 100);

            FichePaie::create([
                'etablissement_id'      => $etab,
                'enseignant_id'         => $ens->id,
                'reference'             => FichePaie::genererReference($etab),
                'mois'                  => $validated['mois'],
                'periode_debut'         => $calc['periode_debut'],
                'periode_fin'           => $calc['periode_fin'],
                'type_remuneration'     => $calc['type_remuneration'],
                'salaire_base'          => $calc['salaire_base'],
                'taux_horaire'          => $calc['taux_horaire'],
                'heures_travaillees'    => $calc['heures_travaillees'],
                'heures_contractuelles' => $calc['heures_contractuelles'],
                'montant_horaire'       => $calc['montant_horaire'],
                'salaire_brut'          => $brut,
                'cotisations_sociales'  => $cnps,
                'impots'                => $iuts,
                'salaire_net'           => $brut - $cnps - $iuts,
                'nb_jours_travailles'   => $calc['nb_jours_travailles'],
                'nb_jours_absents'      => $calc['nb_jours_absents'],
                'nb_retards'            => $calc['nb_retards'],
                'statut'                => 'brouillon',
                'generee_par'           => $request->user()->id,
            ]);
            $count++;
        }

        return redirect()->route('fiches-paie.index', ['mois' => $validated['mois']])
            ->with('success', "$count fiche(s) de paie générée(s) automatiquement.");
    }

    public function show(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $fiche = FichePaie::where('etablissement_id', $etab)
            ->with(['enseignant', 'generePar:id,nom,prenom', 'validePar:id,nom,prenom'])
            ->findOrFail($id);

        $journalier = $this->service->calculerPourEnseignant($fiche->enseignant, $fiche->mois)['detail_journalier'];

        return view('fiches-paie.show', compact('fiche', 'journalier'));
    }

    public function valider(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $fiche = FichePaie::where('etablissement_id', $etab)->findOrFail($id);
        abort_unless($fiche->statut === 'brouillon', 422);

        $fiche->update([
            'statut' => 'validee',
            'validee_par' => $request->user()->id,
            'date_validation' => now(),
        ]);

        return back()->with('success', 'Fiche validée.');
    }

    public function marquerPayee(Request $request, $id)
    {
        $validated = $request->validate([
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money',
            'date_paiement_effectif' => 'required|date',
        ]);
        $etab = $request->user()->etablissement_id;
        $fiche = FichePaie::where('etablissement_id', $etab)->findOrFail($id);
        abort_unless($fiche->statut === 'validee', 422);

        $fiche->update([
            'statut'                 => 'payee',
            'date_paiement_effectif' => $validated['date_paiement_effectif'],
            'mode_paiement'          => $validated['mode_paiement'],
        ]);

        return back()->with('success', 'Fiche marquée comme payée.');
    }

    public function destroy(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $fiche = FichePaie::where('etablissement_id', $etab)->findOrFail($id);
        abort_if($fiche->statut === 'payee', 422, 'Une fiche payée ne peut pas être supprimée.');
        $fiche->delete();
        return redirect()->route('fiches-paie.index', ['mois' => $fiche->mois])
            ->with('success', 'Fiche supprimée.');
    }

    public function pdf(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        $fiche = FichePaie::where('etablissement_id', $etab->id)
            ->with(['enseignant', 'generePar:id,nom,prenom', 'validePar:id,nom,prenom'])
            ->findOrFail($id);

        $journalier = $this->service->calculerPourEnseignant($fiche->enseignant, $fiche->mois)['detail_journalier'];

        $pdf = Pdf::loadView('fiches-paie.pdf', [
            'fiche' => $fiche,
            'etablissement' => $etab,
            'journalier' => $journalier,
        ])->setPaper('a4', 'portrait');

        $nom = "fiche-paie-{$fiche->reference}.pdf";
        return $request->boolean('download') ? $pdf->download($nom) : $pdf->stream($nom);
    }

    // ─── Paramétrage du taux horaire pour un enseignant ───
    public function parametrerRemuneration(Request $request, $id)
    {
        $validated = $request->validate([
            'type_remuneration'           => 'required|in:fixe,horaire,mixte',
            'salaire_base'                => 'required|integer|min:0',
            'taux_horaire'                => 'required|integer|min:0',
            'heures_contractuelles_mois'  => 'nullable|numeric|min:0|max:300',
        ]);

        $etab = $request->user()->etablissement_id;
        $enseignant = Enseignant::where('etablissement_id', $etab)->findOrFail($id);
        $enseignant->update($validated);

        return back()->with('success', 'Paramètres de rémunération mis à jour.');
    }
}
