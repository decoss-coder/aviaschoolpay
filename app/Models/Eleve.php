<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany, HasOne};

class Eleve extends Model
{
    use SoftDeletes;

    public const STATUT_ELEVE_NON_AFFECTE = 'NAFF';
    public const STATUT_ELEVE_AFFECTE = 'AFF';

    protected $fillable = [
        'user_id',
        'etablissement_id',
        'classe_id',
        'statut_eleve',
        'matricule_interne',
        'matricule_desps',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'redoublant',
        'lv2',
        'option_arts',
        'lieu_naissance',
        'nationalite',
        'numero_extrait_naissance',
        'photo_path',
        'adresse',
        'groupe_sanguin',
        'allergies',
        'maladies_chroniques',
        'contact_urgence_nom',
        'contact_urgence_tel',
        'statut',
        'date_premiere_inscription',
        'ecole_precedente',
        'observations',
        'actif',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_premiere_inscription' => 'date',
        'actif' => 'boolean',
        'redoublant' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Eleve $eleve) {
            $eleve->statut_eleve = self::normalizeStatutEleve(
                $eleve->statut_eleve
            ) ?? '';
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            ParentTuteur::class,
            'eleve_parent',
            'eleve_id',
            'parent_id'
        )->withPivot('est_contact_principal');
    }

    public function contactPrincipal()
    {
        return $this->parents()->wherePivot('est_contact_principal', true)->first();
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function inscriptionEnCours(): HasOne
    {
        return $this->hasOne(Inscription::class)
            ->whereHas('anneeScolaire', fn ($q) => $q->enCours());
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function moyennesGenerales(): HasMany
    {
        return $this->hasMany(MoyenneGenerale::class);
    }

    public function moyennesAnnuelles(): HasMany
    {
        return $this->hasMany(MoyenneAnnuelle::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function presences(): HasMany
    {
        return $this->hasMany(PresenceEleve::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentEleve::class);
    }

    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class);
    }

    public function decisionsFinAnnee(): HasMany
    {
        return $this->hasMany(DecisionFinAnnee::class);
    }

    public function transferts(): HasMany
    {
        return $this->hasMany(Transfert::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    /** Compatibilité vues (colonne absente en base). */
    public function getTelephoneAttribute(): ?string
    {
        return $this->contact_urgence_tel;
    }

    public function getEmailAttribute(): ?string
    {
        return null;
    }

    public function getAgeAttribute(): int
    {
        return $this->date_naissance?->age ?? 0;
    }

    public function getStatutEleveLibelleAttribute(): string
    {
        return match ($this->statut_eleve) {
            self::STATUT_ELEVE_AFFECTE => 'Affecté',
            self::STATUT_ELEVE_NON_AFFECTE => 'Non affecté',
            default => '',
        };
    }

    public function getReferenceIaAttribute(): string
    {
        return implode(' | ', array_filter([
            'ELEVE',
            'ID:' . $this->id,
            'MAT:' . ($this->matricule_interne ?? 'N/A'),
            'DESPS:' . ($this->matricule_desps ?? 'N/A'),
            'NOM:' . $this->nom_complet,
            'STATUT_ELEVE:' . ($this->statut_eleve ?? ''),
            'CLASSE_ID:' . ($this->classe_id ?? 'NULL'),
            'ETAB_ID:' . ($this->etablissement_id ?? 'NULL'),
        ]));
    }

    public function estAffecte(): bool
    {
        return $this->statut_eleve === self::STATUT_ELEVE_AFFECTE;
    }

    public function estNonAffecte(): bool
    {
        return $this->statut_eleve === self::STATUT_ELEVE_NON_AFFECTE;
    }

    public function classeActuelle()
    {
        return $this->classeEffective();
    }

    /** Classe directe ou via inscription de l'année en cours. */
    public function classeEffective(): ?\App\Models\Classe
    {
        if ($this->classe_id) {
            return $this->classe ?? $this->classe()->first();
        }

        $this->loadMissing('inscriptionEnCours.classe');

        return $this->inscriptionEnCours?->classe;
    }

    public function soldeScolarite(): int
    {
        $inscription = $this->inscriptionEnCours;

        if (!$inscription) {
            return 0;
        }

        $paye = $this->paiements()
            ->where('inscription_id', $inscription->id)
            ->where('statut', 'confirme')
            ->sum('montant');

        return $inscription->montant_net - $paye;
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeInscrits($query)
    {
        return $query->where('statut', 'inscrit');
    }

    public function scopeAffectes($query)
    {
        return $query->where('statut_eleve', self::STATUT_ELEVE_AFFECTE);
    }

    public function scopeNonAffectes($query)
    {
        return $query->where('statut_eleve', self::STATUT_ELEVE_NON_AFFECTE);
    }

    public function scopeInscritsCetteAnnee($query, ?int $anneeId = null)
    {
        if (! $anneeId) {
            $annee = \App\Services\Scolarite\AnneeScolaireContext::courante();
            $anneeId = $annee?->id;
        }

        if (! $anneeId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('inscriptions', function ($q) use ($anneeId) {
            $q->where('annee_scolaire_id', $anneeId)
                ->where('statut', 'validee');
        });
    }

    public function scopePourAnneeScolaire($query, AnneeScolaire $annee)
    {
        if ($annee->estArchiveConsultation()) {
            return $query->whereHas('inscriptions', function ($q) use ($annee) {
                $q->where('annee_scolaire_id', $annee->id);
            });
        }

        return $query->inscritsCetteAnnee($annee->id);
    }

    public static function genererMatricule(int $etablissementId, ?AnneeScolaire $anneeScolaire = null): string
    {
        $anneeScolaire ??= \App\Services\Scolarite\AnneeScolaireService::courantePourEtablissement($etablissementId);
        $prefixe = $anneeScolaire
            ? (int) explode('-', (string) $anneeScolaire->libelle)[0]
            : (int) now()->year;

        $dernier = static::where('etablissement_id', $etablissementId)
            ->where('matricule_interne', 'like', "AVIA-{$prefixe}-%")
            ->max('matricule_interne');

        $numero = $dernier ? (int) substr($dernier, -4) + 1 : 1;

        return sprintf('AVIA-%d-%04d', $prefixe, $numero);
    }

    /**
     * Retrouve un élève existant par matricule (interne ou DESPS) pour réinscription.
     */
    public static function trouverParMatricule(int $etablissementId, ?string $matriculeInterne, ?string $matriculeDesps): ?self
    {
        $interne = trim((string) $matriculeInterne);
        $desps = trim((string) $matriculeDesps);

        if ($interne !== '') {
            $eleve = static::query()
                ->where('etablissement_id', $etablissementId)
                ->where('matricule_interne', $interne)
                ->first();
            if ($eleve) {
                return $eleve;
            }
        }

        if ($desps !== '') {
            return static::query()
                ->where('etablissement_id', $etablissementId)
                ->where('matricule_desps', $desps)
                ->first();
        }

        return null;
    }

    private static function normalizeStatutEleve(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return in_array($value, [
            self::STATUT_ELEVE_AFFECTE,
            self::STATUT_ELEVE_NON_AFFECTE,
        ], true) ? $value : null;
    }
}