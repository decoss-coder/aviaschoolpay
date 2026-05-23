<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtVacataireSlot extends Model
{
    protected $table = 'edt_vacataire_slots';

    protected $fillable = [
        'import_id',
        'enseignant_id',
        'jour',
        'heure_debut',
        'heure_fin',
        'creneau_id',
        'etat',
        'site_externe',
        'commentaire',
        'source_confidence',
    ];

    protected $casts = [
        'source_confidence' => 'decimal:2',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(EdtVacataireImport::class, 'import_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    public function creneau(): BelongsTo
    {
        return $this->belongsTo(Creneau::class, 'creneau_id');
    }
}