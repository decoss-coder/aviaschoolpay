<?php

namespace App\Services\Finance;

use App\Models\Etablissement;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Services\Eleve\EleveScolariteService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WavePaymentLinkService
{
    public const WAVE_HOST = 'pay.wave.com';

    /**
     * Normalise et valide le lien marchand Wave (sans montant obligatoire à la saisie).
     */
    public static function normaliserLienBase(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        if ($host !== self::WAVE_HOST && ! str_ends_with($host, '.'.self::WAVE_HOST)) {
            throw ValidationException::withMessages([
                'wave_lien_base' => ['Le lien doit être un lien officiel Wave (pay.wave.com).'],
            ]);
        }

        $path = $parts['path'] ?? '/';
        if (! preg_match('#^/m/[^/]+/c/[a-z]{2}/?#i', $path)) {
            throw ValidationException::withMessages([
                'wave_lien_base' => ['Format attendu : https://pay.wave.com/m/IDENTIFIANT/c/ci/ (sans montant obligatoire).'],
            ]);
        }

        $path = rtrim($path, '/').'/';

        // Ignore ?amount=… : le montant est toujours calculé côté serveur.
        return 'https://'.self::WAVE_HOST.$path;
    }

    public static function etablissementPeutEncaisser(Etablissement $etab): bool
    {
        return (bool) $etab->wave_actif
            && filled($etab->wave_lien_base)
            && self::normaliserLienBase($etab->wave_lien_base) !== null;
    }

    /**
     * Construit l'URL Wave avec le montant (FCFA entier).
     */
    public static function construireUrl(Etablissement $etab, int $montant): string
    {
        if ($montant < 100) {
            throw ValidationException::withMessages([
                'montant' => ['Le montant minimum est de 100 FCFA.'],
            ]);
        }

        $base = self::normaliserLienBase($etab->wave_lien_base);

        if (! $base || ! $etab->wave_actif) {
            throw ValidationException::withMessages([
                'wave' => ['Le paiement Wave n\'est pas activé pour cet établissement.'],
            ]);
        }

        return $base.'?amount='.$montant;
    }

    public static function libelleAffichage(Etablissement $etab): string
    {
        return trim((string) ($etab->wave_libelle ?: $etab->nom)) ?: 'Établissement';
    }

    /**
     * Génère un lien + enregistre un paiement en attente (réconciliation manuelle ou future API Wave).
     *
     * @return array{url: string, paiement: Paiement, montant: int, reste_apres: int, libelle: string}
     */
    public static function preparerPaiement(
        Etablissement $etab,
        Eleve $eleve,
        Inscription $inscription,
        int $montantDemande
    ): array {
        abort_unless($eleve->etablissement_id === $etab->id, 403);
        abort_unless($inscription->eleve_id === $eleve->id, 403);

        if (! $eleve->estAffecte() && ! $eleve->estNonAffecte()) {
            throw ValidationException::withMessages([
                'statut_eleve' => ['Statut AFF/NAFF requis pour un paiement.'],
            ]);
        }

        $reste = EleveScolariteService::resteAPayer($inscription, $eleve);
        if ($reste <= 0) {
            throw ValidationException::withMessages([
                'montant' => ['Aucun solde à régler pour cette inscription.'],
            ]);
        }

        $montant = min($montantDemande, $reste);
        $url = self::construireUrl($etab, $montant);
        $libelle = self::libelleAffichage($etab);

        $resume = EleveScolariteService::resumePourEleve($eleve, $inscription->annee_scolaire_id);
        $grille = PaiementService::grilleDepuisResume($resume);
        $repartition = PaiementService::repartirMontant($grille, $montant, 'auto');

        $paiement = Paiement::create([
            'etablissement_id' => $etab->id,
            'inscription_id' => $inscription->id,
            'eleve_id' => $eleve->id,
            'reference' => Paiement::genererReference($etab->id),
            'montant' => $montant,
            'montant_inscription' => $repartition['montant_inscription'],
            'montant_scolarite' => $repartition['montant_scolarite'],
            'poste_cible' => 'auto',
            'canal_paiement' => 'wave',
            'date_paiement' => today()->toDateString(),
            'mode' => 'wave',
            'statut' => 'en_attente',
            'wave_checkout_url' => $url,
            'observations' => 'Lien Wave généré — en attente de confirmation encaissement.',
        ]);

        return [
            'url' => $url,
            'paiement' => $paiement,
            'montant' => $montant,
            'reste_apres' => max(0, $reste - $montant),
            'libelle' => $libelle,
            'message_partage' => sprintf(
                'Veuillez payer %s %s F avec Wave : %s',
                $libelle,
                number_format($montant, 0, ',', ' '),
                $url
            ),
        ];
    }

    /** Masque l'identifiant marchand pour les écrans non admin. */
    public static function masquerLienBase(?string $base): ?string
    {
        if (! $base) {
            return null;
        }

        if (preg_match('#/m/([^/]+)/#', $base, $m)) {
            $id = $m[1];

            return str_replace($id, Str::mask($id, '*', 4), $base);
        }

        return Str::limit($base, 48);
    }
}
