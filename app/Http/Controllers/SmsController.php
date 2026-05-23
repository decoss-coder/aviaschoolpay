<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\SmsEnvoi;
use App\Models\SmsRecharge;
use App\Services\Documents\DocumentsService;
use App\Services\Scolarite\AnneeScolaireService;
use App\Services\Sms\SmsCreditService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function __construct(
        private SmsCreditService $smsService,
        private DocumentsService $docService,
    ) {}

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $credit = $this->smsService->getOrCreateCredit($etab->id);

        $recharges = SmsRecharge::where('etablissement_id', $etab->id)
            ->with(['demandeur:id,nom,prenom', 'crediteParUser:id,nom,prenom'])
            ->latest()->limit(20)->get();

        $envois = SmsEnvoi::where('etablissement_id', $etab->id)
            ->with('envoyePar:id,nom,prenom')
            ->latest()->limit(30)->get();

        $stats = [
            'envoyes_mois'    => SmsEnvoi::where('etablissement_id', $etab->id)
                ->where('statut', 'envoye')
                ->where('created_at', '>=', now()->startOfMonth())->count(),
            'echecs_mois'     => SmsEnvoi::where('etablissement_id', $etab->id)
                ->where('statut', 'echec')
                ->where('created_at', '>=', now()->startOfMonth())->count(),
            'recharges_total' => SmsRecharge::where('etablissement_id', $etab->id)
                ->where('statut', 'credite')->sum('nb_sms'),
        ];

        $prixUnitaire = SmsCreditService::prixUnitaire();
        $waveConfigure = SmsCreditService::lienWaveAvia(50) !== null;

        return view('sms.index', compact('credit', 'recharges', 'envois', 'stats', 'prixUnitaire', 'waveConfigure'));
    }

    public function rechargerStore(Request $request)
    {
        $validated = $request->validate([
            'nb_sms' => 'required|integer|min:50|max:100000',
        ]);

        $etab = $request->user()->etablissement;
        $recharge = $this->smsService->creerRecharge($etab, $request->user(), (int) $validated['nb_sms']);

        return redirect()->route('sms.index')->with('success', "Recharge {$recharge->reference} créée. Payez {$recharge->montant_fcfa} F via Wave puis Avia créditera vos SMS.")
            ->with('recharge_id', $recharge->id);
    }

    public function annulerRecharge(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $recharge = SmsRecharge::where('etablissement_id', $etab)->findOrFail($id);
        $this->smsService->annulerRecharge($recharge, 'Annulé par '.$request->user()->name);
        return back()->with('success', 'Recharge annulée.');
    }

    /**
     * Aperçu : qui sera relancé + coût estimé.
     */
    public function relanceImpayesApercu(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);
        $data = $this->docService->elevesNonSoldes($etab->id, $annee->id,
            $request->classe_id ? (int) $request->classe_id : null);

        $destinataires = $data['inscriptions']
            ->filter(fn($i) => $i->eleve?->contact_urgence_tel)
            ->map(fn($i) => [
                'nom'    => $i->eleve->contact_urgence_nom,
                'tel'    => $i->eleve->contact_urgence_tel,
                'eleve'  => $i->eleve->prenom.' '.$i->eleve->nom,
                'classe' => $i->classe?->nom,
                'reste'  => (int) $i->reste_calc,
            ])->values();

        return response()->json([
            'nb_destinataires' => $destinataires->count(),
            'nb_sms_total'     => $destinataires->count(), // 1 SMS par parent (court)
            'cout_estime'      => $destinataires->count() * SmsCreditService::prixUnitaire(),
            'solde_actuel'     => $this->smsService->solde($etab->id),
            'apercu'           => $destinataires->take(5),
        ]);
    }

    /**
     * Envoie effectivement les SMS de relance.
     */
    public function relanceImpayesEnvoyer(Request $request)
    {
        $validated = $request->validate([
            'classe_id' => 'nullable|exists:classes,id',
            'modele'    => 'nullable|string|max:500',
        ]);

        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);
        $data = $this->docService->elevesNonSoldes($etab->id, $annee->id,
            $validated['classe_id'] ? (int) $validated['classe_id'] : null);

        $modele = $validated['modele'] ?? $this->modeleDefaut($etab);

        $destinataires = [];
        foreach ($data['inscriptions'] as $insc) {
            $tel = $insc->eleve?->contact_urgence_tel;
            if (! $tel) continue;

            $contenu = strtr($modele, [
                '{ETABLISSEMENT}' => $etab->nom ?? 'Ecole',
                '{ELEVE}'         => trim(($insc->eleve->prenom ?? '').' '.($insc->eleve->nom ?? '')),
                '{CLASSE}'        => $insc->classe?->nom ?? '',
                '{RESTE}'         => number_format((float) $insc->reste_calc, 0, ',', ' '),
                '{TELEPHONE}'     => $etab->telephone ?? '',
            ]);

            $destinataires[] = [
                'tel'      => $tel,
                'nom'      => $insc->eleve->contact_urgence_nom,
                'contenu'  => $contenu,
                'ref_type' => 'inscription',
                'ref_id'   => $insc->id,
            ];
        }

        if (empty($destinataires)) {
            return back()->with('error', 'Aucun parent à contacter (numéros manquants).');
        }

        // Vérifier solde
        if ($this->smsService->solde($etab->id) < count($destinataires)) {
            return back()->with('error',
                'Solde SMS insuffisant. Vous avez '.$this->smsService->solde($etab->id)
                .' SMS et il en faut '.count($destinataires).'. Rechargez votre compte.');
        }

        // Envoyer un par un (chaque parent reçoit son message personnalisé)
        $envoyes = 0; $echecs = 0;
        foreach ($destinataires as $d) {
            $r = $this->smsService->envoyer(
                $etab, $request->user(),
                $d['tel'], $d['nom'], $d['contenu'],
                'relance_impaye', $d['ref_type'], $d['ref_id']
            );
            $r['success'] ? $envoyes++ : $echecs++;
        }

        return redirect()->route('sms.index')
            ->with('success', "Campagne relance terminée : $envoyes envoyé(s), $echecs échec(s) sur ".count($destinataires).' parents.');
    }

    public function envoyerManuel(Request $request)
    {
        $validated = $request->validate([
            'destinataire' => 'required|string|max:25',
            'contenu'      => 'required|string|max:1500',
            'nom'          => 'nullable|string|max:200',
        ]);

        $etab = $request->user()->etablissement;
        $r = $this->smsService->envoyer($etab, $request->user(),
            $validated['destinataire'], $validated['nom'] ?? null,
            $validated['contenu'], 'manuel');

        return back()->with($r['success'] ? 'success' : 'error',
            $r['success'] ? 'SMS envoyé avec succès.' : ('Échec : '.($r['error'] ?? '?')));
    }

    private function modeleDefaut($etab): string
    {
        return "Bonjour, l'eleve {ELEVE} ({CLASSE}) a un reste de {RESTE} F CFA a regler. Merci de regulariser au plus tot. {ETABLISSEMENT}";
    }
}
