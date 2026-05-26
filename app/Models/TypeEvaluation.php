<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeEvaluation extends Model
{
    protected $table = 'types_evaluation';

    protected $fillable = [
        'etablissement_id',
        'nom',
        'code',
        'active',
        'actif',
    ];

    protected $casts = [
        'active' => 'boolean',
        'actif' => 'boolean',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'type_evaluation_id');
    }
}
