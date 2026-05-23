<?php

// ══════════════════════════════════════════════════════════════
// app/Models/Budget.php — MODULE 14
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Budget extends Model
{
    protected $table = 'budgets';
    protected $fillable = ['etablissement_id', 'exercice_id', 'libelle', 'periodicite', 'total_prevu_revenus', 'total_prevu_depenses', 'total_reel_revenus', 'total_reel_depenses', 'statut', 'cree_par', 'valide_par'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function exercice(): BelongsTo { return $this->belongsTo(ExerciceComptable::class, 'exercice_id'); }
    public function lignes(): HasMany { return $this->hasMany(LigneBudgetaire::class); }
    public function creePar(): BelongsTo { return $this->belongsTo(User::class, 'cree_par'); }
    public function validePar(): BelongsTo { return $this->belongsTo(User::class, 'valide_par'); }

    public function ecartGlobal(): int { return ($this->total_reel_revenus - $this->total_reel_depenses) - ($this->total_prevu_revenus - $this->total_prevu_depenses); }
    public function resultatPrevu(): int { return $this->total_prevu_revenus - $this->total_prevu_depenses; }
    public function resultatReel(): int { return $this->total_reel_revenus - $this->total_reel_depenses; }
}
