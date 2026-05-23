<?php

// ══════════════════════════════════════════════════════════════
// app/Models/ExerciceComptable.php
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class ExerciceComptable extends Model
{
    protected $table = 'exercices_comptables';
    protected $fillable = ['etablissement_id', 'annee_scolaire_id', 'libelle', 'date_debut', 'date_fin', 'en_cours', 'cloture', 'solde_ouverture', 'solde_cloture'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date', 'en_cours' => 'boolean', 'cloture' => 'boolean'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function anneeScolaire(): BelongsTo { return $this->belongsTo(AnneeScolaire::class); }
    public function ecritures(): HasMany { return $this->hasMany(EcritureComptable::class, 'exercice_id'); }
    public function budgets(): HasMany { return $this->hasMany(Budget::class, 'exercice_id'); }
    public function depenses(): HasMany { return $this->hasMany(Depense::class, 'exercice_id'); }

    public function scopeEnCours($q) { return $q->where('en_cours', true); }
}
