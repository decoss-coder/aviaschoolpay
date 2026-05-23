<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\Paiement;
use App\Services\Eleve\EleveScolariteService;
use App\Services\Finance\TarificationService;
use App\Services\Finance\WavePaymentLinkService;
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        abort_unless($annee, 422, 'Aucune année scolaire en cours.');

        // Recouvrement PROJETÉ (élèves actifs × tarifs niveau/classe selon AFF/NAFF).
        // Évite d'afficher 0 tant que les inscriptions ne sont pas synchronisées.
        $recouvrement = TarificationService::recouvrementProjete($etab->id, $annee->id);

        $paiements = Paiement::query()
            ->where('etablissement_id', $etab->id)
            ->with(['eleve:id,nom,prenom,matricule_interne,statut_eleve', 'inscription.classe:id,nom'])
            ->latest('date_paiement')
            ->paginate(20)
            ->withQueryString();

        // Top élèves avec solde — basé sur les élèves actifs (avec ou sans inscription synchronisée)
        $retards = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->whereIn('statut_eleve', ['AFF', 'NAFF'])
            ->with([
                'classe:id,nom,niveau_id,frais_inscription,frais_reinscription,scolarite_annuelle',
                'classe.niveau:id,frais_scolarite_defaut,frais_inscription_defaut,frais_reinscription_defaut',
            ])
            ->get()
            ->map(function (Eleve $eleve) {
                $resume = EleveScolariteService::resumePourEleve($eleve);
                $resteTotal = (int) ($resume['resume']['reste_a_payer'] ?? 0);
                $duTotal = (int) ($resume['resume']['montant_total_du'] ?? 0);

                return [
                    'eleve' => $eleve,
                    'reste' => $resteTotal,
                    'du' => ['montant_total_du' => $duTotal],
                ];
            })
            ->filter(fn ($r) => $r['reste'] > 0)
            ->sortByDesc('reste')
            ->take(15)
            ->values();

        return view('finances.index', compact('etab', 'annee', 'recouvrement', 'paiements', 'retards'));
    }

    public function wave(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $waveActif = WavePaymentLinkService::etablissementPeutEncaisser($etab);
        $lienMasque = WavePaymentLinkService::masquerLienBase($etab->wave_lien_base);
        $libelle = WavePaymentLinkService::libelleAffichage($etab);

        $paiementsWaveEnAttente = Paiement::query()
            ->where('etablissement_id', $etab->id)
            ->where('mode', 'wave')
            ->where('statut', 'en_attente')
            ->with(['eleve:id,nom,prenom', 'inscription'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        $statsWave = [
            'en_attente' => Paiement::where('etablissement_id', $etab->id)->where('mode', 'wave')->where('statut', 'en_attente')->count(),
            'confirmes' => Paiement::where('etablissement_id', $etab->id)->where('mode', 'wave')->where('statut', 'confirme')->count(),
        ];

        return view('finances.wave', compact(
            'etab',
            'waveActif',
            'lienMasque',
            'libelle',
            'paiementsWaveEnAttente',
            'statsWave'
        ));
    }

    public function tarifs(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        abort_unless($annee, 422);

        $niveaux = Niveau::query()
            ->where('etablissement_id', $etab->id)
            ->orderBy('ordre')
            ->get();

        $college = $niveaux->where('cycle', TarificationService::CYCLE_COLLEGE);
        $lycee = $niveaux->where('cycle', TarificationService::CYCLE_LYCEE);

        // Sépare la 3ème du reste du collège (6e-4e)
        $troisieme = TarificationService::niveauTroisieme($etab->id);
        $college6e4e = $troisieme
            ? $college->reject(fn ($n) => $n->id === $troisieme->id)->values()
            : $college->values();

        // Sépare la/les Terminale(s) du reste du lycée (2nde-1ère)
        $terminales = TarificationService::niveauxTerminale($etab->id);
        $terminaleIds = $terminales->pluck('id')->all();
        $lycee2nde1ere = $lycee->reject(fn ($n) => in_array($n->id, $terminaleIds, true))->values();

        return view('finances.tarifs', compact(
            'etab', 'annee',
            'college', 'college6e4e', 'troisieme',
            'lycee', 'lycee2nde1ere', 'terminales'
        ));
    }

    public function updateTarifsNiveau(Request $request, Niveau $niveau)
    {
        abort_unless($niveau->etablissement_id === $request->user()->etablissement_id, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $request->user()->etablissement_id);
        abort_unless($annee, 422);

        $data = $request->validate([
            'frais_scolarite_defaut' => ['required', 'integer', 'min:0'],
            'frais_inscription_defaut' => ['required', 'integer', 'min:0'],
            'frais_reinscription_defaut' => ['required', 'integer', 'min:0'],
            'appliquer_classes' => ['nullable', 'boolean'],
        ]);

        $niveau->update([
            'frais_scolarite_defaut' => $data['frais_scolarite_defaut'],
            'frais_inscription_defaut' => $data['frais_inscription_defaut'],
            'frais_reinscription_defaut' => $data['frais_reinscription_defaut'],
        ]);

        $nb = 0;
        if ($request->boolean('appliquer_classes', true)) {
            $nb = TarificationService::appliquerNiveauSurClasses($niveau, $annee->id);
        }

        return back()->with('success', "Tarifs du niveau « {$niveau->libelle} » enregistrés. {$nb} classe(s) mise(s) à jour.");
    }

    public function updateTarifsCollege(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        abort_unless($annee, 422);

        $data = $request->validate([
            'scolarite_annuelle' => ['required', 'integer', 'min:0'],
            'frais_inscription' => ['required', 'integer', 'min:0'],
            'frais_reinscription' => ['required', 'integer', 'min:0'],
            'niveau_ids' => ['nullable', 'array'],
            'niveau_ids.*' => ['integer', 'exists:niveaux,id'],
            'groupe' => ['nullable', 'in:6e_4e,3e,tous'],
        ]);

        // Récupère les niveaux collège
        $tousCollege = \App\Models\Niveau::where('etablissement_id', $etab->id)
            ->where('cycle', TarificationService::CYCLE_COLLEGE)
            ->get();
        $troisieme = TarificationService::niveauTroisieme($etab->id);

        // Détermine les niveaux ciblés
        $niveauIds = $data['niveau_ids'] ?? null;
        $label = '6e→3e';

        if (! $niveauIds && ! empty($data['groupe'])) {
            if ($data['groupe'] === '3e' && $troisieme) {
                $niveauIds = [$troisieme->id];
                $label = '3e';
            } elseif ($data['groupe'] === '6e_4e') {
                $niveauIds = $tousCollege
                    ->reject(fn ($n) => $troisieme && $n->id === $troisieme->id)
                    ->pluck('id')
                    ->all();
                $label = '6e à 4e';
            }
        }

        $nb = TarificationService::appliquerCollegeUniforme(
            $etab->id,
            $annee->id,
            $data['scolarite_annuelle'],
            $data['frais_inscription'],
            $data['frais_reinscription'],
            $niveauIds
        );

        return back()->with('success', "Grille collège ({$label}) appliquée sur {$nb} classe(s).");
    }

    public function updateTarifsLycee(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        abort_unless($annee, 422);

        $data = $request->validate([
            'scolarite_annuelle' => ['required', 'integer', 'min:0'],
            'frais_inscription' => ['required', 'integer', 'min:0'],
            'frais_reinscription' => ['required', 'integer', 'min:0'],
            'niveau_ids' => ['nullable', 'array'],
            'niveau_ids.*' => ['integer', 'exists:niveaux,id'],
            'groupe' => ['nullable', 'in:2nde_1ere,terminale,tous'],
        ]);

        $tousLycee = \App\Models\Niveau::where('etablissement_id', $etab->id)
            ->where('cycle', TarificationService::CYCLE_LYCEE)
            ->get();
        $terminales = TarificationService::niveauxTerminale($etab->id);

        $niveauIds = $data['niveau_ids'] ?? null;
        $label = '2nde→Tle';

        if (! $niveauIds && ! empty($data['groupe'])) {
            if ($data['groupe'] === 'terminale') {
                $niveauIds = $terminales->pluck('id')->all();
                $label = 'Terminale(s)';
            } elseif ($data['groupe'] === '2nde_1ere') {
                $terminaleIds = $terminales->pluck('id')->all();
                $niveauIds = $tousLycee
                    ->reject(fn ($n) => in_array($n->id, $terminaleIds, true))
                    ->pluck('id')
                    ->all();
                $label = '2nde et 1ère';
            }
        }

        $nb = TarificationService::appliquerLyceeUniforme(
            $etab->id,
            $annee->id,
            $data['scolarite_annuelle'],
            $data['frais_inscription'],
            $data['frais_reinscription'],
            $niveauIds
        );

        return back()->with('success', "Grille lycée ({$label}) appliquée sur {$nb} classe(s).");
    }

    public function synchroniser(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        abort_unless($annee, 422);

        $nb = TarificationService::synchroniserEtablissement($etab->id, $annee->id);

        return back()->with('success', "{$nb} inscription(s) recalculée(s) selon les tarifs et statuts AFF/NAFF.");
    }

    public function eleve(Request $request, Eleve $eleve)
    {
        abort_unless($eleve->etablissement_id === $request->user()->etablissement_id, 403);

        $eleve->load(['classe.niveau', 'parents', 'etablissement']);
        $finances = EleveScolariteService::resumePourEleve($eleve);
        $etab = $eleve->etablissement;
        $waveActif = $etab && WavePaymentLinkService::etablissementPeutEncaisser($etab);
        $resteWave = (int) ($finances['resume']['reste_a_payer'] ?? 0);

        return view('finances.eleve', compact('eleve', 'finances', 'waveActif', 'resteWave'));
    }

    public function genererLienWave(Request $request, Eleve $eleve)
    {
        abort_unless($eleve->etablissement_id === $request->user()->etablissement_id, 403);

        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        abort_unless($annee, 422);

        $inscription = Inscription::query()
            ->where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('statut', 'validee')
            ->latest('date_inscription')
            ->first();

        if (! $inscription) {
            return back()->withErrors(['montant' => 'Aucune inscription validée pour générer un lien Wave.']);
        }

        $montant = (int) $request->validate([
            'montant' => ['required', 'integer', 'min:100'],
        ])['montant'];

        $result = WavePaymentLinkService::preparerPaiement($etab, $eleve, $inscription, $montant);

        return back()
            ->with('wave_url', $result['url'])
            ->with('wave_message', $result['message_partage'])
            ->with('success', 'Lien Wave généré. Partagez-le au parent ou ouvrez-le sur le téléphone.');
    }

    /**
     * Active / désactive Wave pour l'établissement (toggle du flag wave_actif).
     * Le lien marchand `wave_lien_base` reste configurable par le super-admin.
     */
    public function toggleWave(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        if (! filled($etab->wave_lien_base)) {
            return back()->withErrors([
                'wave' => 'Lien marchand Wave non configuré. Contactez Avia Technologie pour le renseigner.',
            ]);
        }

        $etab->wave_actif = ! (bool) $etab->wave_actif;
        $etab->wave_configured_at = now();
        $etab->wave_configured_by = $request->user()->id;
        $etab->save();

        return back()->with('success', $etab->wave_actif
            ? 'Paiement Wave activé. Les parents peuvent désormais payer via Wave.'
            : 'Paiement Wave désactivé. Aucun nouveau paiement Wave ne sera accepté.');
    }

    /**
     * Active / désactive l'enregistrement des paiements manuels (espèces / chèque / virement).
     * Si désactivé, la direction ne peut plus saisir manuellement — Wave reste la seule option.
     */
    public function toggleManualPayments(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $etab->paiements_manuels_actifs = ! (bool) $etab->paiements_manuels_actifs;
        $etab->save();

        return back()->with('success', $etab->paiements_manuels_actifs
            ? 'Paiements manuels activés. La direction peut saisir espèces / chèque / virement.'
            : 'Paiements manuels désactivés. Seul le paiement en ligne (Wave) est désormais possible.');
    }

    /**
     * Permet à la direction de mettre à jour son lien marchand Wave (libellé + URL).
     */
    public function updateWaveConfig(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $data = $request->validate([
            'wave_libelle'   => ['nullable', 'string', 'max:100'],
            'wave_lien_base' => ['required', 'string', 'max:255'],
        ]);

        $etab->wave_libelle   = $data['wave_libelle'] ?? $etab->nom;
        $etab->wave_lien_base = WavePaymentLinkService::normaliserLienBase($data['wave_lien_base']);
        $etab->wave_configured_at = now();
        $etab->wave_configured_by = $request->user()->id;
        $etab->save();

        return back()->with('success', 'Configuration Wave enregistrée.');
    }
}
