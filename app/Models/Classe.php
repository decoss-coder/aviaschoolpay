<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'niveau_id',
        'serie_id',
        'nom',
        'capacite',
        'effectif',
        'scolarite_annuelle',
        'frais_inscription',
        'frais_reinscription',
        'description',
        'professeur_principal_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'capacite' => 'integer',
        'effectif' => 'integer',
        'scolarite_annuelle' => 'integer',
        'frais_inscription' => 'integer',
        'frais_reinscription' => 'integer',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function serie(): BelongsTo
    {
        return $this->belongsTo(Serie::class);
    }

    public function professeurPrincipal(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'professeur_principal_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function emploiDuTemps(): HasMany
    {
        return $this->hasMany(EmploiDuTemps::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function moyennesMatieres(): HasMany
    {
        return $this->hasMany(MoyenneMatiere::class);
    }

    public function moyennesGenerales(): HasMany
    {
        return $this->hasMany(MoyenneGenerale::class, 'classe_id');
    }

    public function moyennesAnnuelles(): HasMany
    {
        return $this->hasMany(MoyenneAnnuelle::class, 'classe_id');
    }

    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class, 'classe_id');
    }

    public function presencesEleves(): HasMany
    {
        return $this->hasMany(PresenceEleve::class, 'classe_id');
    }

    public function eleves(): HasMany
    {
        return $this->hasMany(Eleve::class, 'classe_id', 'id');
    }

    public function updateEffectif(): void
    {
        if ($this->annee_scolaire_id) {
            $this->updateEffectifDepuisInscriptions();

            return;
        }

        $this->update([
            'effectif' => $this->eleves()
                ->where('actif', true)
                ->count(),
        ]);
    }

    /** Effectif = inscriptions validées de l'année de la classe (source fiable après archive). */
    public function updateEffectifDepuisInscriptions(): void
    {
        $effectif = Inscription::query()
            ->where('classe_id', $this->id)
            ->where('annee_scolaire_id', $this->annee_scolaire_id)
            ->where('statut', 'validee')
            ->whereHas('eleve', fn ($q) => $q->where('actif', true))
            ->count();

        $this->update(['effectif' => $effectif]);
    }

    public function getTotalNouveauAttribute(): int
    {
        return (int) $this->scolarite_annuelle + (int) $this->frais_inscription;
    }

    public function getTotalReinscriptionAttribute(): int
    {
        return (int) $this->scolarite_annuelle + (int) $this->frais_reinscription;
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function getNiveauReglementaireCodeAttribute(): ?string
    {
        return $this->niveau?->code;
    }

    public function getOptionReglementaireCodeAttribute(): ?string
    {
        return null;
    }
}