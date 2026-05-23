<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FichePaie extends Model
{
    use SoftDeletes;

    protected $table = 'fiches_paie';

    protected $fillable = [
        'etablissement_id', 'enseignant_id', 'reference', 'mois', 'periode_debut', 'periode_fin',
        'type_remuneration', 'salaire_base', 'taux_horaire', 'heures_travaillees', 'heures_contractuelles',
        'montant_horaire', 'primes', 'indemnites', 'avances', 'retenues',
        'details_primes', 'details_retenues',
        'salaire_brut', 'cotisations_sociales', 'impots', 'salaire_net',
        'nb_jours_travailles', 'nb_jours_absents', 'nb_retards',
        'statut', 'generee_par', 'validee_par', 'date_validation',
        'date_paiement_effectif', 'mode_paiement', 'observations',
    ];

    protected $casts = [
        'periode_debut'          => 'date',
        'periode_fin'            => 'date',
        'date_validation'        => 'datetime',
        'date_paiement_effectif' => 'date',
        'details_primes'         => 'json',
        'details_retenues'       => 'json',
    ];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function enseignant(): BelongsTo { return $this->belongsTo(Enseignant::class); }
    public function generePar(): BelongsTo { return $this->belongsTo(User::class, 'generee_par'); }
    public function validePar(): BelongsTo { return $this->belongsTo(User::class, 'validee_par'); }

    public static function genererReference(int $etablissementId): string
    {
        $count = static::where('etablissement_id', $etablissementId)
            ->where('mois', now()->format('Y-m'))->count();
        return sprintf('FP-%s-%04d', now()->format('Y-m'), $count + 1);
    }
}
