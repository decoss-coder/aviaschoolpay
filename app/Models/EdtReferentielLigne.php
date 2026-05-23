<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtReferentielLigne extends Model
{
    protected $table = 'edt_referentiel_lignes';

    protected $fillable = [
        'profil_id',
        'matiere_id',
        'obligatoire',
        'facultatif',
        'expression_source',
        'frequence',
        'mode_seance',
        'volume_classe_entiere_minutes',
        'volume_demi_classe_minutes',
        'volume_eleve_minutes',
        'volume_prof_minutes',
        'nb_blocs_souhaite',
        'blocs_consecutifs',
        'ecart_min_jours',
        'ordre_montage',
        'notes',
    ];

    protected $casts = [
        'obligatoire' => 'boolean',
        'facultatif' => 'boolean',
        'blocs_consecutifs' => 'boolean',
    ];

    public function profil(): BelongsTo
    {
        return $this->belongsTo(EdtReferentielProfil::class, 'profil_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }
}