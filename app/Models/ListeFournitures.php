<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class ListeFournitures extends Model
{
    protected $table = 'listes_fournitures';

    protected $fillable = [
        'etablissement_id', 'classe_id', 'annee_scolaire_id',
        'titre', 'notes', 'publie', 'cree_par',
    ];

    protected $casts = ['publie' => 'boolean'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function classe(): BelongsTo { return $this->belongsTo(Classe::class); }
    public function anneeScolaire(): BelongsTo { return $this->belongsTo(AnneeScolaire::class); }
    public function creePar(): BelongsTo { return $this->belongsTo(User::class, 'cree_par'); }
    public function items(): HasMany { return $this->hasMany(FournitureItem::class, 'liste_id')->orderBy('ordre')->orderBy('id'); }
}
