<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtClassePlageHoraire extends Model
{
    protected $table = 'edt_classe_plage_horaire';

    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'classe_id',
        'jour',
        'plage',
        'autorise',
        'notes',
    ];

    protected $casts = [
        'autorise' => 'boolean',
    ];

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    /**
     * Construit le map de restrictions plage par classe.
     *
     * Structure retournée :
     *   $map[$classeId]['*'][$plage]  = bool  (règle "tous les jours")
     *   $map[$classeId][$jour][$plage] = bool  (règle spécifique à un jour)
     *
     * Logique de consultation :
     *   1. Cherche règle spécifique (classe, jour, plage) → si trouvée, l'utilise
     *   2. Cherche règle générale   (classe, *, plage)   → si trouvée, l'utilise
     *   3. Sinon : autorisé par défaut
     */
    public static function buildMap(
        \Illuminate\Support\Collection $classeIds,
        ?int $anneeScolaireId
    ): array {
        $map = [];

        static::query()
            ->whereIn('classe_id', $classeIds)
            ->where(function ($q) use ($anneeScolaireId) {
                $q->whereNull('annee_scolaire_id');
                if ($anneeScolaireId) {
                    $q->orWhere('annee_scolaire_id', $anneeScolaireId);
                }
            })
            ->get()
            ->each(function ($row) use (&$map) {
                $jour = $row->jour ?? '*';
                $map[$row->classe_id][$jour][$row->plage] = (bool) $row->autorise;
            });

        return $map;
    }

    /**
     * Vérifie si une classe peut être planifiée sur une plage/jour donné.
     */
    public static function isAllowed(array $map, int $classeId, string $jour, string $plage): bool
    {
        // Règle spécifique au jour
        if (isset($map[$classeId][$jour][$plage])) {
            return $map[$classeId][$jour][$plage];
        }

        // Règle générale (tous les jours)
        if (isset($map[$classeId]['*'][$plage])) {
            return $map[$classeId]['*'][$plage];
        }

        // Pas de restriction → autorisé
        return true;
    }
}
