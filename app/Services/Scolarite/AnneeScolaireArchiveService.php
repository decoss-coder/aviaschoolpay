<?php

namespace App\Services\Scolarite;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\EmploiDuTemps;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Trimestre;
use App\Models\User;
use App\Services\Finance\WavePaymentLinkService;
use App\Models\PlatformSetting;
use App\Models\Creneau;
use App\Models\Enseignant;
use App\Models\Matiere;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AnneeScolaireArchiveService
{
    public const PLATFORM_WAVE_RESTAURATION_KEY = 'wave_lien_restauration_500';

    public const PLATFORM_WAVE_LIBELLE_KEY = 'wave_libelle_restauration';

    public const FRAIS_RESTAURATION_FCFA = 500;

    /**
     * Archive chiffrée + purge des données opérationnelles de l'année.
     *
     * @return array{restoration_key: string, path: string, meta: array}
     */
    public static function cloturerEtArchiver(AnneeScolaire $annee, User $user, bool $purgerDonnees = true): array
    {
        if ($annee->archivee) {
            throw ValidationException::withMessages(['annee' => ['Cette année est déjà archivée.']]);
        }

        if ($annee->en_cours) {
            throw ValidationException::withMessages(['annee' => ['Désactivez l\'année en cours avant clôture (activez une autre année).']]);
        }

        $restorationKey = strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
        $payload = self::exporterDonnees($annee);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $checksum = hash('sha256', $json);

        $encrypted = self::chiffrer($json, $restorationKey);
        $path = "archives/etab_{$annee->etablissement_id}/annee_{$annee->id}_".now()->format('Ymd_His').'.enc';
        Storage::disk('local')->put($path, $encrypted);

        return DB::transaction(function () use ($annee, $user, $restorationKey, $path, $checksum, $payload, $purgerDonnees) {
            $annee->update([
                'en_cours' => false,
                'cloturee' => true,
                'archivee' => true,
                'archive_path' => $path,
                'archive_checksum' => $checksum,
                'restoration_key_hash' => Hash::make($restorationKey),
                'restoration_key_vault' => Crypt::encryptString($restorationKey),
                'archived_at' => now(),
                'archived_by' => $user->id,
                'archive_meta' => [
                    'counts' => self::compterExports($payload),
                    'purged' => $purgerDonnees,
                ],
            ]);

            if ($purgerDonnees) {
                self::purgerDonneesAnnee($annee);
            }

            return [
                'restoration_key' => $restorationKey,
                'path' => $path,
                'meta' => $annee->archive_meta,
            ];
        });
    }

    /**
     * Normalise la clé saisie (espaces, tirets, casse).
     */
    public static function normaliserCleRestauration(string $cle): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', $cle));

        if (strlen($clean) === 16) {
            return implode('-', str_split($clean, 4));
        }

        return strtoupper(trim($cle));
    }

    /**
     * Déchiffre et réimporte une année archivée.
     *
     * @return array{counts: array<string, int>, libelle: string}
     */
    public static function restaurer(AnneeScolaire $annee, string $cleSaisie): array
    {
        if (! $annee->archivee || ! $annee->archive_path) {
            throw ValidationException::withMessages([
                'cle_restauration' => ['Aucune archive chiffrée pour cette année.'],
            ]);
        }

        $payload = self::chargerPayloadArchive($annee, $cleSaisie);
        $counts = self::compterExports($payload);

        DB::transaction(function () use ($annee, $payload, $counts) {
            self::importerDonnees($annee, $payload);
            $annee->update([
                'archivee' => false,
                'cloturee' => false,
                'en_cours' => false,
                'archive_meta' => array_merge($annee->archive_meta ?? [], [
                    'counts' => $counts,
                    'restaurer_le' => now()->toIso8601String(),
                ]),
            ]);
            AnneeScolaireDonneesService::synchroniserDepuisInscriptions($annee->fresh());
        });

        return [
            'counts' => $counts,
            'libelle' => $annee->libelle,
        ];
    }

    /**
     * Métadonnées lisibles d'une archive (sans déchiffrement).
     *
     * @return array<string, mixed>
     */
    public static function metaArchive(AnneeScolaire $annee): array
    {
        return [
            'libelle' => $annee->libelle,
            'archivee' => (bool) $annee->archivee,
            'archive_path' => $annee->archive_path,
            'archived_at' => $annee->archived_at?->toIso8601String(),
            'counts' => $annee->archive_meta['counts'] ?? [],
            'purged' => $annee->archive_meta['purged'] ?? null,
            'fichier_present' => $annee->archive_path
                ? Storage::disk('local')->exists($annee->archive_path)
                : false,
        ];
    }

    /**
     * Réimporte uniquement l'emploi du temps depuis le fichier .enc (récupération après archivage).
     *
     * @return array{imported: int, dans_archive: int, classes_mappees: int}
     */
    public static function reimporterEmploiDuTemps(AnneeScolaire $annee, ?string $cleSaisie = null): array
    {
        $payload = self::chargerPayloadArchive($annee, $cleSaisie);
        $dansArchive = count($payload['emploi_du_temps'] ?? []);

        if ($dansArchive === 0) {
            throw ValidationException::withMessages([
                'annee' => [
                    'Cette archive ne contient aucun emploi du temps (sauvegarde créée avant la prise en charge EDT, ou EDT déjà perdu à l\'archivage). Il faudra ressaisir ou régénérer l\'emploi du temps.',
                ],
            ]);
        }

        $classeMap = self::construireClasseMapDepuisPayload($annee, $payload);
        $classesRecreees = 0;

        // Si aucune classe en DB pour cette année → recréer depuis le payload (avant import EDT)
        if ($classeMap === []) {
            DB::transaction(function () use ($annee, $payload, &$classeMap, &$classesRecreees) {
                foreach ($payload['classes'] ?? [] as $row) {
                    $oldId = $row['id'] ?? null;
                    if (! $oldId || empty($row['nom'])) continue;

                    unset($row['id'], $row['created_at'], $row['updated_at']);
                    $row['annee_scolaire_id'] = $annee->id;
                    $row['etablissement_id'] = $row['etablissement_id'] ?? $annee->etablissement_id;
                    $row['effectif'] = $row['effectif'] ?? 0;

                    $classe = Classe::updateOrCreate(
                        [
                            'etablissement_id' => $row['etablissement_id'],
                            'annee_scolaire_id' => $annee->id,
                            'nom' => $row['nom'],
                        ],
                        $row
                    );
                    $classeMap[$oldId] = $classe->id;
                    $classesRecreees++;
                }
            });
        }

        if ($classeMap === []) {
            throw ValidationException::withMessages([
                'annee' => [
                    'Impossible de reconstruire les classes depuis l\'archive (payload sans classes). Restaurez l\'année complète depuis « Années scolaires » d\'abord.',
                ],
            ]);
        }

        $imported = 0;
        DB::transaction(function () use ($annee, $payload, $classeMap, &$imported) {
            $imported = self::importerEmploiDuTempsAvecMap($annee, $payload, $classeMap);
        });

        return [
            'imported' => $imported,
            'dans_archive' => $dansArchive,
            'classes_mappees' => count($classeMap),
            'classes_recreees' => $classesRecreees,
        ];
    }

    /**
     * Nombre de créneaux EDT présents dans le fichier d'archive (sans import).
     */
    public static function compterEmploiDansArchive(AnneeScolaire $annee, ?string $cleSaisie = null): int
    {
        try {
            $payload = self::chargerPayloadArchive($annee, $cleSaisie);

            return count($payload['emploi_du_temps'] ?? []);
        } catch (\Throwable) {
            return -1;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function chargerPayloadArchive(AnneeScolaire $annee, ?string $cleSaisie): array
    {
        if (! $annee->archive_path || ! Storage::disk('local')->exists($annee->archive_path)) {
            throw ValidationException::withMessages([
                'annee' => ['Fichier d\'archive introuvable sur le serveur.'],
            ]);
        }

        $cle = self::resoudreCleRestauration($annee, $cleSaisie);

        $encrypted = Storage::disk('local')->get($annee->archive_path);
        $json = self::dechiffrer($encrypted, $cle);

        if ($annee->archive_checksum) {
            $checksum = hash('sha256', $json);
            if ($checksum !== $annee->archive_checksum) {
                throw ValidationException::withMessages([
                    'cle_restauration' => ['Archive corrompue ou clé invalide.'],
                ]);
            }
        }

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private static function resoudreCleRestauration(AnneeScolaire $annee, ?string $cleSaisie): string
    {
        if ($cleSaisie) {
            $cle = self::normaliserCleRestauration($cleSaisie);
            if ($annee->restoration_key_hash && ! Hash::check($cle, (string) $annee->restoration_key_hash)) {
                throw ValidationException::withMessages([
                    'cle_restauration' => ['Clé de restauration incorrecte.'],
                ]);
            }

            return $cle;
        }

        if ($annee->restoration_key_vault) {
            try {
                return Crypt::decryptString($annee->restoration_key_vault);
            } catch (\Throwable) {
                // continue
            }
        }

        throw ValidationException::withMessages([
            'cle_restauration' => ['Clé de restauration requise pour lire l\'archive.'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, int>
     */
    private static function construireClasseMapDepuisPayload(AnneeScolaire $annee, array $payload): array
    {
        $classeMap = [];

        foreach ($payload['classes'] ?? [] as $row) {
            $oldId = $row['id'] ?? null;
            if (! $oldId || empty($row['nom'])) {
                continue;
            }

            $classe = Classe::query()
                ->where('annee_scolaire_id', $annee->id)
                ->where('etablissement_id', $row['etablissement_id'] ?? $annee->etablissement_id)
                ->where('nom', $row['nom'])
                ->first();

            if ($classe) {
                $classeMap[$oldId] = $classe->id;
            }
        }

        return $classeMap;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, int>  $classeMap
     */
    private static function importerEmploiDuTempsAvecMap(AnneeScolaire $annee, array $payload, array $classeMap): int
    {
        $imported = 0;

        foreach ($payload['emploi_du_temps'] ?? [] as $row) {
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['annee_scolaire_id'] = $annee->id;
            $row['etablissement_id'] = $row['etablissement_id'] ?? $annee->etablissement_id;

            if (! isset($row['classe_id'], $classeMap[$row['classe_id']])) {
                continue;
            }

            $row['classe_id'] = $classeMap[$row['classe_id']];

            if (! Enseignant::where('id', $row['enseignant_id'] ?? 0)->exists()
                || ! Matiere::where('id', $row['matiere_id'] ?? 0)->exists()
                || ! Creneau::where('id', $row['creneau_id'] ?? 0)->exists()) {
                continue;
            }

            EmploiDuTemps::updateOrCreate(
                [
                    'etablissement_id' => $row['etablissement_id'],
                    'annee_scolaire_id' => $annee->id,
                    'classe_id' => $row['classe_id'],
                    'creneau_id' => $row['creneau_id'],
                    'jour' => $row['jour'],
                    'matiere_id' => $row['matiere_id'],
                ],
                $row
            );
            $imported++;
        }

        return $imported;
    }

    /**
     * Restauration depuis un fichier .enc externe (récupération de sinistre).
     * Utilisée quand l'archive sur disque a été perdue mais que l'admin
     * dispose d'une sauvegarde du fichier + de la clé de chiffrement.
     *
     * Si $annee est null, crée un nouvel enregistrement année à partir
     * des métadonnées contenues dans le payload.
     *
     * @return AnneeScolaire L'année (existante ou créée) après restauration.
     */
    public static function restaurerDepuisFichier(string $contenuChiffre, string $cleSaisie, int $etablissementId, ?AnneeScolaire $annee = null): AnneeScolaire
    {
        $cle = self::normaliserCleRestauration($cleSaisie);

        // 1) Déchiffrer
        $json = self::dechiffrer($contenuChiffre, $cle);

        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ValidationException::withMessages(['archive' => ['Archive corrompue : contenu illisible après déchiffrement.']]);
        }

        if (! isset($payload['annee'])) {
            throw ValidationException::withMessages(['archive' => ['Format d\'archive invalide (métadonnées année manquantes).']]);
        }

        $anneeMeta = $payload['annee'];
        if ((int) ($anneeMeta['etablissement_id'] ?? 0) !== $etablissementId) {
            throw ValidationException::withMessages(['archive' => ['Cette archive appartient à un autre établissement.']]);
        }

        // 2) Créer ou récupérer l'année cible
        $annee = $annee ?? AnneeScolaire::firstOrCreate(
            [
                'etablissement_id' => $etablissementId,
                'libelle'          => $anneeMeta['libelle'] ?? ('Restaurée-'.now()->format('YmdHis')),
            ],
            [
                'date_debut' => $anneeMeta['date_debut'] ?? now()->subYear()->format('Y-09-01'),
                'date_fin'   => $anneeMeta['date_fin'] ?? now()->format('Y-07-31'),
                'en_cours'   => false,
                'cloturee'   => false,
                'archivee'   => false,
            ]
        );

        // 3) Sauvegarder le fichier dans archives/ + mettre à jour les métas
        $checksum = hash('sha256', $json);
        $path = "archives/etab_{$etablissementId}/restauration_{$annee->id}_".now()->format('Ymd_His').'.enc';
        Storage::disk('local')->put($path, $contenuChiffre);

        DB::transaction(function () use ($annee, $payload, $path, $checksum, $cle) {
            self::importerDonnees($annee, $payload);

            $annee->update([
                'archive_path'           => $path,
                'archive_checksum'       => $checksum,
                'restoration_key_hash'   => Hash::make($cle),
                'restoration_key_vault'  => Crypt::encryptString($cle),
                'cloturee'               => false,
                'archivee'               => false,
                'archive_meta'           => [
                    'counts'         => self::compterExports($payload),
                    'restaurer_le'   => now()->toIso8601String(),
                    'depuis_fichier' => true,
                ],
            ]);
        });

        return $annee->fresh();
    }

    public static function lienWaveRestauration(int $montant = self::FRAIS_RESTAURATION_FCFA): ?string
    {
        $base = PlatformSetting::get(self::PLATFORM_WAVE_RESTAURATION_KEY);
        if (! $base) {
            return null;
        }

        try {
            $fake = new \App\Models\Etablissement([
                'wave_actif' => true,
                'wave_lien_base' => $base,
            ]);

            return WavePaymentLinkService::construireUrl($fake, $montant);
        } catch (\Throwable) {
            return rtrim($base, '/').(str_contains($base, '?') ? '&' : '?').'amount='.$montant;
        }
    }

    /** @return array<string, mixed> */
    private static function exporterDonnees(AnneeScolaire $annee): array
    {
        $annee->load(['trimestres']);

        return [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'annee' => $annee->only(['id', 'etablissement_id', 'libelle', 'date_debut', 'date_fin']),
            'trimestres' => $annee->trimestres->map->toArray()->all(),
            'classes' => Classe::where('annee_scolaire_id', $annee->id)->get()->map->toArray()->all(),
            'inscriptions' => Inscription::where('annee_scolaire_id', $annee->id)->get()->map->toArray()->all(),
            'paiements' => Paiement::whereHas('inscription', fn ($q) => $q->where('annee_scolaire_id', $annee->id))
                ->get()->map->toArray()->all(),
            'emploi_du_temps' => EmploiDuTemps::where('annee_scolaire_id', $annee->id)
                ->get()->map->toArray()->all(),
        ];
    }

    /** @param array<string, mixed> $payload */
    private static function compterExports(array $payload): array
    {
        return [
            'classes' => count($payload['classes'] ?? []),
            'inscriptions' => count($payload['inscriptions'] ?? []),
            'paiements' => count($payload['paiements'] ?? []),
            'trimestres' => count($payload['trimestres'] ?? []),
            'emploi_du_temps' => count($payload['emploi_du_temps'] ?? []),
        ];
    }

    private static function chiffrer(string $plain, string $key): string
    {
        $derived = hash('sha256', $key, true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $derived, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new \RuntimeException('Échec du chiffrement de l\'archive.');
        }

        return base64_encode($iv.$cipher);
    }

    private static function dechiffrer(string $packed, string $key): string
    {
        $raw = base64_decode($packed, true);
        if ($raw === false || strlen($raw) < 17) {
            throw ValidationException::withMessages(['cle' => ['Format d\'archive invalide.']]);
        }

        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $derived = hash('sha256', $key, true);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $derived, OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            throw ValidationException::withMessages(['cle' => ['Impossible de déchiffrer l\'archive avec cette clé.']]);
        }

        return $plain;
    }

    private static function purgerDonneesAnnee(AnneeScolaire $annee): void
    {
        $anneeId = $annee->id;

        Paiement::whereIn('inscription_id', Inscription::where('annee_scolaire_id', $anneeId)->pluck('id'))->delete();
        Inscription::where('annee_scolaire_id', $anneeId)->delete();
        Classe::where('annee_scolaire_id', $anneeId)->delete();
        Trimestre::where('annee_scolaire_id', $anneeId)->delete();
    }

    /**
     * Réimporte les données archivées en construisant les mappings
     * old_id → new_id pour préserver les relations même si les IDs ont changé.
     *
     * @param array<string, mixed> $payload
     */
    private static function importerDonnees(AnneeScolaire $annee, array $payload): void
    {
        $trimestreMap = [];   // old_trimestre_id => new_trimestre_id
        $classeMap = [];      // old_classe_id    => new_classe_id
        $inscriptionMap = []; // old_inscription_id => new_inscription_id

        // ─── 1. Trimestres ───
        foreach ($payload['trimestres'] ?? [] as $row) {
            $oldId = $row['id'] ?? null;
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['annee_scolaire_id'] = $annee->id;

            $trim = Trimestre::updateOrCreate(
                ['annee_scolaire_id' => $annee->id, 'numero' => $row['numero']],
                $row
            );
            if ($oldId) $trimestreMap[$oldId] = $trim->id;
        }

        // ─── 2. Classes ───
        foreach ($payload['classes'] ?? [] as $row) {
            $oldId = $row['id'] ?? null;
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['annee_scolaire_id'] = $annee->id;

            $classe = Classe::updateOrCreate(
                [
                    'etablissement_id' => $row['etablissement_id'],
                    'annee_scolaire_id' => $annee->id,
                    'nom' => $row['nom'],
                ],
                $row
            );
            if ($oldId) $classeMap[$oldId] = $classe->id;
        }

        // ─── 3. Inscriptions (avec traduction classe_id) ───
        foreach ($payload['inscriptions'] ?? [] as $row) {
            $oldId = $row['id'] ?? null;
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['annee_scolaire_id'] = $annee->id;

            // Traduire l'ancien classe_id vers le nouveau
            if (isset($row['classe_id']) && isset($classeMap[$row['classe_id']])) {
                $row['classe_id'] = $classeMap[$row['classe_id']];
            } else {
                // Classe introuvable → on saute cette inscription pour éviter le crash FK
                continue;
            }

            // Vérifier que l'élève existe encore
            if (! \App\Models\Eleve::where('id', $row['eleve_id'])->exists()) {
                continue;
            }

            // updateOrCreate sur la clé unique métier (eleve + année)
            $row['statut'] = ($row['statut'] ?? '') === 'validee' ? 'validee' : 'validee';

            $insc = Inscription::updateOrCreate(
                ['eleve_id' => $row['eleve_id'], 'annee_scolaire_id' => $annee->id],
                $row
            );
            if ($oldId) $inscriptionMap[$oldId] = $insc->id;
        }

        // ─── 4. Paiements (avec traduction inscription_id + trimestre_id si présent) ───
        foreach ($payload['paiements'] ?? [] as $row) {
            unset($row['id'], $row['created_at'], $row['updated_at']);

            if (isset($row['inscription_id']) && isset($inscriptionMap[$row['inscription_id']])) {
                $row['inscription_id'] = $inscriptionMap[$row['inscription_id']];
            } elseif (isset($row['inscription_id'])) {
                continue; // inscription absente → on saute
            }

            if (isset($row['trimestre_id']) && isset($trimestreMap[$row['trimestre_id']])) {
                $row['trimestre_id'] = $trimestreMap[$row['trimestre_id']];
            }

            // updateOrCreate sur référence unique si présente
            if (! empty($row['reference'])) {
                Paiement::updateOrCreate(['reference' => $row['reference']], $row);
            } else {
                Paiement::create($row);
            }
        }

        self::importerEmploiDuTempsAvecMap($annee, $payload, $classeMap);
    }
}
