<?php

namespace App\Services\Sigfne;

use App\Models\Etablissement;
use App\Models\Inscription;
use App\Models\MoyenneGenerale;
use App\Models\RemonteeEleve;
use App\Models\RemonteeSigfne;
use App\Models\Trimestre;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Synchronisation des moyennes trimestrielles vers la plateforme DESPS / SIGFNE
 * (AGFNE pour secondaire, AGCP pour primaire/préscolaire).
 *
 * Endpoint web :   https://agfne.sigfne.net/vas/agcs-moyenne/
 * URL d'upload  :  https://agfne.sigfne.net/api/vas/agcs-moyenne/import (à confirmer)
 */
class SigfneSyncService
{
    public const PLATEFORME_AGFNE = 'agfne';   // secondaire, lycée
    public const PLATEFORME_AGCP  = 'agcp';    // préscolaire, primaire

    public const BASE_URL_AGFNE = 'https://agfne.sigfne.net';
    public const BASE_URL_AGCP  = 'https://agcp.sigfne.net';

    /**
     * Déterminer la plateforme selon le type d'établissement.
     */
    public function plateformePourEtablissement(Etablissement $etab): string
    {
        if ($etab->sigfne_plateforme) return $etab->sigfne_plateforme;
        return in_array($etab->type, ['prescolaire', 'primaire'], true)
            ? self::PLATEFORME_AGCP
            : self::PLATEFORME_AGFNE;
    }

    public function urlPlateforme(Etablissement $etab): string
    {
        return $this->plateformePourEtablissement($etab) === self::PLATEFORME_AGCP
            ? self::BASE_URL_AGCP
            : self::BASE_URL_AGFNE;
    }

    /**
     * Préparer les données pour une remontée (vérifie qui est prêt).
     *
     * @return array{etablissement: Etablissement, trimestre: Trimestre, plateforme: string, total: int, prets: int, sans_matricule: int, sans_moyenne: int, taux: float, eleves: Collection}
     */
    public function preparer(Etablissement $etab, Trimestre $trimestre): array
    {
        $inscriptions = Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $trimestre->annee_scolaire_id)
            ->where('statut', 'validee')
            ->with(['eleve:id,nom,prenom,sexe,date_naissance,matricule_desps,matricule_interne', 'classe:id,nom'])
            ->get();

        $moyennes = MoyenneGenerale::whereIn('eleve_id', $inscriptions->pluck('eleve_id'))
            ->where('trimestre_id', $trimestre->id)
            ->get()->keyBy('eleve_id');

        $eleves = $inscriptions->map(function ($i) use ($moyennes) {
            $e = $i->eleve;
            $m = $moyennes[$e->id] ?? null;
            $erreurs = [];
            if (! $e->matricule_desps) $erreurs[] = 'matricule_desps_manquant';
            if (! $m || $m->moyenne_generale === null) $erreurs[] = 'moyenne_non_calculee';
            return [
                'eleve_id'         => $e->id,
                'nom'              => $e->nom,
                'prenom'           => $e->prenom,
                'sexe'             => $e->sexe,
                'date_naissance'   => $e->date_naissance?->toDateString(),
                'matricule_desps'  => $e->matricule_desps,
                'matricule_interne' => $e->matricule_interne,
                'classe'           => $i->classe?->nom,
                'moyenne'          => $m?->moyenne_generale,
                'rang'             => $m?->rang,
                'pret'             => empty($erreurs),
                'erreurs'          => $erreurs,
            ];
        })->values();

        $prets = $eleves->where('pret', true)->count();
        $sansMatricule = $eleves->filter(fn($e) => in_array('matricule_desps_manquant', $e['erreurs']))->count();
        $sansMoyenne = $eleves->filter(fn($e) => in_array('moyenne_non_calculee', $e['erreurs']))->count();

        return [
            'etablissement'  => $etab,
            'trimestre'      => $trimestre,
            'plateforme'     => $this->plateformePourEtablissement($etab),
            'total'          => $inscriptions->count(),
            'prets'          => $prets,
            'sans_matricule' => $sansMatricule,
            'sans_moyenne'   => $sansMoyenne,
            'taux'           => $inscriptions->count() > 0 ? round(($prets / $inscriptions->count()) * 100, 1) : 0,
            'eleves'         => $eleves,
        ];
    }

    /**
     * Exporter un fichier CSV/Excel au format AGFNE/AGCP.
     * Format colonnes : MATRICULE_DESPS;NOM;PRENOM;SEXE;DATE_NAISSANCE;CLASSE;MOYENNE;RANG
     *
     * @return string Chemin Storage du fichier généré
     */
    public function exporterFichier(Etablissement $etab, Trimestre $trimestre): string
    {
        $data = $this->preparer($etab, $trimestre);
        $plateforme = $data['plateforme'];
        $codeEtab = $etab->code_desps ?: 'XXXXXX';
        $nomFichier = "sigfne/{$plateforme}_{$codeEtab}_T{$trimestre->numero}_".now()->format('Ymd_His').'.csv';

        $entetes = ['MATRICULE_DESPS', 'NOM', 'PRENOM', 'SEXE', 'DATE_NAISSANCE', 'CLASSE', 'MOYENNE', 'RANG'];
        $lignes = [];

        // BOM UTF-8 pour Excel
        $contenu = "\xEF\xBB\xBF";
        $contenu .= implode(';', $entetes)."\r\n";

        foreach ($data['eleves'] as $e) {
            if (! $e['pret']) continue; // on n'exporte que les élèves prêts
            $contenu .= implode(';', [
                $e['matricule_desps'],
                $this->escape($e['nom']),
                $this->escape($e['prenom']),
                $e['sexe'],
                $e['date_naissance'] ? Carbon::parse($e['date_naissance'])->format('d/m/Y') : '',
                $this->escape($e['classe']),
                number_format((float) $e['moyenne'], 2, '.', ''),
                $e['rang'] ?? '',
            ])."\r\n";
        }

        Storage::disk('local')->put($nomFichier, $contenu);
        return $nomFichier;
    }

    /**
     * Exécute la remontée :
     *  1. Génère le fichier CSV
     *  2. Crée l'enregistrement RemonteeSigfne + RemonteeEleve par élève
     *  3. (Optionnel) Push vers l'API SIGFNE si credentials configurés
     *
     * @return RemonteeSigfne
     */
    public function executer(Etablissement $etab, Trimestre $trimestre, User $user, bool $pushApi = false): RemonteeSigfne
    {
        return DB::transaction(function () use ($etab, $trimestre, $user, $pushApi) {
            $data = $this->preparer($etab, $trimestre);
            $cheminFichier = $this->exporterFichier($etab, $trimestre);

            $remontee = RemonteeSigfne::create([
                'etablissement_id'    => $etab->id,
                'trimestre_id'        => $trimestre->id,
                'annee_scolaire_id'   => $trimestre->annee_scolaire_id,
                'plateforme'          => $data['plateforme'],
                'type'                => 'moyennes_trimestrielles',
                'total_eleves'        => $data['total'],
                'eleves_remontes'     => $data['prets'],
                'eleves_en_erreur'    => $data['sans_matricule'] + $data['sans_moyenne'],
                'eleves_sans_matricule' => $data['sans_matricule'],
                'statut'              => $data['sans_matricule'] + $data['sans_moyenne'] === 0 ? 'pret_envoi' : 'erreur',
                'fichier_export_path' => $cheminFichier,
                'date_envoi'          => now(),
                'envoye_par'          => $user->id,
                'erreurs_detail'      => $data['eleves']->filter(fn($e) => ! $e['pret'])->values()->toArray(),
            ]);

            // Détail par élève
            foreach ($data['eleves'] as $e) {
                RemonteeEleve::create([
                    'remontee_sigfne_id' => $remontee->id,
                    'eleve_id'           => $e['eleve_id'],
                    'matricule_desps'    => $e['matricule_desps'],
                    'moyenne_remontee'   => $e['moyenne'],
                    'statut'             => $e['pret'] ? 'ok' : (in_array('matricule_desps_manquant', $e['erreurs']) ? 'erreur_matricule' : 'erreur_moyenne'),
                    'message_erreur'     => $e['pret'] ? null : implode(', ', $e['erreurs']),
                ]);
            }

            // Push API si activé + credentials présents
            if ($pushApi && $etab->sigfne_actif && $etab->sigfne_token) {
                $resultat = $this->pushVersSigfne($etab, $trimestre, $cheminFichier);
                $remontee->update([
                    'statut'         => $resultat['success'] ? 'envoye' : 'erreur_api',
                    'reponse_sigfne' => $resultat['response'] ?? null,
                ]);
                if ($resultat['success']) {
                    $etab->update(['sigfne_derniere_sync' => now()]);
                    $trimestre->update(['moyennes_remontees' => true]);
                }
            }

            return $remontee->fresh();
        });
    }

    /**
     * Push HTTP du fichier CSV vers la plateforme SIGFNE.
     * NOTE: l'endpoint exact dépend de l'API publique de SIGFNE.
     * Cette implémentation suppose un upload multipart avec auth Bearer.
     */
    public function pushVersSigfne(Etablissement $etab, Trimestre $trimestre, string $cheminLocal): array
    {
        $url = $this->urlPlateforme($etab).'/api/vas/agcs-moyenne/import';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$etab->sigfne_token,
                'Accept'        => 'application/json',
            ])
            ->timeout(60)
            ->attach('fichier', Storage::disk('local')->get($cheminLocal), basename($cheminLocal))
            ->post($url, [
                'code_etablissement' => $etab->code_desps,
                'trimestre'          => $trimestre->numero,
                'annee_scolaire'     => $trimestre->anneeScolaire?->libelle,
                'login'              => $etab->sigfne_login ?: $etab->code_desps,
            ]);

            $json = $response->json() ?? [];
            return [
                'success'  => $response->successful(),
                'http'     => $response->status(),
                'response' => $json,
                'error'    => $response->successful() ? null : ($json['message'] ?? 'HTTP '.$response->status()),
            ];
        } catch (\Throwable $e) {
            Log::error('SIGFNE push failed', ['etab' => $etab->id, 'err' => $e->getMessage()]);
            return [
                'success'  => false,
                'response' => null,
                'error'    => 'Erreur réseau : '.$e->getMessage(),
            ];
        }
    }

    private function escape(?string $val): string
    {
        $val = (string) $val;
        // Échappement basique pour CSV
        if (str_contains($val, ';') || str_contains($val, '"') || str_contains($val, "\n")) {
            return '"'.str_replace('"', '""', $val).'"';
        }
        return $val;
    }
}
