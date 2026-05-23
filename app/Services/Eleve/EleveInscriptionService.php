<?php

namespace App\Services\Eleve;

use App\Models\Eleve;
use App\Models\User;

class EleveInscriptionService
{
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '00225')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '225') && strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        // Côte d'Ivoire : 10 chiffres avec 0 initial (ex. 07… / 01…)
        if (strlen($digits) === 9 && $digits[0] !== '0') {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    /** @return list<string> Variantes équivalentes pour rapprochement téléphone. */
    public static function phoneVariants(?string $phone): array
    {
        $canonical = self::normalize($phone);
        if ($canonical === '') {
            return [];
        }

        $variants = [$canonical];
        if (str_starts_with($canonical, '0')) {
            $variants[] = substr($canonical, 1);
            $variants[] = '225'.$canonical;
            $variants[] = '00225'.substr($canonical, 1);
        }

        return array_values(array_unique(array_filter($variants)));
    }

    public static function phonesMatch(?string $a, ?string $b): bool
    {
        $va = self::phoneVariants($a);
        $vb = self::phoneVariants($b);

        return $va !== [] && $vb !== [] && count(array_intersect($va, $vb)) > 0;
    }

    /** @return list<string> */
    public static function referenceParentPhones(Eleve $eleve): array
    {
        $phones = [];

        if ($eleve->contact_urgence_tel) {
            $phones[] = self::normalize($eleve->contact_urgence_tel);
        }

        if ($eleve->telephone) {
            $n = self::normalize($eleve->telephone);
            if ($n !== '' && $n !== '00000000') {
                $phones[] = $n;
            }
        }

        $eleve->loadMissing('parents');
        foreach ($eleve->parents as $parent) {
            if ($parent->telephone) {
                $phones[] = self::normalize($parent->telephone);
            }
            if ($parent->telephone_2) {
                $phones[] = self::normalize($parent->telephone_2);
            }
        }

        return array_values(array_unique(array_filter($phones)));
    }

    /**
     * @return array{ok: bool, normalized: string, message?: string}
     */
    public static function validateParentPhone(Eleve $eleve, string $submitted): array
    {
        $normalized = self::normalize($submitted);

        if (strlen($normalized) < 8) {
            return [
                'ok' => false,
                'normalized' => $normalized,
                'message' => 'Numéro de téléphone du parent invalide (8 chiffres minimum).',
            ];
        }

        $references = self::referenceParentPhones($eleve);

        if ($references !== [] && ! in_array($normalized, $references, true)) {
            return [
                'ok' => false,
                'normalized' => $normalized,
                'message' => 'Ce numéro ne correspond pas à celui enregistré à l\'école pour le parent ou tuteur.',
            ];
        }

        return ['ok' => true, 'normalized' => $normalized];
    }

    public static function uniqueUserTelephone(Eleve $eleve): string
    {
        $candidate = 'E'.str_pad((string) $eleve->id, 9, '0', STR_PAD_LEFT);

        if (! User::where('telephone', $candidate)->exists()) {
            return $candidate;
        }

        do {
            $candidate = 'E'.$eleve->id.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (User::where('telephone', $candidate)->exists());

        return $candidate;
    }

    public static function persistParentPhone(Eleve $eleve, string $normalized): void
    {
        $updates = [];

        if (! $eleve->contact_urgence_tel) {
            $updates['contact_urgence_tel'] = $normalized;
        }

        if ($updates !== []) {
            $eleve->update($updates);
        }
    }
}
