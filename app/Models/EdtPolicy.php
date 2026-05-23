<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EdtPolicy extends Model
{
    protected $table = 'edt_policies';

    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'nom',
        'mode_generation',
        'description',
        'autoriser_reduction_heures',
        'autoriser_matieres_facultatives',
        'prioriser_classes_examen',
        'prioriser_permanents',
        'attendre_horaires_vacataires',
        'max_reduction_minutes_par_classe',
        'max_reduction_minutes_par_matiere',
        'actif',
        'created_by',
    ];

    protected $casts = [
        'autoriser_reduction_heures' => 'boolean',
        'autoriser_matieres_facultatives' => 'boolean',
        'prioriser_classes_examen' => 'boolean',
        'prioriser_permanents' => 'boolean',
        'attendre_horaires_vacataires' => 'boolean',
        'actif' => 'boolean',
    ];

    public function classOverrides(): HasMany
    {
        return $this->hasMany(EdtPolicyClassOverride::class, 'policy_id');
    }

    public function matiereOverrides(): HasMany
    {
        return $this->hasMany(EdtPolicyMatiereOverride::class, 'policy_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }
}