<?php

namespace App\Http\Controllers;

use App\Models\CompteTresorerie;
use App\Models\MouvementTresorerie;
use App\Models\VirementInterne;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TresorerieWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement_id;

        $comptes = CompteTresorerie::where('etablissement_id', $etab)
            ->where('actif', true)
            ->orderByDesc('principal')
            ->orderBy('nom')
            ->get();

        $totalTreso = $comptes->sum('solde_actuel');
        $totalCaisse = $comptes->where('type', 'caisse')->sum('solde_actuel');
        $totalBanque = $comptes->where('type', 'banque')->sum('solde_actuel');
        $totalMM     = $comptes->where('type', 'mobile_money')->sum('solde_actuel');

        // Flux 30 derniers jours
        $debut = now()->subDays(29)->startOfDay();
        $entrees = MouvementTresorerie::where('etablissement_id', $etab)
            ->where('sens', 'entree')
            ->where('date_mouvement', '>=', $debut->toDateString())
            ->sum('montant');
        $sorties = MouvementTresorerie::where('etablissement_id', $etab)
            ->where('sens', 'sortie')
            ->where('date_mouvement', '>=', $debut->toDateString())
            ->sum('montant');

        // Données pour graphique flux quotidiens 30j
        $fluxQuotidiens = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $in = MouvementTresorerie::where('etablissement_id', $etab)
                ->where('sens', 'entree')
                ->where('date_mouvement', $date)
                ->sum('montant');
            $out = MouvementTresorerie::where('etablissement_id', $etab)
                ->where('sens', 'sortie')
                ->where('date_mouvement', $date)
                ->sum('montant');
            $fluxQuotidiens[] = [
                'date' => Carbon::parse($date)->format('d/m'),
                'entree' => (int) $in,
                'sortie' => (int) $out,
            ];
        }

        $derniersMouvements = MouvementTresorerie::where('etablissement_id', $etab)
            ->with(['compte:id,nom,type', 'saisiePar:id,nom,prenom'])
            ->latest('date_mouvement')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('tresorerie.index', compact(
            'comptes', 'totalTreso', 'totalCaisse', 'totalBanque', 'totalMM',
            'entrees', 'sorties', 'fluxQuotidiens', 'derniersMouvements'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'                     => 'required|string|max:100',
            'type'                    => 'required|in:caisse,banque,mobile_money',
            'numero_compte'           => 'nullable|string|max:50',
            'banque'                  => 'nullable|string|max:100',
            'operateur'               => 'nullable|string|max:50',
            'solde_initial'           => 'required|integer|min:0',
            'compte_comptable_numero' => 'nullable|string|max:20',
            'principal'               => 'nullable|boolean',
        ]);

        $etab = $request->user()->etablissement_id;

        DB::transaction(function () use ($validated, $etab, $request) {
            // S'il y a principal=true, retirer le flag des autres
            if (! empty($validated['principal'])) {
                CompteTresorerie::where('etablissement_id', $etab)->update(['principal' => false]);
            }

            CompteTresorerie::create([
                'etablissement_id'        => $etab,
                'nom'                     => $validated['nom'],
                'type'                    => $validated['type'],
                'numero_compte'           => $validated['numero_compte'] ?? null,
                'banque'                  => $validated['banque'] ?? null,
                'operateur'               => $validated['operateur'] ?? null,
                'solde_initial'           => $validated['solde_initial'],
                'solde_actuel'            => $validated['solde_initial'],
                'compte_comptable_numero' => $validated['compte_comptable_numero'] ?? null,
                'actif'                   => true,
                'principal'               => ! empty($validated['principal']),
            ]);
        });

        return redirect()->route('tresorerie.index')->with('success', 'Compte de trésorerie créé.');
    }

    public function mouvements(Request $request)
    {
        $etab = $request->user()->etablissement_id;

        $query = MouvementTresorerie::where('etablissement_id', $etab)
            ->with(['compte:id,nom,type', 'saisiePar:id,nom,prenom']);

        if ($request->filled('compte'))    $query->where('compte_tresorerie_id', $request->compte);
        if ($request->filled('sens'))      $query->where('sens', $request->sens);
        if ($request->filled('date_debut')) $query->where('date_mouvement', '>=', $request->date_debut);
        if ($request->filled('date_fin'))   $query->where('date_mouvement', '<=', $request->date_fin);

        $mouvements = $query->latest('date_mouvement')->latest('id')->paginate(30)->withQueryString();

        $comptes = CompteTresorerie::where('etablissement_id', $etab)->where('actif', true)->orderBy('nom')->get();

        $totalEntrees = (clone $query)->where('sens', 'entree')->sum('montant');
        $totalSorties = (clone $query)->where('sens', 'sortie')->sum('montant');

        return view('tresorerie.mouvements', compact('mouvements', 'comptes', 'totalEntrees', 'totalSorties'));
    }

    public function virement(Request $request)
    {
        $validated = $request->validate([
            'compte_source_id'      => 'required|exists:comptes_tresorerie,id|different:compte_destination_id',
            'compte_destination_id' => 'required|exists:comptes_tresorerie,id',
            'montant'               => 'required|integer|min:1',
            'date_virement'         => 'required|date',
            'motif'                 => 'nullable|string|max:200',
        ]);

        $etab = $request->user()->etablissement_id;

        DB::transaction(function () use ($validated, $etab, $request) {
            $source = CompteTresorerie::where('etablissement_id', $etab)->findOrFail($validated['compte_source_id']);
            $dest   = CompteTresorerie::where('etablissement_id', $etab)->findOrFail($validated['compte_destination_id']);

            abort_if($source->solde_actuel < $validated['montant'], 422, 'Solde insuffisant sur le compte source.');

            $motif = $validated['motif'] ?? "Virement de {$source->nom} vers {$dest->nom}";

            $source->enregistrerMouvement('sortie', $validated['montant'], $motif, 'virement', null);
            $dest->enregistrerMouvement('entree', $validated['montant'], $motif, 'virement', null);

            VirementInterne::create([
                'etablissement_id'      => $etab,
                'compte_source_id'      => $source->id,
                'compte_destination_id' => $dest->id,
                'montant'               => $validated['montant'],
                'date_virement'         => $validated['date_virement'],
                'motif'                 => $motif,
                'effectue_par'          => $request->user()->id,
            ]);
        });

        return redirect()->route('tresorerie.index')->with('success', 'Virement interne effectué.');
    }
}
