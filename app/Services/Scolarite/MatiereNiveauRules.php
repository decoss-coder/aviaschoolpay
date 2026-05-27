<?php

namespace App\Services\Scolarite;

use App\Models\Classe;
use App\Models\Matiere;

/**
 * Règles d'éligibilité matière × niveau (cycle).
 *
 * Encode les restrictions du système scolaire ivoirien :
 *  - ESP / ALL  : 4e, 3e + tout le 2nd cycle (interdites en 6e/5e)
 *  - PHILO      : 2nd cycle uniquement (2nde, 1ère, Tle)
 *  - SVT / PC   : autorisées partout (pas de restriction)
 *  - FR/MATH/HG/ANG/EPS/EDHC : autorisées partout
 *
 * Ajout d'une nouvelle règle : ajouter une entrée dans self::RULES.
 */
class MatiereNiveauRules
{
    /**
     * Codes-matière à valeur restrictive. Si un code n'apparaît pas ici,
     * la matière est autorisée pour tous les niveaux.
     *
     * Chaque entrée :
     *  - cycles      : cycles autorisés (parmi 'premier_cycle', 'second_cycle')
     *  - niveau_min  : code-niveau min (optionnel, dans le 1er cycle) — '4E' / '3E'
     *  - label_court : pour le message d'erreur
     */
    public const RULES = [
        'ESP' => [
            'cycles'      => ['premier_cycle', 'second_cycle'],
            'niveau_min'  => '4E', // dans le 1er cycle, à partir de 4e
            'label_court' => 'Espagnol (LV2)',
        ],
        'ALL' => [
            'cycles'      => ['premier_cycle', 'second_cycle'],
            'niveau_min'  => '4E',
            'label_court' => 'Allemand (LV2)',
        ],
        'PHILO' => [
            'cycles'      => ['second_cycle'],
            'niveau_min'  => null,
            'label_court' => 'Philosophie',
        ],
    ];

    /** Ordre des niveaux du 1er cycle, par code (sert au comparatif niveau_min). */
    private const ORDRE_PREMIER_CYCLE = ['6E' => 1, '5E' => 2, '4E' => 3, '3E' => 4];

    /**
     * True si la matière (par code) est autorisée pour la classe donnée.
     * Retourne true si aucune règle n'est définie pour ce code.
     */
    public static function estAutorisee(?string $matiereCode, ?Classe $classe): bool
    {
        if (! $matiereCode || ! $classe) {
            return true;
        }
        $code = strtoupper(trim($matiereCode));
        if (! isset(self::RULES[$code])) {
            return true;
        }
        $classe->loadMissing('niveau:id,code,cycle');
        $cycle = strtolower($classe->niveau?->cycle ?? '');
        $niveauCode = strtoupper($classe->niveau?->code ?? '');

        $rule = self::RULES[$code];

        // Cycle pas autorisé du tout
        if (! in_array($cycle, $rule['cycles'], true)) {
            return false;
        }

        // Niveau minimum dans le 1er cycle ?
        if ($rule['niveau_min'] && $cycle === 'premier_cycle') {
            $min = self::ORDRE_PREMIER_CYCLE[$rule['niveau_min']] ?? null;
            $cur = self::ORDRE_PREMIER_CYCLE[$niveauCode] ?? null;
            if ($min !== null && $cur !== null && $cur < $min) {
                return false;
            }
        }

        return true;
    }

    /** Variante prenant les ids (utile dans les validators). */
    public static function estAutoriseeIds(?int $matiereId, ?int $classeId): bool
    {
        if (! $matiereId || ! $classeId) {
            return true;
        }
        $matiere = Matiere::select('id', 'code', 'parent_matiere_id')->find($matiereId);
        // Si sous-discipline (Français → CF/OG/EO) : on remonte au parent pour la règle
        $code = $matiere?->code;
        if ($matiere?->parent_matiere_id) {
            $parent = Matiere::select('id', 'code')->find($matiere->parent_matiere_id);
            $code = $parent?->code ?? $code;
        }
        return self::estAutorisee($code, Classe::find($classeId));
    }

    /**
     * Message d'erreur explicite pour l'utilisateur.
     */
    public static function messageInterdit(?string $matiereCode, ?Classe $classe): string
    {
        $code = strtoupper(trim((string) $matiereCode));
        $rule = self::RULES[$code] ?? null;
        if (! $rule) {
            return "La matière {$matiereCode} n'est pas autorisée pour cette classe.";
        }
        $label = $rule['label_court'];
        $cibles = [];
        if (in_array('premier_cycle', $rule['cycles'], true)) {
            $min = $rule['niveau_min'];
            $cibles[] = $min === '4E' ? 'à partir de la 4ème' : 'en 1er cycle';
        }
        if (in_array('second_cycle', $rule['cycles'], true)) {
            $cibles[] = 'tout le 2nd cycle (2nde → Terminale)';
        }
        return "{$label} n'est autorisée que " . implode(' et ', $cibles) . '.';
    }

    /**
     * Filtre une collection de matières pour ne garder que celles éligibles à la classe.
     * Utile pour les pickers (mobile / admin).
     */
    public static function filtrerMatieresEligibles($matieres, ?Classe $classe)
    {
        if (! $classe) {
            return $matieres;
        }
        return collect($matieres)->filter(fn ($m) => self::estAutorisee(
            is_array($m) ? ($m['code'] ?? null) : ($m->code ?? null),
            $classe
        ))->values();
    }
}
