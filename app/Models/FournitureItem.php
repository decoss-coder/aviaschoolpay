<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FournitureItem extends Model
{
    protected $table = 'fournitures_items';

    protected $fillable = [
        'liste_id', 'libelle', 'categorie', 'quantite', 'unite',
        'marque_suggeree', 'obligatoire', 'observations', 'ordre',
    ];

    protected $casts = ['obligatoire' => 'boolean'];

    public function liste(): BelongsTo { return $this->belongsTo(ListeFournitures::class, 'liste_id'); }
}
