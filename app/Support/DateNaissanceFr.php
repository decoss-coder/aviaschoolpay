<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DateNaissanceFr
{
    /**
     * Résout une date ISO (Y-m-d) depuis le champ hidden ou la saisie JJ/MM/AAAA.
     */
    public static function fromRequest(Request $request, string $key = 'date_naissance'): ?string
    {
        $raw = trim((string) $request->input($key, ''));
        if ($raw !== '') {
            try {
                return Carbon::parse($raw)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        return self::parseText((string) $request->input($key.'_text', ''));
    }

    public static function parseText(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $text, $m)) {
            return self::validIso((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $text, $m)) {
            return self::validIso((int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return null;
    }

    private static function validIso(int $year, int $month, int $day): ?string
    {
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * @return array{ok: bool, date: ?string, message?: string}
     */
    public static function validateForEleve(?\DateTimeInterface $eleveDate, ?string $submitted): array
    {
        if ($eleveDate) {
            if ($submitted === null || $submitted === '') {
                return [
                    'ok' => false,
                    'date' => null,
                    'message' => 'La date de naissance est requise pour confirmer votre identité.',
                ];
            }

            if ($eleveDate->format('Y-m-d') !== $submitted) {
                return [
                    'ok' => false,
                    'date' => $submitted,
                    'message' => 'La date de naissance ne correspond pas. Vérifiez auprès de l\'administration.',
                ];
            }

            return ['ok' => true, 'date' => $submitted];
        }

        if ($submitted === null || $submitted === '') {
            return ['ok' => true, 'date' => null];
        }

        return ['ok' => true, 'date' => $submitted];
    }
}
