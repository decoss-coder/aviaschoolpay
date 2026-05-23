<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Salle extends Model
{
    protected $fillable = [
        'etablissement_id',
        'nom',
        'batiment',
        'capacite',
        'type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'capacite' => 'integer',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function qrCodeActif(): HasOne
    {
        return $this->hasOne(QrCode::class)->where('actif', true);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function pointages(): HasMany
    {
        return $this->hasMany(Pointage::class);
    }

    public function emploiDuTemps(): HasMany
    {
        return $this->hasMany(EmploiDuTemps::class);
    }
}