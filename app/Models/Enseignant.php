<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\EdtVacataireImport;
use App\Models\EdtVacataireSlot;
use App\Models\EnseignantHoraireExterne;

class Enseignant extends Model
{
    use SoftDeletes;

    public const STATUT_TITULAIRE = 'titulaire';
    public const STATUT_CONTRACTUEL = 'contractuel';
    public const STATUT_VACATAIRE = 'vacataire';
    public const STATUT_STAGIAIRE = 'stagiaire';

    protected $fillable = [
        'user_id',
        'etablissement_id',
        'matricule_mena',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'telephone',
        'telephone_2',
        'email',
        'adresse',
        'diplome_plus_eleve',
        'specialite',
        'statut',
        'date_prise_fonction',
        'salaire_base',
        'type_remuneration',
        'taux_horaire',
        'heures_contractuelles_mois',
        'banque',
        'numero_compte',
        'photo_path',
        'score_ponctualite',
        'actif',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_prise_fonction' => 'date',
        'salaire_base' => 'decimal:0',
        'taux_horaire' => 'decimal:0',
        'heures_contractuelles_mois' => 'decimal:1',
        'score_ponctualite' => 'decimal:2',
        'actif' => 'boolean',
    ];

    protected $hidden = [
        'numero_compte',
    ];

    protected $appends = [
        'nom_complet',
        'statut_libelle',
        'photo_url',
    ];

    public static function statutsParDefaut(): array
    {
        return [
            self::STATUT_TITULAIRE,
            self::STATUT_CONTRACTUEL,
            self::STATUT_VACATAIRE,
            self::STATUT_STAGIAIRE,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function emploiDuTemps(): HasMany
    {
        return $this->hasMany(EmploiDuTemps::class);
    }

    public function pointages(): HasMany
    {
        return $this->hasMany(Pointage::class);
    }

    public function alertesPointage(): HasMany
    {
        return $this->hasMany(AlertePointage::class);
    }

    public function congesPermissions(): HasMany
    {
        return $this->hasMany(CongePermission::class);
    }

    public function remplacementsAttribues(): HasMany
    {
        return $this->hasMany(CongePermission::class, 'remplacant_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function paies(): HasMany
    {
        return $this->hasMany(PaieEnseignant::class);
    }

    public function statsPonctualite(): HasMany
    {
        return $this->hasMany(StatPonctualiteMensuelle::class);
    }

    public function classesPrincipales(): HasMany
    {
        return $this->hasMany(Classe::class, 'professeur_principal_id');
    }
    
    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(
            Matiere::class,
            'affectations',   // table pivot
            'enseignant_id',  // FK de l'enseignant
            'matiere_id'      // FK de la matière
        )->distinct();
    }

    public function presencesElevesSaisies(): HasMany
    {
        return $this->hasMany(PresenceEleve::class, 'enseignant_id');
    }

    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    public function getStatutLibelleAttribute(): string
    {
        return ucfirst((string) $this->statut);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? route('enseignants.photo', $this->getKey()) : null;
    }

    public function affectationsActives(): HasMany
    {
        return $this->affectations()->where('active', true);
    }

    public function classesActuelles()
    {
        return $this->affectationsActives()
            ->with(['classe', 'matiere'])
            ->get();
    }

    public function pointageDuJour($date = null): ?Pointage
    {
        $date = $date ?: today();

        return $this->pointages()
            ->whereDate('date', $date)
            ->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)
            ->orderByDesc('heure_scan')
            ->first();
    }

    public function pointageAujourdhui(): ?Pointage
    {
        return $this->pointageDuJour(today());
    }

    public function estPresentAujourdhui(): bool
    {
        $pointage = $this->pointageAujourdhui();

        return $pointage !== null
            && in_array($pointage->statut, [
                Pointage::STATUT_PRESENT,
                Pointage::STATUT_RETARD,
            ], true);
    }

    public function estEnRetardAujourdhui(): bool
    {
        $pointage = $this->pointageAujourdhui();

        return $pointage !== null && $pointage->statut === Pointage::STATUT_RETARD;
    }

    public function nbAlertesNonTraitees(): int
    {
        return $this->alertesPointage()
            ->where('traitee', false)
            ->count();
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopePourEtablissement(Builder $query, int $etablissementId): Builder
    {
        return $query->where('etablissement_id', $etablissementId);
    }

    /**
     * Enseignants ayant au moins une affectation active pour l'année donnée
     * (ou l'année courante du contexte si null).
     */
    public function scopeAffectesCetteAnnee(Builder $query, ?int $anneeId = null): Builder
    {
        if (! $anneeId) {
            $annee = \App\Services\Scolarite\AnneeScolaireContext::courante();
            $anneeId = $annee?->id;
        }

        if (! $anneeId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('affectations', function ($q) use ($anneeId) {
            $q->where('annee_scolaire_id', $anneeId)->where('active', true);
        });
    }
    
    public function vacataireImports(): HasMany
{
    return $this->hasMany(EdtVacataireImport::class, 'enseignant_id');
}

public function vacataireSlots(): HasMany
{
    return $this->hasMany(EdtVacataireSlot::class, 'enseignant_id');
}

public function estVacataire(): bool
{
    return $this->statut === self::STATUT_VACATAIRE;
}

public function horairesExternes(): HasMany
{
    return $this->hasMany(EnseignantHoraireExterne::class);
}

public function horairesExternesActifs(): HasMany
{
    return $this->horairesExternes()->where('valide', true);
}
}