<?php

namespace App\Services\Scolarite;

use App\Models\AnneeScolaire;

/**
 * Contexte request : année scolaire en cours pour l'établissement actif.
 */
class AnneeScolaireContext
{
    private static ?AnneeScolaire $courante = null;

    private static ?int $etablissementId = null;

    public static function set(?AnneeScolaire $annee, ?int $etablissementId = null): void
    {
        self::$courante = $annee;
        self::$etablissementId = $etablissementId ?? $annee?->etablissement_id;
    }

    public static function courante(): ?AnneeScolaire
    {
        return self::$courante;
    }

    /** Année du contexte request, ou année active de l'établissement. */
    public static function courantePourEtablissement(int $etablissementId): ?AnneeScolaire
    {
        $ctx = self::$courante;
        if ($ctx && (int) $ctx->etablissement_id === $etablissementId) {
            return $ctx;
        }

        return AnneeScolaireService::courantePourEtablissement($etablissementId);
    }

    public static function id(): ?int
    {
        return self::$courante?->id;
    }

    public static function etablissementId(): ?int
    {
        return self::$etablissementId;
    }

    public static function clear(): void
    {
        self::$courante = null;
        self::$etablissementId = null;
    }

    /** @return array<string, mixed>|null */
    public static function toApiPayload(): ?array
    {
        if (! self::$courante) {
            return null;
        }

        $a = self::$courante;

        return [
            'id' => $a->id,
            'libelle' => $a->libelle,
            'date_debut' => $a->date_debut?->toDateString(),
            'date_fin' => $a->date_fin?->toDateString(),
            'en_cours' => (bool) $a->en_cours,
            'cloturee' => (bool) $a->cloturee,
            'archivee' => (bool) $a->archivee,
            'lecture_seule' => $a->estLectureSeule(),
            'archive_consultation' => $a->estArchiveConsultation(),
            'restaurer_le' => $a->archive_meta['restaurer_le'] ?? null,
        ];
    }
}
