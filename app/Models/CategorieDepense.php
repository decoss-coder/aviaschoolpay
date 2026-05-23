<?php

// ══════════════════════════════════════════════════════════════
// app/Models/CategorieDepense.php
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class CategorieDepense extends Model
{
    protected $table = 'categories_depenses';
    protected $fillable = ['etablissement_id', 'nom', 'code', 'type', 'recurrente', 'compte_comptable_numero', 'icone', 'couleur', 'active'];
    protected $casts = ['recurrente' => 'boolean', 'active' => 'boolean'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function depenses(): HasMany { return $this->hasMany(Depense::class, 'categorie_id'); }

    public function totalMois(string $mois): int
    {
        return (int) $this->depenses()->approuvees()->mois($mois)->sum('montant');
    }
}
