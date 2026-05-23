<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasOne, HasMany};

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'etablissement_id', 'active_etablissement_id', 'nom', 'prenom', 'email', 'telephone', 'password',
        'role', 'avatar_path', 'sexe', 'actif', 'premiere_connexion', 'derniere_connexion', 'langue',
    ];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'email_verified_at' => 'datetime', 'password' => 'hashed',
        'actif' => 'boolean', 'premiere_connexion' => 'boolean', 'derniere_connexion' => 'datetime',
    ];

    /**
     * Accessor "name" — compatibilité : prénom + nom.
     */
    public function getNameAttribute(): string
    {
        return trim(($this->prenom ?? '').' '.($this->nom ?? ''));
    }

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }

    public function activeEtablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class, 'active_etablissement_id');
    }

    /** Profil enseignant principal (compat) — la première fiche liée. */
    public function enseignant(): HasOne { return $this->hasOne(Enseignant::class); }

    /** Toutes les fiches enseignant (1 par école) — pour profs multi-écoles. */
    public function enseignants(): HasMany { return $this->hasMany(Enseignant::class); }

    public function eleve(): HasOne { return $this->hasOne(Eleve::class, 'user_id'); }
    public function parentTuteur(): HasOne { return $this->hasOne(ParentTuteur::class, 'user_id'); }

    public function parentTuteurs(): HasMany { return $this->hasMany(ParentTuteur::class, 'user_id'); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function messagesEnvoyes(): HasMany { return $this->hasMany(Message::class, 'expediteur_id'); }
    public function messagesRecus(): HasMany { return $this->hasMany(Message::class, 'destinataire_id'); }

    public function getNomCompletAttribute(): string { return "{$this->prenom} {$this->nom}"; }
    public function isDirecteur(): bool { return in_array($this->role, ['directeur', 'super_admin']); }

    /** Direction d'établissement (sans accès aux clés d'archive). */
    public function isDirection(): bool
    {
        return in_array($this->role, ['directeur', 'directeur_adjoint'], true);
    }

    /** Peut voir la clé de restauration affichée une fois à la clôture. */
    public function peutVoirCleArchive(): bool
    {
        return in_array($this->role, ['super_admin', 'gestionnaire'], true);
    }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isEnseignant(): bool { return $this->role === 'enseignant'; }
    public function isParent(): bool { return $this->role === 'parent'; }
    public function isEleve(): bool { return $this->role === 'eleve'; }

    public function scopeActif($query) { return $query->where('actif', true); }
    public function scopeRole($query, string $role) { return $query->where('role', $role); }

    // ── Multi-école pour enseignants ───────────────────────────────────────

    /** Écoles où ce user est enseignant (peut être plusieurs). */
    public function ecolesEnseignant()
    {
        return Etablissement::whereIn('id',
            $this->enseignants()->where('actif', true)->pluck('etablissement_id')
        )->get();
    }

    /** ID de l'école active : préférence persistée (API), session web (multi-écoles), sinon etablissement_id. */
    public function ecoleActiveId(): ?int
    {
        if ($this->isSuperAdmin() && session()->has('super_admin_impersonate_etab_id')) {
            return (int) session('super_admin_impersonate_etab_id');
        }

        if ($this->isEnseignant()) {
            if ($this->active_etablissement_id) {
                return (int) $this->active_etablissement_id;
            }
            if (session()->has('active_etablissement_id')) {
                return (int) session('active_etablissement_id');
            }
        }

        return $this->etablissement_id;
    }

    /** Fiche Enseignant correspondant à l'école active (ou à l'école $etabId si fourni). */
    public function enseignantActif(?int $etabId = null): ?Enseignant
    {
        $etabId = $etabId ?? $this->ecoleActiveId();
        if (!$etabId) return $this->enseignant; // fallback : premier
        return $this->enseignants()->where('etablissement_id', $etabId)->where('actif', true)->first()
            ?? $this->enseignant; // dernier filet de sécurité
    }
}
