<?php

// ══════════════════════════════════════════════════════════════
// app/Models/MouvementTresorerie.php
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementTresorerie extends Model
{
    protected $table = 'mouvements_tresorerie';
    protected $fillable = ['etablissement_id', 'compte_tresorerie_id', 'sens', 'montant', 'solde_avant', 'solde_apres', 'date_mouvement', 'libelle', 'reference_type', 'reference_id', 'saisie_par'];
    protected $casts = ['date_mouvement' => 'date'];

    public function compte(): BelongsTo { return $this->belongsTo(CompteTresorerie::class, 'compte_tresorerie_id'); }
}