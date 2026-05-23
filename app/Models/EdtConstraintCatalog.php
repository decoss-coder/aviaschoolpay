<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdtConstraintCatalog extends Model
{
    protected $table = 'edt_constraint_catalog';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'categorie',
        'default_enabled',
        'default_weight',
        'is_mandatory',
    ];

    protected $casts = [
        'default_enabled' => 'boolean',
        'is_mandatory' => 'boolean',
        'default_weight' => 'decimal:2',
    ];
}