<?php

// ══════════════════════════════════════════════════════════════
// app/Models/AlerteFinanciere.php
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlerteFinanciere extends Model
{
    protected $table = 'alertes_financieres';
    protected $fillable = [
        'etablissement_id', 'type', 'gravite', 'titre', 'message', 'recommandation_ia',
        'montant_concerne', 'reference_type', 'reference_id', 'lue', 'traitee', 'traitee_par', 'action_prise',
    ];
    protected $casts = ['lue' => 'boolean', 'traitee' => 'boolean'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
}