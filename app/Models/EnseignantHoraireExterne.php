<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnseignantHoraireExterne extends Model
{
    protected $table = 'edt_enseignant_horaires_externes';

    protected $fillable = [
        'enseignant_id',
        'annee_scolaire_id',
        'etablissement_externe',
        'jour',
        'heure_debut',
        'heure_fin',
        'valide',
        'source',
        'commentaire',
        'created_by',
        'import_id',
    ];

    protected $casts = [
        'valide' => 'boolean',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(EnseignantHoraireImport::class, 'import_id');
    }

    /** Vérifie si ce slot chevauche un créneau donné */
    public function overlaps(string $creneauDebut, string $creneauFin): bool
    {
        // Chevauchement : début_slot < fin_creneau ET fin_slot > début_creneau
        return $this->heure_debut < $creneauFin && $this->heure_fin > $creneauDebut;
    }

    public function getLibelleAttribute(): string
    {
        return "{$this->etablissement_externe} — {$this->jour} {$this->heure_debut}–{$this->heure_fin}";
    }
}
