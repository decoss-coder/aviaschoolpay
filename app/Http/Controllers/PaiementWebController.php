<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Paiement;
use App\Services\Eleve\EleveScolariteService;
use App\Services\Finance\PaiementService;
use App\Services\Finance\TarificationService;
use App\Services\Finance\WavePaymentLinkService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaiementWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = PaiementService::resolveEtablissement($request);
        $annee = PaiementService::resolveAnneeCourante($etab);

        $query = Paiement::query()
            ->where('etablissement_id', $etab->id)
            ->with([
                'eleve:id,nom,prenom,matricule_interne',
                'inscription.classe:id,nom',
            ]);

        if ($annee) {
            $query->pourAnnee($annee->id);
        }

        if ($request->filled('classe_id')) {
            $query->whereHas('inscription', fn ($q) => $q->where('classe_id', $request->classe_id));
        }

        if ($request->filled('canal')) {
            $query->canal($request->canal);
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($w) use ($q) {
                $w->where('reference', 'like', "%{$q}%")
                    ->orWhere('numero_recu', 'like', "%{$q}%")
                    ->orWhereHas('eleve', function ($e) use ($q) {
                        $e->where('nom', 'like', "%{$q}%")
                            ->orWhere('prenom', 'like', "%{$q}%")
                            ->orWhere('matricule_interne', 'like', "%{$q}%");
                    });
            });
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date_paiement', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_paiement', '<=', $request->date_fin);
        }

        $paiements = $query->latest('date_paiement')->latest('id')
            ->paginate(25)->withQueryString();

        $recouvrement = $annee
            ? TarificationService::recouvrementEtablissement($etab->id, $annee->id)
            : ['total_du' => 0, 'total_paye' => 0, 'reste' => 0, 'taux' => 0, 'par_statut' => []];

        $postes = $annee
            ? PaiementService::recouvrementParPostes($etab->id, $annee->id)
            : null;

        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->orderBy('nom')
                ->get(['id', 'nom'])
            : collect();

        $waveActif = WavePaymentLinkService::etablissementPeutEncaisser($etab);
        $paiementsManuelsActifs = (bool) ($etab->paiements_manuels_actifs ?? true);

        return view('paiements.index', compact(
            'paiements', 'recouvrement', 'postes', 'waveActif', 'etab', 'annee',
            'classes', 'paiementsManuelsActifs'
        ));
    }

    public function create(Request $request)
    {
        $etab = PaiementService::resolveEtablissement($request);
        $annee = PaiementService::resolveAnneeCourante($etab);
        abort_unless($annee, 422, 'Veuillez configurer une année scolaire active avant d\'enregistrer un paiement.');

        $classes = Classe::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->with('niveau:id,libelle')
            ->orderBy('niveau_id')
            ->orderBy('nom')
            ->get(['id', 'nom', 'niveau_id']);

        $classeId = (int) $request->query('classe_id', 0);
        $eleveId = (int) $request->query('eleve_id', 0);

        $elevesClasse = $classeId
            ? Eleve::where('etablissement_id', $etab->id)
                ->where('classe_id', $classeId)
                ->where('actif', true)
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get(['id', 'nom', 'prenom', 'matricule_interne', 'statut_eleve', 'classe_id'])
            : collect();

        $eleve = $eleveId
            ? Eleve::where('etablissement_id', $etab->id)->with('classe:id,nom')->find($eleveId)
            : null;

        if ($eleve && ! $classeId) {
            $classeId = (int) $eleve->classe_id;
            if ($classeId) {
                $elevesClasse = Eleve::where('classe_id', $classeId)->where('actif', true)
                    ->orderBy('nom')->orderBy('prenom')
                    ->get(['id', 'nom', 'prenom', 'matricule_interne', 'statut_eleve', 'classe_id']);
            }
        }

        $resume = $eleve ? EleveScolariteService::resumePourEleve($eleve, $annee->id) : null;
        $grille = $resume ? PaiementService::grilleDepuisResume($resume) : null;

        return view('paiements.create', [
            'etab' => $etab,
            'annee' => $annee,
            'eleve' => $eleve,
            'resume' => $resume,
            'grille' => $grille,
            'waveActif' => WavePaymentLinkService::etablissementPeutEncaisser($etab),
            'paiementsManuelsActifs' => (bool) ($etab->paiements_manuels_actifs ?? true),
            'classes' => $classes,
            'elevesClasse' => $elevesClasse,
            'classeId' => $classeId,
        ]);
    }

    public function store(Request $request)
    {
        $etab = PaiementService::resolveEtablissement($request);
        $annee = PaiementService::resolveAnneeCourante($etab);
        abort_unless($annee, 422, 'Aucune année scolaire en cours.');

        $data = $request->validate([
            'eleve_id' => 'required|integer|exists:eleves,id',
            'montant' => 'required|integer|min:100',
            'poste_cible' => 'nullable|in:inscription,scolarite,auto',
            'mode' => 'required|in:especes,cheque,virement,wave,orange_money,mtn_money,moov_money,carte_bancaire',
            'date_paiement' => 'nullable|date',
            'observations' => 'nullable|string|max:500',
            'reference_transaction' => 'nullable|string|max:120',
        ]);

        $posteCible = $data['poste_cible'] ?? 'auto';
        $modesManuels = ['especes', 'cheque', 'virement'];
        $paiementsManuelsActifs = (bool) ($etab->paiements_manuels_actifs ?? true);

        if (! $paiementsManuelsActifs && in_array($data['mode'], $modesManuels, true)) {
            return back()->withInput()->withErrors([
                'mode' => 'Les paiements manuels sont désactivés. Utilisez Wave.',
            ]);
        }

        $eleve = Eleve::where('etablissement_id', $etab->id)->findOrFail($data['eleve_id']);
        $inscription = PaiementService::resolveInscription($etab, $annee, $eleve);

        if (! $inscription) {
            return back()->withInput()->withErrors([
                'eleve_id' => 'Impossible d\'enregistrer : aucune classe ni inscription pour cet élève.',
            ]);
        }

        $resume = EleveScolariteService::resumePourEleve($eleve, $annee->id);
        $grille = PaiementService::grilleDepuisResume($resume);

        $maxMontant = match ($posteCible) {
            'inscription' => (int) $grille['inscription']['reste'],
            'scolarite' => (int) $grille['scolarite']['reste'],
            default => (int) $grille['total']['reste'],
        };

        if ($maxMontant <= 0) {
            return back()->withInput()->withErrors(['montant' => 'Aucun montant restant sur ce poste.']);
        }

        $montant = min((int) $data['montant'], $maxMontant);
        $repartition = PaiementService::repartirMontant($grille, $montant, $posteCible);

        if ($data['mode'] === 'wave') {
            if (! WavePaymentLinkService::etablissementPeutEncaisser($etab)) {
                return back()->withErrors(['mode' => 'Wave non activé pour cet établissement.'])->withInput();
            }

            $result = WavePaymentLinkService::preparerPaiement($etab, $eleve, $inscription, $montant);
            $result['paiement']->update([
                'poste_cible' => $posteCible,
                'montant_inscription' => $repartition['montant_inscription'],
                'montant_scolarite' => $repartition['montant_scolarite'],
            ]);

            return redirect()
                ->route('paiements.show', $result['paiement'])
                ->with('success', 'Lien Wave généré — partagez-le au parent.')
                ->with('wave_url', $result['url'])
                ->with('wave_message', $result['message_partage']);
        }

        $statut = in_array($data['mode'], $modesManuels, true) ? 'confirme' : 'en_attente';

        $paiement = DB::transaction(function () use (
            $etab, $eleve, $inscription, $montant, $data, $statut, $request,
            $posteCible, $repartition
        ) {
            $attrs = [
                'etablissement_id' => $etab->id,
                'inscription_id' => $inscription->id,
                'eleve_id' => $eleve->id,
                'encaisse_par' => $request->user()->id,
                'reference' => Paiement::genererReference($etab->id),
                'reference_transaction' => $data['reference_transaction'] ?? null,
                'montant' => $montant,
                'montant_inscription' => $repartition['montant_inscription'],
                'montant_scolarite' => $repartition['montant_scolarite'],
                'poste_cible' => $posteCible,
                'canal_paiement' => PaiementService::canalDepuisMode($data['mode']),
                'date_paiement' => $data['date_paiement'] ?? today()->toDateString(),
                'mode' => $data['mode'],
                'statut' => $statut,
                'observations' => $data['observations'] ?? null,
            ];

            if ($statut === 'confirme') {
                $attrs['date_validation'] = now();
                $attrs['numero_recu'] = PaiementService::genererNumeroRecu($etab->id);
            }

            return Paiement::create($attrs);
        });

        return redirect()
            ->route('paiements.show', $paiement)
            ->with('success', $statut === 'confirme'
                ? 'Paiement enregistré et confirmé.'
                : 'Paiement enregistré (en attente de confirmation).');
    }

    public function show(Request $request, Paiement $paiement)
    {
        $etab = PaiementService::resolveEtablissement($request);
        abort_unless($paiement->etablissement_id === $etab->id, 403);

        $paiement->load([
            'eleve:id,nom,prenom,matricule_interne',
            'inscription.classe:id,nom',
            'encaissePar:id,nom,prenom',
            'etablissement:id,nom,sigle,adresse,telephone',
        ]);

        return view('paiements.show', compact('paiement'));
    }

    public function confirmer(Request $request, Paiement $paiement)
    {
        $etab = PaiementService::resolveEtablissement($request);
        abort_unless($paiement->etablissement_id === $etab->id, 403);
        abort_unless($paiement->statut === 'en_attente', 422, 'Ce paiement n\'est pas en attente.');

        $request->validate([
            'observations' => 'nullable|string|max:500',
            'reference_transaction' => 'nullable|string|max:120',
        ]);

        DB::transaction(function () use ($paiement, $request, $etab) {
            $paiement->update([
                'statut' => 'confirme',
                'encaisse_par' => $request->user()->id,
                'date_validation' => now(),
                'numero_recu' => $paiement->numero_recu ?: PaiementService::genererNumeroRecu($etab->id),
                'reference_transaction' => $request->reference_transaction ?? $paiement->reference_transaction,
                'observations' => $request->observations ?? $paiement->observations,
            ]);
        });

        return back()->with('success', 'Paiement confirmé. Un reçu peut être édité.');
    }

    /**
     * Recherche un paiement par sa référence (pré-reçu présenté par le parent)
     * et redirige vers sa fiche, où la direction peut le confirmer en un clic.
     */
    public function findByReference(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $reference = trim((string) $request->input('reference', ''));
        if ($reference === '') {
            return back()->withErrors(['reference' => 'Veuillez saisir une référence.']);
        }

        // Recherche exacte d'abord, sinon en LIKE (tolère espaces/casse)
        $paiement = Paiement::where('etablissement_id', $etab->id)
            ->where(function ($q) use ($reference) {
                $q->where('reference', $reference)
                  ->orWhere('reference', 'like', '%' . $reference . '%');
            })
            ->orderBy('id', 'desc')
            ->first();

        if (! $paiement) {
            return back()->withErrors([
                'reference' => "Aucun paiement trouvé avec la référence « {$reference} ».",
            ]);
        }

        return redirect()->route('paiements.show', $paiement)
            ->with('success', "Paiement « {$paiement->reference} » trouvé.");
    }

    public function annuler(Request $request, Paiement $paiement)
    {
        $etab = PaiementService::resolveEtablissement($request);
        abort_unless($paiement->etablissement_id === $etab->id, 403);

        if ($paiement->statut === 'confirme' && $paiement->numero_recu) {
            return back()->withErrors([
                'annulation' => 'Un paiement confirmé avec reçu ne peut pas être annulé. Contactez l\'administration pour un avoir.',
            ]);
        }

        $request->validate([
            'motif_annulation' => 'required|string|max:500',
        ]);

        $paiement->update([
            'statut' => 'annule',
            'motif_annulation' => $request->motif_annulation,
        ]);

        return back()->with('success', 'Paiement annulé.');
    }

    public function recu(Request $request, Paiement $paiement)
    {
        $etab = PaiementService::resolveEtablissement($request);
        abort_unless($paiement->etablissement_id === $etab->id, 403);
        abort_unless($paiement->estConfirme(), 422, 'Reçu disponible uniquement pour les paiements confirmés.');

        $paiement->load([
            'eleve:id,nom,prenom,matricule_interne',
            'inscription.classe:id,nom',
            'encaissePar:id,nom,prenom',
            'etablissement:id,nom,sigle,adresse,telephone',
        ]);

        if (! $paiement->numero_recu) {
            $paiement->update(['numero_recu' => PaiementService::genererNumeroRecu($paiement->etablissement_id)]);
        }

        $pdf = Pdf::loadView('paiements.recu', compact('paiement'))
            ->setPaper('A5', 'portrait');

        return $pdf->download("recu-{$paiement->numero_recu}.pdf");
    }

    public function export(Request $request): StreamedResponse
    {
        $etab = PaiementService::resolveEtablissement($request);
        $annee = PaiementService::resolveAnneeCourante($etab);

        $query = Paiement::where('etablissement_id', $etab->id)
            ->with(['eleve:id,nom,prenom,matricule_interne']);

        if ($annee) {
            $query->pourAnnee($annee->id);
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('canal')) {
            $query->canal($request->canal);
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date_paiement', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_paiement', '<=', $request->date_fin);
        }

        $filename = 'paiements-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Référence', 'N° Reçu', 'Date', 'Élève', 'Matricule',
                'Montant', 'Inscription', 'Scolarité', 'Mode', 'Canal', 'Statut', 'Poste',
            ], ';');

            $query->latest('date_paiement')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [
                        $p->reference,
                        $p->numero_recu ?? '',
                        optional($p->date_paiement)->format('Y-m-d'),
                        trim(($p->eleve?->prenom ?? '').' '.($p->eleve?->nom ?? '')),
                        $p->eleve?->matricule_interne ?? '',
                        (int) $p->montant,
                        (int) ($p->montant_inscription ?? 0),
                        (int) ($p->montant_scolarite ?? 0),
                        $p->mode,
                        $p->canal_paiement ?? '',
                        $p->statut,
                        $p->poste_cible ?? '',
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
